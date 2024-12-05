<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../views/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch posts with user info and comment counts
$posts_query = "
    SELECT 
        cp.*,
        u.email,
        up.first_name,
        up.last_name,
        (SELECT COUNT(*) FROM community_likes WHERE post_id = cp.post_id) as likes_count,
        (SELECT COUNT(*) FROM community_comments WHERE post_id = cp.post_id) as comments_count,
        GROUP_CONCAT(l.user_id) as liked_by
    FROM community_posts cp
    LEFT JOIN users u ON cp.user_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    LEFT JOIN community_likes l ON cp.post_id = l.post_id
    GROUP BY cp.post_id
    ORDER BY cp.is_pinned DESC, cp.created_at DESC";
$posts = $conn->query($posts_query);

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_post') {
        $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
        $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);
        $tags = isset($_POST['tags']) ? json_encode(explode(',', $_POST['tags'])) : null;

        $post_query = "INSERT INTO community_posts (user_id, title, content, tags) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($post_query);
        $stmt->bind_param("isss", $user_id, $title, $content, $tags);
        $stmt->execute();
        header('Location: community.php');
        exit();
    }

    if ($_POST['action'] === 'add_comment') {
        $post_id = filter_var($_POST['post_id'], FILTER_SANITIZE_NUMBER_INT);
        $content = filter_var($_POST['comment_content'], FILTER_SANITIZE_STRING);
        $parent_id = isset($_POST['parent_id']) ? filter_var($_POST['parent_id'], FILTER_SANITIZE_NUMBER_INT) : null;

        $comment_query = "INSERT INTO community_comments (post_id, user_id, content, parent_comment_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($comment_query);
        $stmt->bind_param("iisi", $post_id, $user_id, $content, $parent_id);
        $stmt->execute();

        // Update comment count
        $update_count = "UPDATE community_posts SET comments_count = comments_count + 1 WHERE post_id = ?";
        $count_stmt = $conn->prepare($update_count);
        $count_stmt->bind_param("i", $post_id);
        $count_stmt->execute();

        header('Location: community.php#post-' . $post_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - PetCare</title>
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
    width: 430%;
}

.header {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.post-form {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.post-form h2 {
    color: #1F2937;
    margin-bottom: 1.5rem;
}

.post-form input[type="text"],
.post-form textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.post-form input[type="text"]:focus,
.post-form textarea:focus {
    outline: none;
    border-color: #8B5CF6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.post-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
}

.post-card:hover {
    transform: translateY(-2px);
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.post-author {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.author-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #8B5CF6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 600;
}

.comment {
    background: #F9FAFB;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.reply {
    margin-left: 3rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    margin-top: 1rem;
    border-left: 3px solid #8B5CF6;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.comment-author {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.comment-content {
    color: #4B5563;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.comment-actions {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary {
    background: #8B5CF6;
    color: white;
}

.btn-primary:hover {
    background: #7C3AED;
    transform: translateY(-1px);
}

.btn-link {
    background: none;
    color: #6B7280;
    padding: 0.5rem;
}

.btn-link:hover {
    color: #8B5CF6;
}

.reply-form {
    margin-top: 1rem;
    padding-left: 3rem;
}

.reply-form textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    resize: vertical;
}

.tags {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin: 1rem 0;
}

.tag {
    padding: 0.25rem 0.75rem;
    background: #EEF2FF;
    color: #6366F1;
    border-radius: 9999px;
    font-size: 0.875rem;
}

.pinned-badge {
    padding: 0.5rem 1rem;
    background: #FEF3C7;
    color: #D97706;
    border-radius: 9999px;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.post-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    margin-top: 1rem;
    border-top: 1px solid #E5E7EB;
}
.nav-link {
            display: flex;
            padding: 0.75rem 1rem;
            color: #4B5563;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-link:hover {
            background: #F3F4F6;
            color: #8B5CF6;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .sidebar h2 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #8B5CF6;
        }
        .comment-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    resize: vertical;
    min-height: 60px;
    margin-bottom: 12px;
    background-color: #f9fafb;
    transition: all 0.2s ease;
}

.comment-input:focus {
    border-color: #8B5CF6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    outline: none;
    background-color: #ffffff;
}

.comment-input::placeholder {
    color: #9CA3AF;
}


    </style>
</head>
<body>
    <div class="dashboard">
    <div class="sidebar">
            <h2 style="margin-bottom: 2rem; color: #8B5CF6;">
                <i class="fas fa-paw"></i> PetCare
            </h2>
            <nav>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="my-pets.php" class="nav-link">
                    <i class="fas fa-dog"></i> My Pets
                </a>
                <a href="consultations.php" class="nav-link">
                    <i class="fas fa-stethoscope"></i> Consultations
                </a>
                <a href="vaccinations.php" class="nav-link">
                    <i class="fas fa-syringe"></i> Vaccinations
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
                <a href="community.php" class="nav-link">
                    <i class="fas fa-users"></i> Community
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="../views/logout.php" class="nav-link" style="color: #DC2626;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Community Discussion</h1>
            </div>

            <div class="post-form">
                <h2>Create a New Post</h2>
                <form method="POST" action="community.php">
                    <input type="hidden" name="action" value="create_post">
                    <input type="text" name="title" placeholder="Post Title" required>
                    <textarea name="content" placeholder="What's on your mind?" required></textarea>
                    <input type="text" name="tags" placeholder="Tags (comma-separated)">
                    <button type="submit" class="btn btn-primary">Post</button>
                </form>
            </div>

            <div class="posts-container">
                <?php while($post = $posts->fetch_assoc()): ?>
                    <div class="post-card" id="post-<?php echo $post['post_id']; ?>">
                        <div class="post-header">
                            <div class="post-author">
                                <div class="author-avatar">
                                    <?php echo strtoupper(substr($post['first_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h3><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h3>
                                    <small><?php echo date('M d, Y', strtotime($post['created_at'])); ?></small>
                                </div>
                            </div>
                            <?php if($post['is_pinned']): ?>
                                <span class="pinned-badge">📌 Pinned</span>
                            <?php endif; ?>
                        </div>

                        <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </div>

                        <?php if($post['tags']): ?>
                            <div class="post-tags">
                                <?php foreach(json_decode($post['tags']) as $tag): ?>
                                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="post-footer">
                            <span><?php echo $post['comments_count']; ?> comments</span>
                            <button onclick="toggleComments(<?php echo $post['post_id']; ?>)" class="btn btn-primary">
                                Show Comments
                            </button>
                        </div>


                        <div id="comments-<?php echo $post['post_id']; ?>" class="comments-section" style="display: none;">
                            <div id="comments-list-<?php echo $post['post_id']; ?>" class="comments-list"></div>
                                <form onsubmit="addComment(event, <?php echo $post['post_id']; ?>)" class="comment-form">
                                    <textarea class="comment-input" placeholder="Write a comment..." required></textarea>
                                <button type="submit" class="btn btn-primary">Post Comment</button>
                                </form>
                            </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    
    if (commentsSection.style.display === 'none' || !commentsSection.style.display) {
        commentsSection.style.display = 'block';
        loadComments(postId);
    } else {
        commentsSection.style.display = 'none';
    }
}

function loadComments(postId) {
    fetch(`get-comments.php?post_id=${postId}`)
        .then(response => response.json())
        .then(data => {
            const commentsList = document.getElementById(`comments-list-${postId}`);
            commentsList.innerHTML = data.comments.map(comment => `
                <div class="comment-card">
                    <div class="comment-header">
                        <strong>${comment.author}</strong>
                        <span class="comment-date">${comment.date}</span>
                    </div>
                    <p class="comment-text">${comment.content}</p>
                </div>
            `).join('');
        });
}

    </script>
</body>
</html>
