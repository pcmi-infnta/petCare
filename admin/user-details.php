<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get user ID from URL
$user_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Fetch user details with profile
$query = "
    SELECT u.*, up.* 
    FROM users u 
    LEFT JOIN user_profiles up ON u.user_id = up.user_id 
    WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch user's pets
$pets_query = "
    SELECT * FROM pets 
    WHERE owner_id = ? 
    ORDER BY created_at DESC";
$pets_stmt = $conn->prepare($pets_query);
$pets_stmt->bind_param("i", $user_id);
$pets_stmt->execute();
$pets = $pets_stmt->get_result();

// Fetch user's consultations
$consultations_query = "
    SELECT mc.*, p.name as pet_name 
    FROM medical_consultations mc 
    JOIN pets p ON mc.pet_id = p.pet_id 
    WHERE p.owner_id = ? 
    ORDER BY mc.consultation_date DESC";
$consultations_stmt = $conn->prepare($consultations_query);
$consultations_stmt->bind_param("i", $user_id);
$consultations_stmt->execute();
$consultations = $consultations_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Admin Dashboard</title>
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
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6B7280;
            text-decoration: none;
            margin-bottom: 1rem;
        }

        .user-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 2rem;
            align-items: center;
        }

        .avatar {
            width: 100px;
            height: 100px;
            background: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .user-info h1 {
            color: #1F2937;
            margin-bottom: 0.5rem;
        }

        .user-actions {
            display: flex;
            gap: 1rem;
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

        .btn-danger {
            background: #DC2626;
            color: white;
        }

        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .section h2 {
            color: #1F2937;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #E5E7EB;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 5px;
        }

        .info-item label {
            color: #6B7280;
            font-size: 0.875rem;
            display: block;
            margin-bottom: 0.25rem;
        }

        .info-item span {
            color: #1F2937;
            font-weight: 500;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }

        .data-table tr:hover {
            background: #F9FAFB;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background: #D1FAE5;
            color: #059669;
        }

        .status-inactive {
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
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.active {
    opacity: 1;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(20px);
    transition: transform 0.3s ease;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal.active .modal-content {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #E5E7EB;
}

.modal-header h2 {
    color: #1F2937;
    font-size: 1.5rem;
    font-weight: 600;
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
.form-group select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s;
    background: #F9FAFB;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #8B5CF6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    outline: none;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
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
    <div class="container">
        <a href="dashboard.php" class="back-button">← Back to Dashboard</a>

        <div class="user-header">
            <div class="avatar">
                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
            <div class="user-actions">
                <button class="btn btn-primary" onclick="openModal('editModal')">
                    <i class="fas fa-edit"></i> Edit User
                </button>
                <button class="btn btn-danger" onclick="openModal('deleteModal')">
                    <i class="fas fa-trash-alt"></i> Delete User
                </button>
            </div>

        </div>

        <div class="section">
            <h2>Medical Consultations (<?php echo $consultations->num_rows; ?>)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pet</th>
                        <th>Main Symptoms</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($consultation = $consultations->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($consultation['consultation_date'])); ?></td>
                        <td><?php echo htmlspecialchars($consultation['pet_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($consultation['main_symptoms'], 0, 50)) . '...'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $consultation['status']; ?>">
                                <?php echo ucfirst($consultation['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="consultation-details.php?id=<?php echo $consultation['consultation_id']; ?>" class="btn btn-primary">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Replace the existing modals with these updated versions -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
            <span class="close" data-modal="editModal">&times;</span>
        </div>
        <form id="editUserForm" method="POST" action="edit-user.php">
            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete User</h2>
            <span class="close" data-modal="deleteModal">&times;</span>
        </div>
        <div style="padding: 1rem 0;">
            <p>Are you sure you want to delete this user? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button onclick="deleteUser(<?php echo $user['user_id']; ?>)" class="btn btn-danger">Delete</button>
            <button class="btn btn-secondary close-modal">Cancel</button>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';
    requestAnimationFrame(() => {
        modal.classList.add('active');
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

document.querySelectorAll('.close, .close-modal').forEach(button => {
    button.addEventListener('click', function() {
        const modalId = this.closest('.modal').id;
        closeModal(modalId);
    });
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
}

document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('process-edit-user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('editModal');
            location.reload();
        } else {
            alert(data.message);
        }
    });
});

function deleteUser(userId) {
    fetch('process-delete-user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'users.php';
        } else {
            alert(data.message);
        }
    });
}
</script>

</body>
</html>
