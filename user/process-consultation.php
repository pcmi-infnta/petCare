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
    $consultation_date = filter_var($_POST['consultation_date'], FILTER_SANITIZE_STRING);
    $main_symptoms = filter_var($_POST['main_symptoms'], FILTER_SANITIZE_STRING);
    $symptoms_details = filter_var($_POST['symptoms_details'], FILTER_SANITIZE_STRING);

    // Insert consultation
    $insert_query = "INSERT INTO medical_consultations 
        (pet_id, consultation_date, main_symptoms, symptoms_details, status) 
        VALUES (?, ?, ?, ?, 'pending')";
    
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("isss", 
        $pet_id,
        $consultation_date,
        $main_symptoms,
        $symptoms_details
    );

    if ($insert_stmt->execute()) {
        $consultation_id = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Consultation scheduled successfully',
            'consultation_id' => $consultation_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to schedule consultation']);
    }
}
