<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $role = filter_var($_POST['role'], FILTER_SANITIZE_STRING);
    $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
    $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);

    $conn->begin_transaction();

    try {
        // Update users table
        $user_query = "UPDATE users SET email = ?, role = ? WHERE user_id = ?";
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->bind_param("ssi", $email, $role, $user_id);
        $user_stmt->execute();

        // Update user_profiles table
        $profile_query = "UPDATE user_profiles SET first_name = ?, last_name = ?, phone_number = ? WHERE user_id = ?";
        $profile_stmt = $conn->prepare($profile_query);
        $profile_stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
        $profile_stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
}
?>
