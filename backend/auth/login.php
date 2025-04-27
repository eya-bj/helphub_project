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
    // Redirect back with error
    header('Location: ../../index.html?error=missing_fields#loginModal');
    exit;
    // echo json_encode(['error' => 'User type, pseudo, and password are required']);
    // exit;
}

// Validate user_type
$user_type = $data['user_type']; // Assign to variable
if (!in_array($user_type, ['association', 'donor'])) {
    // Redirect back with error
    header('Location: ../../index.html?error=invalid_user_type#loginModal');
    exit;
    // echo json_encode(['error' => 'Invalid user type. Must be "association" or "donor"']);
    // exit;
}

$pseudo = trim($data['pseudo']); // Use $data
$password = $data['password']; // Use $data, don't trim password

try {
    $table = $data['user_type'];
    $id_field = $data['user_type'] === 'association' ? 'assoc_id' : 'donor_id';
    
    // Get user by pseudo
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE pseudo = ?");
    $stmt->execute([$data['pseudo']]);
    $user = $stmt->fetch();
    
    // Check if user exists
    if (!$user) {
        // Redirect back with error parameter
        header('Location: ../../index.html?error=user_not_found#loginModal');
        exit; 
        // echo json_encode(['error' => 'User not found']); // Replaced with redirect
        // exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) { // Use $password variable
        // Redirect back with error
        header('Location: ../../index.html?error=invalid_password#loginModal');
        exit;
        // echo json_encode(['error' => 'Invalid password']);
        // exit;
    }

    // Regenerate session ID upon successful login
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user[$id_field];
    $_SESSION['user_type'] = $data['user_type'];
    $_SESSION['pseudo'] = $user['pseudo'];
    
    // Remove password from user data
    // unset($user['password']); // No longer needed as we are redirecting
    
    // Determine redirect URL based on user type
    $redirect_url = '';
    if ($_SESSION['user_type'] === 'donor') {
        $redirect_url = '../../dashboard-donor.html';
    } elseif ($_SESSION['user_type'] === 'association') {
        $redirect_url = '../../dashboard-association.html';
    } else {
        // Fallback or error handling if user type is somehow invalid
        $redirect_url = '../../index.html?error=login_failed#loginModal';
    }

    // Redirect to the appropriate dashboard
    header('Location: ' . $redirect_url);
    exit; // Important: Stop script execution after redirect

    /* Removed JSON success response:
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Login successful',
            'user' => $user,
            'session_id' => session_id()
        ]
    ]);
    */

} catch (PDOException $e) {
    // Redirect back with a generic error on database issues
    header('Location: ../../index.html?error=login_failed#loginModal');
    exit;
    // echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
    // exit;
}
?>
