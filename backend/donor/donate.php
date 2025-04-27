<?php
/**
 * Donation Processing Script
 * 
 * Processes donations from donors to projects
 */

// Start session
session_start();

// Check if user is logged in as donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header("Location: ../../index.html?error=unauthorized");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../projects.php");
    exit;
}

// Include database connection
require_once '../db.php';

// Get POST data
$project_id = $_POST['project_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$anonymous = isset($_POST['anonymous']) ? 1 : 0;
$donor_id = $_SESSION['user_id'];

// Basic validation
if (!$project_id || !is_numeric($project_id) || !$amount || !is_numeric($amount) || $amount <= 0) {
    header("Location: ../../project-details-donor.php?id=$project_id&error=invalid_amount");
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get project details
    $stmt = $pdo->prepare("SELECT * FROM project WHERE project_id = ? AND status = 'active'");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        $pdo->rollBack();
        header("Location: ../../projects.php?error=project_not_found");
        exit;
    }
    
    // Check if project is still accepting donations (not past end date)
    $today = new DateTime();
    $end_date = new DateTime($project['end_date']);
    if ($today > $end_date) {
        $pdo->rollBack();
        header("Location: ../../project-details-donor.php?id=$project_id&error=project_ended");
        exit;
    }
    
    // Check if amount is not more than what's needed to reach the goal
    $remaining = $project['goal_amount'] - $project['current_amount'];
    if ($amount > $remaining) {
        $amount = $remaining; // Automatically adjust to remaining amount
    }
    
    // Insert donation record
    $stmt = $pdo->prepare("
        INSERT INTO donation (donor_id, project_id, amount, anonymous, donation_date)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$donor_id, $project_id, $amount, $anonymous]);
    
    // Update project current amount
    // (Note: This is redundant due to the trigger but included for clarity)
    $stmt = $pdo->prepare("
        UPDATE project 
        SET current_amount = current_amount + ?
        WHERE project_id = ?
    ");
    $stmt->execute([$amount, $project_id]);
    
    // If project has reached its goal, update status
    if ($project['current_amount'] + $amount >= $project['goal_amount']) {
        $stmt = $pdo->prepare("
            UPDATE project
            SET status = 'completed'
            WHERE project_id = ? AND status = 'active'
        ");
        $stmt->execute([$project_id]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect with success message
    header("Location: ../../project-details-donor.php?id=$project_id&success=donation_complete");
    exit;
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Donation error: " . $e->getMessage());
    header("Location: ../../project-details-donor.php?id=$project_id&error=donation_failed");
    exit;
} catch (Exception $e) {
    // Rollback transaction on any other error
    $pdo->rollBack();
    error_log("Donation error: " . $e->getMessage());
    header("Location: ../../project-details-donor.php?id=$project_id&error=donation_failed");
    exit;
}
?>
