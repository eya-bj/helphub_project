<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    echo json_encode(['error' => 'Unauthorized access. Please login as an association']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Only GET method is allowed']);
    exit;
}

require_once '../db.php';

try {
    $assoc_id = $_SESSION['user_id'];
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    $query = "SELECT * FROM project WHERE assoc_id = ?";
    $params = [$assoc_id];
    
    if ($status_filter !== 'all' && in_array($status_filter, ['active', 'inactive', 'funded', 'completed'])) {
        $query .= " AND status = ?";
        $params[] = $status_filter;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'projects' => $projects,
            'count' => count($projects)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error listing projects: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred.']);
    exit;
}
?>
