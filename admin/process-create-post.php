<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$content = filter_var($data['content'], FILTER_SANITIZE_STRING);
$user_id = $_SESSION['user_id'];

try {
    $query = "INSERT INTO community_posts (user_id, content) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $content);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Post created successfully'
        ]);
    } else {
        throw new Exception('Failed to create post');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Operation failed: ' . $e->getMessage()
    ]);
}
