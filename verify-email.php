<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';

$token = isset($_GET['token']) && is_string($_GET['token']) ? trim($_GET['token']) : '';
$result = user_verify_email_token($token);
$isSuccess = !empty($result['success']);
$message = (string) ($result['message'] ?? ($isSuccess ? 'Your email has been verified.' : 'Unable to verify your email.'));

$meta = [
    'title' => 'Verify Email | mybrandplease',
    'description' => 'Verify your mybrandplease account email address.',
    'canonical' => 'verify-email.php',
];

include 'includes/head.php';
include 'includes/header.php';
?>

<style>
.verify-page {
    background: #f8f9fa;
    min-height: 55vh;
}
.verify-card {
    max-width: 520px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    text-align: center;
}
.verify-card h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0c0c0c;
    margin: 0 0 10px;
}
.verify-card p {
    color: #666;
}
.verify-alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin: 20px 0;
}
.verify-alert--success {
    background: #d4edda;
    color: #155724;
}
.verify-alert--error {
    background: #f8d7da;
    color: #721c24;
}
.verify-btn {
    display: inline-block;
    background: #ee2d7a;
    color: #fff;
    border-radius: 8px;
    padding: 12px 20px;
    font-weight: 700;
    text-decoration: none;
}
</style>

<section class="verify-page section-spacing-120">
    <div class="container container-1352">
        <div class="verify-card">
            <div>
                <h1>Email Verification</h1>
                <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="verify-alert <?php echo $isSuccess ? 'verify-alert--success' : 'verify-alert--error'; ?>">
                <i class="fa-regular <?php echo $isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <a href="<?php echo htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8'); ?>" class="verify-btn">
                Go to Login
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
