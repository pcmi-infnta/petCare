<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all care guides with creator information
// Fetch all care guides with creator information
$guides_query = "
    SELECT cg.*, u.email as creator_email, up.first_name, up.last_name
    FROM care_guides cg
    LEFT JOIN users u ON cg.created_by = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    ORDER BY cg.created_at DESC";
$guides = $conn->query($guides_query);

// Get statistics
$stats = [
    'total_guides' => $conn->query("SELECT COUNT(*) FROM care_guides")->fetch_row()[0],
    'dog_guides' => $conn->query("SELECT COUNT(*) FROM care_guides WHERE pet_type = 'dog'")->fetch_row()[0],
    'cat_guides' => $conn->query("SELECT COUNT(*) FROM care_guides WHERE pet_type = 'cat'")->fetch_row()[0],
    'other_guides' => $conn->query("SELECT COUNT(*) FROM care_guides WHERE pet_type NOT IN ('dog', 'cat')")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Care Guides Management - Admin Dashboard</title>
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

        .guides-section {
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

        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            background: #F3F4F6;
            color: #374151;
        }

        .pet-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .type-dog { background: #DBEAFE; color: #1D4ED8; }
        .type-cat { background: #FCE7F3; color: #BE185D; }
        .type-bird { background: #FEF3C7; color: #D97706; }
        .type-fish { background: #E0E7FF; color: #4F46E5; }
        .type-other { background: #E5E7EB; color: #374151; }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            text-decoration: none;
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
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
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

        .close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6B7280;
            transition: all 0.2s;
            background: #F3F4F6;
        }

        .close:hover {
            background: #E5E7EB;
            color: #1F2937;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            background: #F9FAFB;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            outline: none;
        }

        .modal-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #E5E7EB;
            display: flex;
            justify-content: flex-end;
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
            <h1 style="margin-bottom: 2rem; color: #1F2937;">Care Guides Management</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Guides</h3>
                    <p style="font-size: 2rem; color: #8B5CF6;"><?php echo $stats['total_guides']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Dog Guides</h3>
                    <p style="font-size: 2rem; color: #1D4ED8;"><?php echo $stats['dog_guides']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Cat Guides</h3>
                    <p style="font-size: 2rem; color: #BE185D;"><?php echo $stats['cat_guides']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Categories</h3>
                    <p style="font-size: 2rem; color: #374151;">
                        <?php echo $conn->query("SELECT COUNT(DISTINCT category) FROM care_guides")->fetch_row()[0]; ?>
                    </p>
                </div>
            </div>

            <div class="guides-section">
                <div class="section-header">
                    <h2>All Care Guides</h2>
                    <button onclick="openModal('addGuideModal')" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Care Guide
                    </button>
                </div>

                <div class="filters">
                    <select class="filter-select" id="petTypeFilter">
                        <option value="">All Pet Types</option>
                        <option value="dog">Dogs</option>
                        <option value="cat">Cats</option>
                        <option value="bird">Birds</option>
                        <option value="fish">Fish</option>
                        <option value="other">Other</option>
                    </select>
                    <select class="filter-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="grooming">Grooming</option>
                        <option value="health">Health</option>
                        <option value="behavior">Behavior</option>
                        <option value="nutrition">Nutrition</option>
                    </select>
                    <input type="text" class="search-input" placeholder="Search guides..." id="searchGuides">
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Pet Type</th>
                            <th>Category</th>
                            <th>Content Preview</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($guide = $guides->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($guide['title']); ?></td>
                            <td>
                                <span class="pet-type-badge type-<?php echo $guide['pet_type']; ?>">
                                    <?php echo ucfirst($guide['pet_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="category-badge">
                                    <?php echo ucfirst($guide['category']); ?>
                                </span>
                            </td>
                            <td class="content-preview">
                                <?php echo htmlspecialchars(substr($guide['content'], 0, 100)) . '...'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($guide['first_name'] . ' ' . $guide['last_name']); ?></td>
                            <td>
                                <a href="view-care-guide.php?id=<?php echo $guide['guide_id']; ?>" class="btn btn-primary">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addGuideModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Care Guide</h2>
                <span class="close" data-modal="addGuideModal">&times;</span>
            </div>
            <form id="addGuideForm" method="POST">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="pet_type">Pet Type</label>
                    <select id="pet_type" name="pet_type" required>
                        <option value="dog">Dog</option>
                        <option value="cat">Cat</option>
                        <option value="bird">Bird</option>
                        <option value="fish">Fish</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="grooming">Grooming</option>
                        <option value="health">Health</option>
                        <option value="exercise">Exercise</option>
                        <option value="general">General Care</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="content">Care Instructions</label>
                    <textarea id="content" name="content" rows="6" required></textarea>
                </div>

                <div class="form-group">
                    <label for="tips">Additional Tips</label>
                    <textarea id="tips" name="tips" rows="4"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Care Guide</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'flex';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Close button functionality
            document.querySelectorAll('.close').forEach(button => {
                button.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-modal');
                    closeModal(modalId);
                });
            });

            // Click outside modal to close
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    closeModal(event.target.id);
                }
            }

            // Form submission
            document.getElementById('addGuideForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('process-add-care-guide.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeModal('addGuideModal');
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            });
        });
        // Search and filter functionality
        const searchInput = document.getElementById('searchGuides');
        const typeFilter = document.getElementById('petTypeFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const tableRows = document.querySelectorAll('.data-table tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedType = typeFilter.value.toLowerCase();
            const selectedCategory = categoryFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const type = row.querySelector('.pet-type-badge').textContent.toLowerCase();
                const category = row.querySelector('.category-badge').textContent.toLowerCase();
                
                const matchesSearch = text.includes(searchTerm);
                const matchesType = !selectedType || type.includes(selectedType);
                const matchesCategory = !selectedCategory || category.includes(selectedCategory);
                
                row.style.display = matchesSearch && matchesType && matchesCategory ? '' : 'none';
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        typeFilter.addEventListener('change', filterTable);
        categoryFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
