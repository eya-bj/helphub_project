<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    echo json_encode(['error' => 'Unauthorized access. Please login as an association']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

require_once '../db.php';

$data = $_POST;
if (empty($data)) {
    $data = json_decode(file_get_contents('php://input'), true);
}

if (!isset($data['project_id']) || !is_numeric($data['project_id'])) {
    echo json_encode(['error' => 'Valid project_id parameter is required']);
    exit;
}

try {
    $assoc_id = $_SESSION['user_id'];
    $project_id = $data['project_id'];
    
    $stmt = $pdo->prepare("
        SELECT * FROM project 
        WHERE project_id = ? AND assoc_id = ?
    ");
    $stmt->execute([$project_id, $assoc_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        echo json_encode(['error' => 'Project not found or does not belong to your association']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as donation_count
        FROM donation
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    $result = $stmt->fetch();
    
    if ($result['donation_count'] > 0) {
        
        $stmt = $pdo->prepare("
            UPDATE project
            SET status = 'inactive'
            WHERE project_id = ?
        ");
        $stmt->execute([$project_id]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'message' => 'Project marked as inactive (has existing donations)',
                'project_id' => $project_id
            ]
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        DELETE FROM project
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    
    if (!empty($project['image_path'])) {
        $image_file = '../../' . $project['image_path'];
        if (file_exists($image_file)) {
            unlink($image_file);
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Project deleted successfully',
            'project_id' => $project_id
        ]
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Project delete error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred during project deletion']);
    exit;
}
?>
