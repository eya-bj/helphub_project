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

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// If no data was received through JSON, try regular POST
if (!$data) {
    $data = $_POST;
}

// Validate required fields
$required_fields = ['name', 'address', 'fiscal_id', 'pseudo', 'password', 'email', 'representative_name', 'representative_surname', 'cin'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['error' => "Field '$field' is required"]);
        exit;
    }
}

// Validate fiscal_id (format: $AAA12)
if (!preg_match('/^\$[A-Z]{3}[0-9]{2}$/', $data['fiscal_id'])) {
    echo json_encode(['error' => 'Invalid fiscal ID format. Must be $ followed by 3 uppercase letters and 2 digits']);
    exit;
}

// Validate CIN (8 digits)
if (!preg_match('/^[0-9]{8}$/', $data['cin'])) {
    echo json_encode(['error' => 'Invalid CIN format. Must be 8 digits']);
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
    $stmt = $pdo->prepare("SELECT * FROM association WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Check if pseudo already exists
    $stmt = $pdo->prepare("SELECT * FROM association WHERE pseudo = ?");
    $stmt->execute([$data['pseudo']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'Pseudo already taken']);
        exit;
    }

    // Check if CIN already exists
    $stmt = $pdo->prepare("SELECT * FROM association WHERE cin = ?");
    $stmt->execute([$data['cin']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'CIN already registered']);
        exit;
    }

    // Check if fiscal_id already exists
    $stmt = $pdo->prepare("SELECT * FROM association WHERE fiscal_id = ?");
    $stmt->execute([$data['fiscal_id']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'Fiscal ID already registered']);
        exit;
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
