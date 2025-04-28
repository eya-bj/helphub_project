<?php
/**
 * Donor Registration Endpoint
 * 
 * Handles registration of new donors
 * Method: POST
 * Data: name, surname, ctn, pseudo, password, email
 */

// Set JSON content type
header('Content-Type: application/json');

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

// Get database connection
require_once '../db.php';

// Get POST data directly
$data = $_POST;

// Check for required fields
$required_fields = ['name', 'surname', 'ctn', 'pseudo', 'password', 'email'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        header('Location: ../../register-donor.html?error=missing_fields');
        exit;
    }
}

// Assign variables AFTER validation
$name = trim($data['name']);
$surname = trim($data['surname']);
$cin = trim($data['ctn']); 
$pseudo = trim($data['pseudo']);
$password = $data['password']; 

// Validate CIN (format: 8 digits only)
if (!preg_match('/^[0-9]{8}$/', $cin)) {
    header('Location: ../../register-donor.html?error=invalid_cin');
    exit;
}

// Validate pseudo (letters and numbers, min 3 chars)
if (!preg_match('/^[a-zA-Z0-9]{3,}$/', $pseudo)) {
    header('Location: ../../register-donor.html?error=invalid_pseudo');
    exit;
}

// Validate password (≥ 8 chars and ends with $ or #)
if (strlen($password) < 8 || !(substr($password, -1) === '$' || substr($password, -1) === '#')) {
    header('Location: ../../register-donor.html?error=invalid_password');
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../register-donor.html?error=invalid_email');
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../../register-donor.html?error=email_exists');
        exit;
    }

    // Check if pseudo already exists
    $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE pseudo = ?");
    $stmt->execute([$pseudo]);
    if ($stmt->fetch()) {
        header('Location: ../../register-donor.html?error=pseudo_exists');
        exit;
    }

    // Check if CIN already exists
    $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE ctn = ?");
    $stmt->execute([$cin]);
    if ($stmt->fetch()) {
        header('Location: ../../register-donor.html?error=cin_exists');
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert donor
    $stmt = $pdo->prepare("
        INSERT INTO donor (name, surname, ctn, pseudo, password, email) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $surname, $cin, $pseudo, $hashed_password, $email]);

    // Redirect to login page with success message
    header('Location: ../../index.php?register=success_donor#loginModal'); 
    exit;

} catch (PDOException $e) {
    error_log("Donor registration error: " . $e->getMessage());
    header('Location: ../../register-donor.html?error=database');
    exit;
}
?>
