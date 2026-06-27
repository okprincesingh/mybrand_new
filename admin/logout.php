<?php
require_once __DIR__ . '/_init.php';
admin_logout();
header('Location: login.php');
exit;
