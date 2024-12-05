<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = filter_var($data['post_id'], FILTER_SANITIZE_NUMBER_INT);
$action = filter_var($data['action'], FILTER_SANITIZE_STRING);

$is_pinned = ($action === 'pin') ? 1 : 0;

try {
    $query = "UPDATE community_posts SET is_pinned = ? WHERE post_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $is_pinned, $post_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Post ' . ($is_pinned ? 'pinned' : 'unpinned') . ' successfully'
        ]);
    } else {
        throw new Exception('Failed to update post status');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Operation failed: ' . $e->getMessage()
    ]);
}
