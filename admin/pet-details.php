<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../views/login.php');
    exit();
}

$pet_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Fetch pet details with owner information
$query = "
    SELECT p.*, u.email as owner_email, up.first_name, up.last_name
    FROM pets p 
    JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id 
    WHERE p.pet_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();

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
    <title>Pet Details - Admin Dashboard</title>
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
            grid-template-columns: auto 1fr;
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
            margin-top: 1rem;
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

        .status-pending {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-completed {
            background: #D1FAE5;
            color: #059669;
        }

        .status-in_progress {
            background: #DBEAFE;
            color: #2563EB;
        }

        .status-cancelled {
            background: #FEE2E2;
            color: #DC2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="pets.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Pets
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
                <p style="color: #6B7280;">Owner: <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?></p>
            </div>
        </div>

        <div class="section">
            <h2>Pet Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Type</label>
                    <span><?php echo ucfirst($pet['pet_type']); ?></span>
                </div>
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
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Veterinarian</th>
                        <th>Main Symptoms</th>
                        <th>Status</th>
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
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Vaccination Records</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vaccine</th>
                        <th>Date</th>
                        <th>Next Due Date</th>
                        <th>Batch Number</th>
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
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
