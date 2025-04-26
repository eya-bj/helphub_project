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

// Get POST data directly - removed JSON handling
$data = $_POST;

// Check for required fields (keeping this basic validation for security)
$required_fields = ['name', 'surname', 'ctn', 'pseudo', 'password', 'email'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['error' => "Field '$field' is required"]);
        exit;
    }
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
