<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';

// If already logged in, redirect to dashboard
if (user_current()) {
    header('Location: ' . url('user-dashboard.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validation
    if (empty($email) || empty($password) || empty($confirmPassword) || empty($firstName) || empty($lastName)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = user_register($email, $password, $firstName, $lastName, $phone);
        if ($result && $result['success']) {
            $success = 'Registration successful! Please check your email for verification.';
        } else {
            $error = $result['message'] ?? 'Registration failed';
        }
    }
}

$meta = [
    'title' => 'Mybrandplease | Register',
    'description' => 'Create your Mybrandplease account',
    'canonical' => 'register.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
            <div class="breadcumb-wrapper__title">Register</div>
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
                    <span class="breadcumb-wrapper__items-list-title2">Register</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="register-page section-spacing-120">
    <div class="container container-1352">
        <div class="register-card">
            <div class="register-card__header">
                <h1>Create Account</h1>
                <p>Join us today and start shopping</p>
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

            <form method="post" class="register-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required placeholder="Enter your first name">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required placeholder="Enter your last name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email address">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required placeholder="Create a password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="password-strength"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-match" id="password-match"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span>I agree to the <a href="<?php echo url('terms.php'); ?>">Terms of Service</a> and <a href="<?php echo url('privacy.php'); ?>">Privacy Policy</a> <span class="required">*</span></span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="newsletter">
                        <span>Subscribe to our newsletter for updates and offers</span>
                    </label>
                </div>

                <button type="submit" class="register-btn">Create Account</button>

                <div class="register-divider">
                    <span>OR</span>
                </div>

                <div class="social-login">
                    <button type="button" class="social-btn google-btn">
                        <i class="fa-brands fa-google"></i>
                        Sign up with Google
                    </button>
                    <button type="button" class="social-btn apple-btn">
                        <i class="fa-brands fa-apple"></i>
                        Sign up with Apple
                    </button>
                </div>

                <div class="register-footer">
                    <p>Already have an account? <a href="<?php echo url('login.php'); ?>">Sign in here</a></p>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.register-page {
    background: #f8f9fa;
    min-height: 60vh;
}
.register-card {
    max-width: 600px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    text-align: center;
}
.register-card__header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0C0C0C;
    margin: 0 0 8px;
}
.register-card__header p {
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
.register-form {
    text-align: left;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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
.required {
    color: #EE2D7A;
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
.password-strength, .password-match {
    margin-top: 8px;
    font-size: 12px;
    min-height: 16px;
}
.password-strength.weak { color: #ef4444; }
.password-strength.medium { color: #f59e0b; }
.password-strength.strong { color: #10b981; }
.password-match.match { color: #10b981; }
.password-match.no-match { color: #ef4444; }
.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
    line-height: 1.4;
}
.checkbox-label input {
    width: auto;
    margin-top: 2px;
}
.checkbox-label a {
    color: #EE2D7A;
    text-decoration: none;
}
.register-btn {
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
.register-btn:hover {
    background: #d4256a;
    transform: translateY(-2px);
}
.register-divider {
    margin: 24px 0;
    text-align: center;
    position: relative;
}
.register-divider span {
    background: #fff;
    padding: 0 16px;
    color: #666;
    font-size: 14px;
}
.register-divider::before {
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
.register-footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
.register-footer p {
    margin: 0;
    color: #666;
    font-size: 14px;
}
.register-footer a {
    color: #EE2D7A;
    text-decoration: none;
    font-weight: 600;
}
.register-footer a:hover {
    text-decoration: underline;
}
@media (max-width: 768px) {
    .register-card {
        padding: 24px;
        margin: 0 16px;
    }
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
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
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleBtn = passwordInput.nextElementSibling.querySelector('i');
    
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

// Password strength indicator
const passwordInput = document.getElementById('password');
const passwordStrength = document.getElementById('password-strength');

passwordInput.addEventListener('input', function() {
    const value = this.value;
    let strength = 'weak';
    let text = 'Weak';
    
    if (value.length >= 8 && /[A-Z]/.test(value) && /[0-9]/.test(value) && /[^A-Za-z0-9]/.test(value)) {
        strength = 'strong';
        text = 'Strong';
    } else if (value.length >= 6 && /[A-Z]/.test(value) && /[0-9]/.test(value)) {
        strength = 'medium';
        text = 'Medium';
    }
    
    passwordStrength.className = 'password-strength ' + strength;
    passwordStrength.textContent = 'Password strength: ' + text;
});

// Password match indicator
const confirmPasswordInput = document.getElementById('confirm_password');
const passwordMatch = document.getElementById('password-match');

confirmPasswordInput.addEventListener('input', function() {
    const password = passwordInput.value;
    const confirmPassword = this.value;
    
    if (confirmPassword === '') {
        passwordMatch.className = 'password-match';
        passwordMatch.textContent = '';
    } else if (password === confirmPassword) {
        passwordMatch.className = 'password-match match';
        passwordMatch.textContent = 'Passwords match';
    } else {
        passwordMatch.className = 'password-match no-match';
        passwordMatch.textContent = 'Passwords do not match';
    }
});
</script>

<?php include 'includes/footer.php'; ?>