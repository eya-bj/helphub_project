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

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// If no data was received through JSON, try regular POST
if (!$data) {
    $data = $_POST;
}

// Validate required fields
$required_fields = ['name', 'surname', 'ctn', 'pseudo', 'password', 'email'];
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
    $stmt = $pdo->prepare("SELECT * FROM donor WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Check if pseudo already exists
    $stmt = $pdo->prepare("SELECT * FROM donor WHERE pseudo = ?");
    $stmt->execute([$data['pseudo']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'Pseudo already taken']);
        exit;
    }

    // Check if CTN already exists
    $stmt = $pdo->prepare("SELECT * FROM donor WHERE ctn = ?");
    $stmt->execute([$data['ctn']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['error' => 'CTN already registered']);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert donor
    $stmt = $pdo->prepare("
        INSERT INTO donor (
            name, surname, ctn, pseudo, password, email
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['name'],
        $data['surname'],
        $data['ctn'],
        $data['pseudo'],
        $hashed_password,
        $data['email']
    ]);

    // Return success
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Donor registered successfully',
            'donor_id' => $pdo->lastInsertId()
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
    exit;
}
?>
