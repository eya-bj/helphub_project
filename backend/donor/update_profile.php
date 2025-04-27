<?php
session_start();
require_once '../db.php'; // Adjust path as needed

// Check if user is logged in as a donor and request is POST
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.html?error=unauthorized");
    exit;
}

$donor_id = $_SESSION['user_id'];

// Get data from POST request
$name = trim($_POST['name'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$email = trim($_POST['email'] ?? '');

// Basic Validation (Add more robust validation as needed)
if (empty($name) || strlen($name) < 2 || empty($surname) || strlen($surname) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../profile-donor.php?error=invalid_input");
    exit;
}

try {
    // Check if email is already taken by another donor
    $stmt_check_email = $pdo->prepare("SELECT donor_id FROM donor WHERE email = ? AND donor_id != ?");
    $stmt_check_email->execute([$email, $donor_id]);
    if ($stmt_check_email->fetch()) {
        header("Location: ../../profile-donor.php?error=email_exists");
        exit;
    }

    // Update donor information
    $stmt = $pdo->prepare("UPDATE donor SET name = ?, surname = ?, email = ? WHERE donor_id = ?");
    $success = $stmt->execute([$name, $surname, $email, $donor_id]);

    if ($success) {
        // Update session name if needed
        $_SESSION['user_name'] = $name . ' ' . $surname;
        header("Location: ../../profile-donor.php?success=updated");
        exit;
    } else {
        header("Location: ../../profile-donor.php?error=update_failed");
        exit;
    }

} catch (PDOException $e) {
    error_log("Donor update error: " . $e->getMessage());
    header("Location: ../../profile-donor.php?error=db_error"); // Generic DB error
    exit;
}
?>
