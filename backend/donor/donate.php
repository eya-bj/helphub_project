<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header("Location: ../../index.php?error=unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../projects.php");
    exit;
}

require_once '../db.php';

$project_id = $_POST['project_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$anonymous = isset($_POST['anonymous']) ? 1 : 0;
$donor_id = $_SESSION['user_id'];

if (!$project_id || !is_numeric($project_id) || !$amount || !is_numeric($amount) || $amount <= 0) {
    header("Location: ../../project-details-donor.php?id=$project_id&error=invalid_input");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT status, end_date FROM project WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        header("Location: ../../projects.php?error=project_not_found");
        exit;
    }

    if ($project['status'] !== 'active' || new DateTime() > new DateTime($project['end_date'])) {
        header("Location: ../../project-details-donor.php?id=$project_id&error=project_inactive");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO donation (donor_id, project_id, amount, anonymous)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$donor_id, $project_id, $amount, $anonymous]);
    $donation_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        UPDATE project
        SET current_amount = current_amount + ?
        WHERE project_id = ?
    ");
    $stmt->execute([$amount, $project_id]);

    $stmt = $pdo->prepare("SELECT current_amount, goal_amount FROM project WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $updated_project = $stmt->fetch();

    if ($updated_project['current_amount'] >= $updated_project['goal_amount']) {
        $stmt = $pdo->prepare("UPDATE project SET status = 'funded' WHERE project_id = ?");
        $stmt->execute([$project_id]);
    }

    $pdo->commit();

    header("Location: ../../project-details-donor.php?id=$project_id&success=donation_successful");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Donation Error: " . $e->getMessage());
    header("Location: ../../project-details-donor.php?id=$project_id&error=donation_failed");
    exit;
}
?>
