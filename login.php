<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';

$redirect = trim((string) ($_GET['redirect'] ?? $_POST['redirect'] ?? ''));
if ($redirect === '' || preg_match('#^(https?:)?//#i', $redirect) || str_contains($redirect, '..')) {
    $redirect = 'user-dashboard.php';
}

// If already logged in, redirect to dashboard
if (user_current()) {
    header('Location: ' . url($redirect));
    exit;
}

$error = '';
$success = '';
if (!empty($_SESSION['checkout_login_notice'])) {
    $success = (string) $_SESSION['checkout_login_notice'];
    unset($_SESSION['checkout_login_notice']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = user_login($email, $password, $remember);
        if ($result && $result['success']) {
            header('Location: ' . url($redirect));
            exit;
        } else {
            $error = $result['message'] ?? 'Login failed';
        }
    }
}

$meta = [
    'title' => 'Mybrandplease | Login',
    'description' => 'Login to your Mybrandplease account',
    'canonical' => 'login.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
            <div class="breadcumb-wrapper__title">Login</div>
            <ul class="breadcumb-wrapper__items">
                <li class="breadcumb-wrapper__items-list">
                    <i class="fa-regular fa-house"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <a href="<?php echo url('index.php'); ?>" class="breadcumb-wrapper__items-list-title">Home</a>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <span class="breadcumb-wrapper__items-list-title2">Login</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="login-page section-spacing-120">
    <div class="container container-1352">
        <div class="login-card">
            <div class="login-card__header">
                <h1>Welcome Back</h1>
                <p>Sign in to your account to continue</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-regular fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-regular fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="<?php echo url('forgot-password.php'); ?>" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">Sign In</button>

                <div class="login-divider">
                    <span>OR</span>
                </div>

                <div class="social-login">
                    <button type="button" class="social-btn google-btn">
                        <i class="fa-brands fa-google"></i>
                        Continue with Google
                    </button>
                    <button type="button" class="social-btn apple-btn">
                        <i class="fa-brands fa-apple"></i>
                        Continue with Apple
                    </button>
                </div>

                <div class="login-footer">
                    <p>Don't have an account? <a href="<?php echo url('register.php'); ?>">Sign up here</a></p>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.login-page {
    background: #f8f9fa;
    min-height: 60vh;
}
.login-card {
    max-width: 450px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    text-align: center;
}
.login-card__header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0C0C0C;
    margin: 0 0 8px;
}
.login-card__header p {
    color: #666;
    margin: 0;
}
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #bbf7d0;
}
.alert i {
    font-size: 16px;
}
.login-form {
    text-align: left;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}
.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}
.form-group input:focus {
    outline: none;
    border-color: #EE2D7A;
    box-shadow: 0 0 0 3px rgba(238, 45, 122, 0.1);
}
.password-input {
    position: relative;
}
.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
}
.password-toggle:hover {
    color: #333;
}
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
}
.checkbox-label input {
    width: auto;
}
.forgot-link {
    color: #EE2D7A;
    text-decoration: none;
    font-size: 14px;
}
.forgot-link:hover {
    text-decoration: underline;
}
.login-btn {
    width: 100%;
    padding: 14px 24px;
    background: #EE2D7A;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.login-btn:hover {
    background: #d4256a;
    transform: translateY(-2px);
}
.login-divider {
    margin: 24px 0;
    text-align: center;
    position: relative;
}
.login-divider span {
    background: #fff;
    padding: 0 16px;
    color: #666;
    font-size: 14px;
}
.login-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #ddd;
    z-index: -1;
}
.social-login {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}
.social-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}
.social-btn:hover {
    background: #f8f9fa;
    border-color: #ccc;
}
.google-btn i {
    color: #db4437;
}
.apple-btn i {
    color: #000;
}
.login-footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
.login-footer p {
    margin: 0;
    color: #666;
    font-size: 14px;
}
.login-footer a {
    color: #EE2D7A;
    text-decoration: none;
    font-weight: 600;
}
.login-footer a:hover {
    text-decoration: underline;
}
@media (max-width: 768px) {
    .login-card {
        padding: 24px;
        margin: 0 16px;
    }
    .social-login {
        gap: 8px;
    }
    .social-btn {
        padding: 10px 12px;
        font-size: 13px;
    }
}
</style>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.classList.remove('fa-eye');
        toggleBtn.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleBtn.classList.remove('fa-eye-slash');
        toggleBtn.classList.add('fa-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
