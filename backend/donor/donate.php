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

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in as donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    echo json_encode(['error' => 'Unauthorized access. Please login as a donor']);
    exit;
}

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

// Get database connection
require_once '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// If no data was received through JSON, try regular POST
if (!$data) {
    $data = $_POST;
}

// Validate required fields
if (!isset($data['project_id']) || !isset($data['amount'])) {
    echo json_encode(['error' => 'Project ID and amount are required']);
    exit;
}

// Validate amount
if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
    echo json_encode(['error' => 'Amount must be a positive number']);
    exit;
}

// Process anonymous flag (default to false if not set)
$anonymous = isset($data['anonymous']) ? (bool)$data['anonymous'] : false;

try {
    $donor_id = $_SESSION['user_id'];
    $project_id = $data['project_id'];
    $amount = floatval($data['amount']);
    
    // Check if project exists and is active
    $stmt = $pdo->prepare("
        SELECT * FROM project 
        WHERE project_id = ? AND status = 'active' AND end_date >= CURDATE()
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        echo json_encode(['error' => 'Project not found or is not active']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert donation
    $stmt = $pdo->prepare("
        INSERT INTO donation (donor_id, project_id, amount, anonymous)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$donor_id, $project_id, $amount, $anonymous]);
    $donation_id = $pdo->lastInsertId();
    
    // Update project current_amount
    $stmt = $pdo->prepare("
        UPDATE project
        SET current_amount = current_amount + ?
        WHERE project_id = ?
    ");
    $stmt->execute([$amount, $project_id]);
    
    // Fetch updated project data
    $stmt = $pdo->prepare("SELECT * FROM project WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $updated_project = $stmt->fetch();
    
    // Calculate new progress percentage
    $progress = $updated_project['goal_amount'] > 0 
        ? round(($updated_project['current_amount'] / $updated_project['goal_amount']) * 100, 2) 
        : 0;
    
    // Commit transaction
    $pdo->commit();
    
    // Return success
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Donation successful',
            'donation_id' => $donation_id,
            'project' => [
                'project_id' => $updated_project['project_id'],
                'title' => $updated_project['title'],
                'current_amount' => $updated_project['current_amount'],
                'goal_amount' => $updated_project['goal_amount'],
                'progress' => $progress
            ]
        ]
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['error' => 'Donation failed: ' . $e->getMessage()]);
    exit;
}
?>
