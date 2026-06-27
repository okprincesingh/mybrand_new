<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/google-meet.php';
require_once __DIR__ . '/includes/url.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$envPath = __DIR__ . '/.env';
$messages = [];
$errors = [];

function google_calendar_redirect_uri(): string
{
    return url('google-calendar-connect.php');
}

function google_calendar_update_env_file(string $envPath, array $updates): bool
{
    $contents = is_file($envPath) ? (string) file_get_contents($envPath) : '';
    foreach ($updates as $key => $value) {
        $safeValue = str_replace(["\r", "\n"], '', (string) $value);
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        $line = $key . '=' . $safeValue;
        if (preg_match($pattern, $contents) === 1) {
            $contents = (string) preg_replace($pattern, $line, $contents);
        } else {
            $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
        }
        putenv($key . '=' . $safeValue);
        $_ENV[$key] = $safeValue;
        $_SERVER[$key] = $safeValue;
    }
    return file_put_contents($envPath, $contents, LOCK_EX) !== false;
}

function google_calendar_auth_url(): string
{
    $clientId = trim((string) getenv('GOOGLE_CALENDAR_CLIENT_ID'));
    $params = [
        'client_id' => $clientId,
        'redirect_uri' => google_calendar_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'access_type' => 'offline',
        'prompt' => 'consent',
        'include_granted_scopes' => 'true',
        'state' => csrf_token(),
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function google_calendar_exchange_code(string $code): ?array
{
    $clientId = trim((string) getenv('GOOGLE_CALENDAR_CLIENT_ID'));
    $clientSecret = trim((string) getenv('GOOGLE_CALENDAR_CLIENT_SECRET'));
    if ($clientId === '' || $clientSecret === '') {
        meeting_google_meet_last_error('Google client ID/secret is missing.');
        return null;
    }
    return meeting_google_form_request('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => google_calendar_redirect_uri(),
        'grant_type' => 'authorization_code',
    ]);
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    verify_csrf_or_fail();
    $clientId = trim((string) ($_POST['client_id'] ?? ''));
    $clientSecret = trim((string) ($_POST['client_secret'] ?? ''));
    $calendarId = trim((string) ($_POST['calendar_id'] ?? 'primary'));
    $refreshToken = trim((string) ($_POST['refresh_token'] ?? ''));

    if ($clientId === '' || $clientSecret === '') {
        $errors[] = 'Google Client ID and Client Secret are required.';
    } else {
        $updates = [
            'GOOGLE_CALENDAR_CLIENT_ID' => $clientId,
            'GOOGLE_CALENDAR_CLIENT_SECRET' => $clientSecret,
            'GOOGLE_CALENDAR_ID' => $calendarId !== '' ? $calendarId : 'primary',
        ];
        if ($refreshToken !== '') {
            $updates['GOOGLE_CALENDAR_REFRESH_TOKEN'] = $refreshToken;
        }

        $saved = google_calendar_update_env_file($envPath, $updates);
        if ($saved) {
            $messages[] = $refreshToken !== ''
                ? 'Google Calendar settings and refresh token saved.'
                : 'Google Calendar client settings saved. Click Connect Google Calendar below.';
        } else {
            $errors[] = 'Could not save Google Calendar settings to .env.';
        }
    }
}

$oauthState = isset($_GET['state']) && is_string($_GET['state']) ? trim($_GET['state']) : '';
$oauthCode = isset($_GET['code']) && is_string($_GET['code']) ? trim($_GET['code']) : '';
$oauthError = isset($_GET['error']) && is_string($_GET['error']) ? trim($_GET['error']) : '';
$oauthAction = isset($_GET['action']) && is_string($_GET['action']) ? trim($_GET['action']) : '';
$hasClientId = trim((string) getenv('GOOGLE_CALENDAR_CLIENT_ID')) !== '';
$hasClientSecret = trim((string) getenv('GOOGLE_CALENDAR_CLIENT_SECRET')) !== '';
$hasRefreshToken = trim((string) getenv('GOOGLE_CALENDAR_REFRESH_TOKEN')) !== '';
$isCalendarReady = $hasClientId && $hasClientSecret && $hasRefreshToken;

if ($oauthError !== '') {
    $errors[] = 'Google authorization failed: ' . e($oauthError);
}

if ($oauthAction === 'connect') {
    if (trim((string) getenv('GOOGLE_CALENDAR_CLIENT_ID')) === '' || trim((string) getenv('GOOGLE_CALENDAR_CLIENT_SECRET')) === '') {
        $errors[] = 'Save Google Client ID and Client Secret first.';
    } else {
        header('Location: ' . google_calendar_auth_url());
        exit;
    }
}

if ($oauthCode !== '') {
    if ($oauthState === '' || !hash_equals(csrf_token(), $oauthState)) {
        $errors[] = 'Invalid Google OAuth state.';
    } else {
        $tokenResponse = google_calendar_exchange_code($oauthCode);
        $refreshToken = is_array($tokenResponse) ? trim((string) ($tokenResponse['refresh_token'] ?? '')) : '';
        if ($refreshToken === '') {
            $errors[] = 'Google did not return a refresh token. Try again and make sure the consent screen is shown.';
        } else {
            $saved = google_calendar_update_env_file($envPath, [
                'GOOGLE_CALENDAR_REFRESH_TOKEN' => $refreshToken,
            ]);
            if ($saved) {
                $messages[] = 'Google Calendar connected successfully. New Google Meet links can now be created from the meeting form.';
            } else {
                $errors[] = 'Refresh token received, but could not save it to .env.';
            }
        }
    }
}

$meta = [
    'title' => 'Google Calendar Connect | Mybrandplease',
    'description' => 'Connect Google Calendar for automatic Google Meet links.',
    'canonical' => 'google-calendar-connect.php',
];
include 'includes/head.php';
include 'includes/header.php';
?>

<style>
.gc-wrap { padding: 48px 0; }
.gc-card { max-width: 920px; margin: 0 auto; background: #fff; border: 1px solid #e8e8e8; border-radius: 18px; box-shadow: 0 12px 40px rgba(0,0,0,0.06); padding: 32px; }
.gc-title { font-size: 34px; font-weight: 800; margin-bottom: 8px; }
.gc-muted { color: #666; margin-bottom: 24px; }
.gc-box { background: #fafafa; border: 1px solid #ececec; border-radius: 14px; padding: 18px; margin-bottom: 20px; }
.gc-label { font-weight: 700; margin-bottom: 8px; display: block; }
.gc-input { width: 100%; border: 1px solid #d9d9d9; border-radius: 10px; padding: 12px 14px; }
.gc-btn { display: inline-flex; align-items: center; gap: 10px; border: none; border-radius: 999px; padding: 13px 22px; background: #006bff; color: #fff; font-weight: 700; text-decoration: none; }
.gc-btn:hover { color: #fff; background: #0057d1; }
.gc-code { word-break: break-all; background: #fff; border: 1px dashed #d6d6d6; border-radius: 10px; padding: 12px; }
.gc-status { border-radius: 14px; padding: 16px 18px; margin-bottom: 20px; font-weight: 600; }
.gc-status.ready { background: #ecfdf3; border: 1px solid #b7ebc6; color: #166534; }
.gc-status.pending { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
.gc-help { font-size: 14px; color: #666; margin-top: 8px; }
</style>

<section class="gc-wrap">
  <div class="container">
    <div class="gc-card">
      <h1 class="gc-title">Google Calendar Connect</h1>
      <p class="gc-muted">Use this once to connect Google Calendar so meeting form submissions create a fresh Google Meet link automatically.</p>

      <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endforeach; ?>
      <?php foreach ($messages as $message): ?>
        <div class="alert alert-success"><?= e($message) ?></div>
      <?php endforeach; ?>

      <div class="gc-status <?= $isCalendarReady ? 'ready' : 'pending' ?>">
        <?= $isCalendarReady
          ? 'Google Calendar is connected. Meeting form can now create fresh Google Meet links automatically.'
          : 'Client credentials are ' . (($hasClientId && $hasClientSecret) ? 'saved' : 'not saved yet') . ', but Google OAuth connection is incomplete until a refresh token is generated.' ?>
      </div>

      <div class="gc-box">
        <div class="gc-label">Redirect URI for Google Console</div>
        <div class="gc-code"><?= e(google_calendar_redirect_uri()) ?></div>
      </div>

      <form method="post" action="<?= e(url('google-calendar-connect.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="gc-box">
          <label class="gc-label" for="client_id">Google Client ID</label>
          <input id="client_id" name="client_id" class="gc-input" value="<?= e((string) getenv('GOOGLE_CALENDAR_CLIENT_ID')) ?>" required>
        </div>
        <div class="gc-box">
          <label class="gc-label" for="client_secret">Google Client Secret</label>
          <input id="client_secret" name="client_secret" type="password" class="gc-input" value="<?= e((string) getenv('GOOGLE_CALENDAR_CLIENT_SECRET')) ?>" autocomplete="off" required>
        </div>
        <div class="gc-box">
          <label class="gc-label" for="calendar_id">Google Calendar ID</label>
          <input id="calendar_id" name="calendar_id" class="gc-input" value="<?= e((string) getenv('GOOGLE_CALENDAR_ID') ?: 'primary') ?>">
        </div>
        <div class="gc-box">
          <label class="gc-label" for="refresh_token">Google Refresh Token (optional manual paste)</label>
          <input id="refresh_token" name="refresh_token" type="password" class="gc-input" value="<?= e((string) getenv('GOOGLE_CALENDAR_REFRESH_TOKEN')) ?>" autocomplete="off">
          <div class="gc-help">Agar aapke paas refresh token already hai, yahan paste karke direct save kar sakte hain. Nahi hai to neeche wala connect flow use karein.</div>
        </div>
        <button type="submit" class="gc-btn">Save Google Settings</button>
      </form>

      <div class="gc-box mt-4">
        <div class="gc-label">Step 2</div>
        <p class="mb-3">After saving the client credentials, click below, sign in to Google, and allow Calendar access.</p>
        <a class="gc-btn" href="<?= e(url('google-calendar-connect.php?action=connect')) ?>">Connect Google Calendar</a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
