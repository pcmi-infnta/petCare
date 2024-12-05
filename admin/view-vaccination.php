<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get vaccination details
$vaccination_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$query = "
    SELECT vl.*, p.name as pet_name, p.pet_type,
    u.email as owner_email, up.first_name, up.last_name,
    admin.email as administrator_email,
    mc.consultation_date
    FROM vaccination_logs vl
    JOIN pets p ON vl.pet_id = p.pet_id
    JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    LEFT JOIN users admin ON vl.administered_by = admin.user_id
    LEFT JOIN medical_consultations mc ON vl.consultation_id = mc.consultation_id
    WHERE vl.vaccination_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $vaccination_id);
$stmt->execute();
$vaccination = $stmt->get_result()->fetch_assoc();

// Get all pets for edit modal
$pets_query = "SELECT pet_id, name FROM pets ORDER BY name";
$pets = $conn->query($pets_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Details - Admin Dashboard</title>
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

        .nav-link:hover {
            background: #F3F4F6;
            color: #8B5CF6;
            transform: translateX(5px);
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

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
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

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-upcoming {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .status-completed {
            background: #D1FAE5;
            color: #059669;
        }

        .status-overdue {
            background: #FEE2E2;
            color: #DC2626;
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

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6B7280;
            transition: color 0.2s;
        }

        .close:hover {
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
            background: #F9FAFB;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            outline: none;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #E5E7EB;
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
            <div class="details-container">
                <div class="details-header">
                    <h1>Vaccination Details</h1>
                    <div class="action-buttons">
                        <button onclick="openEditModal()" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="openDeleteModal()" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                        <a href="vaccinations.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Pet Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($vaccination['pet_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Owner</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($vaccination['first_name'] . ' ' . $vaccination['last_name']); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Vaccine Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Vaccination Date</div>
                        <div class="detail-value">
                            <?php echo date('F d, Y', strtotime($vaccination['vaccination_date'])); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Next Due Date</div>
                        <div class="detail-value">
                            <?php 
                            if ($vaccination['next_due_date']) {
                                $next_due = new DateTime($vaccination['next_due_date']);
                                $now = new DateTime();
                                $status = 'completed';
                                $statusClass = 'status-completed';
                                
                                if ($next_due < $now) {
                                    $status = 'overdue';
                                    $statusClass = 'status-overdue';
                                } elseif ($next_due->diff($now)->days <= 30) {
                                    $status = 'upcoming';
                                    $statusClass = 'status-upcoming';
                                }
                                
                                echo '<span class="status-indicator ' . $statusClass . '">';
                                echo date('F d, Y', strtotime($vaccination['next_due_date']));
                                echo ' (' . ucfirst($status) . ')';
                                echo '</span>';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Batch Number</div>
                        <div class="detail-value"><?php echo htmlspecialchars($vaccination['batch_number']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Administered By</div>
                        <div class="detail-value"><?php echo htmlspecialchars($vaccination['administrator_email']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Notes</div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($vaccination['notes'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Vaccination Record</h2>
            <form id="editVaccinationForm">
                <input type="hidden" name="vaccination_id" value="<?php echo $vaccination_id; ?>">
                
                <div class="form-group">
                    <label for="pet_id">Pet</label>
                    <select id="pet_id" name="pet_id" required>
                        <?php while($pet = $pets->fetch_assoc()): ?>
                            <option value="<?php echo $pet['pet_id']; ?>" 
                                <?php echo ($pet['pet_id'] == $vaccination['pet_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pet['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="vaccine_name">Vaccine Name</label>
                    <input type="text" id="vaccine_name" name="vaccine_name" 
                           value="<?php echo htmlspecialchars($vaccination['vaccine_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="vaccination_date">Vaccination Date</label>
                    <input type="date" id="vaccination_date" name="vaccination_date" 
                           value="<?php echo $vaccination['vaccination_date']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="next_due_date">Next Due Date</label>
                    <input type="date" id="next_due_date" name="next_due_date" 
                           value="<?php echo $vaccination['next_due_date']; ?>">
                </div>

                <div class="form-group">
                    <label for="batch_number">Batch Number</label>
                    <input type="text" id="batch_number" name="batch_number" 
                           value="<?php echo htmlspecialchars($vaccination['batch_number']); ?>">
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes"><?php echo htmlspecialchars($vaccination['notes']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Delete Vaccination Record</h2>
            <p>Are you sure you want to delete this vaccination record? This action cannot be undone.</p>
            <div class="modal-actions">
                <button onclick="confirmDelete()" class="btn btn-danger">Delete</button>
                <button onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        const closeButtons = document.getElementsByClassName('close');

        function openEditModal() {
            editModal.style.display = 'flex';
        }

        function openDeleteModal() {
            deleteModal.style.display = 'flex';
        }

        function closeModals() {
            editModal.style.display = 'none';
            deleteModal.style.display = 'none';
        }

        // Close button handlers
        Array.from(closeButtons).forEach(button => {
            button.onclick = closeModals;
        });

        // Click outside modal to close
        window.onclick = function(event) {
            if (event.target == editModal || event.target == deleteModal) {
                closeModals();
            }
        }

        // Edit form submission
        document.getElementById('editVaccinationForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('process-add-vaccination.php', {
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
        }

        // Delete confirmation
        function confirmDelete() {
            fetch('process-delete-vaccination.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    vaccination_id: <?php echo $vaccination_id; ?> 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'vaccinations.php';
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
