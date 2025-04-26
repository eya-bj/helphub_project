<?php
/**
 * Login Endpoint
 * 
 * Handles login for both associations and donors
 * Method: POST
 * Data: user_type (association|donor), pseudo, password
 */

// Start session
session_start();

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
if (empty($data['user_type']) || empty($data['pseudo']) || empty($data['password'])) {
    echo json_encode(['error' => 'User type, pseudo, and password are required']);
    exit;
}

// Basic user_type check (this is security-critical so we keep it)
if (!in_array($data['user_type'], ['association', 'donor'])) {
    echo json_encode(['error' => 'Invalid user type. Must be "association" or "donor"']);
    exit;
}

try {
    $table = $data['user_type'];
    $id_field = $data['user_type'] === 'association' ? 'assoc_id' : 'donor_id';
    
    // Get user by pseudo
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE pseudo = ?");
    $stmt->execute([$data['pseudo']]);
    $user = $stmt->fetch();
    
    // Check if user exists
    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Verify password (this is security-critical so we keep it)
    if (!password_verify($data['password'], $user['password'])) {
        echo json_encode(['error' => 'Invalid password']);
        exit;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user[$id_field];
    $_SESSION['user_type'] = $data['user_type'];
    $_SESSION['pseudo'] = $user['pseudo'];
    
    // Remove password from user data
    unset($user['password']);
    
    // Return success
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Login successful',
            'user' => $user,
            'session_id' => session_id()
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
    exit;
}
?>
