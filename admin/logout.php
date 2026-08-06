<?php
require_once __DIR__ . '/_init.php';
preview_mode_toggle(false);
admin_logout();
header('Location: login.php');
exit;
