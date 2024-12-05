<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../views/login.php');
    exit();
}

// Fetch user's pets types for filtering
$user_id = $_SESSION['user_id'];
$pets_query = "SELECT DISTINCT pet_type FROM pets WHERE owner_id = ?";
$pets_stmt = $conn->prepare($pets_query);
$pets_stmt->bind_param("i", $user_id);
$pets_stmt->execute();
$user_pet_types = $pets_stmt->get_result();

// Fetch training tips with optional filtering
$selected_pet_type = isset($_GET['pet_type']) ? $_GET['pet_type'] : null;
$selected_difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : null;

$tips_query = "
    SELECT tt.*, u.email as author_email 
    FROM training_tips tt 
    LEFT JOIN users u ON tt.created_by = u.user_id
    WHERE 1=1 " . 
    ($selected_pet_type ? "AND tt.pet_type = ? " : "") . 
    ($selected_difficulty ? "AND tt.difficulty_level = ? " : "") . 
    "ORDER BY tt.created_at DESC";

$tips_stmt = $conn->prepare($tips_query);
if ($selected_pet_type && $selected_difficulty) {
    $tips_stmt->bind_param("ss", $selected_pet_type, $selected_difficulty);
} elseif ($selected_pet_type) {
    $tips_stmt->bind_param("s", $selected_pet_type);
} elseif ($selected_difficulty) {
    $tips_stmt->bind_param("s", $selected_difficulty);
}
$tips_stmt->execute();
$tips = $tips_stmt->get_result();

// Get unique difficulty levels for filtering
$difficulties_query = "SELECT DISTINCT difficulty_level FROM training_tips WHERE difficulty_level IS NOT NULL";
$difficulties = $conn->query($difficulties_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Tips - PetCare</title>
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
        }

        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .nav-link {
            display: flex;
            padding: 0.75rem 1rem;
            color: #4B5563;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-link:hover {
            background: #F3F4F6;
            color: #8B5CF6;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-button {
            padding: 0.75rem 1.5rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            text-decoration: none;
            color: #4B5563;
        }

        .filter-button:hover {
            border-color: #8B5CF6;
            color: #8B5CF6;
        }

        .filter-button.active {
            background: #8B5CF6;
            color: white;
            border-color: #8B5CF6;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .tip-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .tip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }

        .tip-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .tip-icon {
            font-size: 2.5rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }

        .tip-title {
            color: #1F2937;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .tip-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .tip-difficulty {
            padding: 0.25rem 0.75rem;
            background: #F3F4F6;
            color: #4B5563;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .tip-duration {
            color: #6B7280;
            font-size: 0.875rem;
        }

        .tip-content {
            color: #4B5563;
            line-height: 1.6;
            margin-bottom: 0.75rem;
        }

        .prerequisites {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E5E7EB;
        }

        .prerequisites h4 {
            color: #1F2937;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .prerequisites ul {
            list-style: none;
            color: #6B7280;
            font-size: 0.875rem;
        }

        .prerequisites li {
            margin-bottom: 0.25rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6B7280;
        }

        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
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
                <h1>Training Tips</h1>
            </div>

            <div class="filter-section">
                <a href="training-tips.php" class="filter-button <?php echo !$selected_pet_type && !$selected_difficulty ? 'active' : ''; ?>">
                    All Tips
                </a>
                <?php while($pet_type = $user_pet_types->fetch_assoc()): ?>
                    <a href="?pet_type=<?php echo $pet_type['pet_type']; ?>" 
                       class="filter-button <?php echo $selected_pet_type === $pet_type['pet_type'] ? 'active' : ''; ?>">
                        <?php echo ucfirst($pet_type['pet_type']); ?>
                    </a>
                <?php endwhile; ?>
                <?php while($difficulty = $difficulties->fetch_assoc()): ?>
                    <a href="?difficulty=<?php echo urlencode($difficulty['difficulty_level']); ?>" 
                       class="filter-button <?php echo $selected_difficulty === $difficulty['difficulty_level'] ? 'active' : ''; ?>">
                        <?php echo ucfirst($difficulty['difficulty_level']); ?>
                    </a>
                <?php endwhile; ?>
            </div>

            <div class="tips-grid">
                <?php if ($tips->num_rows > 0): ?>
                    <?php while($tip = $tips->fetch_assoc()): ?>
                        <div class="tip-card">
                            <div class="tip-header">
                                <div class="tip-icon">
                                    <?php
                                    echo match($tip['pet_type']) {
                                        'dog' => '🐕',
                                        'cat' => '🐱',
                                        'bird' => '🦜',
                                        'fish' => '🐠',
                                        'reptile' => '🦎',
                                        default => '🐾'
                                    };
                                    ?>
                                </div>
                                <h3 class="tip-title"><?php echo htmlspecialchars($tip['title']); ?></h3>
                            </div>
                            
                            <div class="tip-meta">
                                <?php if ($tip['difficulty_level']): ?>
                                    <span class="tip-difficulty"><?php echo htmlspecialchars($tip['difficulty_level']); ?></span>
                                <?php endif; ?>
                                <?php if ($tip['estimated_duration']): ?>
                                    <span class="tip-duration"><?php echo htmlspecialchars($tip['estimated_duration']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="tip-content">
                                <?php echo nl2br(htmlspecialchars($tip['content'])); ?>
                            </div>

                            <?php if ($tip['prerequisites']): ?>
                                <div class="prerequisites">
                                <h4>Prerequisites</h4>
                                    <ul>
                                        <?php foreach(json_decode($tip['prerequisites']) as $prerequisite): ?>
                                            <li>
                                                <i class="fas fa-check-circle" style="color: #8B5CF6; margin-right: 0.5rem;"></i>
                                                <?php echo htmlspecialchars($prerequisite); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap"></i>
                        <h2>No Training Tips Available</h2>
                        <p>No training tips found for the selected filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

