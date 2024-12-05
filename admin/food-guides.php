<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all food guides with creator information
$guides_query = "
    SELECT fg.*, u.email as creator_email, up.first_name, up.last_name
    FROM food_guides fg
    LEFT JOIN users u ON fg.created_by = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    ORDER BY fg.created_at DESC";
$guides = $conn->query($guides_query);

// Get statistics
$stats = [
    'total_guides' => $conn->query("SELECT COUNT(*) FROM food_guides")->fetch_row()[0],
    'dog_guides' => $conn->query("SELECT COUNT(*) FROM food_guides WHERE pet_type = 'dog'")->fetch_row()[0],
    'cat_guides' => $conn->query("SELECT COUNT(*) FROM food_guides WHERE pet_type = 'cat'")->fetch_row()[0],
    'other_guides' => $conn->query("SELECT COUNT(*) FROM food_guides WHERE pet_type NOT IN ('dog', 'cat')")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Food Guides Management - Admin Dashboard</title>
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

        .pet-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .type-dog {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .type-cat {
            background: #FCE7F3;
            color: #BE185D;
        }

        .type-other {
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

        .age-range {
            color: #6B7280;
            font-size: 0.875rem;
        }

        .weight-range {
            color: #6B7280;
            font-size: 0.875rem;
        }
        /* Main Styles */
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
    min-height: 100px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #8B5CF6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    outline: none;
}

.input-group {
    display: flex;
    gap: 1rem;
}

.input-group input {
    flex: 1;
}

.modal-footer {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 2px solid #E5E7EB;
    display: flex;
    justify-content: flex-end;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
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


        <!-- Main content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem; color: #1F2937;">Food Guides Management</h1>

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
                    <h3>Other Guides</h3>
                    <p style="font-size: 2rem; color: #374151;"><?php echo $stats['other_guides']; ?></p>
                </div>
            </div>

            <div class="guides-section">
                <div class="section-header">
                    <h2>All Food Guides</h2>
                    <button onclick="openModal('addGuideModal')" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Guide
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
                    <input type="text" class="search-input" placeholder="Search guides..." id="searchGuides">
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pet Type</th>
                            <th>Age Range</th>
                            <th>Weight Range</th>
                            <th>Food Type</th>
                            <th>Portion Size</th>
                            <th>Meals/Day</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($guide = $guides->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="pet-type-badge type-<?php echo $guide['pet_type']; ?>">
                                    <?php echo ucfirst($guide['pet_type']); ?>
                                </span>
                            </td>
                            <td class="age-range">
                                <?php echo $guide['age_range_start_months'] . '-' . 
                                ($guide['age_range_end_months'] ?? '∞') . ' months'; ?>
                            </td>
                            <td class="weight-range">
                                <?php echo $guide['weight_range_start_kg'] . '-' . 
                                ($guide['weight_range_end_kg'] ?? '∞') . ' kg'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($guide['food_type']); ?></td>
                            <td><?php echo $guide['portion_size_grams'] . 'g'; ?></td>
                            <td><?php echo $guide['meals_per_day']; ?></td>
                            <td><?php echo htmlspecialchars($guide['first_name'] . ' ' . $guide['last_name']); ?></td>
                            <td>
                                <a href="view-food-guide.php?id=<?php echo $guide['guide_id']; ?>" class="btn btn-primary">View</a>
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
                <h2>Add New Food Guide</h2>
                <span class="close" data-modal="addGuideModal">&times;</span>
            </div>
            <form id="addGuideForm" method="POST">
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
                    <label>Age Range (months)</label>
                    <div class="input-group">
                        <input type="number" id="age_range_start" name="age_range_start_months" 
                               placeholder="Start" required min="0">
                        <input type="number" id="age_range_end" name="age_range_end_months" 
                               placeholder="End" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Weight Range (kg)</label>
                    <div class="input-group">
                        <input type="number" id="weight_range_start" name="weight_range_start_kg" 
                               placeholder="Start" required step="0.1" min="0">
                        <input type="number" id="weight_range_end" name="weight_range_end_kg" 
                               placeholder="End" step="0.1" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="food_type">Food Type</label>
                    <input type="text" id="food_type" name="food_type" required>
                </div>

                <div class="form-group">
                    <label for="portion_size">Portion Size (grams)</label>
                    <input type="number" id="portion_size" name="portion_size_grams" required min="0" step="0.1">
                </div>

                <div class="form-group">
                    <label for="meals_per_day">Meals Per Day</label>
                    <input type="number" id="meals_per_day" name="meals_per_day" required min="1" max="10">
                </div>

                <div class="form-group">
                    <label for="feeding_instructions">Feeding Instructions</label>
                    <textarea id="feeding_instructions" name="feeding_instructions" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="special_notes">Special Notes</label>
                    <textarea id="special_notes" name="special_notes" rows="3"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Food Guide</button>
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
                
                fetch('process-add-food-guide.php', {
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
        const tableRows = document.querySelectorAll('.data-table tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedType = typeFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const type = row.querySelector('.pet-type-badge').textContent.toLowerCase();
                const matchesSearch = text.includes(searchTerm);
                const matchesType = !selectedType || type.includes(selectedType);
                
                row.style.display = matchesSearch && matchesType ? '' : 'none';
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        typeFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
