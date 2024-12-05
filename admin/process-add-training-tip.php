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
    $difficulty_level = filter_var($_POST['difficulty_level'], FILTER_SANITIZE_STRING);
    $estimated_duration = filter_var($_POST['estimated_duration'], FILTER_SANITIZE_STRING);
    $prerequisites = $_POST['prerequisites']; // Already JSON string
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);
    $created_by = $_SESSION['user_id'];
    $tip_id = isset($_POST['tip_id']) ? filter_var($_POST['tip_id'], FILTER_SANITIZE_NUMBER_INT) : null;

    try {
        $conn->begin_transaction();

        if ($tip_id) {
            // Update existing tip
            $query = "UPDATE training_tips SET 
                title = ?, 
                pet_type = ?, 
                difficulty_level = ?, 
                estimated_duration = ?, 
                prerequisites = ?,
                content = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE tip_id = ? AND created_by = ?";

            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssssssii",
                $title,
                $pet_type,
                $difficulty_level,
                $estimated_duration,
                $prerequisites,
                $content,
                $tip_id,
                $created_by
            );
        } else {
            // Insert new tip
            $query = "INSERT INTO training_tips (
                title, 
                pet_type, 
                difficulty_level, 
                estimated_duration, 
                prerequisites,
                content,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssssssi",
                $title,
                $pet_type,
                $difficulty_level,
                $estimated_duration,
                $prerequisites,
                $content,
                $created_by
            );
        }

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => $tip_id ? 'Training tip updated successfully' : 'Training tip added successfully',
                'tip_id' => $tip_id ?? $conn->insert_id
            ]);
        } else {
            throw new Exception('Failed to save training tip');
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
