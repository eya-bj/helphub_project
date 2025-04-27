<?php
/**
 * Update Project Endpoint
 * 
 * Allows associations to update their projects
 * Method: POST
 * Data: project_id, title, description, category, goal_amount, end_date, status, image (optional)
 */

// Start session
session_start();

// Check if user is logged in as association
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header("Location: ../../index.html?error=unauthorized");
    exit;
}

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard-association.php?error=invalid_method");
    exit;
}

// Get database connection
require_once '../db.php';

// Get POST data 
$project_id = $_POST['project_id'] ?? null;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? '';
$goal_amount = $_POST['goal_amount'] ?? 0;
$end_date = $_POST['end_date'] ?? '';
$status = $_POST['status'] ?? 'active';

// Basic validation
if (!$project_id || !is_numeric($project_id)) {
    header("Location: ../../dashboard-association.php?error=invalid_project");
    exit;
}

// Validate required fields
$required_fields = ['title', 'description', 'category', 'goal_amount', 'end_date', 'status'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header("Location: ../../project-details-association.php?id=$project_id&error=missing_fields");
        exit;
    }
}

// Validate goal amount - must be a positive number
if (!is_numeric($goal_amount) || $goal_amount <= 0) {
    header("Location: ../../project-details-association.php?id=$project_id&error=invalid_goal");
    exit;
}

try {
    // Check if project exists and belongs to the association
    $stmt = $pdo->prepare("SELECT * FROM project WHERE project_id = ? AND assoc_id = ?");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        header("Location: ../../dashboard-association.php?error=project_not_found");
        exit;
    }
    
    // Ensure goal amount is not less than current amount
    if ($goal_amount < $project['current_amount']) {
        header("Location: ../../project-details-association.php?id=$project_id&error=goal_too_small");
        exit;
    }
    
    // Process image upload if provided
    $image_path = $project['image_path']; // Default to current image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
        finfo_close($file_info);
        
        if (!in_array($file_type, $allowed_types)) {
            header("Location: ../../project-details-association.php?id=$project_id&error=invalid_image_type");
            exit;
        }
        
        $max_file_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['image']['size'] > $max_file_size) {
            header("Location: ../../project-details-association.php?id=$project_id&error=image_too_large");
            exit;
        }
        
        $upload_dir = '../../uploads/projects/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = 'project_' . $project_id . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        // If there's an existing image, delete it
        if (!empty($project['image_path'])) {
            $old_image_path = '../../' . $project['image_path'];
            if (file_exists($old_image_path) && is_file($old_image_path)) {
                unlink($old_image_path);
            }
        }
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image_path = 'uploads/projects/' . $file_name;
        } else {
            header("Location: ../../project-details-association.php?id=$project_id&error=image_upload_failed");
            exit;
        }
    }
    
    // Update project in database
    $stmt = $pdo->prepare("
        UPDATE project SET 
            title = ?, 
            description = ?, 
            category = ?, 
            goal_amount = ?, 
            end_date = ?, 
            status = ?,
            image_path = ?
        WHERE project_id = ? AND assoc_id = ?
    ");
    
    $stmt->execute([
        htmlspecialchars($title),
        htmlspecialchars($description),
        htmlspecialchars($category),
        $goal_amount,
        $end_date,
        $status,
        $image_path,
        $project_id,
        $_SESSION['user_id']
    ]);
    
    // Redirect back to project details page with success message
    header("Location: ../../project-details-association.php?id=$project_id&success=updated");
    exit;
    
} catch (PDOException $e) {
    error_log("Project update error: " . $e->getMessage());
    header("Location: ../../project-details-association.php?id=$project_id&error=database_error");
    exit;
} catch (Exception $e) {
    error_log("Project update error: " . $e->getMessage());
    header("Location: ../../project-details-association.php?id=$project_id&error=unexpected_error");
    exit;
}
?>
