<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all consultations with related information
$consultations_query = "
    SELECT mc.*, p.name as pet_name, p.pet_type,
    u.email as owner_email, up.first_name, up.last_name,
    vet.email as vet_email
    FROM medical_consultations mc 
    JOIN pets p ON mc.pet_id = p.pet_id
    JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    LEFT JOIN users vet ON mc.vet_id = vet.user_id
    ORDER BY mc.consultation_date DESC";
$consultations = $conn->query($consultations_query);

// Get statistics
$stats = [
    'total_consultations' => $conn->query("SELECT COUNT(*) FROM medical_consultations")->fetch_row()[0],
    'pending_consultations' => $conn->query("SELECT COUNT(*) FROM medical_consultations WHERE status = 'pending'")->fetch_row()[0],
    'completed_consultations' => $conn->query("SELECT COUNT(*) FROM medical_consultations WHERE status = 'completed'")->fetch_row()[0],
    'today_consultations' => $conn->query("SELECT COUNT(*) FROM medical_consultations WHERE DATE(consultation_date) = CURDATE()")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Consultations Management - Admin Dashboard</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .consultations-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-select {
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 5px;
            font-size: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 5px;
            font-size: 1rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table tr:hover {
            background-color: #f9fafb;
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
            <h1 style="margin-bottom: 2rem; color: #1F2937;">Consultations Management</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Consultations</h3>
                    <p style="font-size: 2rem; color: #8B5CF6;"><?php echo $stats['total_consultations']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending</h3>
                    <p style="font-size: 2rem; color: #D97706;"><?php echo $stats['pending_consultations']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Completed</h3>
                    <p style="font-size: 2rem; color: #059669;"><?php echo $stats['completed_consultations']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Today's Consultations</h3>
                    <p style="font-size: 2rem; color: #1D4ED8;"><?php echo $stats['today_consultations']; ?></p>
                </div>
            </div>

            <div class="consultations-section">
                <div class="section-header">
                    <h2>All Consultations</h2>
                </div>

                <div class="filters">
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <input type="text" class="search-input" placeholder="Search consultations..." id="searchConsultations">
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Pet</th>
                            <th>Owner</th>
                            <th>Vet</th>
                            <th>Status</th>
                            <th>Follow-up</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($consultation = $consultations->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($consultation['consultation_date'])); ?></td>
                            <td><?php echo htmlspecialchars($consultation['pet_name']); ?></td>
                            <td><?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($consultation['vet_email'] ?? 'Not Assigned'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $consultation['status']; ?>">
                                    <?php echo ucfirst($consultation['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $consultation['follow_up_date'] ? date('M d, Y', strtotime($consultation['follow_up_date'])) : 'None'; ?></td>
                            <td>
                                <a href="consultation-details.php?id=<?php echo $consultation['consultation_id']; ?>" class="btn btn-primary">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Search and filter functionality
        const searchInput = document.getElementById('searchConsultations');
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('.data-table tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.querySelector('.status-badge').textContent.toLowerCase();
                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !selectedStatus || status.includes(selectedStatus);
                
                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
