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

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log login attempts
$log_file = '../login_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Login attempt\n" . "POST data: " . print_r($_POST, true) . "\n\n", FILE_APPEND);

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.html?error=invalid_method#loginModal'); // Redirect back to index, maybe open modal
    exit;
}

// Connect to database
require_once '../db.php';

// Check for required fields
if (empty($_POST['user_type']) || empty($_POST['pseudo']) || empty($_POST['password'])) {
    header('Location: ../../index.html?error=missing_fields#loginModal');
    exit;
}

// Validate user_type
$user_type = $_POST['user_type'];
if (!in_array($user_type, ['association', 'donor'])) {
    header('Location: ../../index.html?error=invalid_user_type#loginModal');
    exit;
}

$pseudo = trim($_POST['pseudo']);
$password = $_POST['password'];

try {
    $table = $user_type; // 'association' or 'donor'
    $id_field = ($user_type === 'association') ? 'assoc_id' : 'donor_id';
    
    // Get user by pseudo
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE pseudo = ?");
    $stmt->execute([$pseudo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists
    if (!$user) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Login failed: User not found (Pseudo: $pseudo, Type: $user_type)\n\n", FILE_APPEND);
        header('Location: ../../index.html?error=user_not_found#loginModal');
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Login failed: Invalid password (Pseudo: $pseudo, Type: $user_type)\n\n", FILE_APPEND);
        header('Location: ../../index.html?error=invalid_password#loginModal');
        exit;
    }
    
    // Regenerate session ID upon successful login
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user[$id_field];
    $_SESSION['user_type'] = $user_type;
    $_SESSION['pseudo'] = $user['pseudo'];
    // Store other relevant info if needed, e.g., name
    $_SESSION['user_name'] = $user['name'] ?? ($user['representative_name'] ?? ''); 

    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Login successful: User ID=" . $_SESSION['user_id'] . ", Type=" . $_SESSION['user_type'] . ", Pseudo=" . $_SESSION['pseudo'] . "\n\n", FILE_APPEND);

    // Redirect to the appropriate dashboard
    $dashboard_url = '../../dashboard-' . $user_type . '.html?success=login_successful';
    header('Location: ' . $dashboard_url);
    exit;

} catch (PDOException $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - PDO Exception: " . $e->getMessage() . "\n\n", FILE_APPEND);
    header('Location: ../../index.html?error=database_error#loginModal');
    exit;
}
?>
