<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = filter_var($data['post_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    $conn->begin_transaction();

    // Delete likes first
    $delete_likes = "DELETE FROM community_likes WHERE post_id = ?";
    $stmt = $conn->prepare($delete_likes);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Delete comments
    $delete_comments = "DELETE FROM community_comments WHERE post_id = ?";
    $stmt = $conn->prepare($delete_comments);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Delete the post
    $delete_post = "DELETE FROM community_posts WHERE post_id = ?";
    $stmt = $conn->prepare($delete_post);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Post and related content deleted successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Operation failed: ' . $e->getMessage()
    ]);
}
