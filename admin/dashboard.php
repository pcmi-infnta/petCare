<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetch_row()[0],
    'total_pets' => $conn->query("SELECT COUNT(*) FROM pets")->fetch_row()[0],
    'total_consultations' => $conn->query("SELECT COUNT(*) FROM medical_consultations")->fetch_row()[0],
    'pending_consultations' => $conn->query("SELECT COUNT(*) FROM medical_consultations WHERE status = 'pending'")->fetch_row()[0]
];

// Fetch recent users
$recent_users = $conn->query("
    SELECT u.*, up.first_name, up.last_name 
    FROM users u 
    LEFT JOIN user_profiles up ON u.user_id = up.user_id 
    ORDER BY u.created_at DESC LIMIT 5
");

// Fetch recent pets
$recent_pets = $conn->query("
    SELECT p.*, u.email as owner_email 
    FROM pets p 
    JOIN users u ON p.owner_id = u.user_id 
    ORDER BY p.created_at DESC LIMIT 5
");

// Fetch recent consultations
$recent_consultations = $conn->query("
    SELECT mc.*, p.name as pet_name, u.email as owner_email
    FROM medical_consultations mc
    JOIN pets p ON mc.pet_id = p.pet_id
    JOIN users u ON p.owner_id = u.user_id
    ORDER BY mc.created_at DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Admin Dashboard - PetCare</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .recent-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .data-table th, .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table tr:hover {
            background-color: #f9fafb;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            background: #8B5CF6;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background: #7C3AED;
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

        .chart-container {
            margin-top: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
            <h1 style="margin-bottom: 2rem; color: #1F2937;">Dashboard Overview</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p style="font-size: 2rem; color: #8B5CF6;"><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Pets</h3>
                    <p style="font-size: 2rem; color: #8B5CF6;"><?php echo $stats['total_pets']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Consultations</h3>
                    <p style="font-size: 2rem; color: #8B5CF6;"><?php echo $stats['total_consultations']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Consultations</h3>
                    <p style="font-size: 2rem; color: #DC2626;"><?php echo $stats['pending_consultations']; ?></p>
                </div>
            </div>

            <div class="recent-section">
                <h2>Recent Users</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="user-details.php?id=<?php echo $user['user_id']; ?>" class="btn">View Details</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="recent-section">
                <h2>Recent Consultations</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pet Name</th>
                            <th>Owner</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($consultation = $recent_consultations->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($consultation['pet_name']); ?></td>
                            <td><?php echo htmlspecialchars($consultation['owner_email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($consultation['consultation_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $consultation['status']; ?>">
                                    <?php echo ucfirst($consultation['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="consultation-details.php?id=<?php echo $consultation['consultation_id']; ?>" class="btn">Manage</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
