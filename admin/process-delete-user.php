<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = filter_var($data['user_id'], FILTER_SANITIZE_NUMBER_INT);

    // Prevent deleting own account
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit();
    }

    try {
        // Due to CASCADE delete in database, this will remove all related records
        $delete_query = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            throw new Exception('Delete operation failed');
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
}
?>
