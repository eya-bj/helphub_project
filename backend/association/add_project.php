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

// Get POST data directly - removed JSON handling
$data = $_POST;

// Check for required fields (keeping this basic validation for security)
$required_fields = ['title', 'description', 'category', 'goal_amount', 'start_date', 'end_date'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['error' => "Field '$field' is required"]);
        exit;
    }
}

// Basic amount check (security check to prevent negative amounts)
if (!is_numeric($data['goal_amount']) || $data['goal_amount'] <= 0) {
    echo json_encode(['error' => 'Goal amount must be a positive number']);
    exit;
}

// Basic date check (critical to prevent logical errors)
$start_date = new DateTime($data['start_date']);
$end_date = new DateTime($data['end_date']);

if ($end_date < $start_date) {
    echo json_encode(['error' => 'End date must be after start date']);
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
        htmlspecialchars($data['title']),
        htmlspecialchars($data['description']),
        htmlspecialchars($data['category']),
        $data['goal_amount'],
        $data['start_date'],
        $data['end_date'],
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
