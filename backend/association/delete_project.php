<?php
/**
 * Delete Project Endpoint
 * 
 * Allows associations to delete or deactivate their projects
 * Method: POST
 * Data: project_id
 */

// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log attempts
$log_file = '../project_ops_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Delete Project attempt\n" . "POST data: " . print_r($_POST, true) . "\nSession: " . print_r($_SESSION, true) . "\n\n", FILE_APPEND);

// Check if user is logged in as association
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header('Location: ../../index.html?error=unauthorized');
    exit;
}

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../dashboard-association.html?error=invalid_method');
    exit;
}

// Connect to database
require_once '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// If no data was received through JSON, try regular POST
if (!$data) {
    $data = $_POST;
}

// Validate project_id
if (!isset($_POST['project_id']) || !is_numeric($_POST['project_id'])) {
    header('Location: ../../dashboard-association.html?error=missing_fields');
    exit;
}

$project_id = $_POST['project_id'];
$assoc_id = $_SESSION['user_id'];

try {
    // Check if project exists and belongs to the association
    $stmt = $pdo->prepare("SELECT project_id, image_path FROM project WHERE project_id = ? AND assoc_id = ?");
    $stmt->execute([$project_id, $assoc_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        header('Location: ../../dashboard-association.html?error=project_not_found');
        exit;
    }
    
    // Check if project has donations
    $stmt = $pdo->prepare("SELECT COUNT(*) as donation_count FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['donation_count'] > 0) {
        // Deactivate instead of deleting
        $stmt = $pdo->prepare("UPDATE project SET status = 'inactive' WHERE project_id = ?");
        $stmt->execute([$project_id]);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Project deactivated (had donations): ID=" . $project_id . "\n\n", FILE_APPEND);
        header('Location: ../../dashboard-association.html?success=project_deactivated');
        exit;
    } else {
        // Delete project (donations table constraint should be ON DELETE CASCADE or handle manually)
        // Assuming ON DELETE CASCADE is set
        $stmt = $pdo->prepare("DELETE FROM project WHERE project_id = ?");
        $success = $stmt->execute([$project_id]);

        if ($success) {
            // Delete project image if exists
            if (!empty($project['image_path'])) {
                $image_file = '../../' . $project['image_path'];
                if (file_exists($image_file)) {
                    unlink($image_file);
                }
            }
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Project deleted successfully: ID=" . $project_id . "\n\n", FILE_APPEND);
            header('Location: ../../dashboard-association.html?success=project_deleted');
            exit;
        } else {
             $errorInfo = $stmt->errorInfo();
             file_put_contents($log_file, date('Y-m-d H:i:s') . " - Project delete failed: " . print_r($errorInfo, true) . "\n\n", FILE_APPEND);
             header('Location: ../../dashboard-association.html?error=database_error&code=' . $errorInfo[1]);
             exit;
        }
    }

} catch (PDOException $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - PDO Exception: " . $e->getMessage() . "\n\n", FILE_APPEND);
    header('Location: ../../dashboard-association.html?error=database_error&msg=' . urlencode($e->getCode()));
    exit;
}
?>
