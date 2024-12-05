<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $pet_type = filter_var($_POST['pet_type'], FILTER_SANITIZE_STRING);
    $age_range_start = filter_var($_POST['age_range_start_months'], FILTER_VALIDATE_INT);
    $age_range_end = !empty($_POST['age_range_end_months']) ? filter_var($_POST['age_range_end_months'], FILTER_VALIDATE_INT) : null;
    $weight_range_start = filter_var($_POST['weight_range_start_kg'], FILTER_VALIDATE_FLOAT);
    $weight_range_end = !empty($_POST['weight_range_end_kg']) ? filter_var($_POST['weight_range_end_kg'], FILTER_VALIDATE_FLOAT) : null;
    $food_type = filter_var($_POST['food_type'], FILTER_SANITIZE_STRING);
    $portion_size = filter_var($_POST['portion_size_grams'], FILTER_VALIDATE_FLOAT);
    $meals_per_day = filter_var($_POST['meals_per_day'], FILTER_VALIDATE_INT);
    $feeding_instructions = filter_var($_POST['feeding_instructions'], FILTER_SANITIZE_STRING);
    $special_notes = filter_var($_POST['special_notes'], FILTER_SANITIZE_STRING);
    $created_by = $_SESSION['user_id'];

    try {
        $conn->begin_transaction();

        $query = "INSERT INTO food_guides (
            pet_type,
            age_range_start_months,
            age_range_end_months,
            weight_range_start_kg,
            weight_range_end_kg,
            food_type,
            portion_size_grams,
            meals_per_day,
            feeding_instructions,
            special_notes,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "siiddsdissi",
            $pet_type,
            $age_range_start,
            $age_range_end,
            $weight_range_start,
            $weight_range_end,
            $food_type,
            $portion_size,
            $meals_per_day,
            $feeding_instructions,
            $special_notes,
            $created_by
        );

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Food guide added successfully',
                'guide_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception('Failed to save food guide');
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Operation failed: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
