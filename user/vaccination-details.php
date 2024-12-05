<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit();
}

$vaccination_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$user_id = $_SESSION['user_id'];

// Fetch vaccination details with ownership verification
$query = "
    SELECT vl.*, p.name as pet_name, p.pet_type, u.email as vet_email 
    FROM vaccination_logs vl 
    JOIN pets p ON vl.pet_id = p.pet_id 
    LEFT JOIN users u ON vl.administered_by = u.user_id 
    WHERE vl.vaccination_id = ? AND p.owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $vaccination_id, $user_id);
$stmt->execute();
$vaccination = $stmt->get_result()->fetch_assoc();

if (!$vaccination) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Details - <?php echo htmlspecialchars($vaccination['pet_name']); ?></title>
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

.vaccination-header {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.vaccination-header h1 {
    color: #1F2937;
    margin-bottom: 0.5rem;
}

.vaccination-header p {
    color: #6B7280;
    font-size: 1.1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.info-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
}

.info-card:hover {
    transform: translateY(-2px);
}

.info-card label {
    display: block;
    color: #4B5563;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.info-card .value {
    color: #1F2937;
    font-weight: 500;
    font-size: 1.1rem;
}

.notes-section {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-top: 1.5rem;
}

.notes-section h2 {
    color: #1F2937;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #E5E7EB;
}

.notes-section p {
    color: #4B5563;
    line-height: 1.6;
}

    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="vaccination-header">
            <h1><?php echo htmlspecialchars($vaccination['pet_name']); ?>'s Vaccination Record</h1>
            <p>Vaccine: <?php echo htmlspecialchars($vaccination['vaccine_name']); ?></p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <label>Vaccination Date</label>
                <div class="value"><?php echo date('F d, Y', strtotime($vaccination['vaccination_date'])); ?></div>
            </div>

            <div class="info-card">
                <label>Next Due Date</label>
                <div class="value">
                    <?php if ($vaccination['next_due_date']): ?>
                        <?php echo date('F d, Y', strtotime($vaccination['next_due_date'])); ?>
                    <?php else: ?>
                        Not scheduled
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-card">
                <label>Administered By</label>
                <div class="value"><?php echo htmlspecialchars($vaccination['vet_email'] ?? 'Not specified'); ?></div>
            </div>

            <div class="info-card">
                <label>Batch Number</label>
                <div class="value"><?php echo htmlspecialchars($vaccination['batch_number'] ?? 'Not specified'); ?></div>
            </div>
        </div>

        <?php if ($vaccination['notes']): ?>
        <div class="notes-section">
            <h2>Notes</h2>
            <p><?php echo nl2br(htmlspecialchars($vaccination['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
