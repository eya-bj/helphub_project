<?php
session_start();
require_once '../db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.html?error=unauthorized");
    exit;
}

$assoc_id = $_SESSION['user_id'];

$rep_name = trim($_POST['representative_name'] ?? '');
$rep_surname = trim($_POST['representative_surname'] ?? '');
$email = trim($_POST['email'] ?? '');
$assoc_name = trim($_POST['name'] ?? '');
$assoc_address = trim($_POST['address'] ?? '');

if (empty($rep_name) || strlen($rep_name) < 2 || empty($rep_surname) || strlen($rep_surname) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($assoc_name) || strlen($assoc_name) < 3 || empty($assoc_address) || strlen($assoc_address) < 5) {
    header("Location: ../../profile-association.php?error=invalid_input");
    exit;
}

$logo_path_to_save = null; 

try {
    $stmt_check_email = $pdo->prepare("SELECT assoc_id FROM association WHERE email = ? AND assoc_id != ?");
    $stmt_check_email->execute([$email, $assoc_id]);
    if ($stmt_check_email->fetch()) {
        header("Location: ../../profile-association.php?error=email_exists");
        exit;
    }

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_info, $_FILES['logo']['tmp_name']);
        finfo_close($file_info);

        $max_file_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file_type, $allowed_types)) {
            header("Location: ../../profile-association.php?error=invalid_file_type");
            exit;
        }
        if ($_FILES['logo']['size'] > $max_file_size) {
             header("Location: ../../profile-association.php?error=file_too_large");
             exit;
        }

        $upload_dir = '../../uploads/logos/'; 
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $file_name = 'logo_' . $assoc_id . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        $stmt_old_logo = $pdo->prepare("SELECT logo_path FROM association WHERE assoc_id = ?");
        $stmt_old_logo->execute([$assoc_id]);
        $old_logo_relative = $stmt_old_logo->fetchColumn();
        if ($old_logo_relative) {
            $old_logo_absolute = '../../' . $old_logo_relative; 
             if (file_exists($old_logo_absolute)) {
                 unlink($old_logo_absolute);
             }
        }


        if (move_uploaded_file($_FILES['logo']['tmp_name'], $file_path)) {
            $logo_path_to_save = 'uploads/logos/' . $file_name; 
        } else {
            error_log("Failed to move uploaded file: " . $_FILES['logo']['error']);
            header("Location: ../../profile-association.php?error=file_upload_error");
            exit;
        }
    }

    if ($logo_path_to_save !== null) {
        $sql = "UPDATE association SET representative_name = ?, representative_surname = ?, email = ?, name = ?, address = ?, logo_path = ? WHERE assoc_id = ?";
        $params = [$rep_name, $rep_surname, $email, $assoc_name, $assoc_address, $logo_path_to_save, $assoc_id];
    } else {
        $sql = "UPDATE association SET representative_name = ?, representative_surname = ?, email = ?, name = ?, address = ? WHERE assoc_id = ?";
        $params = [$rep_name, $rep_surname, $email, $assoc_name, $assoc_address, $assoc_id];
    }

    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);

    if ($success) {
        $_SESSION['user_name'] = $assoc_name;
        if ($logo_path_to_save !== null) {
             $_SESSION['profile_image'] = $logo_path_to_save;
        }
        $_SESSION['user_contact'] = $rep_name . ' ' . $rep_surname;
        header("Location: ../../profile-association.php?success=updated");
        exit;
    } else {
        if ($logo_path_to_save !== null && file_exists('../../' . $logo_path_to_save)) {
            unlink('../../' . $logo_path_to_save);
        }
        header("Location: ../../profile-association.php?error=update_failed");
        exit;
    }

} catch (PDOException $e) {
    error_log("Association update error: " . $e->getMessage());
    if ($logo_path_to_save !== null && file_exists('../../' . $logo_path_to_save)) {
        unlink('../../' . $logo_path_to_save);
    }
    header("Location: ../../profile-association.php?error=db_error"); 
    exit;
}
?>
