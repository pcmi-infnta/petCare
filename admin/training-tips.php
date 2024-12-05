<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all training tips with creator information
$tips_query = "
    SELECT tt.*, u.email as creator_email, up.first_name, up.last_name
    FROM training_tips tt
    LEFT JOIN users u ON tt.created_by = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    ORDER BY tt.created_at DESC";
$tips = $conn->query($tips_query);

// Get statistics
$stats = [
    'total_tips' => $conn->query("SELECT COUNT(*) FROM training_tips")->fetch_row()[0],
    'beginner_tips' => $conn->query("SELECT COUNT(*) FROM training_tips WHERE difficulty_level = 'beginner'")->fetch_row()[0],
    'intermediate_tips' => $conn->query("SELECT COUNT(*) FROM training_tips WHERE difficulty_level = 'intermediate'")->fetch_row()[0],
    'advanced_tips' => $conn->query("SELECT COUNT(*) FROM training_tips WHERE difficulty_level = 'advanced'")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Training Tips Management - Admin Dashboard</title>
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

        .tips-section {
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

        .difficulty-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .difficulty-beginner {
            background: #D1FAE5;
            color: #059669;
        }

        .difficulty-intermediate {
            background: #FEF3C7;
            color: #D97706;
        }

        .difficulty-advanced {
            background: #FEE2E2;
            color: #DC2626;
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

        .prerequisites-list {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .prerequisite-item {
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
    gap: 0.5rem;
}

.prerequisites-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.prerequisite-item {
    background: #F3F4F6;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.prerequisite-item i {
    cursor: pointer;
    color: #6B7280;
    transition: color 0.2s;
}

.prerequisite-item i:hover {
    color: #DC2626;
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

.btn-secondary {
    background: #6B7280;
    color: white;
}

.btn-secondary:hover {
    background: #4B5563;
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
            <h1 style="margin-bottom: 2rem; color: #1F2937;">Training Tips Management</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Tips</h3>
                    <p style="font-size: 2rem; color: #8B5CF6;"><?php echo $stats['total_tips']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Beginner Level</h3>
                    <p style="font-size: 2rem; color: #059669;"><?php echo $stats['beginner_tips']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Intermediate Level</h3>
                    <p style="font-size: 2rem; color: #D97706;"><?php echo $stats['intermediate_tips']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Advanced Level</h3>
                    <p style="font-size: 2rem; color: #DC2626;"><?php echo $stats['advanced_tips']; ?></p>
                </div>
            </div>

            <div class="tips-section">
                <div class="section-header">
                    <h2>All Training Tips</h2>
                    <button onclick="openModal('addTipModal')" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Tip
                    </button>
                </div>


                <div class="filters">
                    <select class="filter-select" id="petTypeFilter">
                        <option value="">All Pet Types</option>
                        <option value="dog">Dogs</option>
                        <option value="cat">Cats</option>
                        <option value="bird">Birds</option>
                        <option value="other">Other</option>
                    </select>
                    <select class="filter-select" id="difficultyFilter">
                        <option value="">All Difficulties</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <input type="text" class="search-input" placeholder="Search tips..." id="searchTips">
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Pet Type</th>
                            <th>Difficulty</th>
                            <th>Duration</th>
                            <th>Prerequisites</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($tip = $tips->fetch_assoc()): ?>
                        <tr>
                            <td class="content-preview"><?php echo htmlspecialchars($tip['title']); ?></td>
                            <td>
                                <span class="pet-type-badge type-<?php echo $tip['pet_type']; ?>">
                                    <?php echo ucfirst($tip['pet_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="difficulty-badge difficulty-<?php echo $tip['difficulty_level']; ?>">
                                    <?php echo ucfirst($tip['difficulty_level']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($tip['estimated_duration']); ?></td>
                            <td>
                                <div class="prerequisites-list">
                                    <?php 
                                    $prerequisites = json_decode($tip['prerequisites'], true);
                                    if ($prerequisites) {
                                        foreach ($prerequisites as $prerequisite) {
                                            echo "<span class='prerequisite-item'>" . htmlspecialchars($prerequisite) . "</span>";
                                        }
                                    }
                                    ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($tip['first_name'] . ' ' . $tip['last_name']); ?></td>
                            <td>
                                <a href="view-training-tip.php?id=<?php echo $tip['tip_id']; ?>" class="btn btn-primary">view</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addTipModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Training Tip</h2>
                <span class="close" data-modal="addTipModal">&times;</span>
            </div>
            <form id="addTipForm" method="POST">
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
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="difficulty_level">Difficulty Level</label>
                    <select id="difficulty_level" name="difficulty_level" required>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estimated_duration">Estimated Duration</label>
                    <input type="text" id="estimated_duration" name="estimated_duration" required>
                </div>

                <div class="form-group">
                    <label for="prerequisites">Prerequisites</label>
                    <div class="input-group">
                        <input type="text" id="prerequisite_input">
                        <button type="button" class="btn btn-secondary" onclick="addPrerequisite()">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    <div id="prerequisites_list" class="prerequisites-list"></div>
                    <input type="hidden" id="prerequisites" name="prerequisites">
                </div>

                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="6" required></textarea>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Training Tip</button>
                </div>
            </form>
        </div>
    </div>

    <script>

let prerequisites = [];

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
}

function addPrerequisite() {
    const input = document.getElementById('prerequisite_input');
    const value = input.value.trim();
    
    if (value) {
        prerequisites.push(value);
        updatePrerequisitesList();
        input.value = '';
    }
}

function removePrerequisite(index) {
    prerequisites.splice(index, 1);
    updatePrerequisitesList();
}

function updatePrerequisitesList() {
    const list = document.getElementById('prerequisites_list');
    const hiddenInput = document.getElementById('prerequisites');
    
    list.innerHTML = prerequisites.map((prereq, index) => `
        <span class="prerequisite-item">
            ${prereq}
            <i class="fas fa-times" onclick="removePrerequisite(${index})"></i>
        </span>
    `).join('');
    
    hiddenInput.value = JSON.stringify(prerequisites);
}

// Event Listeners
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
    document.getElementById('addTipForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('process-add-training-tip.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('addTipModal');
                location.reload();
            } else {
                alert(data.message);
            }
        });
    });
});
        const searchInput = document.getElementById('searchTips');
        const typeFilter = document.getElementById('petTypeFilter');
        const difficultyFilter = document.getElementById('difficultyFilter');
        const tableRows = document.querySelectorAll('.data-table tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedType = typeFilter.value.toLowerCase();
            const selectedDifficulty = difficultyFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const type = row.querySelector('.pet-type-badge').textContent.toLowerCase();
                const difficulty = row.querySelector('.difficulty-badge').textContent.toLowerCase();
                
                const matchesType = !selectedType || type.includes(selectedType);
                const matchesDifficulty = !selectedDifficulty || difficulty.includes(selectedDifficulty);
                
                row.style.display = matchesSearch && matchesType && matchesDifficulty ? '' : 'none';
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        typeFilter.addEventListener('change', filterTable);
        difficultyFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>

