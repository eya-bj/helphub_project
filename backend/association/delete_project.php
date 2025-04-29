<?php
/**
 * Delete Project Endpoint
 * 
 * Allows associations to delete their projects
 * Method: POST
 * Data: project_id
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

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

// Get database connection
require_once '../db.php';

// Get POST data directly - removed JSON handling
$data = $_POST;

// Validate project_id
if (!isset($data['project_id']) || !is_numeric($data['project_id'])) {
    echo json_encode(['error' => 'Valid project_id is required']);
    exit;
}

try {
    $assoc_id = $_SESSION['user_id'];
    $project_id = $data['project_id'];
    
    // Check if project exists and belongs to the association
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
    
    // Optional: Check if project has donations
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as donation_count
        FROM donation
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    $result = $stmt->fetch();
    
    if ($result['donation_count'] > 0) {
        // Option 1: Don't allow deletion if project has donations
        // echo json_encode(['error' => 'Cannot delete project with existing donations']);
        // exit;
        
        // Option 2: Set status to inactive instead of deleting
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
    
    // Delete project (this will cascade to donations due to foreign key constraints)
    $stmt = $pdo->prepare("
        DELETE FROM project
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    
    // Delete project image if exists
    if (!empty($project['image_path'])) {
        $image_file = '../../' . $project['image_path'];
        if (file_exists($image_file)) {
            unlink($image_file);
        }
    }
    
    // Return success
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Project deleted successfully',
            'project_id' => $project_id
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to delete project: ' . $e->getMessage()]);
    exit;
}
?>
