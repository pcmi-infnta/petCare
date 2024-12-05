<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit();
}

// Get pet ID and validate ownership
$pet_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$user_id = $_SESSION['user_id'];

// Fetch pet details with owner verification
$pet_query = "
    SELECT p.*, u.email as owner_email 
    FROM pets p 
    JOIN users u ON p.owner_id = u.user_id 
    WHERE p.pet_id = ? AND p.owner_id = ?";
$stmt = $conn->prepare($pet_query);
$stmt->bind_param("ii", $pet_id, $user_id);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();

if (!$pet) {
    header('Location: dashboard.php');
    exit();
}

// Fetch pet's consultations
$consultations_query = "
    SELECT mc.*, u.email as vet_email 
    FROM medical_consultations mc 
    LEFT JOIN users u ON mc.vet_id = u.user_id 
    WHERE mc.pet_id = ? 
    ORDER BY mc.consultation_date DESC";
$cons_stmt = $conn->prepare($consultations_query);
$cons_stmt->bind_param("i", $pet_id);
$cons_stmt->execute();
$consultations = $cons_stmt->get_result();

// Fetch pet's vaccinations
$vaccinations_query = "
    SELECT * FROM vaccination_logs 
    WHERE pet_id = ? 
    ORDER BY vaccination_date DESC";
$vacc_stmt = $conn->prepare($vaccinations_query);
$vacc_stmt->bind_param("i", $pet_id);
$vacc_stmt->execute();
$vaccinations = $vacc_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Details - <?php echo htmlspecialchars($pet['name']); ?></title>
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
            transition: color 0.2s;
        }

        .back-button:hover {
            color: #4B5563;
        }

        .pet-header {
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

        .pet-avatar {
            width: 120px;
            height: 120px;
            background: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }

        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .section h2 {
            color: #1F2937;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #E5E7EB;
        }

        .info-grid {
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
            display: block;
            color: #6B7280;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
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
            transform: translateY(-1px);
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

        .status-completed {
            background: #D1FAE5;
            color: #059669;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }

        tr:hover {
            background: #F9FAFB;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        /* Add to existing CSS */
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

.btn {
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transform: translateY(0);
    transition: all 0.2s;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="pet-header">
            <div class="pet-avatar">
                <?php 
                $icon = match($pet['pet_type']) {
                    'dog' => '🐕',
                    'cat' => '🐱',
                    'bird' => '🦜',
                    'fish' => '🐠',
                    'reptile' => '🦎',
                    default => '🐾'
                };
                echo $icon;
                ?>
            </div>
            <div>
                <h1><?php echo htmlspecialchars($pet['name']); ?></h1>
                <p style="color: #6B7280;"><?php echo ucfirst($pet['pet_type']); ?></p>
            </div>
            <div class="action-buttons">
                <a href="#" class="btn btn-primary" onclick="openModal('editPetModal')">
                    <i class="fas fa-edit"></i> Edit Pet
                </a>
                <a href="#" class="btn btn-primary" onclick="openModal('scheduleConsultationModal')">
                    <i class="fas fa-calendar-plus"></i> Schedule Consultation
                </a>
            </div>

        </div>

        <div class="section">
            <h2>Pet Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Breed</label>
                    <span><?php echo htmlspecialchars($pet['breed'] ?: 'Not specified'); ?></span>
                </div>
                <div class="info-item">
                    <label>Age</label>
                    <span><?php echo $pet['age_years']; ?> years <?php echo $pet['age_months']; ?> months</span>
                </div>
                <div class="info-item">
                    <label>Gender</label>
                    <span><?php echo ucfirst($pet['gender']); ?></span>
                </div>
                <div class="info-item">
                    <label>Weight</label>
                    <span><?php echo $pet['weight_kg']; ?> kg</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Medical History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Veterinarian</th>
                        <th>Main Symptoms</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($consultation = $consultations->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($consultation['consultation_date'])); ?></td>
                        <td><?php echo htmlspecialchars($consultation['vet_email'] ?: 'Not assigned'); ?></td>
                        <td><?php echo htmlspecialchars(substr($consultation['main_symptoms'], 0, 100)) . '...'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $consultation['status']; ?>">
                                <?php echo ucfirst($consultation['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="consultation-details.php?id=<?php echo $consultation['consultation_id']; ?>" class="btn btn-primary">
                                View Details
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Vaccination Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Vaccine</th>
                        <th>Date</th>
                        <th>Next Due Date</th>
                        <th>Batch Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($vaccination = $vaccinations->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($vaccination['vaccination_date'])); ?></td>
                        <td>
                            <?php if ($vaccination['next_due_date']): ?>
                                <?php echo date('M d, Y', strtotime($vaccination['next_due_date'])); ?>
                            <?php else: ?>
                                Not scheduled
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($vaccination['batch_number']); ?></td>
                        <td>
                            <a href="vaccination-details.php?id=<?php echo $vaccination['vaccination_id']; ?>" class="btn btn-primary">
                                View Details
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit Pet Modal -->
<div id="editPetModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Pet Details</h2>
            <span class="close" data-modal="editPetModal">&times;</span>
        </div>
        <form id="editPetForm" method="POST" action="process-edit-pet.php">
            <input type="hidden" name="pet_id" value="<?php echo $pet['pet_id']; ?>">
            <div class="form-group">
                <label for="pet_name">Pet Name</label>
                <input type="text" id="pet_name" name="name" value="<?php echo htmlspecialchars($pet['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="pet_type">Pet Type</label>
                <select id="pet_type" name="pet_type" required>
                    <?php
                    $pet_types = ['dog', 'cat', 'bird', 'fish', 'small_animal', 'reptile', 'other'];
                    foreach ($pet_types as $type) {
                        $selected = ($type === $pet['pet_type']) ? 'selected' : '';
                        echo "<option value=\"$type\" $selected>" . ucfirst($type) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="breed">Breed</label>
                <input type="text" id="breed" name="breed" value="<?php echo htmlspecialchars($pet['breed']); ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="age_years">Age (Years)</label>
                    <input type="number" id="age_years" name="age_years" min="0" max="30" value="<?php echo $pet['age_years']; ?>">
                </div>
                <div class="form-group">
                    <label for="age_months">Age (Months)</label>
                    <input type="number" id="age_months" name="age_months" min="0" max="11" value="<?php echo $pet['age_months']; ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="male" <?php echo $pet['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo $pet['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                    <option value="unknown" <?php echo $pet['gender'] === 'unknown' ? 'selected' : ''; ?>>Unknown</option>
                </select>
            </div>
            <div class="form-group">
                <label for="weight">Weight (kg)</label>
                <input type="number" id="weight" name="weight_kg" step="0.01" value="<?php echo $pet['weight_kg']; ?>">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Schedule Consultation Modal -->
<div id="scheduleConsultationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Schedule Consultation</h2>
            <span class="close" data-modal="scheduleConsultationModal">&times;</span>
        </div>
        <form id="scheduleConsultationForm" method="POST" action="process-consultation.php">
            <input type="hidden" name="pet_id" value="<?php echo $pet['pet_id']; ?>">
            <div class="form-group">
                <label for="consultation_date">Consultation Date</label>
                <input type="datetime-local" id="consultation_date" name="consultation_date" required>
            </div>
            <div class="form-group">
                <label for="main_symptoms">Main Symptoms</label>
                <textarea id="main_symptoms" name="main_symptoms" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="symptoms_details">Additional Details</label>
                <textarea id="symptoms_details" name="symptoms_details" rows="4"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Schedule Consultation</button>
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

// Handle form submissions with AJAX
document.getElementById('editPetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('process-edit-pet.php', {
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

document.getElementById('scheduleConsultationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('process-consultation.php', {
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
</script>

</body>
</html>
