<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/google-meet.php';
require_once __DIR__ . '/includes/url.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function meeting_mail_hit_gmail_quota(string $status): bool
{
    return str_contains(strtolower($status), 'daily user sending limit exceeded');
}

$isPost = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
$requestData = $isPost ? $_POST : $_GET;

$date = isset($requestData['date']) && is_string($requestData['date']) ? trim($requestData['date']) : '';
$time = isset($requestData['time']) && is_string($requestData['time']) ? trim($requestData['time']) : '';
$timezone = isset($requestData['timezone']) && is_string($requestData['timezone']) ? trim($requestData['timezone']) : '';

if ($date === '' || $time === '' || $timezone === '') {
    header('Location: ' . url('meeting-schedule.php'));
    exit;
}

try {
    $tzObject = new DateTimeZone($timezone);
} catch (Exception $e) {
    $timezone = 'Asia/Kolkata';
    $tzObject = new DateTimeZone($timezone);
}
if (preg_match('/^\d{1,2}:\d{2}(am|pm)$/i', $time)) {
    $tmp = DateTime::createFromFormat('g:ia', strtolower($time), $tzObject);
    if ($tmp instanceof DateTime) {
        $time = $tmp->format('H:i');
    }
}

$dateTime = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $tzObject);

if (!$dateTime) {
    header('Location: ' . url('meeting-schedule.php'));
    exit;
}

$endDateTime = clone $dateTime;
$endDateTime->modify('+30 minutes');
$eventDisplay = $dateTime->format('g:ia') . ' - ' . $endDateTime->format('g:ia') . ', ' . $dateTime->format('l, F j, Y');

$errors = [];
$warnings = [];
$success = '';
$createdMeetingLink = '';

if ($isPost) {
    verify_csrf_or_fail();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $guestsRaw = trim((string)($_POST['guests'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));
    $googleMeetLink = '';
    $guestEmails = [];

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

    if (!$errors) {
        if ($guestsRaw !== '') {
            $parts = preg_split('/[\s,;]+/', $guestsRaw) ?: [];
            foreach ($parts as $guest) {
                $guest = trim((string) $guest);
                if ($guest === '') {
                    continue;
                }
                if (!filter_var($guest, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid guest email: ' . e($guest);
                    continue;
                }
                $guestEmails[] = strtolower($guest);
            }
            $guestEmails = array_values(array_unique($guestEmails));
        }
    }

    if (!$errors) {
        $googleMeetLink = meeting_create_google_meet_link($dateTime, $endDateTime, $timezone, $name, $email, $guestEmails, $notes);
        if ($googleMeetLink === null || !filter_var($googleMeetLink, FILTER_VALIDATE_URL)) {
            $fallbackMeetLink = meeting_google_meet_link();
            if (filter_var($fallbackMeetLink, FILTER_VALIDATE_URL)) {
                $googleMeetLink = $fallbackMeetLink;
            } else {
                $setupLink = url('google-calendar-connect.php');
                $errors[] = 'Could not create a valid Google Meet link. ' . e(meeting_google_meet_last_error() !== '' ? meeting_google_meet_last_error() : 'Please configure Google Calendar integration first.') . ' If client ID and secret are already saved, complete the Google OAuth connect step so a refresh token is stored. <a href="' . e($setupLink) . '">Open Google Calendar setup</a>.';
            }
        }
    }

    if (!$errors) {
        $createdMeetingLink = $googleMeetLink;
        $guestHtml = count($guestEmails) > 0
            ? ('<ul><li>' . implode('</li><li>', array_map('e', $guestEmails)) . '</li></ul>')
            : '<p>None</p>';
        $notesHtml = $notes !== '' ? nl2br(e($notes)) : 'N/A';

        $meetWarning = meeting_google_meet_last_error() !== ''
            ? '<p><strong>Google Meet Note:</strong><br>' . e(meeting_google_meet_last_error()) . ' Default Google Meet link was used for this booking.</p>'
            : '';

        $adminBody = '
            <p>Hi NIMISHA IMPEX WORLDWIDE (P) LIMITED,</p>
            <p>A new event has been scheduled.</p>
            <p><strong>Event Type:</strong><br>30 Minute Meeting</p>
            <p><strong>Invitee:</strong><br>' . e($name) . '</p>
            <p><strong>Invitee Email:</strong><br>' . e($email) . '</p>
            <p><strong>Additional Guests:</strong>' . $guestHtml . '</p>
            <p><strong>Event Date/Time:</strong><br>' . e($eventDisplay) . ' (' . e($timezone) . ')</p>
            <p><strong>Location:</strong><br>This is a Google Meet web conference. <a href="' . e($googleMeetLink) . '">Join now</a></p>
            ' . $meetWarning . '
            <p><strong>Invitee Time Zone:</strong><br>' . e($timezone) . '</p>
            <p><strong>Notes:</strong><br>' . $notesHtml . '</p>
        ';

        $userBody = '
            <p>Hi ' . e($name) . ',</p>
            <p>Your event has been scheduled.</p>
            <p><strong>Event Type:</strong><br>30 Minute Meeting</p>
            <p><strong>Event Date/Time:</strong><br>' . e($eventDisplay) . ' (' . e($timezone) . ')</p>
            <p><strong>Additional Guests:</strong>' . $guestHtml . '</p>
            <p><strong>Location:</strong><br>This is a Google Meet web conference. <a href="' . e($googleMeetLink) . '">Join now</a></p>
            <p><strong>Notes:</strong><br>' . $notesHtml . '</p>
        ';

        $adminEmail = meeting_mail_admin_email();
        $sameRecipient = strcasecmp($adminEmail, $email) === 0;

        $adminSent = meeting_send_html_mail($adminEmail, 'New Event Scheduled - 30 Minute Meeting', $adminBody, $email, $name);
        $adminStatus = meeting_mail_last_error();

        $userSent = $adminSent;
        $userStatus = $adminStatus;
        if (!$sameRecipient) {
            if (meeting_mail_hit_gmail_quota($adminStatus)) {
                $userSent = false;
                $userStatus = $adminStatus;
            } else {
                $userSent = meeting_send_html_mail($email, 'Meeting Confirmation - 30 Minute Meeting', $userBody);
                $userStatus = meeting_mail_last_error();
            }
        }

        $success = 'Meeting scheduled successfully. Your Google Meet link is ready below.';
        if (!$adminSent) {
            $warnings[] = 'Admin email failed (' . e($adminEmail) . '): ' . e(meeting_mail_human_error($adminStatus));
        }
        if (!$userSent && !$sameRecipient) {
            $warnings[] = 'User email failed (' . e($email) . '): ' . e(meeting_mail_human_error($userStatus));
        }
    }
}

$meta = [
    'title' => 'Meeting Details | Mybrandplease',
    'description' => 'Enter your details to confirm and schedule a meeting.',
    'canonical' => 'meeting-details.php',
];

include 'includes/head.php';
include 'includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary-blue: #006bff;
    --text-dark: #1a1a1a;
    --text-muted: #666666;
    --border-color: #e6e6e6;
}

body { font-family: 'Inter', sans-serif; background: #fff; }

.det-page { padding: 40px 0; }
.det-shell {
    background: #fff; 
    border: 1px solid var(--border-color); 
    border-radius: 8px; 
    box-shadow: 0 1px 20px rgba(0,0,0,0.05); 
    overflow: hidden;
}

/* Left Sidebar */
.det-left { border-right: 1px solid var(--border-color); padding: 40px; height: 100%; position: relative; }
.det-back-btn { 
    width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border-color); 
    display: flex; align-items: center; justify-content: center; color: var(--primary-blue); 
    text-decoration: none; margin-bottom: 30px; transition: 0.2s;
}
.det-back-btn:hover { background: #f0f7ff; }

.det-logo-main { max-width: 150px; margin-bottom: 40px; display: block; }
.det-avatar { width: 64px; height: 64px; border-radius: 50%; margin-bottom: 15px; }
.det-company-name { font-size: 14px; color: var(--text-muted); font-weight: 700; margin-bottom: 5px; text-transform: uppercase; }
.det-event-title { font-size: 26px; font-weight: 800; color: var(--text-dark); margin-bottom: 25px; }
.det-meta { color: var(--text-muted); font-size: 15px; margin-bottom: 12px; font-weight: 500; display: flex; align-items: flex-start; gap: 12px; }
.det-meta i { margin-top: 4px; font-size: 18px; width: 20px; text-align: center; }

/* Right Content */
.det-right { padding: 40px; }
.det-right h2 { font-size: 20px; font-weight: 700; margin-bottom: 25px; color: var(--text-dark); }

.form-group { margin-bottom: 20px; }
.form-label { font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
.form-control { 
    border: 1px solid #ced4da; border-radius: 8px; padding: 12px; font-size: 15px; 
}
.form-control:focus { border-color: var(--primary-blue); box-shadow: none; }

.btn-add-guests { 
    background: transparent; border: 1px solid var(--primary-blue); color: var(--primary-blue);
    padding: 8px 20px; border-radius: 20px; font-weight: 600; font-size: 14px; margin-bottom: 20px;
}
.btn-add-guests:hover { background: #f0f7ff; }

.prepare-text { font-size: 14px; font-weight: 700; margin-top: 25px; margin-bottom: 10px; color: var(--text-dark); }
.privacy-text { font-size: 13px; color: var(--text-muted); margin: 25px 0; line-height: 1.5; }
.privacy-text a { color: var(--primary-blue); text-decoration: none; font-weight: 600; }

.btn-schedule { 
    background: var(--primary-blue); color: #fff; border: none; padding: 12px 25px; 
    border-radius: 30px; font-weight: 700; font-size: 16px; transition: 0.3s;
}
.btn-schedule:hover { background: #0056cc; transform: translateY(-1px); }

@media (max-width: 1199px) { .det-left { border-right: none; border-bottom: 1px solid var(--border-color); } }
</style>

<section class="det-page">
    <div class="container" style="max-width: 1060px;">
        <div class="det-shell">
            <div class="row g-0">
                <div class="col-lg-4">
                    <div class="det-left">
                        <a href="<?= url('meeting-schedule.php') ?>" class="det-back-btn">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        
                        <img src="<?= url('assets/imgs/logo/logo.gif') ?>" class="det-logo-main" alt="Logo">
                        
                        <div class="mt-4">
                            <div class="det-company-name">NIMISHA IMPEX WORLDWIDE (P) LIMITED</div>
                            <h1 class="det-event-title">30 Minute Meeting</h1>
                            
                            <div class="det-meta">
                                <i class="fa-regular fa-clock"></i>
                                <span>30 min</span>
                            </div>
                            <div class="det-meta">
                                <i class="fa-solid fa-video"></i>
                                <span>Web conferencing details provided upon confirmation.</span>
                            </div>
                            <div class="det-meta">
                                <i class="fa-regular fa-calendar"></i>
                                <span><?= e($eventDisplay) ?></span>
                            </div>
                            <div class="det-meta">
                                <i class="fa-solid fa-earth-americas"></i>
                                <span><?= e(str_replace('_', ' ', $timezone)) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="det-right">
                        <h2>Enter Details</h2>
                        
                        <?php if ($errors): ?>
                            <div class="alert alert-danger small py-2"><?= implode('<br>', $errors) ?></div>
                        <?php endif; ?>
                        <?php if ($warnings): ?>
                            <div class="alert alert-warning small py-2"><?= implode('<br>', $warnings) ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success small py-2"><?= $success ?></div>
                            <?php if ($createdMeetingLink !== ''): ?>
                                <div class="alert alert-light border py-3 px-3 mb-3">
                                    <div class="fw-bold mb-2">Join Meeting Link</div>
                                    <div class="small mb-3">
                                        <a href="<?= e($createdMeetingLink) ?>" target="_blank" rel="noopener noreferrer"><?= e($createdMeetingLink) ?></a>
                                    </div>
                                    <a href="<?= e($createdMeetingLink) ?>" target="_blank" rel="noopener noreferrer" class="btn-schedule d-inline-flex align-items-center gap-2 text-decoration-none">
                                        <span>Join Meeting</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <form method="post" action="<?= url('meeting-details.php') ?>" id="meetingDetailsForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="date" value="<?= e($date) ?>">
                            <input type="hidden" name="time" value="<?= e($time) ?>">
                            <input type="hidden" name="timezone" value="<?= e($timezone) ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control w-75" required value="<?= e($_POST['name'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control w-75" required value="<?= e($_POST['email'] ?? '') ?>">
                            </div>

                            <button type="button" class="btn-add-guests" id="addGuestsBtn">Add Guests</button>
                            <div id="guestsWrapper" style="display:none;" class="mb-3">
                                <label class="form-label">Guest Emails</label>
                                <textarea name="guests" class="form-control w-75" rows="2" placeholder="Separate emails with commas"><?= e($_POST['guests'] ?? '') ?></textarea>
                            </div>

                            <p class="prepare-text">Please share anything that will help prepare for our meeting.</p>
                            <textarea name="notes" class="form-control w-75" rows="3"><?= e($_POST['notes'] ?? '') ?></textarea>

                            <p class="privacy-text">
                                By proceeding, you confirm that you have read and agree to<br>
                                <a href="#">Calendly's Terms of Use</a> and <a href="#">Privacy Notice</a>.
                            </p>

                            <button type="submit" class="btn-schedule">Schedule Event</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addGuestsBtn = document.getElementById('addGuestsBtn');
    const guestsWrapper = document.getElementById('guestsWrapper');

    if (!addGuestsBtn || !guestsWrapper) {
        return;
    }

    addGuestsBtn.addEventListener('click', function() {
        this.style.display = 'none';
        guestsWrapper.style.display = 'block';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
