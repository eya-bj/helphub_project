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

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// If no data was received through JSON, try regular POST
if (!$data) {
    $data = $_POST;
}

// Validate required fields
$required_fields = ['name', 'surname', 'ctn', 'pseudo', 'password', 'email'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['error' => "Field '$field' is required"]);
        exit;
    }
}

// Validate CTN (8 digits)
if (!preg_match('/^[0-9]{8}$/', $data['ctn'])) {
    echo json_encode(['error' => 'Invalid CTN format. Must be 8 digits']);
    exit;
}

// Validate pseudo (letters only)
if (!preg_match('/^[a-zA-Z]+$/', $data['pseudo'])) {
    echo json_encode(['error' => 'Invalid pseudo format. Must contain only letters']);
    exit;
}

// Validate password (≥ 8 chars and ends with $ or #)
if (strlen($data['password']) < 8 || !(substr($data['password'], -1) === '$' || substr($data['password'], -1) === '#')) {
    echo json_encode(['error' => 'Invalid password. Must be at least 8 characters and end with $ or #']);
    exit;
}

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email address']);
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
