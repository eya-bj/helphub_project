<?php
/**
 * Donor Profile Update Endpoint
 * 
 * Handles updates to donor profile information.
 * Method: POST
 * Data: name, surname, email (CTN/Pseudo usually not changeable)
 */

// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log attempts
$log_file = '../profile_update_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Donor Profile Update attempt\n" . "POST data: " . print_r($_POST, true) . "\nSession: " . print_r($_SESSION, true) . "\n\n", FILE_APPEND);

// Check if user is logged in as donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header('Location: ../../index.html?error=unauthorized');
    exit;
}

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../profile-donor.html?error=invalid_method');
    exit;
}

// Connect to database
require_once '../db.php';

// Check for required fields
if (empty($_POST['name']) || empty($_POST['surname']) || empty($_POST['email'])) {
    header('Location: ../../profile-donor.html?error=missing_fields');
    exit;
}

// --- Basic Server-Side Validation ---
$name = trim($_POST['name']);
$surname = trim($_POST['surname']);
$email = trim($_POST['email']);
$donor_id = $_SESSION['user_id'];

if (strlen($name) < 2 || strlen($surname) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
     header('Location: ../../profile-donor.html?error=validation_failed');
     exit;
}

try {
    // Check if email is being changed and if the new email already exists for another donor
    $stmt = $pdo->prepare("SELECT email FROM donor WHERE donor_id = ?");
    $stmt->execute([$donor_id]);
    $current_email = $stmt->fetchColumn();

    if ($email !== $current_email) {
        $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE email = ? AND donor_id != ?");
        $stmt->execute([$email, $donor_id]);
        if ($stmt->fetch()) {
            header('Location: ../../profile-donor.html?error=email_exists');
            exit;
        }
    }

    // Update donor information
    $stmt = $pdo->prepare("
        UPDATE donor 
        SET name = ?, surname = ?, email = ?
        WHERE donor_id = ?
    ");

    $success = $stmt->execute([$name, $surname, $email, $donor_id]);

    if ($success) {
        // Update session name if needed
        $_SESSION['user_name'] = $name; 
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Donor profile updated successfully: ID=" . $donor_id . "\n\n", FILE_APPEND);
        header('Location: ../../profile-donor.html?success=profile_updated');
        exit;
    } else {
        $errorInfo = $stmt->errorInfo();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Donor profile update failed: " . print_r($errorInfo, true) . "\n\n", FILE_APPEND);
        header('Location: ../../profile-donor.html?error=database_error&code=' . $errorInfo[1]);
        exit;
    }

} catch (PDOException $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - PDO Exception: " . $e->getMessage() . "\n\n", FILE_APPEND);
    header('Location: ../../profile-donor.html?error=database_error&msg=' . urlencode($e->getCode()));
    exit;
}
?>
