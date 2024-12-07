<?php
session_start();
require_once(__DIR__ . '/../config/database.php');

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit();
    } else if ($_SESSION['role'] === 'user') {
        header('Location: ../user/dashboard.php');
        exit();
    }
}

header('Location: ../views/login.php');
exit();