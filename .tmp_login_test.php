<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['REQUEST_METHOD']='POST';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='codex-test';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/security.php';
require __DIR__ . '/includes/jwt.php';
require __DIR__ . '/includes/auth.php';

$r = admin_login('admin@mybrandplease.com','Admin@123');
var_dump($r);
var_dump($_SESSION['admin_access_token'] ?? null);
