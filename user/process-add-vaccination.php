<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $pet_id = filter_var($_POST['pet_id'], FILTER_SANITIZE_NUMBER_INT);
    $vaccine_name = filter_var($_POST['vaccine_name'], FILTER_SANITIZE_STRING);
    $vaccination_date = filter_var($_POST['vaccination_date'], FILTER_SANITIZE_STRING);
    $next_due_date = !empty($_POST['next_due_date']) ? filter_var($_POST['next_due_date'], FILTER_SANITIZE_STRING) : null;
    $batch_number = !empty($_POST['batch_number']) ? filter_var($_POST['batch_number'], FILTER_SANITIZE_STRING) : generateBatchNumber();
    $notes = filter_var($_POST['notes'], FILTER_SANITIZE_STRING);

    // Verify pet ownership
    $verify_query = "SELECT owner_id FROM pets WHERE pet_id = ? AND owner_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $pet_id, $_SESSION['user_id']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid pet selection']);
        exit();
    }

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Insert vaccination record
        $query = "INSERT INTO vaccination_logs (
            pet_id, 
            vaccine_name, 
            vaccination_date, 
            next_due_date, 
            batch_number, 
            notes,
            administered_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "isssssi",
            $pet_id,
            $vaccine_name,
            $vaccination_date,
            $next_due_date,
            $batch_number,
            $notes,
            $_SESSION['user_id']
        );

        $stmt->execute();
        $vaccination_id = $conn->insert_id;

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Vaccination record added successfully',
            'vaccination_id' => $vaccination_id,
            'batch_number' => $batch_number
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add vaccination record'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

function generateBatchNumber() {
    $prefix = 'VAX';
    $timestamp = date('ymd');
    $random = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
    return "{$prefix}-{$timestamp}-{$random}";
}
?>
