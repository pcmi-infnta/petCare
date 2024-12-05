<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = filter_var($_POST['post_id'], FILTER_SANITIZE_NUMBER_INT);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);

    try {
        $query = "UPDATE community_posts SET title = ?, content = ? WHERE post_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $title, $content, $post_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Post updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update post');
        }
    } catch (Exception $e) {
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
