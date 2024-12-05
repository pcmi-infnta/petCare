<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

$post_id = filter_var($_GET['post_id'], FILTER_SANITIZE_NUMBER_INT);

$query = "
    SELECT 
        cc.*,
        u.email,
        up.first_name,
        up.last_name,
        DATE_FORMAT(cc.created_at, '%M %d, %Y %h:%i %p') as formatted_date
    FROM community_comments cc
    LEFT JOIN users u ON cc.user_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE cc.post_id = ?
    ORDER BY cc.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'id' => $row['comment_id'],
            'content' => htmlspecialchars($row['content']),
            'author' => htmlspecialchars($row['first_name'] . ' ' . $row['last_name']),
            'date' => $row['formatted_date'],
            'user_id' => $row['user_id']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch comments'
    ]);
}
