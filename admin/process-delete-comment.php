<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$comment_id = filter_var($data['comment_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    $query = "DELETE FROM community_comments WHERE comment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete comment');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Operation failed: ' . $e->getMessage()
    ]);
}
