<?php
// Remove session_start() as it may cause issues with Vercel's serverless functions
// session_start();

require_once(__DIR__ . '/../config/database.php');

// Return JSON response instead of redirects for API endpoints
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        echo json_encode(['redirect' => '../admin/dashboard.php']);
    } else if ($_SESSION['role'] === 'user') {
        echo json_encode(['redirect' => '../user/dashboard.php']);
    }
} else {
    echo json_encode(['redirect' => '../views/login.php']);
}