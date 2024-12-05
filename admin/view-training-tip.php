<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get training tip details
$tip_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$query = "
    SELECT tt.*, u.email as creator_email, up.first_name, up.last_name
    FROM training_tips tt
    LEFT JOIN users u ON tt.created_by = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE tt.tip_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tip_id);
$stmt->execute();
$tip = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Tip Details - Admin Dashboard</title>
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

.nav-link {
            display: block;
            padding: 0.75rem 1rem;
            color: #4B5563;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }

.details-container {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #E5E7EB;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

.detail-item {
    margin-bottom: 1.5rem;
}

.detail-label {
    font-weight: 600;
    color: #4B5563;
    margin-bottom: 0.5rem;
}

.detail-value {
    color: #1F2937;
    font-size: 1.1rem;
}

.content-block {
    grid-column: span 2;
    white-space: pre-wrap;
    background: #F9FAFB;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #E5E7EB;
}

.action-buttons {
    display: flex;
    gap: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
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

.btn-danger {
    background: #DC2626;
    color: white;
}

.btn-danger:hover {
    background: #B91C1C;
}

.btn-secondary {
    background: #6B7280;
    color: white;
}

.btn-secondary:hover {
    background: #4B5563;
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

.pet-type-badge,
.difficulty-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.type-dog { background: #DBEAFE; color: #1D4ED8; }
.type-cat { background: #FCE7F3; color: #BE185D; }
.type-bird { background: #FEF3C7; color: #D97706; }
.type-other { background: #E5E7EB; color: #374151; }

.difficulty-beginner { background: #D1FAE5; color: #059669; }
.difficulty-intermediate { background: #FEF3C7; color: #D97706; }
.difficulty-advanced { background: #FEE2E2; color: #DC2626; }

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
            <div class="details-container">
                <div class="details-header">
                    <h1>Training Tip Details</h1>
                    <div class="action-buttons">
                        <button onclick="openEditModal()" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="openDeleteModal()" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                        <a href="training-tips.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Title</div>
                        <div class="detail-value"><?php echo htmlspecialchars($tip['title']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Pet Type</div>
                        <div class="detail-value">
                            <span class="pet-type-badge type-<?php echo $tip['pet_type']; ?>">
                                <?php echo ucfirst($tip['pet_type']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Difficulty Level</div>
                        <div class="detail-value">
                            <span class="difficulty-badge difficulty-<?php echo $tip['difficulty_level']; ?>">
                                <?php echo ucfirst($tip['difficulty_level']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Estimated Duration</div>
                        <div class="detail-value"><?php echo htmlspecialchars($tip['estimated_duration']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Prerequisites</div>
                        <div class="detail-value">
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
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Created By</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($tip['first_name'] . ' ' . $tip['last_name']); ?>
                        </div>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <div class="detail-label">Content</div>
                        <div class="detail-value content-block">
                            <?php echo nl2br(htmlspecialchars($tip['content'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Training Tip</h2>
                <span class="close" data-modal="editModal">&times;</span>
            </div>
            <form id="editTipForm" method="POST">
                <input type="hidden" name="tip_id" value="<?php echo $tip_id; ?>">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($tip['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="pet_type">Pet Type</label>
                    <select id="pet_type" name="pet_type" required>
                        <option value="dog" <?php echo $tip['pet_type'] == 'dog' ? 'selected' : ''; ?>>Dog</option>
                        <option value="cat" <?php echo $tip['pet_type'] == 'cat' ? 'selected' : ''; ?>>Cat</option>
                        <option value="bird" <?php echo $tip['pet_type'] == 'bird' ? 'selected' : ''; ?>>Bird</option>
                        <option value="other" <?php echo $tip['pet_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="difficulty_level">Difficulty Level</label>
                    <select id="difficulty_level" name="difficulty_level" required>
                        <option value="beginner" <?php echo $tip['difficulty_level'] == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo $tip['difficulty_level'] == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo $tip['difficulty_level'] == 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estimated_duration">Estimated Duration</label>
                    <input type="text" id="estimated_duration" name="estimated_duration" 
                           value="<?php echo htmlspecialchars($tip['estimated_duration']); ?>" required>
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
                    <input type="hidden" id="prerequisites" name="prerequisites" 
                           value='<?php echo htmlspecialchars($tip['prerequisites']); ?>'>
                </div>

                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="6" required><?php echo htmlspecialchars($tip['content']); ?></textarea>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Training Tip</h2>
                <span class="close" data-modal="deleteModal">&times;</span>
            </div>
            <p>Are you sure you want to delete this training tip? This action cannot be undone.</p>
            <div class="modal-footer">
                <button onclick="confirmDelete()" class="btn btn-danger">Delete</button>
                <button onclick="closeModal('deleteModal')" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let prerequisites = <?php echo $tip['prerequisites'] ?: '[]'; ?>;

        function openEditModal() {
            document.getElementById('editModal').style.display = 'flex';
            updatePrerequisitesList();
        }

        function openDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
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

        document.getElementById('editTipForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('process-add-training-tip.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });

        function confirmDelete() {
            fetch('process-delete-training-tip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ tip_id: <?php echo $tip_id; ?> })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'training-tips.php';
                } else {
                    alert(data.message);
                }
            });
        }

        // Close button handlers
        document.querySelectorAll('.close').forEach(button => {
            button.onclick = function() {
                closeModal(this.getAttribute('data-modal'));
            }
        });

        // Click outside modal to close
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        }
    </script>
</body>
</html>
