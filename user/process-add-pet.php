<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $owner_id = $_SESSION['user_id'];
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $pet_type = filter_var($_POST['pet_type'], FILTER_SANITIZE_STRING);
    $breed = filter_var($_POST['breed'], FILTER_SANITIZE_STRING);
    $age_years = filter_var($_POST['age_years'], FILTER_VALIDATE_INT);
    $age_months = filter_var($_POST['age_months'], FILTER_VALIDATE_INT);
    $gender = filter_var($_POST['gender'], FILTER_SANITIZE_STRING);
    $weight_kg = filter_var($_POST['weight_kg'], FILTER_VALIDATE_FLOAT);

    // Validate required fields
    if (empty($name) || empty($pet_type)) {
        echo json_encode(['success' => false, 'message' => 'Name and pet type are required']);
        exit();
    }

    // Insert pet into database
    $query = "INSERT INTO pets (owner_id, name, pet_type, breed, age_years, age_months, gender, weight_kg) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssssd", 
        $owner_id, 
        $name, 
        $pet_type, 
        $breed, 
        $age_years, 
        $age_months, 
        $gender, 
        $weight_kg
    );

    if ($stmt->execute()) {
        $pet_id = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Pet added successfully',
            'pet_id' => $pet_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to add pet'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}
