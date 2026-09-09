<?php
require_once __DIR__ . '/includes/User.php';
session_start();

$user = new User($_SESSION['user_id'] ?? null);
if ($user->getData()) {
    $user->logout();
}

header('Location: index.php');
exit;