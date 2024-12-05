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
    $guide_id = filter_var($data['guide_id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        $conn->begin_transaction();

        $query = "DELETE FROM food_guides WHERE guide_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $guide_id);

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Food guide deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete food guide');
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
