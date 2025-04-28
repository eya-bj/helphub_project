<?php
// Start session
session_start();

// Include database connection
require_once '../db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $pseudo = trim($_POST['pseudo'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    
    // Validate input
    if (empty($pseudo) || empty($password) || empty($user_type)) {
        // Redirect back with error
        header("Location: ../../index.php?error=empty_fields#loginModal"); // Updated link
        exit;
    }
    
    try {
        // Check if user exists based on type
        if ($user_type === 'donor') {
            $stmt = $pdo->prepare("SELECT donor_id, name, surname, pseudo, password FROM donor WHERE pseudo = ?");
            $stmt->execute([$pseudo]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Authentication successful
                $_SESSION['user_id'] = $user['donor_id'];
                $_SESSION['user_name'] = $user['name'] . ' ' . $user['surname'];
                $_SESSION['user_pseudo'] = $user['pseudo'];
                $_SESSION['user_type'] = 'donor';
                
                // Redirect to donor dashboard
                header("Location: ../../dashboard-donor.php");
                exit;
            }
        } elseif ($user_type === 'association') {
            // Updated query to use representative_name and representative_surname instead of contact_name
            $stmt = $pdo->prepare("SELECT assoc_id, name, fiscal_id, representative_name, representative_surname, password FROM association WHERE pseudo = ?");
            $stmt->execute([$pseudo]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Authentication successful
                $_SESSION['user_id'] = $user['assoc_id'];
                $_SESSION['user_name'] = $user['name']; 
                $_SESSION['user_fiscal_id'] = $user['fiscal_id'];
                $_SESSION['user_contact'] = $user['representative_name'] . ' ' . $user['representative_surname']; // Correctly constructed from two fields
                $_SESSION['user_type'] = 'association';
                
                // Redirect to association dashboard
                header("Location: ../../dashboard-association.php");
                exit;
            }
        }
        
        // If we get here, authentication failed
        header("Location: ../../index.php?error=invalid_login#loginModal"); // Updated link
        exit;
        
    } catch (PDOException $e) {
        // Log error for debugging (not visible to user)
        error_log("Login error: " . $e->getMessage());
        // Redirect with database error message
        header("Location: ../../index.php?error=database#loginModal"); // Updated link
        exit;
    }
} else {
    // If not POST request, redirect to login page
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../../index.php"); // Update all redirects to use index.php
        exit;
    }
}
?>
