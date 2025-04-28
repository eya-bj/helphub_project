<?php
session_start();
require_once '../db.php'; // Adjust path as needed

// Check if user is logged in as a donor and request is POST
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.html?error=unauthorized");
    exit;
}

$donor_id = $_SESSION['user_id'];

// Get data from POST request
$name = trim($_POST['name'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$email = trim($_POST['email'] ?? '');

// Basic Validation (Add more robust validation as needed)
if (empty($name) || strlen($name) < 2 || empty($surname) || strlen($surname) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../profile-donor.php?error=invalid_input");
    exit;
}

$profile_image_path_to_save = null; 

try {
    // Check if email is already taken by another donor
    $stmt_check_email = $pdo->prepare("SELECT donor_id FROM donor WHERE email = ? AND donor_id != ?");
    $stmt_check_email->execute([$email, $donor_id]);
    if ($stmt_check_email->fetch()) {
        header("Location: ../../profile-donor.php?error=email_exists");
        exit;
    }

    // Handle profile image upload if provided
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_info, $_FILES['profile_image']['tmp_name']);
        finfo_close($file_info);

        $max_file_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file_type, $allowed_types)) {
            header("Location: ../../profile-donor.php?error=invalid_file_type");
            exit;
        }
        if ($_FILES['profile_image']['size'] > $max_file_size) {
            header("Location: ../../profile-donor.php?error=file_too_large");
            exit;
        }

        $upload_dir = '../../uploads/donors/'; 
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'donor_' . $donor_id . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        // Before moving, delete old image if it exists
        $stmt_old_image = $pdo->prepare("SELECT profile_image FROM donor WHERE donor_id = ?");
        $stmt_old_image->execute([$donor_id]);
        $old_image_relative = $stmt_old_image->fetchColumn();
        if ($old_image_relative) {
            $old_image_absolute = '../../' . $old_image_relative; 
            if (file_exists($old_image_absolute)) {
                unlink($old_image_absolute);
            }
        }

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $file_path)) {
            $profile_image_path_to_save = 'uploads/donors/' . $file_name; 
        } else {
            header("Location: ../../profile-donor.php?error=file_upload_error");
            exit;
        }
    }

    // Update donor information with or without new profile image
    if ($profile_image_path_to_save !== null) {
        $stmt = $pdo->prepare("UPDATE donor SET name = ?, surname = ?, email = ?, profile_image = ? WHERE donor_id = ?");
        $success = $stmt->execute([$name, $surname, $email, $profile_image_path_to_save, $donor_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE donor SET name = ?, surname = ?, email = ? WHERE donor_id = ?");
        $success = $stmt->execute([$name, $surname, $email, $donor_id]);
    }

    if ($success) {
        // Update session name if needed
        $_SESSION['user_name'] = $name . ' ' . $surname;
        header("Location: ../../profile-donor.php?success=updated");
        exit;
    } else {
        // If update failed but image was uploaded, clean up
        if ($profile_image_path_to_save !== null && file_exists('../../' . $profile_image_path_to_save)) {
            unlink('../../' . $profile_image_path_to_save);
        }
        header("Location: ../../profile-donor.php?error=update_failed");
        exit;
    }

} catch (PDOException $e) {
    error_log("Donor update error: " . $e->getMessage());
    // Clean up uploaded image if there was a database error
    if ($profile_image_path_to_save !== null && file_exists('../../' . $profile_image_path_to_save)) {
        unlink('../../' . $profile_image_path_to_save);
    }
    header("Location: ../../profile-donor.php?error=db_error"); 
}
?>
