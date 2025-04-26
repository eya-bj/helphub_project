<?php
/**
 * Logout Endpoint
 * 
 * Handles user logout by destroying the session
 * Method: POST
 */

// Start session
session_start();

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['error' => 'No active session found']);
    exit;
}

// Get user info before destroying session
$user_type = $_SESSION['user_type'];

// Destroy session
session_destroy();

// Return success
echo json_encode([
    'success' => true,
    'data' => [
        'message' => 'Successfully logged out',
        'user_type' => $user_type
    ]
]);
?>
