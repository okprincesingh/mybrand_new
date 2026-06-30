<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/url.php';
require_once __DIR__ . '/includes/cms.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function contact_post_value(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$formData = [
    'first_name' => '',
    'last_name' => '',
    'name' => '',
    'email' => '',
    'country' => '',
    'message' => '',
    'phone' => '',
    'subject' => '',
    'address' => '',
    'requirements' => '',
    'product_id' => '',
];
$errors = [];
$success = '';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    verify_csrf_or_fail();

    $formData = [
        'first_name' => contact_post_value('first_name'),
        'last_name' => contact_post_value('last_name'),
        'name' => contact_post_value('name'),
        'email' => contact_post_value('email'),
        'country' => contact_post_value('country'),
        'message' => contact_post_value('message'),
        'phone' => contact_post_value('phone'),
        'subject' => contact_post_value('subject'),
        'address' => contact_post_value('address'),
        'requirements' => contact_post_value('requirements'),
        'product_id' => contact_post_value('product_id'),
    ];
    if ($formData['name'] === '') {
        $formData['name'] = trim($formData['first_name'] . ' ' . $formData['last_name']);
    }
    if ($formData['message'] === '') {
        $formData['message'] = contact_post_value('comment');
    }

    $isConsultationForm = $formData['requirements'] !== '' || $formData['address'] !== '' || $formData['product_id'] !== '';
    $mailSubject = $formData['subject'] !== '' ? $formData['subject'] : ($isConsultationForm ? 'New Website Consultation Request' : 'New Contact Form Submission');

    if ($formData['name'] === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($formData['country'] === '' && !$isConsultationForm) {
        $errors[] = 'Country is required.';
    }
    if ($isConsultationForm) {
        if ($formData['phone'] === '') {
            $errors[] = 'Phone is required.';
        }
        if ($formData['requirements'] === '') {
            $errors[] = 'Requirements are required.';
        }
    } else {
        if ($formData['message'] === '') {
            $errors[] = 'Message is required.';
        }
    }

    if (!$errors) {
        $adminEmail = meeting_mail_admin_email();

        $adminBodyParts = [
            '<p>A new website enquiry has been submitted.</p>',
            '<p><strong>Name:</strong><br>' . e($formData['name']) . '</p>',
            '<p><strong>Email:</strong><br>' . e($formData['email']) . '</p>',
        ];

        if ($formData['phone'] !== '') {
            $adminBodyParts[] = '<p><strong>Phone:</strong><br>' . e($formData['phone']) . '</p>';
        }
        if ($formData['country'] !== '') {
            $adminBodyParts[] = '<p><strong>Country:</strong><br>' . e($formData['country']) . '</p>';
        }
        if ($formData['subject'] !== '') {
            $adminBodyParts[] = '<p><strong>Subject:</strong><br>' . e($formData['subject']) . '</p>';
        }
        if ($formData['product_id'] !== '') {
            $adminBodyParts[] = '<p><strong>Product:</strong><br>' . e($formData['product_id']) . '</p>';
        }
        if ($formData['address'] !== '') {
            $adminBodyParts[] = '<p><strong>Address:</strong><br>' . nl2br(e($formData['address'])) . '</p>';
        }
        if ($formData['message'] !== '') {
            $adminBodyParts[] = '<p><strong>Message:</strong><br>' . nl2br(e($formData['message'])) . '</p>';
        }
        if ($formData['requirements'] !== '') {
            $adminBodyParts[] = '<p><strong>Requirements:</strong><br>' . nl2br(e($formData['requirements'])) . '</p>';
        }

        $adminBody = implode("\n", $adminBodyParts);

        $userBody = implode("\n", [
            '<p>Hi ' . e($formData['name']) . ',</p>',
            '<p>Thanks for contacting MyBrandPlease. We have received your enquiry and our team will get back to you soon.</p>',
            $formData['message'] !== ''
                ? '<p><strong>Your Message:</strong><br>' . nl2br(e($formData['message'])) . '</p>'
                : '',
            $formData['requirements'] !== ''
                ? '<p><strong>Your Requirements:</strong><br>' . nl2br(e($formData['requirements'])) . '</p>'
                : '',
            '<p>Regards,<br>MyBrandPlease</p>',
        ]);

        $adminSent = meeting_send_html_mail($adminEmail, $mailSubject, $adminBody, $formData['email'], $formData['name']);
        $adminStatus = meeting_mail_last_error();

        $userSent = meeting_send_html_mail(
            $formData['email'],
            'We Received Your Enquiry - MyBrandPlease',
            $userBody
        );
        $userStatus = meeting_mail_last_error();

        if ($adminSent && $userSent) {
            $success = 'Your enquiry was submitted successfully. We have also sent a confirmation email to you.';
            $formData = [
                'first_name' => '',
                'last_name' => '',
                'name' => '',
                'email' => '',
                'country' => '',
                'message' => '',
                'phone' => '',
                'subject' => '',
                'address' => '',
                'requirements' => '',
                'product_id' => '',
            ];
            csrf_regenerate_token();
        } else {
            if (!$adminSent) {
                $errors[] = 'Admin email failed: ' . e($adminStatus);
            }
            if (!$userSent) {
                $errors[] = 'User confirmation email failed: ' . e($userStatus);
            }
        }
    }
}

$contactOffices = cms_get_home_offices();

$meta = [
  'title' => 'Mybrandplease | contact',
  'description' => 'Mybrandplease - contact page',
  'canonical' => 'contact.php'
];
include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
          <div class="container rr-container-1895">
            <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
              <div class="breadcumb-wrapper__title">Contact Us</div>
              <ul class="breadcumb-wrapper__items">
                <li class="breadcumb-wrapper__items-list">
                  <i class="fa-regular fa-house"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                  <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                  <a href="contact.php" class="breadcumb-wrapper__items-list-title">
                    Category
                  </a>
                </li>
                <li class="breadcumb-wrapper__items-list">
                  <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                  <a href="contact.php" class="breadcumb-wrapper__items-list-title2">
                    Contact Us
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>


        <section class="contact2 section-spacing-120 rr-ov-hidden">
          <div class="container">
            <div class="row d-flex justify-content-center">
              <div class="col-xl-7">
                <div class="section-heading wow fadeInRight" data-wow-delay="0.3s">
                  <h2 class="section-heading__title">Get In Touch Today!</h2>
                  <p class="section-heading__text">We’d love to hear from you! Reach out today for inquiries, support,
                    or collaborations, and our friendly team will respond promptly with all the help you need.</p>
                </div>

                <?php if ($success !== ''): ?>
                  <div class="alert alert-success mb-4" role="alert"><?php echo e($success); ?></div>
                <?php endif; ?>

                <?php if ($errors): ?>
                  <div class="alert alert-danger mb-4" role="alert">
                    <?php foreach ($errors as $error): ?>
                      <div><?php echo $error; ?></div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <form action="<?php echo e(url('contact.php')); ?>" id="contact-form" method="POST" class="contact2-form">
                  <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                  <div class="contact2-form__content">
                    <div class="row g-4">
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".3s">
                        <span class="contact2-form__input-name">Name <em>*</em></span>
                        <div class="row g-4">
                          <div class="col-md-6">
                            <div class="contact2-form__input">
                              <input
                                type="text"
                                class="contact2-form__input-field"
                                name="first_name"
                                id="first_name"
                                placeholder="First Name"
                                value="<?php echo e($formData['first_name']); ?>"
                                required>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="contact2-form__input">
                              <input
                                type="text"
                                class="contact2-form__input-field"
                                name="last_name"
                                id="last_name"
                                placeholder="Last Name"
                                value="<?php echo e($formData['last_name']); ?>">
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".4s">
                        <div class="contact2-form__input">
                          <span class="contact2-form__input-name">Your Email <em>*</em></span>
                          <input
                            type="email"
                            class="contact2-form__input-field"
                            name="email"
                            id="email1"
                            placeholder="Your Email"
                            value="<?php echo e($formData['email']); ?>"
                            required>
                        </div>
                      </div>
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".5s">
                        <div class="contact2-form__input">
                          <span class="contact2-form__input-name">Where are you located? <em>*</em></span>
                          <input
                            type="text"
                            class="contact2-form__input-field"
                            name="country"
                            id="country"
                            placeholder="Country"
                            value="<?php echo e($formData['country']); ?>"
                            required>
                        </div>
                      </div>
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".6s">
                        <div class="contact2-form__input">
                          <span class="contact2-form__input-name">Phone Number</span>
                          <input
                            type="tel"
                            class="contact2-form__input-field"
                            name="phone"
                            id="phone"
                            placeholder="Phone Number"
                            value="<?php echo e($formData['phone']); ?>">
                        </div>
                      </div>
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".7s">
                        <div class="contact2-form__input">
                          <span class="contact2-form__input-name">Subject</span>
                          <input
                            type="text"
                            class="contact2-form__input-field"
                            name="subject"
                            id="subject"
                            placeholder="Subject"
                            value="<?php echo e($formData['subject']); ?>">
                        </div>
                      </div>
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".8s">
                        <div class="contact2-form__input">
                          <span class="contact2-form__input-name">Comment or Message</span>
                          <textarea
                            name="message"
                            class="contact2-form__input-field textarea"
                            id="message"
                            placeholder="Your Message"
                            required><?php echo e($formData['message']); ?></textarea>
                        </div>
                      </div>
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".9s">
                        <div class="contact2-form__button">
                          <button class="btn-orange" type="submit">SUBMIT</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </section>


        <section class="office-showcase contact-offices section-spacing-120 rr-ov-hidden">
          <div class="container">
            <div class="office-showcase__intro wow fadeInUp" data-wow-delay=".2s">
              <h2 class="office-showcase__title">~ Our Offices ~</h2>
            </div>
            <div class="office-grid">
              <?php foreach ($contactOffices as $office): ?>
                <?php
                  $officeCountry = trim((string) ($office['country'] ?? 'Office'));
                  $officeCompanyName = trim((string) ($office['company_name'] ?? ''));
                  $officeAddress = trim((string) ($office['address'] ?? ''));
                  $officeEmail = trim((string) ($office['email'] ?? ''));
                  $officePhone = trim((string) ($office['phone'] ?? ''));
                  $officeRegistrationLabel = trim((string) ($office['registration_label'] ?? ''));
                  $officeRegistrationNumber = trim((string) ($office['registration_number'] ?? ''));
                  $officeWebsite = trim((string) ($office['website'] ?? ''));
                  if ($officeWebsite === '') {
                    $officeWebsite = 'https://www.mybrandplease.com';
                  }
                  $officeImage = trim((string) ($office['image_path'] ?? ''));
                  $officeImageUrl = $officeImage !== '' ? url($officeImage) : url('assets/imgs/home/office/Flag-United-Kingdom.webp');
                  $officePhoneHref = preg_replace('/\D+/', '', $officePhone);
                ?>
                <article class="office-card wow fadeInUp" data-wow-delay=".1s">
                  <div class="office-card__topline"></div>
                  <div class="office-card__flag">
                    <img src="<?php echo htmlspecialchars($officeImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($officeCountry, ENT_QUOTES, 'UTF-8'); ?> Office">
                  </div>
                  <div class="office-card__body">
                    <h3 class="office-card__title"><?php echo htmlspecialchars($officeCountry, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php if ($officeCompanyName !== ''): ?>
                      <p class="office-card__company"><?php echo htmlspecialchars($officeCompanyName, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <p class="office-card__address"><?php echo nl2br(htmlspecialchars($officeAddress, ENT_QUOTES, 'UTF-8')); ?></p>
                    <div class="office-card__meta-list">
                      <?php if ($officeRegistrationLabel !== '' || $officeRegistrationNumber !== ''): ?>
                        <div class="office-card__meta office-card__meta--plain">
                          <span class="office-card__meta-icon"><i class="fa-regular fa-building"></i></span>
                          <span>
                            <?php if ($officeRegistrationLabel !== ''): ?>
                              <strong><?php echo htmlspecialchars($officeRegistrationLabel, ENT_QUOTES, 'UTF-8'); ?>:</strong>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($officeRegistrationNumber, ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        </div>
                      <?php endif; ?>
                      <?php if ($officePhone !== ''): ?>
                        <a class="office-card__meta" href="https://wa.me/<?php echo htmlspecialchars($officePhoneHref ?: $officePhone, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                          <span class="office-card__meta-icon"><i class="fa-brands fa-whatsapp"></i></span>
                          <span><?php echo htmlspecialchars($officePhone, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                      <?php endif; ?>
                      <?php if ($officeEmail !== ''): ?>
                        <a class="office-card__meta" href="mailto:<?php echo htmlspecialchars($officeEmail, ENT_QUOTES, 'UTF-8'); ?>">
                          <span class="office-card__meta-icon"><i class="fa-regular fa-envelope"></i></span>
                          <span><?php echo htmlspecialchars($officeEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                      <?php endif; ?>
                      <a class="office-card__meta" href="<?php echo htmlspecialchars($officeWebsite, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                        <span class="office-card__meta-icon"><i class="fa-solid fa-globe"></i></span>
                        <span><?php echo htmlspecialchars(preg_replace('#^https?://#', '', $officeWebsite), ENT_QUOTES, 'UTF-8'); ?></span>
                      </a>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        </section>

<?php include 'includes/footer.php'; ?>
