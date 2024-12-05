<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../views/login.php');
    exit();
}

$consultation_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Fetch consultation details with related information
$query = "
    SELECT mc.*, 
           p.name as pet_name, p.pet_type, p.breed,
           u.email as owner_email,
           up.first_name as owner_first_name, up.last_name as owner_last_name,
           v.email as vet_email,
           vp.first_name as vet_first_name, vp.last_name as vet_last_name
    FROM medical_consultations mc
    JOIN pets p ON mc.pet_id = p.pet_id
    JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    LEFT JOIN users v ON mc.vet_id = v.user_id
    LEFT JOIN user_profiles vp ON v.user_id = vp.user_id
    WHERE mc.consultation_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $consultation_id);
$stmt->execute();
$consultation = $stmt->get_result()->fetch_assoc();

// Fetch all vets for assignment
$vets_query = "SELECT u.user_id, u.email, up.first_name, up.last_name 
               FROM users u 
               LEFT JOIN user_profiles up ON u.user_id = up.user_id 
               WHERE u.role = 'admin'";
$vets = $conn->query($vets_query);

// Handle form submission for updating consultation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $vet_id = filter_var($_POST['vet_id'], FILTER_SANITIZE_NUMBER_INT);
    $diagnosis = filter_var($_POST['diagnosis'], FILTER_SANITIZE_STRING);
    $treatment_plan = filter_var($_POST['treatment_plan'], FILTER_SANITIZE_STRING);
    $follow_up_date = $_POST['follow_up_date'] ? $_POST['follow_up_date'] : null;

    $update_query = "
    UPDATE medical_consultations 
    SET status = ?, vet_id = ?, diagnosis = ?, 
        treatment_plan = ?, follow_up_date = ?
    WHERE consultation_id = ?";

$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("sisssi", 
    $status, 
    $vet_id, 
    $diagnosis, 
    $treatment_plan, 
    $follow_up_date, 
    $consultation_id
);


    if ($update_stmt->execute()) {
        header("Location: consultation-details.php?id=" . $consultation_id . "&success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Details - Admin Dashboard</title>
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

        .consultation-header {
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4B5563;
            font-weight: 500;
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
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
            transform: translateY(-1px);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-in_progress {
            background: #DBEAFE;
            color: #2563EB;
        }

        .status-completed {
            background: #D1FAE5;
            color: #059669;
        }

        .status-cancelled {
            background: #FEE2E2;
            color: #DC2626;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 8px;
        }

        .info-item label {
            color: #6B7280;
            font-size: 0.875rem;
        }

        .info-item span {
            display: block;
            color: #1F2937;
            font-weight: 500;
            margin-top: 0.25rem;
        }
        .alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: slideIn 0.3s ease-out;
}

.alert-success {
    background: #D1FAE5;
    color: #059669;
    border-left: 4px solid #059669;
}

.close-alert {
    margin-left: auto;
    background: none;
    border: none;
    color: currentColor;
    cursor: pointer;
    padding: 0.25rem;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <a href="consultations.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Consultations
        </a>

        <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" id="successAlert">
        <i class="fas fa-check-circle"></i>
        <span>Consultation updated successfully!</span>
        <button onclick="closeAlert()" class="close-alert">
            <i class="fas fa-times"></i>
        </button>
    </div>
<?php endif; ?>

        <div class="consultation-header">
            <div>
                <h1 style="margin-bottom: 0.5rem;">Consultation Details</h1>
                <p style="color: #6B7280;">ID: #<?php echo $consultation['consultation_id']; ?></p>
            </div>
            <div>
                <span class="status-badge status-<?php echo $consultation['status']; ?>">
                    <?php echo ucfirst($consultation['status']); ?>
                </span>
            </div>
        </div>

        <div class="section">
            <h2>Pet & Owner Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Pet Name</label>
                    <span><?php echo htmlspecialchars($consultation['pet_name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Pet Type</label>
                    <span><?php echo ucfirst($consultation['pet_type']); ?></span>
                </div>
                <div class="info-item">
                    <label>Breed</label>
                    <span><?php echo htmlspecialchars($consultation['breed']); ?></span>
                </div>
                <div class="info-item">
                    <label>Owner</label>
                    <span><?php echo htmlspecialchars($consultation['owner_first_name'] . ' ' . $consultation['owner_last_name']); ?></span>
                </div>
            </div>
        </div>

        <form method="POST" class="section">
            <h2>Consultation Management</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="pending" <?php echo $consultation['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $consultation['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $consultation['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $consultation['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="vet_id">Assign Veterinarian</label>
                    <select id="vet_id" name="vet_id">
                        <option value="">Select Veterinarian</option>
                        <?php while($vet = $vets->fetch_assoc()): ?>
                            <option value="<?php echo $vet['user_id']; ?>" 
                                    <?php echo $consultation['vet_id'] == $vet['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name'] . ' (' . $vet['email'] . ')'); ?>
                            </option>
                            <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="main_symptoms">Main Symptoms</label>
                    <textarea id="main_symptoms" readonly><?php echo htmlspecialchars($consultation['main_symptoms']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="symptoms_details">Detailed Symptoms</label>
                    <textarea id="symptoms_details" readonly><?php echo htmlspecialchars($consultation['symptoms_details']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="diagnosis">Diagnosis</label>
                    <textarea id="diagnosis" name="diagnosis"><?php echo htmlspecialchars($consultation['diagnosis'] ?? ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="treatment_plan">Treatment Plan</label>
                    <textarea id="treatment_plan" name="treatment_plan"><?php echo htmlspecialchars($consultation['treatment_plan'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="follow_up_date">Follow-up Date</label>
                    <input type="date" id="follow_up_date" name="follow_up_date" 
                           value="<?php echo $consultation['follow_up_date'] ? date('Y-m-d', strtotime($consultation['follow_up_date'])) : ''; ?>">
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Update Consultation</button>
            </div>
        </form>

        <div class="section">
            <h2>Consultation Timeline</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-date">
                        <?php echo date('M d, Y H:i', strtotime($consultation['created_at'])); ?>
                    </div>
                    <div class="timeline-content">
                        <strong>Consultation Created</strong>
                        <p>Consultation request submitted by <?php echo htmlspecialchars($consultation['owner_first_name'] . ' ' . $consultation['owner_last_name']); ?></p>
                    </div>
                </div>
                <?php if($consultation['vet_id']): ?>
                <div class="timeline-item">
                    <div class="timeline-date">
                        <?php echo date('M d, Y H:i', strtotime($consultation['updated_at'])); ?>
                    </div>
                    <div class="timeline-content">
                        <strong>Veterinarian Assigned</strong>
                        <p>Assigned to Dr. <?php echo htmlspecialchars($consultation['vet_first_name'] . ' ' . $consultation['vet_last_name']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
function closeAlert() {
    const alert = document.getElementById('successAlert');
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-20px)';
    setTimeout(() => alert.remove(), 300);
}

setTimeout(() => {
    const alert = document.getElementById('successAlert');
    if (alert) closeAlert();
}, 2000);
</script>
</body>
</html>
