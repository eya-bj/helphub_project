<?php
/**
 * Add Project Endpoint
 * 
 * Allows associations to create new projects
 * Method: POST
 * Data: title, description, category, goal_amount, start_date, end_date, image (file)
 */

// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log attempts
$log_file = '../project_ops_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Add Project attempt\n" . "POST data: " . print_r($_POST, true) . "\nFILES data: " . print_r($_FILES, true) . "\nSession: " . print_r($_SESSION, true) . "\n\n", FILE_APPEND);


// Check if user is logged in as association
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    // Redirect to login or index if not authorized
    header('Location: ../../index.html?error=unauthorized');
    exit;
}

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../dashboard-association.html?error=invalid_method#addProjectModal');
    exit;
}

// Connect to database
require_once '../db.php';

// Check for required fields
$required_fields = ['title', 'description', 'category', 'goal_amount', 'start_date', 'end_date']; // Removed name attributes from HTML, need to add them back
$missing_fields = [];
foreach ($required_fields as $field) {
    // Check POST data for field names matching the required fields
    if (empty($_POST[$field])) { 
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    header('Location: ../../dashboard-association.html?error=missing_fields&fields=' . implode(',', $missing_fields) . '#addProjectModal');
    exit;
}

// --- Basic Server-Side Validation ---
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$category = trim($_POST['category']);
$goal_amount = $_POST['goal_amount'];
$start_date_str = $_POST['start_date'];
$end_date_str = $_POST['end_date'];

// Validate amount
if (!is_numeric($goal_amount) || $goal_amount < 100) { // Assuming min goal is 100
    header('Location: ../../dashboard-association.html?error=invalid_amount#addProjectModal');
    exit;
}

// Validate dates
try {
    $start_date = new DateTime($start_date_str);
    $end_date = new DateTime($end_date_str);
    $today = new DateTime(); // Today's date without time

    if ($end_date < $start_date || $start_date < $today->setTime(0,0,0)) {
        header('Location: ../../dashboard-association.html?error=invalid_dates#addProjectModal');
        exit;
    }
} catch (Exception $e) {
    header('Location: ../../dashboard-association.html?error=invalid_dates#addProjectModal');
    exit;
}


try {
    // Process image upload if provided
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            header('Location: ../../dashboard-association.html?error=invalid_file_type#addProjectModal');
            exit;
        }
        
        $upload_dir = '../../uploads/projects/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid('proj_', true) . '_' . basename($_FILES['image']['name']);
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image_path = 'uploads/projects/' . $file_name; // Relative path for DB
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Project image upload failed.\n\n", FILE_APPEND);
            header('Location: ../../dashboard-association.html?error=upload_failed#addProjectModal');
            exit;
        }
    }

    // Insert project
    $stmt = $pdo->prepare("
        INSERT INTO project (
            assoc_id, title, description, category, goal_amount, current_amount, 
            start_date, end_date, image_path, status
        ) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, 'active')
    ");

    $success = $stmt->execute([
        $_SESSION['user_id'],
        htmlspecialchars($title),
        htmlspecialchars($description),
        htmlspecialchars($category),
        $goal_amount,
        $start_date->format('Y-m-d'), // Format date for DB
        $end_date->format('Y-m-d'),   // Format date for DB
        $image_path
    ]);

    if ($success) {
        $project_id = $pdo->lastInsertId();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Project added successfully: ID=" . $project_id . "\n\n", FILE_APPEND);
        header('Location: ../../dashboard-association.html?success=project_added');
        exit;
    } else {
        $errorInfo = $stmt->errorInfo();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Project insert failed: " . print_r($errorInfo, true) . "\n\n", FILE_APPEND);
        header('Location: ../../dashboard-association.html?error=database_error&code=' . $errorInfo[1] . '#addProjectModal');
        exit;
    }

} catch (PDOException $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - PDO Exception: " . $e->getMessage() . "\n\n", FILE_APPEND);
    header('Location: ../../dashboard-association.html?error=database_error&msg=' . urlencode($e->getCode()) . '#addProjectModal');
    exit;
}
?>
