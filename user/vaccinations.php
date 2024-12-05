<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../views/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all vaccinations for user's pets
$query = "
    SELECT vl.*, p.name as pet_name, p.pet_type, u.email as vet_email 
    FROM vaccination_logs vl 
    JOIN pets p ON vl.pet_id = p.pet_id 
    LEFT JOIN users u ON vl.administered_by = u.user_id 
    WHERE p.owner_id = ? 
    ORDER BY vl.vaccination_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$vaccinations = $stmt->get_result();

// Fetch upcoming vaccinations
$upcoming_query = "
    SELECT vl.*, p.name as pet_name 
    FROM vaccination_logs vl 
    JOIN pets p ON vl.pet_id = p.pet_id 
    WHERE p.owner_id = ? AND vl.next_due_date > CURRENT_DATE 
    ORDER BY vl.next_due_date ASC 
    LIMIT 5";
$upcoming_stmt = $conn->prepare($upcoming_query);
$upcoming_stmt->bind_param("i", $user_id);
$upcoming_stmt->execute();
$upcoming = $upcoming_stmt->get_result();

// Fetch user's pets for the add vaccination modal
$pets_query = "SELECT pet_id, name FROM pets WHERE owner_id = ?";
$pets_stmt = $conn->prepare($pets_query);
$pets_stmt->bind_param("i", $user_id);
$pets_stmt->execute();
$pets = $pets_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccinations - PetCare</title>
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

        .header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }

        tbody tr:hover {
            background: #F9FAFB;
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

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6B7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #8B5CF6;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
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

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Vaccination Records</h1>
                <a href="#" class="btn btn-primary" onclick="openModal('addVaccinationModal')">
                    <i class="fas fa-plus"></i> Add Vaccination Record
                </a>
            </div>

            <?php if ($upcoming->num_rows > 0): ?>
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
                        <?php while($vacc = $upcoming->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vacc['pet_name']); ?></td>
                            <td><?php echo htmlspecialchars($vacc['vaccine_name']); ?></td>
                            <td>
                                <span class="status-badge status-upcoming">
                                    <?php echo date('M d, Y', strtotime($vacc['next_due_date'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="vaccination-details.php?id=<?php echo $vacc['vaccination_id']; ?>" class="btn btn-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="section">
                <h2>All Vaccination Records</h2>
                <?php if ($vaccinations->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Vaccine</th>
                            <th>Date</th>
                            <th>Administered By</th>
                            <th>Next Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($vaccination = $vaccinations->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vaccination['pet_name']); ?></td>
                            <td><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($vaccination['vaccination_date'])); ?></td>
                            <td><?php echo htmlspecialchars($vaccination['vet_email'] ?? 'Not specified'); ?></td>
                            <td>
                                <?php if ($vaccination['next_due_date']): ?>
                                    <span class="status-badge status-upcoming">
                                        <?php echo date('M d, Y', strtotime($vaccination['next_due_date'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span>Not scheduled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="vaccination-details.php?id=<?php echo $vaccination['vaccination_id']; ?>" class="btn btn-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-syringe"></i>
                    <h2>No Vaccination Records</h2>
                    <p>Start tracking your pet's vaccinations by adding a record.</p>
                    <a href="#" class="btn btn-primary" style="margin-top: 1rem;" onclick="openModal('addVaccinationModal')">
                        <i class="fas fa-plus"></i> Add Vaccination Record
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../components/add-vaccination-modal.php'; ?>
</body>
</html>
