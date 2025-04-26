<?php
/**
 * Project Details Endpoint for Association
 * 
 * Fetches detailed information about a specific project
 * Method: GET
 * Required params: project_id
 */

// Start session
session_start();

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in as association
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    echo json_encode(['error' => 'Unauthorized access. Please login as an association']);
    exit;
}

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Only GET method is allowed']);
    exit;
}

// Validate project_id parameter
if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    echo json_encode(['error' => 'Valid project_id parameter is required']);
    exit;
}

// Get database connection
require_once '../db.php';

try {
    $assoc_id = $_SESSION['user_id'];
    $project_id = $_GET['project_id'];
    
    // Get project details
    $stmt = $pdo->prepare("
        SELECT * FROM project 
        WHERE project_id = ? AND assoc_id = ?
    ");
    $stmt->execute([$project_id, $assoc_id]);
    $project = $stmt->fetch();
    
    // Check if project exists and belongs to the association
    if (!$project) {
        echo json_encode(['error' => 'Project not found or does not belong to your association']);
        exit;
    }
    
    // Calculate progress percentage
    $project['progress'] = $project['goal_amount'] > 0 
        ? round(($project['current_amount'] / $project['goal_amount']) * 100, 2) 
        : 0;
    
    // Calculate days remaining
    $end_date = new DateTime($project['end_date']);
    $today = new DateTime();
    $project['days_remaining'] = $today <= $end_date ? $end_date->diff($today)->days : 0;
    
    // Get donations with donor information
    $stmt = $pdo->prepare("
        SELECT d.donation_id, d.amount, d.anonymous, d.donation_date,
               donor.name, donor.surname, donor.pseudo
        FROM donation d
        JOIN donor ON d.donor_id = donor.donor_id
        WHERE d.project_id = ?
        ORDER BY d.donation_date DESC
    ");
    $stmt->execute([$project_id]);
    $donations = $stmt->fetchAll();
    
    // Anonymize donor information for anonymous donations
    foreach ($donations as &$donation) {
        if ($donation['anonymous']) {
            $donation['name'] = 'Anonymous';
            $donation['surname'] = '';
            $donation['pseudo'] = 'Anonymous';
        }
    }
    
    // Get donation statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_donations,
            COUNT(DISTINCT donor_id) as unique_donors,
            MAX(amount) as largest_donation,
            AVG(amount) as average_donation
        FROM donation
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    $stats = $stmt->fetch();
    
    // Return success
    echo json_encode([
        'success' => true,
        'data' => [
            'project' => $project,
            'donations' => $donations,
            'statistics' => $stats
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch project details: ' . $e->getMessage()]);
    exit;
}
?>
