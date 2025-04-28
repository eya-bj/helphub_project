<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../index.html?error=unauthorized");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$profile_page = ($user_type === 'donor') ? '../../profile-donor.php' : '../../profile-association.php';

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: $profile_page?error=missing_fields");
    exit;
}

if ($new_password !== $confirm_password) {
    header("Location: $profile_page?error=password_mismatch");
    exit;
}

if (strlen($new_password) < 8 || !(substr($new_password, -1) === '$' || substr($new_password, -1) === '#')) {
    header("Location: $profile_page?error=invalid_new_password");
    exit;
}

try {
    $table = ($user_type === 'donor') ? 'donor' : 'association';
    $id_column = ($user_type === 'donor') ? 'donor_id' : 'assoc_id';

    $stmt = $pdo->prepare("SELECT password FROM $table WHERE $id_column = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password'])) {
        header("Location: $profile_page?error=incorrect_current_password");
        exit;
    }

    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE $table SET password = ? WHERE $id_column = ?");
    $stmt->execute([$hashed_new_password, $user_id]);

    header("Location: $profile_page?success=password_changed");
    exit;

} catch (PDOException $e) {
    error_log("Password Change Error: " . $e->getMessage());
    header("Location: $profile_page?error=database_error");
    exit;
}
?>
