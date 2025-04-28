<?php
/**
 * Association Registration Endpoint
 * 
 * Handles registration of new associations
 * Method: POST
 * Data: name, address, fiscal_id, pseudo, password, email, representative_name, representative_surname, cin
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

// Get POST data directly - removed JSON handling
$data = $_POST;

// Check for required fields 
$required_fields = ['name', 'address', 'fiscal_id', 'pseudo', 'password', 'email', 'representative_name', 'representative_surname', 'cin'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        // Redirect back to registration form with error
        header('Location: ../../register-association.html?error=missing_fields'); 
        exit;
    }
}

// Assign variables AFTER validation
$name = trim($data['name']);
$address = trim($data['address']);
$fiscal_id = trim($data['fiscal_id']);
$pseudo = trim($data['pseudo']);
$password = $data['password']; 
$email = trim($data['email']);
$rep_name = trim($data['representative_name']);
$rep_surname = trim($data['representative_surname']);
$cin = trim($data['cin']);

// Validate fiscal_id (format: $AAA12)
if (!preg_match('/^\$[A-Z]{3}[0-9]{2}$/', $fiscal_id)) {
    // Redirect back with error
    header('Location: ../../register-association.html?error=invalid_fiscal_id');
    exit;
}

// Validate CIN (8 digits)
if (!preg_match('/^[0-9]{8}$/', $cin)) {
    // Redirect back with error
    header('Location: ../../register-association.html?error=invalid_cin');
    exit;
}

// Validate pseudo (letters and numbers, min 3 chars - consistent with JS)
if (!preg_match('/^[a-zA-Z0-9]{3,}$/', $pseudo)) {
    // Redirect back with error
    header('Location: ../../register-association.html?error=invalid_pseudo');
    exit;
}

// Validate password (≥ 8 chars and ends with $ or #)
if (strlen($password) < 8 || !(substr($password, -1) === '$' || substr($password, -1) === '#')) {
    // Redirect back with error
    header('Location: ../../register-association.html?error=invalid_password');
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Redirect back with error
    header('Location: ../../register-association.html?error=invalid_email');
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE email = ?"); 
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../../register-association.html?error=email_exists');
        exit;
    }

    // Check if pseudo already exists
    $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE pseudo = ?");
    $stmt->execute([$pseudo]);
    if ($stmt->fetch()) {
        header('Location: ../../register-association.html?error=pseudo_exists');
        exit;
    }

    // Check if CIN already exists
    $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE cin = ?");
    $stmt->execute([$cin]);
    if ($stmt->fetch()) {
        header('Location: ../../register-association.html?error=cin_exists');
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Handle logo upload
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/logos/';
        
        // Create the directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $new_filename = 'assoc_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            $logo_path = 'uploads/logos/' . $new_filename;
        }
    }

    // Insert association - Fixed SQL to match database structure
    $stmt = $pdo->prepare("
        INSERT INTO association (name, address, fiscal_id, pseudo, password, email, representative_name, representative_surname, cin, logo_path) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$name, $address, $fiscal_id, $pseudo, $hashed_password, $email, $rep_name, $rep_surname, $cin, $logo_path]);

    // Redirect to login page with success message
    header('Location: ../../index.php?register=success_association#loginModal');
    exit;

} catch (PDOException $e) {
    error_log("Association registration error: " . $e->getMessage());
    header('Location: ../../register-association.html?error=database&message=' . urlencode($e->getMessage()));
    exit;
}
?>
