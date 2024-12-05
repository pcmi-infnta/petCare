<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = filter_var($_POST['pet_id'], FILTER_SANITIZE_NUMBER_INT);
    $owner_id = $_SESSION['user_id'];
    
    // Verify pet ownership
    $verify_query = "SELECT pet_id FROM pets WHERE pet_id = ? AND owner_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $pet_id, $owner_id);
    $verify_stmt->execute();
    
    if ($verify_stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }

    // Sanitize and validate input
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $pet_type = filter_var($_POST['pet_type'], FILTER_SANITIZE_STRING);
    $breed = filter_var($_POST['breed'], FILTER_SANITIZE_STRING);
    $age_years = filter_var($_POST['age_years'], FILTER_VALIDATE_INT);
    $age_months = filter_var($_POST['age_months'], FILTER_VALIDATE_INT);
    $gender = filter_var($_POST['gender'], FILTER_SANITIZE_STRING);
    $weight_kg = filter_var($_POST['weight_kg'], FILTER_VALIDATE_FLOAT);

    // Update pet information
    $update_query = "UPDATE pets SET 
        name = ?, 
        pet_type = ?, 
        breed = ?, 
        age_years = ?, 
        age_months = ?, 
        gender = ?, 
        weight_kg = ?
        WHERE pet_id = ? AND owner_id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssiisdii", 
        $name, 
        $pet_type, 
        $breed, 
        $age_years, 
        $age_months, 
        $gender, 
        $weight_kg,
        $pet_id,
        $owner_id
    );

    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pet updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update pet']);
    }
}
