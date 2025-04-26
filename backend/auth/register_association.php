<?php
/**
 * Association Registration Endpoint
 * 
 * Handles registration of new associations
 * Method: POST
 * Data: name, address, fiscal_id, pseudo, password, email, representative_name, representative_surname, cin, logo (file)
 */

// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log the incoming request
$log_file = '../registration_debug.log';
file_put_contents($log_file, 
    date('Y-m-d H:i:s') . " - Association Registration attempt\n" . 
    "POST data: " . print_r($_POST, true) . "\n" .
    "FILES data: " . print_r($_FILES, true) . "\n\n", 
    FILE_APPEND);

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../register-association.html?error=invalid_method');
    exit;
}

// Connect to database
require_once '../db.php';

// Check for required fields
$required_fields = ['name', 'address', 'fiscal_id', 'pseudo', 'password', 'email', 'representative_name', 'representative_surname', 'cin'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    header('Location: ../../register-association.html?error=missing_fields&fields=' . implode(',', $missing_fields));
    exit;
}

// --- Basic Server-Side Validation ---
$name = trim($_POST['name']);
$address = trim($_POST['address']);
$fiscal_id = trim($_POST['fiscal_id']);
$pseudo = trim($_POST['pseudo']);
$password = $_POST['password'];
$email = trim($_POST['email']);
$rep_name = trim($_POST['representative_name']);
$rep_surname = trim($_POST['representative_surname']);
$cin = trim($_POST['cin']);

// Add validation rules similar to JS if needed
if (strlen($name) < 3 || strlen($address) < 5 || !preg_match('/^\$[A-Z]{3}\d{2}$/', $fiscal_id) || !preg_match('/^[a-zA-Z0-9]{3,}$/', $pseudo) || !(strlen($password) >= 8 && (str_ends_with($password, '$') || str_ends_with($password, '#'))) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($rep_name) < 2 || strlen($rep_surname) < 2 || !preg_match('/^\d{8}$/', $cin)) {
    header('Location: ../../register-association.html?error=validation_failed');
    exit;
}


try {
    // Check for existing unique fields
    $checks = [
        'email' => [$email, 'email_exists'],
        'pseudo' => [$pseudo, 'pseudo_exists'],
        'cin' => [$cin, 'cin_exists'],
        'fiscal_id' => [$fiscal_id, 'fiscal_id_exists']
    ];

    foreach ($checks as $field => $details) {
        $value = $details[0];
        $error_code = $details[1];
        $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE $field = ?");
        $stmt->execute([$value]);
        if ($stmt->fetch()) {
            header('Location: ../../register-association.html?error=' . $error_code);
            exit;
        }
    }

    // Process logo upload if provided
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['logo']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            header('Location: ../../register-association.html?error=invalid_file_type');
            exit;
        }
        
        $upload_dir = '../../uploads/logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid('logo_', true) . '_' . basename($_FILES['logo']['name']);
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $file_path)) {
            $logo_path = 'uploads/logos/' . $file_name; // Relative path for DB
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Logo upload failed.\n\n", FILE_APPEND);
            header('Location: ../../register-association.html?error=upload_failed');
            exit;
        }
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert association
    $stmt = $pdo->prepare("
        INSERT INTO association (
            name, address, fiscal_id, logo_path, pseudo, password, email, 
            representative_name, representative_surname, cin
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $success = $stmt->execute([
        $name, $address, $fiscal_id, $logo_path, $pseudo, $hashed_password, 
        $email, $rep_name, $rep_surname, $cin
    ]);

    if ($success) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Association registered successfully: Pseudo=" . $pseudo . "\n\n", FILE_APPEND);
        header('Location: ../../index.html?success=association_registered&pseudo=' . urlencode($pseudo));
        exit;
    } else {
        $errorInfo = $stmt->errorInfo();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Insert failed: " . print_r($errorInfo, true) . "\n\n", FILE_APPEND);
        header('Location: ../../register-association.html?error=database_error&code=' . $errorInfo[1]);
        exit;
    }

} catch (PDOException $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - PDO Exception: " . $e->getMessage() . "\n\n", FILE_APPEND);
    header('Location: ../../register-association.html?error=database_error&msg=' . urlencode($e->getCode()));
    exit;
}
?>
