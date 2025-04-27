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

// Get POST data - Use $_POST directly for forms with enctype="multipart/form-data"
// $data = json_decode(file_get_contents('php://input'), true);
// if (!$data) {
//     $data = $_POST;
// }
// Use $_POST instead of $data below

// Validate required fields
$required_fields = ['title', 'description', 'category', 'goal_amount', 'start_date', 'end_date'];
$missing_fields = []; // Initialize array
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

// Assign variables AFTER validation
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$category = trim($_POST['category']);
$goal_amount = $_POST['goal_amount'];
$start_date_str = $_POST['start_date'];
$end_date_str = $_POST['end_date'];


// Validate goal_amount
if (!is_numeric($goal_amount) || $goal_amount <= 0) {
    // Redirect back with error
    header('Location: ../../dashboard-association.html?error=invalid_goal#addProjectModal');
    exit;
    // echo json_encode(['error' => 'Goal amount must be a positive number']);
    // exit;
}

// Validate dates
try {
    $start_date = new DateTime($start_date_str);
    $end_date = new DateTime($end_date_str);
    $today = new DateTime(); // Consider timezone if necessary
    $today->setTime(0, 0, 0); // Set time to midnight for comparison

    // Optional: Check if start date is not in the past (unless allowed)
    // if ($start_date < $today) {
    //     header('Location: ../../dashboard-association.html?error=invalid_start_date_past#addProjectModal');
    //     exit;
    // }

    if ($end_date < $start_date) {
        // Redirect back with error
        header('Location: ../../dashboard-association.html?error=invalid_end_date#addProjectModal');
        exit;
        // echo json_encode(['error' => 'End date must be after start date']);
        // exit;
    }
} catch (Exception $e) {
    // Handle invalid date formats
    header('Location: ../../dashboard-association.html?error=invalid_date_format#addProjectModal');
    exit;
}

try {
    // Process image upload if provided
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed']);
            exit;
        }
        
        $upload_dir = '../../uploads/projects/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image_path = 'uploads/projects/' . $file_name;
        } else {
            echo json_encode(['error' => 'Failed to upload image']);
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

    $stmt->execute([
        $_SESSION['user_id'],
        htmlspecialchars($title),
        htmlspecialchars($description),
        htmlspecialchars($category),
        $goal_amount,
        $start_date_str,
        $end_date_str,
        $image_path
    ]);

    // Get the created project
    $project_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM project WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    // Return success
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => 'Project created successfully',
            'project' => $project
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to create project: ' . $e->getMessage()]);
    exit;
}
?>
