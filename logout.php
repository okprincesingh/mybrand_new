<?php
require_once __DIR__ . '/includes/user.php';

// Perform logout
user_logout();

// Set logout success message in session for display
$_SESSION['logout_message'] = 'You have been successfully logged out.';

// Redirect to home page with a smooth transition
header('Location: index.php?logout=success');
exit;