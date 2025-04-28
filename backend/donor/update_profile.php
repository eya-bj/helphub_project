<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header("Location: ../../index.php?error=unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../profile-donor.php");
    exit;
}

$donor_id = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$surname = $_POST['surname'] ?? '';
$email = $_POST['email'] ?? '';
$profile_image_path = null;
$debug_message = "";

if (empty($name) || empty($surname) || empty($email)) {
    header('Location: ../../profile-donor.php?error=missing_fields');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../profile-donor.php?error=invalid_email');
    exit;
}

if (!preg_match('/^[a-zA-Z]{2,}$/', $name)) {
    header('Location: ../../profile-donor.php?error=invalid_name');
    exit;
}

if (!preg_match('/^[a-zA-Z]{2,}$/', $surname)) {
    header('Location: ../../profile-donor.php?error=invalid_surname');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT email, profile_image FROM donor WHERE donor_id = ?");
    $stmt->execute([$donor_id]);
    $current_donor = $stmt->fetch();

    if ($email !== $current_donor['email']) {
        $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header('Location: ../../profile-donor.php?error=email_exists');
            exit;
        }
    }

    $profile_image_path = $current_donor['profile_image'];

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/profile_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        $file_size = $_FILES['profile_image']['size'];
        $max_size = 2 * 1024 * 1024;

        if (!in_array($file_type, $allowed_types)) {
            header('Location: ../../profile-donor.php?error=invalid_file_type');
            exit;
        }

        if ($file_size > $max_size) {
            header('Location: ../../profile-donor.php?error=file_too_large');
            exit;
        }

        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'donor_' . $donor_id . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
            if ($profile_image_path && file_exists($profile_image_path) && strpos($profile_image_path, 'default') === false) {
                unlink($profile_image_path);
            }
            $profile_image_path = 'uploads/profile_images/' . $new_filename;
        } else {
            $debug_message = "Failed to move uploaded file.";
            header('Location: ../../profile-donor.php?error=upload_failed&debug=' . urlencode($debug_message));
            exit;
        }
    }

    $sql = "UPDATE donor SET name = ?, surname = ?, email = ?";
    $params = [$name, $surname, $email];

    if ($profile_image_path !== $current_donor['profile_image']) {
        $sql .= ", profile_image = ?";
        $params[] = $profile_image_path;
    }

    $sql .= " WHERE donor_id = ?";
    $params[] = $donor_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['user_name'] = $name;
    $_SESSION['profile_image'] = $profile_image_path;

    header("Location: ../../profile-donor.php?success=profile_updated");
    exit;

} catch (PDOException $e) {
    error_log("Donor Profile Update Error: " . $e->getMessage());
    header("Location: ../../profile-donor.php?error=db_error");
    exit;
}
?>
