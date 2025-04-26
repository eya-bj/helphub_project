<?php
/**
 * Association Profile Update Endpoint
 * 
 * Handles updates to association profile information.
 * Method: POST
 * Data: representative_name, representative_surname, email, name (assoc), address, logo (file)
 */

// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log attempts
$log_file = '../profile_update_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Association Profile Update attempt\n" . "POST data: " . print_r($_POST, true) . "\nFILES data: " . print_r($_FILES, true) . "\nSession: " . print_r($_SESSION, true) . "\n\n", FILE_APPEND);

// Check if user is logged in as association
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header('Location: ../../index.html?error=unauthorized');
    exit;
}

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../profile-association.html?error=invalid_method');
    exit;
}

// Connect to database
require_once '../db.php';

// Check for required fields
$required_fields = ['representative_name', 'representative_surname', 'email', 'name', 'address'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}
if (!empty($missing_fields)) {
    header('Location: ../../profile-association.html?error=missing_fields&fields=' . implode(',', $missing_fields));
    exit;
}

// --- Basic Server-Side Validation ---
$rep_name = trim($_POST['representative_name']);
$rep_surname = trim($_POST['representative_surname']);
$email = trim($_POST['email']);
$assoc_name = trim($_POST['name']);
$address = trim($_POST['address']);
$assoc_id = $_SESSION['user_id'];

if (strlen($rep_name) < 2 || strlen($rep_surname) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($assoc_name) < 3 || strlen($address) < 5) {
     header('Location: ../../profile-association.html?error=validation_failed');
     exit;
}

try {
    // Check if email is being changed and if the new email already exists for another association
    $stmt = $pdo->prepare("SELECT email, logo_path FROM association WHERE assoc_id = ?");
    $stmt->execute([$assoc_id]);
    $current_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_email = $current_data['email'];
    $current_logo_path = $current_data['logo_path'];

    if ($email !== $current_email) {
        $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE email = ? AND assoc_id != ?");
        $stmt->execute([$email, $assoc_id]);
        if ($stmt->fetch()) {
            header('Location: ../../profile-association.html?error=email_exists');
            exit;
        }
    }

    // Process logo upload if provided
    $logo_path_to_update = $current_logo_path; // Keep old logo unless new one is uploaded
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['logo']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            header('Location: ../../profile-association.html?error=invalid_file_type');
            exit;
        }
        
        $upload_dir = '../../uploads/logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid('logo_', true) . '_' . basename($_FILES['logo']['name']);
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $file_path)) {
            // Delete old logo file if it exists and is different
            if ($current_logo_path && file_exists('../../' . $current_logo_path)) {
                unlink('../../' . $current_logo_path);
            }
            $logo_path_to_update = 'uploads/logos/' . $file_name; // New relative path
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Logo update upload failed.\n\n", FILE_APPEND);
            header('Location: ../../profile-association.html?error=upload_failed');
            exit;
        }
    }

    // Update association information
    $stmt = $pdo->prepare("
        UPDATE association 
        SET representative_name = ?, representative_surname = ?, email = ?, 
            name = ?, address = ?, logo_path = ?
        WHERE assoc_id = ?
    ");

    $success = $stmt->execute([
        $rep_name, $rep_surname, $email, $assoc_name, $address, $logo_path_to_update, $assoc_id
    ]);

    if ($success) {
        // Update session name if needed
        $_SESSION['user_name'] = $rep_name; 
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Association profile updated successfully: ID=" . $assoc_id . "\n\n", FILE_APPEND);
        header('Location: ../../profile-association.html?success=profile_updated');
        exit;
    } else {
        $errorInfo = $stmt->errorInfo();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Association profile update failed: " . print_r($errorInfo, true) . "\n\n", FILE_APPEND);
        header('Location: ../../profile-association.html?error=database_error&code=' . $errorInfo[1]);
        exit;
    }

} catch (PDOException $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - PDO Exception: " . $e->getMessage() . "\n\n", FILE_APPEND);
    header('Location: ../../profile-association.html?error=database_error&msg=' . urlencode($e->getCode()));
    exit;
}
?>
