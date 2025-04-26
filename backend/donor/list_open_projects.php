<?php
/**
 * List Open Projects Endpoint for Donors
 * 
 * Fetches all active projects available for donation
 * Method: GET
 * Optional params: category, search, sort (newest|goal|progress)
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

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Only GET method is allowed']);
    exit;
}

// Get database connection
require_once '../db.php';

try {
    // Build query based on filters
    $query = "
        SELECT p.*, a.name as association_name, a.logo_path as association_logo
        FROM project p
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.status = 'active'
        AND p.end_date >= CURDATE()
    ";
    $params = [];
    
    // Apply category filter if provided
    if (!empty($_GET['category'])) {
        $query .= " AND p.category = ?";
        $params[] = htmlspecialchars($_GET['category']);
    }
    
    // Apply search filter if provided
    if (!empty($_GET['search'])) {
        $search_term = '%' . htmlspecialchars($_GET['search']) . '%';
        $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR a.name LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Apply sorting
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    switch ($sort) {
        case 'goal':
            $query .= " ORDER BY p.goal_amount DESC";
            break;
        case 'progress':
            $query .= " ORDER BY (p.current_amount / p.goal_amount) DESC";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY p.created_at DESC";
            break;
    }
    
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
    
    // Get available categories
    $stmt = $pdo->prepare("
        SELECT DISTINCT category 
        FROM project 
        WHERE status = 'active'
        ORDER BY category
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Return success
    echo json_encode([
        'success' => true,
        'data' => [
            'projects' => $projects,
            'count' => count($projects),
            'categories' => $categories
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch projects: ' . $e->getMessage()]);
    exit;
}
?>
