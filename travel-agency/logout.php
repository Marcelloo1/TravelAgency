<?php
session_start();
$_SESSION = [];

if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, '/');
}

session_destroy();
header('Location: login.php');
exit;
