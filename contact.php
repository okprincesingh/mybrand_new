<?php
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/url.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function contact_post_value(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$formData = [
    'name' => '',
    'email' => '',
    'message' => '',
    'phone' => '',
    'address' => '',
    'requirements' => '',
    'product_id' => '',
];
$errors = [];
$success = '';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    verify_csrf_or_fail();

    $formData = [
        'name' => contact_post_value('name'),
        'email' => contact_post_value('email'),
        'message' => contact_post_value('message'),
        'phone' => contact_post_value('phone'),
        'address' => contact_post_value('address'),
        'requirements' => contact_post_value('requirements'),
        'product_id' => contact_post_value('product_id'),
    ];

    $isConsultationForm = $formData['requirements'] !== '' || $formData['address'] !== '' || $formData['product_id'] !== '' || $formData['phone'] !== '';
    $mailSubject = $isConsultationForm ? 'New Website Consultation Request' : 'New Contact Form Submission';

    if ($formData['name'] === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
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
                'name' => '',
                'email' => '',
                'message' => '',
                'phone' => '',
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
                      <div class="col-lg-6 wow fadeInUp" data-wow-delay=".3s">
                        <div class="contact2-form__input">
                          <span class="contact2-form__input-name">Your Name</span>
                          <input
                            type="text"
                            class="contact2-form__input-field"
                            name="name"
                            id="name"
                            placeholder="Your name"
                            value="<?php echo e($formData['name']); ?>"
                            required>
                        </div>
                      </div>
                      <div class="col-lg-6 wow fadeInUp" data-wow-delay=".5s">
                        <div class="contact2-form__input">
                          <span class="contact2-form__input-name">Your Email</span>
                          <input
                            type="email"
                            class="contact2-form__input-field"
                            name="email"
                            id="email1"
                            placeholder="Email address"
                            value="<?php echo e($formData['email']); ?>"
                            required>
                        </div>
                      </div>
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".7s">
                        <div class="contact2-form__input">
                          <span class="contact2-form__input-name">Your Message</span>
                          <textarea
                            name="message"
                            class="contact2-form__input-field textarea"
                            id="message"
                            placeholder="Type your message"
                            required><?php echo e($formData['message']); ?></textarea>
                        </div>
                      </div>
                      <div class="col-lg-12 wow fadeInUp" data-wow-delay=".9s">
                        <div class="contact2-form__button">
                          <button class="btn-orange" type="submit">SEND MESSAGE</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </section>


        <div class="map fix">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d52872517.59607392!2d-161.691169406869!3d36.018281840171966!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x54eab584e432360b%3A0x1c3bb99243deb742!2sUnited%20States!5e0!3m2!1sen!2sbd!4v1769883541208!5m2!1sen!2sbd"></iframe>
        </div>

<?php include 'includes/footer.php'; ?>
