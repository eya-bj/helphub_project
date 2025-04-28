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

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? '';
$goal_amount = $_POST['goal_amount'] ?? 0;
$start_date_str = $_POST['start_date'] ?? '';
$end_date_str = $_POST['end_date'] ?? '';
$image_path = null;

$required_fields = ['title', 'description', 'category', 'goal_amount', 'start_date', 'end_date'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header('Location: ../../dashboard-association.php?error=missing_fields#addProjectModal'); // Updated link
        exit;
    }
}

if (!is_numeric($goal_amount) || $goal_amount <= 0) {
    header('Location: ../../dashboard-association.php?error=invalid_goal#addProjectModal'); // Updated link
    exit;
}

try {
    $start_date = new DateTime($start_date_str);
    $end_date = new DateTime($end_date_str);
    if ($start_date >= $end_date) {
        header('Location: ../../dashboard-association.php?error=invalid_dates#addProjectModal'); // Updated link
        exit;
    }
} catch (Exception $e) {
    header('Location: ../../dashboard-association.php?error=invalid_date_format#addProjectModal'); // Updated link
    exit;
}

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
    finfo_close($file_info);

    if (!in_array($file_type, $allowed_types)) {
        header('Location: ../../dashboard-association.php?error=invalid_file_type#addProjectModal'); // Updated link
        exit;
    }

    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['image']['size'] > $max_size) {
         header('Location: ../../dashboard-association.php?error=file_too_large#addProjectModal'); // Updated link
         exit;
    }

    $upload_dir = '../../uploads/projects/';

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

$project_id = $pdo->lastInsertId();
$stmt = $pdo->prepare("SELECT * FROM project WHERE project_id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

echo json_encode([
    'success' => true,
    'data' => [
        'message' => 'Project created successfully',
        'project' => $project
    ]
]);

header('Location: ../../dashboard-association.php?success=project_added'); // Updated link
exit;

} catch (PDOException $e) {
    error_log("Project add error: " . $e->getMessage());
    if ($image_path !== null && file_exists('../../' . $image_path)) {
        unlink('../../' . $image_path);
    }
    echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
    exit;
}
?>
