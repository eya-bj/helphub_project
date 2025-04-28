<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    echo json_encode(['error' => 'Unauthorized access. Please login as an association']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Only GET method is allowed']);
    exit;
}

if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    echo json_encode(['error' => 'Valid project_id parameter is required']);
    exit;
}

require_once '../db.php';

try {
    $assoc_id = $_SESSION['user_id'];
    $project_id = $_GET['project_id'];
    
    $stmt = $pdo->prepare("
        SELECT * FROM project 
        WHERE project_id = ? AND assoc_id = ?
    ");
    $stmt->execute([$project_id, $assoc_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        echo json_encode(['error' => 'Project not found or does not belong to your association']);
        exit;
    }
    
    $project['progress'] = $project['goal_amount'] > 0 
        ? round(($project['current_amount'] / $project['goal_amount']) * 100, 2) 
        : 0;
    
    $end_date = new DateTime($project['end_date']);
    $today = new DateTime();
    $project['days_remaining'] = $today <= $end_date ? $end_date->diff($today)->days : 0;
    
    $stmt = $pdo->prepare("
        SELECT d.amount, d.donation_date, d.anonymous, dn.name as donor_name, dn.surname as donor_surname, dn.profile_image as donor_image
        FROM donation d
        LEFT JOIN donor dn ON d.donor_id = dn.donor_id
        WHERE d.project_id = ?
        ORDER BY d.donation_date DESC
    ");
    $stmt->execute([$project_id]);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $total_donations = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT donor_id) FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $unique_donors = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT AVG(amount) FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $average_donation = $stmt->fetchColumn() ?? 0;
    
    $stmt = $pdo->prepare("SELECT MAX(amount) FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $largest_donation = $stmt->fetchColumn() ?? 0;
    
    $stmt = $pdo->prepare("SELECT MIN(amount) FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $smallest_donation = $stmt->fetchColumn() ?? 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'project' => $project,
            'donations' => $donations,
            'statistics' => [
                'total_donations' => $total_donations,
                'unique_donors' => $unique_donors,
                'average_donation' => round($average_donation, 2),
                'largest_donation' => $largest_donation,
                'smallest_donation' => $smallest_donation
            ]
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error fetching project details: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred.']);
    exit;
}
?>
