<?php
/**
 * Donation Endpoint
 * 
 * Allows donors to make a donation to a project
 * Method: POST
 * Data: project_id, amount, anonymous (boolean)
 */

// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log attempts
$log_file = '../donation_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Donation attempt\n" . "POST data: " . print_r($_POST, true) . "\nSession: " . print_r($_SESSION, true) . "\n\n", FILE_APPEND);

// Check if user is logged in as donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    // Redirect to login or index if not authorized
    // Pass project_id back if possible to reopen modal? Maybe too complex for now.
    header('Location: ../../index.html?error=unauthorized'); 
    exit;
}

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect back to the project page where the donation was attempted
    $project_id = $_POST['project_id'] ?? 'unknown';
    header('Location: ../../project-details-donor.html?id=' . $project_id . '&error=invalid_method#donationModal');
    exit;
}

// Connect to database
require_once '../db.php';

// Check for required fields
if (!isset($_POST['project_id']) || !isset($_POST['amount'])) {
    $project_id = $_POST['project_id'] ?? 'unknown';
    header('Location: ../../project-details-donor.html?id=' . $project_id . '&error=missing_fields#donationModal');
    exit;
}

$project_id = $_POST['project_id'];
$amount = $_POST['amount'];

// Validate amount (e.g., positive, within limits if any)
if (!is_numeric($amount) || $amount <= 0 || $amount > 1800) { // Assuming max 1800 from HTML
    header('Location: ../../project-details-donor.html?id=' . $project_id . '&error=invalid_amount#donationModal');
    exit;
}

// Process anonymous flag
$anonymous = isset($_POST['anonymous']) && $_POST['anonymous'] == '1' ? 1 : 0; // 1 for true, 0 for false in DB

try {
    $donor_id = $_SESSION['user_id'];
    $amount_float = floatval($amount);
    
    // Check if project exists and is active/valid for donation
    $stmt = $pdo->prepare("
        SELECT project_id FROM project 
        WHERE project_id = ? AND status = 'active' AND end_date >= CURDATE()
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header('Location: ../../project-details-donor.html?id=' . $project_id . '&error=project_not_active#donationModal');
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert donation
    $stmt = $pdo->prepare("
        INSERT INTO donation (donor_id, project_id, amount, anonymous)
        VALUES (?, ?, ?, ?)
    ");
    $success = $stmt->execute([$donor_id, $project_id, $amount_float, $anonymous]);
    
    if (!$success) {
        $pdo->rollBack();
        $errorInfo = $stmt->errorInfo();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Donation insert failed: " . print_r($errorInfo, true) . "\n\n", FILE_APPEND);
        header('Location: ../../project-details-donor.html?id=' . $project_id . '&error=database_error&code=' . $errorInfo[1] . '#donationModal');
        exit;
    }
    
    // The trigger 'after_donation_insert' should handle updating project.current_amount
    
    // Commit transaction
    $pdo->commit();
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Donation successful: DonorID=" . $donor_id . ", ProjectID=" . $project_id . ", Amount=" . $amount_float . "\n\n", FILE_APPEND);
    
    // Redirect back to project page with success message
    header('Location: ../../project-details-donor.html?id=' . $project_id . '&success=donation_complete&amount=' . urlencode($amount_float));
    exit;

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - PDO Exception: " . $e->getMessage() . "\n\n", FILE_APPEND);
    header('Location: ../../project-details-donor.html?id=' . $project_id . '&error=database_error&msg=' . urlencode($e->getCode()) . '#donationModal');
    exit;
}
?>
