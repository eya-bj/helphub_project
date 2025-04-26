<?php
/**
 * Donor Registration Endpoint
 * 
 * Handles registration of new donors
 * Method: POST
 * Data: name, surname, ctn, pseudo, password, email
 */

// Start session
session_start();

// Enable error reporting for debugging (consider disabling in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log the incoming request for debugging
$log_file = '../registration_debug.log';
file_put_contents($log_file, 
    date('Y-m-d H:i:s') . " - Donor Registration attempt\n" . 
    "POST data: " . print_r($_POST, true) . "\n\n", 
    FILE_APPEND);

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect back to form with error
    header('Location: ../../register-donor.html?error=invalid_method');
    exit;
}

// Connect to database
require_once '../db.php'; // Ensure this path is correct

// Check for required fields
$required_fields = ['name', 'surname', 'ctn', 'pseudo', 'password', 'email'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    // Redirect back with error
    header('Location: ../../register-donor.html?error=missing_fields&fields=' . implode(',', $missing_fields));
    exit;
}

// --- Basic Server-Side Validation (Example) ---
// You should add more robust validation here matching JS rules
$name = trim($_POST['name']);
$surname = trim($_POST['surname']);
$ctn = trim($_POST['ctn']);
$pseudo = trim($_POST['pseudo']);
$password = $_POST['password']; // Keep original for hashing
$email = trim($_POST['email']);

if (strlen($name) < 2 || strlen($surname) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{8}$/', $ctn) || !preg_match('/^[a-zA-Z0-9]{3,}$/', $pseudo) || !(strlen($password) >= 8 && (str_ends_with($password, '$') || str_ends_with($password, '#')))) {
     header('Location: ../../register-donor.html?error=validation_failed');
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

    // Check if CTN already exists
    $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE ctn = ?");
    $stmt->execute([$ctn]);
    if ($stmt->fetch()) {
        header('Location: ../../register-donor.html?error=ctn_exists');
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert donor
    $stmt = $pdo->prepare("
        INSERT INTO donor (name, surname, ctn, pseudo, password, email) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // Execute the insertion
    $success = $stmt->execute([
        $name,
        $surname,
        $ctn,
        $pseudo,
        $hashed_password,
        $email
    ]);

    if ($success) {
        // Log success
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Donor registered successfully: Pseudo=" . $pseudo . "\n\n", FILE_APPEND);
        // Redirect to login page (or index) with success message
        header('Location: ../../index.html?success=donor_registered&pseudo=' . urlencode($pseudo));
        exit;
    } else {
        // Log the error if insertion failed
        $errorInfo = $stmt->errorInfo();
        file_put_contents($log_file, 
            date('Y-m-d H:i:s') . " - Insert failed\n" . 
            "Error info: " . print_r($errorInfo, true) . "\n\n", 
            FILE_APPEND);
        
        header('Location: ../../register-donor.html?error=database_error&code=' . $errorInfo[1]);
        exit;
    }

} catch (PDOException $e) {
    // Log the PDO exception
    file_put_contents($log_file, 
        date('Y-m-d H:i:s') . " - PDO Exception\n" . 
        "Error: " . $e->getMessage() . "\n\n", 
        FILE_APPEND);
    
    // Redirect with a generic database error
    header('Location: ../../register-donor.html?error=database_error&msg=' . urlencode($e->getCode()));
    exit;
}
?>
