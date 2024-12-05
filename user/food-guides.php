<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

// Fetch user's pets for filtering
$user_id = $_SESSION['user_id'];
$pets_query = "SELECT DISTINCT pet_type FROM pets WHERE owner_id = ?";
$stmt = $conn->prepare($pets_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_pets = $stmt->get_result();

// Fetch all food guides
$guides_query = "
    SELECT fg.*, u.email as creator_email 
    FROM food_guides fg
    LEFT JOIN users u ON fg.created_by = u.user_id
    ORDER BY fg.created_at DESC";
$guides = $conn->query($guides_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Guides - PetCare</title>
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

        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 150px;
        }

        .guides-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .guide-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.2s;
        }

        .guide-card:hover {
            transform: translateY(-5px);
        }

        .guide-header {
            background: #8B5CF6;
            color: white;
            padding: 1rem;
        }

        .guide-content {
            padding: 1.5rem;
        }

        .guide-info {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .guide-label {
            font-weight: 600;
            color: #4B5563;
            margin-bottom: 0.25rem;
        }

        .guide-value {
            color: #1F2937;
        }

        .nutritional-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .nutrient {
            background: #F3F4F6;
            padding: 0.5rem;
            border-radius: 5px;
            text-align: center;
        }

        .special-instructions {
            background: #FEF3C7;
            color: #92400E;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
        }

        .pet-type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .type-dog { background: #DBEAFE; color: #1D4ED8; }
        .type-cat { background: #FCE7F3; color: #BE185D; }
        .type-bird { background: #FEF3C7; color: #D97706; }
        .type-other { background: #E5E7EB; color: #374151; }

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
            <h1 style="margin-bottom: 2rem; color: #1F2937;">Food Guides</h1>

            <div class="filters">
                <select class="filter-select" id="petTypeFilter">
                    <option value="">All Pet Types</option>
                    <?php while($pet = $user_pets->fetch_assoc()): ?>
                        <option value="<?php echo $pet['pet_type']; ?>">
                            <?php echo ucfirst($pet['pet_type']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select class="filter-select" id="ageRangeFilter">
                    <option value="">All Age Ranges</option>
                    <option value="puppy">Puppy/Kitten</option>
                    <option value="adult">Adult</option>
                    <option value="senior">Senior</option>
                </select>
            </div>

            <div class="guides-grid">
    <?php while($guide = $guides->fetch_assoc()): ?>
        <div class="guide-card" data-pet-type="<?php echo $guide['pet_type']; ?>">
            <div class="guide-header">
                <span class="pet-type-badge type-<?php echo $guide['pet_type']; ?>">
                    <?php echo ucfirst($guide['pet_type']); ?>
                </span>
                <h2><?php echo htmlspecialchars($guide['food_type']); ?></h2>
            </div>

            <div class="guide-content">
                <div class="guide-info">
                    <div class="guide-label">Age Range</div>
                    <div class="guide-value">
                        <?php 
                        echo $guide['age_range_start_months'] . ' - ' . 
                        ($guide['age_range_end_months'] ? $guide['age_range_end_months'] : '∞') . ' months'; 
                        ?>
                    </div>
                </div>

                <div class="guide-info">
                    <div class="guide-label">Weight Range</div>
                    <div class="guide-value">
                        <?php 
                        echo $guide['weight_range_start_kg'] . ' - ' . 
                        ($guide['weight_range_end_kg'] ? $guide['weight_range_end_kg'] : '∞') . ' kg'; 
                        ?>
                    </div>
                </div>

                <div class="guide-info">
                    <div class="guide-label">Portion Size</div>
                    <div class="guide-value"><?php echo $guide['portion_size_grams']; ?> grams</div>
                </div>

                <div class="guide-info">
                    <div class="guide-label">Meals Per Day</div>
                    <div class="guide-value"><?php echo $guide['meals_per_day']; ?></div>
                </div>

                <div class="nutritional-info">
                    <?php 
                    $nutritional_info = json_decode($guide['nutritional_info'], true);
                    if ($nutritional_info): 
                        foreach($nutritional_info as $nutrient => $value):
                    ?>
                        <div class="nutrient">
                            <div class="guide-label"><?php echo ucfirst($nutrient); ?></div>
                            <div class="guide-value"><?php echo $value; ?></div>
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>

                <?php if($guide['special_notes']): ?>
                    <div class="special-instructions">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo nl2br(htmlspecialchars($guide['special_notes'])); ?>
                    </div>
                <?php endif; ?>

                <div class="feeding-instructions">
                    <div class="guide-label">Feeding Instructions</div>
                    <div class="guide-value">
                        <?php echo nl2br(htmlspecialchars($guide['feeding_instructions'])); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>


    <script>
        const petTypeFilter = document.getElementById('petTypeFilter');
        const ageRangeFilter = document.getElementById('ageRangeFilter');
        const guideCards = document.querySelectorAll('.guide-card');

        function filterGuides() {
            const selectedPetType = petTypeFilter.value.toLowerCase();
            const selectedAgeRange = ageRangeFilter.value.toLowerCase();

            guideCards.forEach(card => {
                const petType = card.dataset.petType.toLowerCase();
                const ageRange = card.querySelector('.guide-value').textContent.toLowerCase();

                const matchesPetType = !selectedPetType || petType === selectedPetType;
                const matchesAgeRange = !selectedAgeRange || ageRange.includes(selectedAgeRange);

                card.style.display = matchesPetType && matchesAgeRange ? 'block' : 'none';
            });
        }

        petTypeFilter.addEventListener('change', filterGuides);
        ageRangeFilter.addEventListener('change', filterGuides);
    </script>
</body>
</html>
