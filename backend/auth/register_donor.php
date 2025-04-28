<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../register-donor.html');
    exit;
}

$name = $_POST['name'] ?? '';
$surname = $_POST['surname'] ?? '';
$email = $_POST['email'] ?? '';
$ctn = $_POST['ctn'] ?? '';
$pseudo = $_POST['pseudo'] ?? '';
$password = $_POST['password'] ?? '';
$terms = isset($_POST['terms']);

if (empty($name) || empty($surname) || empty($email) || empty($ctn) || empty($pseudo) || empty($password) || !$terms) {
    header('Location: ../../register-donor.html?error=missing_fields');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../register-donor.html?error=invalid_email');
    exit;
}

if (!preg_match('/^[a-zA-Z]{2,}$/', $name)) {
    header('Location: ../../register-donor.html?error=invalid_name');
    exit;
}

if (!preg_match('/^[a-zA-Z]{2,}$/', $surname)) {
    header('Location: ../../register-donor.html?error=invalid_surname');
    exit;
}

if (!preg_match('/^\d{8}$/', $ctn)) {
    header('Location: ../../register-donor.html?error=invalid_ctn');
    exit;
}

if (!preg_match('/^[a-zA-Z0-9]{3,}$/', $pseudo)) {
    header('Location: ../../register-donor.html?error=invalid_pseudo');
    exit;
}

if (strlen($password) < 8 || !(substr($password, -1) === '$' || substr($password, -1) === '#')) {
    header('Location: ../../register-donor.html?error=invalid_password');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../../register-donor.html?error=email_exists');
        exit;
    }

    $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE pseudo = ?");
    $stmt->execute([$pseudo]);
    if ($stmt->fetch()) {
        header('Location: ../../register-donor.html?error=pseudo_exists');
        exit;
    }

    $stmt = $pdo->prepare("SELECT donor_id FROM donor WHERE ctn = ?");
    $stmt->execute([$ctn]);
    if ($stmt->fetch()) {
        header('Location: ../../register-donor.html?error=ctn_exists');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO donor (name, surname, email, ctn, pseudo, password)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $surname, $email, $ctn, $pseudo, $hashed_password]);

    header('Location: ../../index.php?register=success_donor#loginModal');
    exit;

} catch (PDOException $e) {
    error_log("Donor Registration Error: " . $e->getMessage());
    header('Location: ../../register-donor.html?error=db_error');
    exit;
}
?>
