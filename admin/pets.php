<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all pets with owner information
$pets_query = "
    SELECT p.*, u.email as owner_email, up.first_name, up.last_name,
    (SELECT COUNT(*) FROM medical_consultations WHERE pet_id = p.pet_id) as consultation_count
    FROM pets p 
    JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id 
    ORDER BY p.created_at DESC";
$pets = $conn->query($pets_query);

// Get statistics
$stats = [
    'total_pets' => $conn->query("SELECT COUNT(*) FROM pets")->fetch_row()[0],
    'total_dogs' => $conn->query("SELECT COUNT(*) FROM pets WHERE pet_type = 'dog'")->fetch_row()[0],
    'total_cats' => $conn->query("SELECT COUNT(*) FROM pets WHERE pet_type = 'cat'")->fetch_row()[0],
    'other_pets' => $conn->query("SELECT COUNT(*) FROM pets WHERE pet_type NOT IN ('dog', 'cat')")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Pet Management - Admin Dashboard</title>
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

        .pets-section {
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

        .pet-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .type-dog {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .type-cat {
            background: #FCE7F3;
            color: #BE185D;
        }

        .type-other {
            background: #E5E7EB;
            color: #374151;
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
            <h1 style="margin-bottom: 2rem; color: #1F2937;">Pet Management</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Pets</h3>
                    <p style="font-size: 2rem; color: #8B5CF6;"><?php echo $stats['total_pets']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Dogs</h3>
                    <p style="font-size: 2rem; color: #1D4ED8;"><?php echo $stats['total_dogs']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Cats</h3>
                    <p style="font-size: 2rem; color: #BE185D;"><?php echo $stats['total_cats']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Other Pets</h3>
                    <p style="font-size: 2rem; color: #374151;"><?php echo $stats['other_pets']; ?></p>
                </div>
            </div>

            <div class="pets-section">
                <div class="section-header">
                    <h2>All Pets</h2>
                </div>

                <div class="filters">
                    <select class="filter-select" id="petTypeFilter">
                        <option value="">All Types</option>
                        <option value="dog">Dogs</option>
                        <option value="cat">Cats</option>
                        <option value="bird">Birds</option>
                        <option value="fish">Fish</option>
                        <option value="other">Other</option>
                    </select>
                    <input type="text" class="search-input" placeholder="Search pets..." id="searchPets">
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Breed</th>
                            <th>Age</th>
                            <th>Owner</th>
                            <th>Consultations</th>
                            <th>Added Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($pet = $pets->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pet['name']); ?></td>
                            <td>
                                <span class="pet-type-badge type-<?php echo $pet['pet_type']; ?>">
                                    <?php echo ucfirst($pet['pet_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($pet['breed']); ?></td>
                            <td><?php echo $pet['age_years'] . ' years ' . $pet['age_months'] . ' months'; ?></td>
                            <td><?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?></td>
                            <td><?php echo $pet['consultation_count']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($pet['created_at'])); ?></td>
                            <td>
                                <a href="pet-details.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-primary">View</a>
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
        const searchInput = document.getElementById('searchPets');
        const typeFilter = document.getElementById('petTypeFilter');
        const tableRows = document.querySelectorAll('.data-table tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedType = typeFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const type = row.querySelector('.pet-type-badge').textContent.toLowerCase();
                const matchesSearch = text.includes(searchTerm);
                const matchesType = !selectedType || type.includes(selectedType);
                
                row.style.display = matchesSearch && matchesType ? '' : 'none';
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        typeFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
