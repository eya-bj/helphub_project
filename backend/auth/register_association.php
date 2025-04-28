<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../register-association.html');
    exit;
}

$representative_name = $_POST['representative_name'] ?? '';
$representative_surname = $_POST['representative_surname'] ?? '';
$cin = $_POST['cin'] ?? '';
$email = $_POST['email'] ?? '';
$name = $_POST['name'] ?? '';
$address = $_POST['address'] ?? '';
$fiscal_id = $_POST['fiscal_id'] ?? '';
$pseudo = $_POST['pseudo'] ?? '';
$password = $_POST['password'] ?? '';
$terms = isset($_POST['terms']);
$logo_path = null;

if (empty($representative_name) || empty($representative_surname) || empty($cin) || empty($email) || empty($name) || empty($address) || empty($fiscal_id) || empty($pseudo) || empty($password) || !$terms) {
    header('Location: ../../register-association.html?error=missing_fields');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../register-association.html?error=invalid_email');
    exit;
}

if (!preg_match('/^[a-zA-Z]{2,}$/', $representative_name)) {
    header('Location: ../../register-association.html?error=invalid_rep_name');
    exit;
}

if (!preg_match('/^[a-zA-Z]{2,}$/', $representative_surname)) {
    header('Location: ../../register-association.html?error=invalid_rep_surname');
    exit;
}

if (!preg_match('/^\d{8}$/', $cin)) {
    header('Location: ../../register-association.html?error=invalid_cin');
    exit;
}

if (!preg_match('/^[a-zA-Z0-9]{3,}$/', $pseudo)) {
    header('Location: ../../register-association.html?error=invalid_pseudo');
    exit;
}

if (strlen($password) < 8 || !(substr($password, -1) === '$' || substr($password, -1) === '#')) {
    header('Location: ../../register-association.html?error=invalid_password');
    exit;
}

if (!preg_match('/^\$[A-Z]{3}\d{2}$/', $fiscal_id)) {
    header('Location: ../../register-association.html?error=invalid_fiscal_id');
    exit;
}

if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
    $upload_dir = '../../uploads/logos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_info = pathinfo($_FILES['logo']['name']);
    $extension = strtolower($file_info['extension']);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($extension, $allowed_extensions)) {
        header('Location: ../../register-association.html?error=invalid_logo_type');
        exit;
    }

    if ($_FILES['logo']['size'] > $max_size) {
        header('Location: ../../register-association.html?error=logo_too_large');
        exit;
    }

    $new_filename = uniqid('logo_', true) . '.' . $extension;
    $destination = $upload_dir . $new_filename;

    if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
        $logo_path = 'uploads/logos/' . $new_filename;
    } else {
        header('Location: ../../register-association.html?error=logo_upload_failed');
        exit;
    }
}

try {
    $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../../register-association.html?error=email_exists');
        exit;
    }

    $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE pseudo = ?");
    $stmt->execute([$pseudo]);
    if ($stmt->fetch()) {
        header('Location: ../../register-association.html?error=pseudo_exists');
        exit;
    }

    $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE cin = ?");
    $stmt->execute([$cin]);
    if ($stmt->fetch()) {
        header('Location: ../../register-association.html?error=cin_exists');
        exit;
    }

    $stmt = $pdo->prepare("SELECT assoc_id FROM association WHERE fiscal_id = ?");
    $stmt->execute([$fiscal_id]);
    if ($stmt->fetch()) {
        header('Location: ../../register-association.html?error=fiscal_id_exists');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO association (representative_name, representative_surname, cin, email, name, address, fiscal_id, pseudo, password, logo_path)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $representative_name,
        $representative_surname,
        $cin,
        $email,
        $name,
        $address,
        $fiscal_id,
        $pseudo,
        $hashed_password,
        $logo_path
    ]);

    header('Location: ../../index.php?register=success_association#loginModal');
    exit;

} catch (PDOException $e) {
    error_log("Association Registration Error: " . $e->getMessage());
    header('Location: ../../register-association.html?error=registration_failed');
    exit;
}
?>
