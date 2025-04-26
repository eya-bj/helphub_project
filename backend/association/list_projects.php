<?php
/**
 * List Projects Endpoint for Association
 * 
 * Fetches all projects for the logged-in association
 * Method: GET
 * Optional params: status (active|inactive|all)
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

// Get database connection
require_once '../db.php';

try {
    $assoc_id = $_SESSION['user_id'];
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    // Build query based on status filter
    $query = "SELECT * FROM project WHERE assoc_id = ?";
    $params = [$assoc_id];
    
    if ($status_filter !== 'all') {
        $query .= " AND status = ?";
        $params[] = $status_filter;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
    
    // Calculate additional metrics for each project
    foreach ($projects as &$project) {
        // Calculate progress percentage
        $project['progress'] = $project['goal_amount'] > 0 
            ? round(($project['current_amount'] / $project['goal_amount']) * 100, 2) 
            : 0;
        
        // Calculate days remaining
        $end_date = new DateTime($project['end_date']);
        $today = new DateTime();
        $project['days_remaining'] = $today <= $end_date ? $end_date->diff($today)->days : 0;
        
        // Get donor count
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT donor_id) as donor_count 
            FROM donation 
            WHERE project_id = ?
        ");
        $stmt->execute([$project['project_id']]);
        $result = $stmt->fetch();
        $project['donor_count'] = $result['donor_count'];
    }
    
    // Return success
    echo json_encode([
        'success' => true,
        'data' => [
            'projects' => $projects,
            'count' => count($projects)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch projects: ' . $e->getMessage()]);
    exit;
}
?>
