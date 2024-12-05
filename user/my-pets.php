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

// Fetch all user's pets
$pets_query = "SELECT * FROM pets WHERE owner_id = ? ORDER BY created_at DESC";
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
    <title>My Pets - PetCare</title>
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

        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .pet-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .pet-card:hover {
            transform: translateY(-5px);
        }

        .pet-avatar {
            width: 80px;
            height: 80px;
            background: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 1rem;
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

        .pet-info {
            margin-top: 1rem;
        }

        .pet-info p {
            color: #6B7280;
            margin: 0.25rem 0;
        }

        .pet-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
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
            <div class="header">
                <h1>My Pets</h1>
                <a href="#" class="btn btn-primary" onclick="openModal('addPetModal')">
                    <i class="fas fa-plus"></i> Add New Pet
                </a>
            </div>

            <?php if ($pets->num_rows > 0): ?>
            <div class="pets-grid">
                <?php while($pet = $pets->fetch_assoc()): ?>
                    <div class="pet-card">
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
                        <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                        <div class="pet-info">
                            <p><i class="fas fa-paw"></i> <?php echo ucfirst($pet['pet_type']); ?></p>
                            <p><i class="fas fa-birthday-cake"></i> <?php echo $pet['age_years']; ?> years <?php echo $pet['age_months']; ?> months</p>
                            <p><i class="fas fa-venus-mars"></i> <?php echo ucfirst($pet['gender']); ?></p>
                            <?php if ($pet['breed']): ?>
                                <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($pet['breed']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="pet-actions">
                            <a href="pet-details.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-paw"></i>
                <h2>No Pets Yet</h2>
                <p>Add your first pet to get started!</p>
                <a href="#" class="btn btn-primary" style="margin-top: 1rem;" onclick="openModal('addPetModal')">
                    <i class="fas fa-plus"></i> Add New Pet
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Include the Add Pet Modal here -->
    <?php include '../components/add-pet-modal.php'; ?>
</body>
</html>
