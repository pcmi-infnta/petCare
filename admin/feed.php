<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all posts with user info, likes, and comments
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
    GROUP BY cp.post_id, cp.user_id, cp.content, cp.created_at, cp.is_pinned, 
             u.email, up.first_name, up.last_name
    ORDER BY cp.is_pinned DESC, cp.created_at DESC";

$posts = $conn->query($posts_query);

try {
    $posts = $conn->query($posts_query);
    if (!$posts) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    die("Error fetching posts: " . $e->getMessage());
}

// Fetch comments for each post
function getPostComments($post_id, $conn) {
    $query = "
        SELECT 
            cc.*,
            u.email,
            up.first_name,
            up.last_name
        FROM community_comments cc
        LEFT JOIN users u ON cc.user_id = u.user_id
        LEFT JOIN user_profiles up ON u.user_id = up.user_id
        WHERE cc.post_id = ?
        ORDER BY cc.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Check if user has liked a post
function hasUserLikedPost($post_id, $user_id, $liked_by_users) {
    $liked_users = explode(',', $liked_by_users);
    return in_array($user_id, $liked_users);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Feed - Admin Dashboard</title>
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

        .feed-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .post-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            padding: 1.5rem;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .post-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-content {
            margin-bottom: 1rem;
            white-space: pre-wrap;
        }

        .post-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: none;
            background: none;
            cursor: pointer;
            color: #6B7280;
            transition: all 0.2s;
        }

        .action-btn:hover {
            color: #8B5CF6;
        }

        .action-btn.liked {
            color: #DC2626;
        }

        .comments-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .comment-form {
            margin-bottom: 1rem;
        }

        .comment-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            resize: vertical;
        }

        .comment-card {
            background: #F9FAFB;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .create-post {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .create-post textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            resize: vertical;
        }

        .pinned-badge {
            background: #FEF3C7;
            color: #D97706;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
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
            <div class="feed-container">
                <!-- Create Post Section -->
                <div class="create-post">
                    <h2 style="margin-bottom: 1rem;">Create Post</h2>
                    <form id="createPostForm">
                        <textarea name="content" rows="4" placeholder="What's on your mind?" required></textarea>
                        <button type="submit" class="btn btn-primary">Post</button>
                    </form>
                </div>

                <!-- Posts Feed -->
                <?php while($post = $posts->fetch_assoc()): ?>
                    <div class="post-card">
                        <div class="post-header">
                                <div class="post-author">
                                    <strong><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></strong>
                                    <span style="color: #6B7280;">• <?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
                                </div>
                            <?php if($post['is_pinned']): ?>
                                <span class="pinned-badge">
                                    <i class="fas fa-thumbtack"></i> Pinned
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </div>

                        <div class="post-actions">
                                <?php 
                                $liked_by = $post['liked_by'] ? explode(',', $post['liked_by']) : [];
                                $is_liked = in_array($_SESSION['user_id'], $liked_by);
                                ?>
                            <button class="action-btn <?php echo $is_liked ? 'liked' : ''; ?>" 
                                    onclick="toggleLike(<?php echo $post['post_id']; ?>)">
                                <i class="fas fa-heart"></i>
                                <span><?php echo $post['likes_count']; ?></span>
                            </button>
                            <button class="action-btn" onclick="toggleComments(<?php echo $post['post_id']; ?>)">
                                <i class="fas fa-comment"></i>
                                <span><?php echo $post['comments_count']; ?></span>
                            </button>
                            <button class="action-btn" onclick="togglePin(<?php echo $post['post_id']; ?>)">
                                <i class="fas fa-thumbtack"></i>
                                <?php echo $post['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                            </button>
                        </div>

                        <!-- Comments Section -->
                        <div id="comments-<?php echo $post['post_id']; ?>" class="comments-section" style="display: none;">
                            <form class="comment-form" onsubmit="addComment(event, <?php echo $post['post_id']; ?>)">
                                <textarea class="comment-input" placeholder="Write a comment..." rows="2"></textarea>
                                <button type="submit" class="btn btn-primary">Comment</button>
                            </form>
                            <div id="comments-list-<?php echo $post['post_id']; ?>">
                                <!-- Comments will be loaded here -->
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleLike(postId) {
            fetch('process-toggle-like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function toggleComments(postId) {
            const commentsSection = document.getElementById(`comments-${postId}`);
            const commentsList = document.getElementById(`comments-list-${postId}`);
            
            if (commentsSection.style.display === 'none') {
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


        function addComment(event, postId) {
            event.preventDefault();
            const content = event.target.querySelector('textarea').value;
            
            fetch('process-add-comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ post_id: postId, content: content })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    event.target.querySelector('textarea').value = '';
                    loadComments(postId);
                }
            });
        }

        function togglePin(postId) {
    const currentStatus = event.target.textContent.trim();
    const action = currentStatus === 'Pin' ? 'pin' : 'unpin';
    
    fetch('process-pin-post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            post_id: postId,
            action: action 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}


        document.getElementById('createPostForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const content = this.querySelector('textarea').value;
            
            fetch('process-create-post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ content: content })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>
