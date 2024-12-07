<?php
$host = getenv('DB_HOST') ?: 'sql300.infinityfree.com';
$dbname = getenv('DB_NAME') ?: 'if0_37858321_petCareDB';
$username = getenv('DB_USER') ?: 'if0_37858321';
$password = getenv('DB_PASS') ?: 'GcEQFqnydWQU';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}