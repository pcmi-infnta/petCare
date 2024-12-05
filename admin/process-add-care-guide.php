<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $pet_type = filter_var($_POST['pet_type'], FILTER_SANITIZE_STRING);
    $category = filter_var($_POST['category'], FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);
    $tips = filter_var($_POST['tips'], FILTER_SANITIZE_STRING);
    $created_by = $_SESSION['user_id'];

    try {
        $conn->begin_transaction();

        $query = "INSERT INTO care_guides (
            title,
            pet_type,
            category,
            content,
            tips,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "sssssi",
            $title,
            $pet_type,
            $category,
            $content,
            $tips,
            $created_by
        );

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Care guide added successfully',
                'guide_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception('Failed to save care guide');
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Operation failed: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
