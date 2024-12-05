<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all community posts with user information
$posts_query = "
    SELECT cp.*, u.email, up.first_name, up.last_name,
    (SELECT COUNT(*) FROM community_comments WHERE post_id = cp.post_id) as total_comments
    FROM community_posts cp
    LEFT JOIN users u ON cp.user_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    ORDER BY cp.is_pinned DESC, cp.created_at DESC";
$posts = $conn->query($posts_query);

// Get statistics
$stats = [
    'total_posts' => $conn->query("SELECT COUNT(*) FROM community_posts")->fetch_row()[0],
    'total_comments' => $conn->query("SELECT COUNT(*) FROM community_comments")->fetch_row()[0],
    'active_posts' => $conn->query("SELECT COUNT(*) FROM community_posts WHERE is_archived = 0")->fetch_row()[0],
    'pinned_posts' => $conn->query("SELECT COUNT(*) FROM community_posts WHERE is_pinned = 1")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Posts Management - Admin Dashboard</title>
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
        }

        .main-content {
            padding: 2rem;
            overflow-y: auto;
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

        .posts-section {
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

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table tr:hover {
            background-color: #f9fafb;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pinned {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-archived {
            background: #E5E7EB;
            color: #374151;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: #8B5CF6;
            color: white;
        }

        .btn-warning {
            background: #F59E0B;
            color: white;
        }

        .btn-danger {
            background: #DC2626;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
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

        .tags-container {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .tag {
            background: #F3F4F6;
            color: #374151;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.75rem;
        }

        .content-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
            <h1 style="margin-bottom: 2rem; color: #1F2937;">Community Posts Management</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Posts</h3>
                    <p style="font-size: 2rem; color: #8B5CF6;"><?php echo $stats['total_posts']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Comments</h3>
                    <p style="font-size: 2rem; color: #059669;"><?php echo $stats['total_comments']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Posts</h3>
                    <p style="font-size: 2rem; color: #1D4ED8;"><?php echo $stats['active_posts']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pinned Posts</h3>
                    <p style="font-size: 2rem; color: #D97706;"><?php echo $stats['pinned_posts']; ?></p>
                </div>
            </div>

            <div class="posts-section">
                <div class="section-header">
                    <h2>Community Posts</h2>
                </div>

                <div class="filters">
                    <input type="text" class="search-input" placeholder="Search posts..." id="searchPosts">
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="pinned">Pinned</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Content Preview</th>
                            <th>Tags</th>
                            <th>Likes</th>
                            <th>Comments</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($post = $posts->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></td>
                                <td class="content-preview"><?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>...</td>
                                <td>
                                    <div class="tags-container">
                                        <?php 
                                        $tags = json_decode($post['tags'], true);
                                        if ($tags) {
                                            foreach ($tags as $tag) {
                                                echo "<span class='tag'>" . htmlspecialchars($tag) . "</span>";
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td><?php echo $post['likes_count']; ?></td>
                                <td><?php echo $post['total_comments']; ?></td>
                                <td>
                                    <?php if ($post['is_pinned']): ?>
                                        <span class="status-badge status-pinned">Pinned</span>
                                    <?php endif; ?>
                                    <?php if ($post['is_archived']): ?>
                                        <span class="status-badge status-archived">Archived</span>
                                        <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                <td>
                                    <a href="view-community-post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchPosts');
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('.data-table tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const statusBadges = row.querySelectorAll('.status-badge');
                let status = 'active';
                
                statusBadges.forEach(badge => {
                    if (badge.textContent.toLowerCase() === 'pinned') status = 'pinned';
                    if (badge.textContent.toLowerCase() === 'archived') status = 'archived';
                });

                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !selectedStatus || status === selectedStatus;
                
                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }

        function togglePin(postId) {
            // Add AJAX call to toggle pin status
            fetch(`toggle-pin.php?id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) location.reload();
                });
        }

        function toggleArchive(postId) {
            // Add AJAX call to toggle archive status
            fetch(`toggle-archive.php?id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) location.reload();
                });
        }

        searchInput.addEventListener('keyup', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
