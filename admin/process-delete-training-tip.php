<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $tip_id = filter_var($data['tip_id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        $conn->begin_transaction();

        // Delete the training tip
        $query = "DELETE FROM training_tips WHERE tip_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tip_id);

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Training tip deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete training tip');
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
