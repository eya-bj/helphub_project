<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header("Location: ../../index.php?error=unauthorized"); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard-association.php?error=invalid_method");
    exit;
}

require_once '../db.php';

$project_id = $_POST['project_id'] ?? null;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? '';
$goal_amount = $_POST['goal_amount'] ?? 0;
$end_date = $_POST['end_date'] ?? '';
$status = $_POST['status'] ?? 'active';

if (!$project_id || !is_numeric($project_id)) {
    header("Location: ../../dashboard-association.php?error=invalid_project");
    exit;
}

$required_fields = ['title', 'description', 'category', 'goal_amount', 'end_date', 'status'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header("Location: ../../project-details-association.php?id=$project_id&error=missing_fields");
        exit;
    }
}

if (!is_numeric($goal_amount) || $goal_amount <= 0) {
    header("Location: ../../project-details-association.php?id=$project_id&error=invalid_goal");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM project WHERE project_id = ? AND assoc_id = ?");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        header("Location: ../../dashboard-association.php?error=project_not_found");
        exit;
    }
    
    if ($goal_amount < $project['current_amount']) {
        header("Location: ../../project-details-association.php?id=$project_id&error=goal_too_small");
        exit;
    }
    
    $image_path = $project['image_path']; 
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
        finfo_close($file_info);
        
        if (!in_array($file_type, $allowed_types)) {
            header("Location: ../../project-details-association.php?id=$project_id&error=invalid_file_type");
            exit;
        }

        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['image']['size'] > $max_size) {
            header("Location: ../../project-details-association.php?id=$project_id&error=file_too_large");
            exit;
        }

        $upload_dir = '../../uploads/projects/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if ($image_path && file_exists('../../' . $image_path)) {
            unlink('../../' . $image_path);
        }

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'project_' . $project_id . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'uploads/projects/' . $new_filename;
        } else {
            header("Location: ../../project-details-association.php?id=$project_id&error=upload_failed");
            exit;
        }
    }

    $sql = "UPDATE project SET 
                title = ?, 
                description = ?, 
                category = ?, 
                goal_amount = ?, 
                end_date = ?, 
                status = ?, 
                image_path = ? 
            WHERE project_id = ? AND assoc_id = ?";
            
    $params = [
        htmlspecialchars($title),
        htmlspecialchars($description),
        htmlspecialchars($category),
        $goal_amount,
        $end_date,
        htmlspecialchars($status),
        $image_path,
        $project_id,
        $_SESSION['user_id']
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header("Location: ../../project-details-association.php?id=$project_id&success=project_updated");
    exit;

} catch (PDOException $e) {
    error_log("Project Update Error: " . $e->getMessage());
    header("Location: ../../project-details-association.php?id=$project_id&error=db_error");
    exit;
} catch (Exception $e) {
    error_log("File Upload/Date Error: " . $e->getMessage());
    header("Location: ../../project-details-association.php?id=$project_id&error=general_error");
    exit;
}
?>
