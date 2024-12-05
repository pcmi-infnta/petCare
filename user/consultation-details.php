<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit();
}

$consultation_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$user_id = $_SESSION['user_id'];

// Fetch consultation details with ownership verification
$query = "
    SELECT mc.*, p.name as pet_name, p.pet_type, u.email as vet_email 
    FROM medical_consultations mc 
    JOIN pets p ON mc.pet_id = p.pet_id 
    LEFT JOIN users u ON mc.vet_id = u.user_id 
    WHERE mc.consultation_id = ? AND p.owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $consultation_id, $user_id);
$stmt->execute();
$consultation = $stmt->get_result()->fetch_assoc();

if (!$consultation) {
    header('Location: dashboard.php');
    exit();
}

// Fetch related vaccinations
$vacc_query = "
    SELECT * FROM vaccination_logs 
    WHERE consultation_id = ? 
    ORDER BY vaccination_date DESC";
$vacc_stmt = $conn->prepare($vacc_query);
$vacc_stmt->bind_param("i", $consultation_id);
$vacc_stmt->execute();
$vaccinations = $vacc_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Details - <?php echo htmlspecialchars($consultation['pet_name']); ?></title>
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
}

.consultation-header h1 {
    margin: 1rem 0;
    color: #1F2937;
}

.consultation-header p {
    color: #6B7280;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-weight: 500;
    font-size: 0.875rem;
}

.status-pending {
    background: #FEF3C7;
    color: #D97706;
}

.status-in_progress {
    background: #DBEAFE;
    color: #1D4ED8;
}

.status-completed {
    background: #D1FAE5;
    color: #059669;
}

.status-cancelled {
    background: #FEE2E2;
    color: #DC2626;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.detail-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.detail-card h3 {
    color: #4B5563;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #E5E7EB;
}

.detail-card p {
    color: #1F2937;
    line-height: 1.6;
}

.section {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-top: 2rem;
}

.section h2 {
    color: #1F2937;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #E5E7EB;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #E5E7EB;
}

th {
    color: #4B5563;
    font-weight: 500;
}

tr:hover {
    background: #F9FAFB;
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
    transform: translateY(-1px);
}

    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="consultation-header">
            <div class="status-badge status-<?php echo $consultation['status']; ?>">
                <?php echo ucfirst($consultation['status']); ?>
            </div>
            <h1><?php echo htmlspecialchars($consultation['pet_name']); ?>'s Consultation</h1>
            <p>Date: <?php echo date('F d, Y', strtotime($consultation['consultation_date'])); ?></p>
            <p>Veterinarian: <?php echo htmlspecialchars($consultation['vet_email'] ?? 'Not assigned'); ?></p>
        </div>

        <div class="details-grid">
            <div class="detail-card">
                <h3>Main Symptoms</h3>
                <p><?php echo nl2br(htmlspecialchars($consultation['main_symptoms'])); ?></p>
            </div>

            <div class="detail-card">
                <h3>Detailed Symptoms</h3>
                <p><?php echo nl2br(htmlspecialchars($consultation['symptoms_details'] ?? 'No details provided')); ?></p>
            </div>

            <?php if ($consultation['diagnosis']): ?>
            <div class="detail-card">
                <h3>Diagnosis</h3>
                <p><?php echo nl2br(htmlspecialchars($consultation['diagnosis'])); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($consultation['treatment_plan']): ?>
            <div class="detail-card">
                <h3>Treatment Plan</h3>
                <p><?php echo nl2br(htmlspecialchars($consultation['treatment_plan'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($vaccinations->num_rows > 0): ?>
        <div class="section">
            <h2>Related Vaccinations</h2>
            <table>
                <thead>
                    <tr>
                        <th>Vaccine</th>
                        <th>Date</th>
                        <th>Next Due Date</th>
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
                        <td>
                            <a href="vaccination-details.php?id=<?php echo $vaccination['vaccination_id']; ?>" class="btn btn-primary">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
