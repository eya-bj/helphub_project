<?php
session_start();
require_once '../db.php'; 
// Check if user is logged in and request is POST
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.php?error=unauthorized"); // Updated link
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$profile_page = ($user_type === 'donor') ? '../../profile-donor.php' : '../../profile-association.php';

// Get confirmation text
$confirm_text = $_POST['confirm_text'] ?? '';

// Validate confirmation text
if ($confirm_text !== 'DELETE') {
    header("Location: $profile_page?error=confirm_delete");
    exit;
}

try {
    // Determine table and ID column based on user type
    $table = ($user_type === 'donor') ? 'donor' : 'association';
    $id_column = ($user_type === 'donor') ? 'donor_id' : 'assoc_id';

    // Begin transaction
    $pdo->beginTransaction();



    if ($user_type === 'association') {

        // Delete logo file if exists
        $stmt_logo = $pdo->prepare("SELECT logo_path FROM association WHERE assoc_id = ?");
        $stmt_logo->execute([$user_id]);
        $logo_relative = $stmt_logo->fetchColumn();
        if ($logo_relative) {
            $logo_absolute = '../../' . $logo_relative; // Adjust path relative to this script
             if (file_exists($logo_absolute)) {
                 unlink($logo_absolute);
             }
        }
    }
     elseif ($user_type === 'donor') {

    }

    // Delete the user record
    $stmt_delete = $pdo->prepare("DELETE FROM $table WHERE $id_column = ?");
    $success = $stmt_delete->execute([$user_id]);

    if ($success) {
        // Commit transaction
        $pdo->commit();

        // Destroy session and redirect to homepage
        session_destroy();
        header("Location: ../../index.php?message=account_deleted"); 
    } else {
        // Rollback transaction
        $pdo->rollBack();
        header("Location: $profile_page?error=delete_failed");
        exit;
    }

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Account deletion error: " . $e->getMessage());
    header("Location: $profile_page?error=db_error");
    exit;
}
?>
