<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all comments with related information
$comments_query = "
    SELECT 
        cc.*,
        cp.title as post_title,
        u.email,
        up.first_name,
        up.last_name,
        parent.content as parent_content,
        DATE_FORMAT(cc.created_at, '%M %d, %Y %h:%i %p') as formatted_date
    FROM community_comments cc
    JOIN community_posts cp ON cc.post_id = cp.post_id
    LEFT JOIN users u ON cc.user_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    LEFT JOIN community_comments parent ON cc.parent_comment_id = parent.comment_id
    ORDER BY cc.created_at DESC";

$comments = $conn->query($comments_query);

// Get statistics
$stats = [
    'total_comments' => $conn->query("SELECT COUNT(*) FROM community_comments")->fetch_row()[0],
    'today_comments' => $conn->query("SELECT COUNT(*) FROM community_comments WHERE DATE(created_at) = CURDATE()")->fetch_row()[0],
    'edited_comments' => $conn->query("SELECT COUNT(*) FROM community_comments WHERE is_edited = 1")->fetch_row()[0],
    'replies' => $conn->query("SELECT COUNT(*) FROM community_comments WHERE parent_comment_id IS NOT NULL")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments Management - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

body {
    background-color: #f0f2ff;
    min-height: 100vh;
    background-image: radial-gradient(circle at 10% 20%, rgb(239, 246, 255) 0%, rgb(219, 228, 255) 100%);
}

.dashboard {
    display: grid;
    grid-template-columns: 250px 1fr;
    min-height: 100vh;
}

.sidebar {
    background: white;
    padding: 2rem;
    border-right: 1px solid #e5e7eb;
    box-shadow: 4px 0 6px rgba(0, 0, 0, 0.05);
    position: fixed;
    width: 250px;
    height: 100vh;
    overflow-y: auto;
    z-index: 1000;
}

.main-content {
    margin-left: 250px;
    padding: 2rem;
    overflow-y: auto;
    width: 430%;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.comments-section {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-select {
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 5px;
    font-size: 1rem;
}

.search-input {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 5px;
    font-size: 1rem;
}

.comment-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
}

.comment-card:hover {
    transform: translateY(-2px);
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    color: #4B5563;
}

.comment-content {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    line-height: 1.5;
}

.comment-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.badge-edited {
    background: #FEF3C7;
    color: #D97706;
}

.badge-reply {
    background: #DBEAFE;
    color: #1D4ED8;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: #8B5CF6;
    color: white;
}

.btn-primary:hover {
    background: #7C3AED;
    transform: translateY(-1px);
}

.btn-danger {
    background: #DC2626;
    color: white;
}

.btn-danger:hover {
    background: #B91C1C;
    transform: translateY(-1px);
}

.nav-link {
    display: block;
    padding: 0.75rem 1rem;
    color: #4B5563;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 0.5rem;
    transition: all 0.2s;
}

.nav-link:hover {
    background: #F3F4F6;
    color: #8B5CF6;
    transform: translateX(5px);
}

.reply-to {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #F3F4F6;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #4B5563;
}

.status {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
/* Add these styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(17, 24, 39, 0.7);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    position: relative;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #E5E7EB;
}

.modal-body {
    margin-bottom: 2rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.close {
    cursor: pointer;
    font-size: 1.5rem;
    color: #6B7280;
}

    </style>
</head>
<body>
    <div class="dashboard">
    <div class="sidebar">
    <h2 style="margin-bottom: 2rem; color: #8B5CF6;">
        <i class="fas fa-paw"></i> Admin Panel
    </h2>
    <nav>
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="users.php" class="nav-link">
            <i class="fas fa-users"></i> Users Management
        </a>
        <a href="pets.php" class="nav-link">
            <i class="fas fa-dog"></i> Pets Management
        </a>
        <a href="consultations.php" class="nav-link">
            <i class="fas fa-stethoscope"></i> Consultations
        </a>
        <a href="vaccinations.php" class="nav-link">
            <i class="fas fa-syringe"></i> Vaccination Logs
        </a>
        <a href="food-guides.php" class="nav-link">
            <i class="fas fa-bone"></i> Food Guides
        </a>
        <a href="care-guides.php" class="nav-link">
            <i class="fas fa-heart"></i> Care Guides
        </a>
        <a href="training-tips.php" class="nav-link">
            <i class="fas fa-graduation-cap"></i> Training Tips
        </a>
        <a href="feed.php" class="nav-link">
            <i class="fas fa-rss"></i> Feed
        </a>
        <a href="community-posts.php" class="nav-link">
            <i class="fas fa-comments"></i> Community Posts
        </a>
        <a href="community-comments.php" class="nav-link">
            <i class="fas fa-comment-dots"></i> Comments Management
        </a>
        <a href="reports.php" class="nav-link">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a href="../views/logout.php" class="nav-link" style="color: #DC2626;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>
        
        <div class="main-content">
            <h1>Comments Management</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Comments</h3>
                    <p><?php echo $stats['total_comments']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Today's Comments</h3>
                    <p><?php echo $stats['today_comments']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Edited Comments</h3>
                    <p><?php echo $stats['edited_comments']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Replies</h3>
                    <p><?php echo $stats['replies']; ?></p>
                </div>
            </div>

            <div class="comments-list">
                <?php while($comment = $comments->fetch_assoc()): ?>
                    <div class="comment-card">
                        <div class="comment-header">
                            <div>
                                <strong><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></strong>
                                <span class="text-gray-500">on post: <?php echo htmlspecialchars($comment['post_title']); ?></span>
                            </div>
                            <span><?php echo $comment['formatted_date']; ?></span>
                        </div>

                        <?php if($comment['parent_content']): ?>
                            <div class="reply-to">
                                <span class="badge badge-reply">Replying to:</span>
                                <p><?php echo htmlspecialchars(substr($comment['parent_content'], 0, 100)) . '...'; ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="comment-content">
                            <?php echo htmlspecialchars($comment['content']); ?>
                        </div>

                        <div class="comment-footer">
                            <div class="status">
                                <?php if($comment['is_edited']): ?>
                                    <span class="badge badge-edited">Edited</span>
                                <?php endif; ?>
                                <span class="badge"><?php echo $comment['likes_count']; ?> likes</span>
                            </div>
                            <div class="actions">
                                <button onclick="deleteComment(<?php echo $comment['comment_id']; ?>)" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete Comment</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button id="confirmDelete" class="btn btn-danger">Delete</button>
            <button onclick="closeModal()" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

    <script>
        // Update the JavaScript
let commentToDelete = null;

function deleteComment(commentId) {
    commentToDelete = commentId;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

document.querySelector('.close').onclick = closeModal;

document.getElementById('confirmDelete').onclick = function() {
    if (commentToDelete) {
        fetch('process-delete-comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ comment_id: commentToDelete })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
    closeModal();
};

window.onclick = function(event) {
    if (event.target == document.getElementById('deleteModal')) {
        closeModal();
    }
};

    </script>
</body>
</html>
