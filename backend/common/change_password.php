<?php
session_start();
require_once '../db.php'; // Adjust path as needed

// Check if user is logged in and request is POST
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php?error=unauthorized"); // Changed from index.html
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$profile_page = ($user_type === 'donor') ? '../../profile-donor.php' : '../../profile-association.php';

// Get data from POST request
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: $profile_page?error=missing_fields");
    exit;
}

if ($new_password !== $confirm_password) {
    header("Location: $profile_page?error=password_mismatch");
    exit;
}

// Validate new password format (≥ 8 chars and ends with $ or #)
if (strlen($new_password) < 8 || !(substr($new_password, -1) === '$' || substr($new_password, -1) === '#')) {
    header("Location: $profile_page?error=password_format");
    exit;
}


try {
    // Determine table and ID column based on user type
    $table = ($user_type === 'donor') ? 'donor' : 'association';
    $id_column = ($user_type === 'donor') ? 'donor_id' : 'assoc_id';

    // Fetch current hashed password
    $stmt_fetch = $pdo->prepare("SELECT password FROM $table WHERE $id_column = ?");
    $stmt_fetch->execute([$user_id]);
    $user = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Should not happen if session is valid
        header("Location: $profile_page?error=user_not_found");
        exit;
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        header("Location: $profile_page?error=current_password_incorrect");
        exit;
    }

    // Hash the new password
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $stmt_update = $pdo->prepare("UPDATE $table SET password = ? WHERE $id_column = ?");
    $success = $stmt_update->execute([$hashed_new_password, $user_id]);

    if ($success) {
        header("Location: $profile_page?success=password_changed");
        exit;
    } else {
        header("Location: $profile_page?error=update_failed");
        exit;
    }

} catch (PDOException $e) {
    error_log("Password change error: " . $e->getMessage());
    header("Location: $profile_page?error=db_error");
    exit;
}
?>
