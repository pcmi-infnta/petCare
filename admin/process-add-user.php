<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
    $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
    $role = filter_var($_POST['role'], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);

    // Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert into users table
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $user_query = "INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)";
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->bind_param("sss", $email, $password_hash, $role);
        $user_stmt->execute();
        
        $user_id = $conn->insert_id;

        // Insert into user_profiles table
        $profile_query = "INSERT INTO user_profiles (user_id, first_name, last_name, phone_number) VALUES (?, ?, ?, ?)";
        $profile_stmt = $conn->prepare($profile_query);
        $profile_stmt->bind_param("isss", $user_id, $first_name, $last_name, $phone);
        $profile_stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User added successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
