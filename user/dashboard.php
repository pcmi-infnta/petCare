<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../views/login.php');
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$user_query = "
    SELECT u.*, up.* 
    FROM users u 
    LEFT JOIN user_profiles up ON u.user_id = up.user_id 
    WHERE u.user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch user's pets
$pets_query = "SELECT * FROM pets WHERE owner_id = ? ORDER BY created_at DESC";
$pets_stmt = $conn->prepare($pets_query);
$pets_stmt->bind_param("i", $user_id);
$pets_stmt->execute();
$pets = $pets_stmt->get_result();

// Fetch upcoming consultations
$consultations_query = "
    SELECT mc.*, p.name as pet_name 
    FROM medical_consultations mc 
    JOIN pets p ON mc.pet_id = p.pet_id 
    WHERE p.owner_id = ? AND mc.status != 'completed' 
    ORDER BY mc.consultation_date ASC 
    LIMIT 5";
$cons_stmt = $conn->prepare($consultations_query);
$cons_stmt->bind_param("i", $user_id);
$cons_stmt->execute();
$consultations = $cons_stmt->get_result();

// Fetch upcoming vaccinations
$vaccinations_query = "
    SELECT vl.*, p.name as pet_name 
    FROM vaccination_logs vl 
    JOIN pets p ON vl.pet_id = p.pet_id 
    WHERE p.owner_id = ? AND vl.next_due_date > CURRENT_DATE 
    ORDER BY vl.next_due_date ASC 
    LIMIT 5";
$vacc_stmt = $conn->prepare($vaccinations_query);
$vacc_stmt->bind_param("i", $user_id);
$vacc_stmt->execute();
$vaccinations = $vacc_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - PetCare</title>
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

        .user-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
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
            font-size: 2.5rem;
        }

        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .pet-card {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .pet-card:hover {
            transform: translateY(-5px);
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
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-upcoming {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb;
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
    gap: 1rem;
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
            <div class="user-header">
                <div class="avatar">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                </div>
                <div>
                    <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                    <p style="color: #6B7280;">Here's what's happening with your pets</p>
                </div>
            </div>

            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>My Pets</h2>
                    <a href="#" class="btn btn-primary" onclick="openModal('addPetModal')">
                        <i class="fas fa-plus"></i> Add New Pet
                    </a>
                </div>
                <div class="grid">
                    <?php while($pet = $pets->fetch_assoc()): ?>
                        <div class="pet-card">
                            <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                            <p style="color: #6B7280;"><?php echo ucfirst($pet['pet_type']); ?></p>
                            <p>Age: <?php echo $pet['age_years']; ?> years <?php echo $pet['age_months']; ?> months</p>
                            <a href="pet-details.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-primary" style="margin-top: 1rem;">
                                View Details
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="section">
                <h2>Upcoming Consultations</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($consultation = $consultations->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($consultation['pet_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($consultation['consultation_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $consultation['status']; ?>">
                                        <?php echo ucfirst($consultation['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="consultation-details.php?id=<?php echo $consultation['consultation_id']; ?>" class="btn btn-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>Upcoming Vaccinations</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Vaccine</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($vaccination = $vaccinations->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vaccination['pet_name']); ?></td>
                                <td><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></td>
                                <td>
                                    <span class="status-badge status-upcoming">
                                        <?php echo date('M d, Y', strtotime($vaccination['next_due_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="vaccination-details.php?id=<?php echo $vaccination['vaccination_id']; ?>" class="btn btn-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addPetModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Pet</h2>
            <span class="close" data-modal="addPetModal">&times;</span>
        </div>
        <form id="addPetForm" method="POST" action="process-add-pet.php">
            <div class="form-group">
                <label for="pet_name">Pet Name</label>
                <input type="text" id="pet_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="pet_type">Pet Type</label>
                <select id="pet_type" name="pet_type" required>
                    <option value="dog">Dog</option>
                    <option value="cat">Cat</option>
                    <option value="bird">Bird</option>
                    <option value="fish">Fish</option>
                    <option value="small_animal">Small Animal</option>
                    <option value="reptile">Reptile</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="breed">Breed</label>
                <input type="text" id="breed" name="breed">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="age_years">Age (Years)</label>
                    <input type="number" id="age_years" name="age_years" min="0" max="30">
                </div>
                <div class="form-group">
                    <label for="age_months">Age (Months)</label>
                    <input type="number" id="age_months" name="age_months" min="0" max="11">
                </div>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="unknown">Unknown</option>
                </select>
            </div>
            <div class="form-group">
                <label for="weight">Weight (kg)</label>
                <input type="number" id="weight" name="weight_kg" step="0.01">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Add Pet</button>
            </div>
        </form>
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

document.querySelectorAll('.close').forEach(button => {
    button.addEventListener('click', function() {
        const modalId = this.getAttribute('data-modal');
        closeModal(modalId);
    });
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
}

document.getElementById('addPetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('process-add-pet.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('addPetModal');
            location.reload();
        } else {
            alert(data.message);
        }
    });
});
</script>
</body>
</html>
