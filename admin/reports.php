<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get date ranges
$current_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

// Fetch statistics for reports
$stats = [
    'users' => [
        'total' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetch_row()[0],
        'new_this_month' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND DATE_FORMAT(created_at, '%Y-%m') = '$current_month'")->fetch_row()[0],
        'active_users' => $conn->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetch_row()[0]
    ],
    'pets' => [
        'total' => $conn->query("SELECT COUNT(*) FROM pets")->fetch_row()[0],
        'by_type' => $conn->query("SELECT pet_type, COUNT(*) as count FROM pets GROUP BY pet_type")->fetch_all(MYSQLI_ASSOC)
    ],
    'consultations' => [
        'total' => $conn->query("SELECT COUNT(*) FROM medical_consultations")->fetch_row()[0],
        'this_month' => $conn->query("SELECT COUNT(*) FROM medical_consultations WHERE DATE_FORMAT(consultation_date, '%Y-%m') = '$current_month'")->fetch_row()[0],
        'by_status' => $conn->query("SELECT status, COUNT(*) as count FROM medical_consultations GROUP BY status")->fetch_all(MYSQLI_ASSOC)
    ],
    'vaccinations' => [
        'total' => $conn->query("SELECT COUNT(*) FROM vaccination_logs")->fetch_row()[0],
        'this_month' => $conn->query("SELECT COUNT(*) FROM vaccination_logs WHERE DATE_FORMAT(vaccination_date, '%Y-%m') = '$current_month'")->fetch_row()[0]
    ],
    'community' => [
        'total_posts' => $conn->query("SELECT COUNT(*) FROM community_posts")->fetch_row()[0],
        'total_comments' => $conn->query("SELECT COUNT(*) FROM community_comments")->fetch_row()[0],
        'active_posts' => $conn->query("SELECT COUNT(*) FROM community_posts WHERE is_archived = 0")->fetch_row()[0]
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
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
    position: fixed;
    width: 250px;
    height: 100vh;
    overflow-y: auto;
    z-index: 1000;
}


.main-content {
    margin-left: 250px;
    padding: 2rem;
    width: 430%;
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

.report-section {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.chart-container {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-top: 1rem;
}

.print-button {
    background: #8B5CF6;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    transition: background-color 0.2s;
}

.print-button:hover {
    background: #7C3AED;
    transform: translateY(-1px);
}

.data-table {
    width: 100%;
    margin-top: 1rem;
    border-collapse: collapse;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table th, .data-table td {
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
}

.data-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.data-table tr:hover {
    background-color: #f9fafb;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.date-range {
    color: #6B7280;
    font-size: 0.875rem;
    font-weight: 500;
}

@media print {
    .sidebar, .print-button {
        display: none;
    }
    
    .dashboard {
        display: block;
    }
    
    .main-content {
        padding: 0;
    }
    
    .report-section {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #e5e7eb;
    }
    
    .data-table {
        box-shadow: none;
    }
    
    body {
        background: white;
    }
}

@media (max-width: 768px) {
    .dashboard {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        display: none;
    }
    
    .report-grid {
        grid-template-columns: 1fr;
    }
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
            <div class="report-header">
                <h1 style="color: #1F2937;">System Reports</h1>
                <button onclick="window.print()" class="print-button">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>

            <div class="report-section">
                <h2>User Statistics</h2>
                <div class="report-grid">
                    <table class="data-table">
                        <tr>
                            <th>Metric</th>
                            <th>Count</th>
                        </tr>
                        <tr>
                            <td>Total Users</td>
                            <td><?php echo $stats['users']['total']; ?></td>
                        </tr>
                        <tr>
                            <td>New Users This Month</td>
                            <td><?php echo $stats['users']['new_this_month']; ?></td>
                        </tr>
                        <tr>
                            <td>Active Users</td>
                            <td><?php echo $stats['users']['active_users']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="report-section">
                <h2>Pet Distribution</h2>
                <div class="report-grid">
                    <table class="data-table">
                        <tr>
                            <th>Pet Type</th>
                            <th>Count</th>
                        </tr>
                        <?php foreach ($stats['pets']['by_type'] as $pet_stat): ?>
                        <tr>
                            <td><?php echo ucfirst($pet_stat['pet_type']); ?></td>
                            <td><?php echo $pet_stat['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="report-section">
                <h2>Medical Consultations Overview</h2>
                <div class="report-grid">
                    <table class="data-table">
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                        <?php foreach ($stats['consultations']['by_status'] as $cons_stat): ?>
                        <tr>
                            <td><?php echo ucfirst($cons_stat['status']); ?></td>
                            <td><?php echo $cons_stat['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="report-section">
                <h2>Community Engagement</h2>
                <div class="report-grid">
                    <table class="data-table">
                        <tr>
                            <th>Metric</th>
                            <th>Count</th>
                        </tr>
                        <tr>
                            <td>Total Posts</td>
                            <td><?php echo $stats['community']['total_posts']; ?></td>
                        </tr>
                        <tr>
                            <td>Total Comments</td>
                            <td><?php echo $stats['community']['total_comments']; ?></td>
                        </tr>
                        <tr>
                            <td>Active Posts</td>
                            <td><?php echo $stats['community']['active_posts']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="report-section">
                <h2>Vaccination Records</h2>
                <div class="report-grid">
                    <table class="data-table">
                        <tr>
                            <th>Period</th>
                            <th>Count</th>
                        </tr>
                        <tr>
                            <td>Total Vaccinations</td>
                            <td><?php echo $stats['vaccinations']['total']; ?></td>
                        </tr>
                        <tr>
                            <td>This Month</td>
                            <td><?php echo $stats['vaccinations']['this_month']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
