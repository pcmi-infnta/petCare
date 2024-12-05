<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = filter_var($data['post_id'], FILTER_SANITIZE_NUMBER_INT);
$user_id = $_SESSION['user_id'];

// Check if user already liked the post
$check_query = "SELECT like_id FROM community_likes WHERE post_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $post_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

try {
    if ($result->num_rows > 0) {
        // Unlike
        $query = "DELETE FROM community_likes WHERE post_id = ? AND user_id = ?";
    } else {
        // Like
        $query = "INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $post_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $result->num_rows > 0 ? 'Post unliked' : 'Post liked'
        ]);
    } else {
        throw new Exception('Failed to update like status');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Operation failed: ' . $e->getMessage()
    ]);
}
