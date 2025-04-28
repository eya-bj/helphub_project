<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    echo json_encode(['error' => 'Unauthorized access. Please login as a donor']);
    exit;
}

require_once '../db.php';

$donor_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            d.donation_id, d.amount, d.donation_date, d.anonymous,
            p.project_id, p.title as project_title, p.image_path as project_image, p.status as project_status,
            a.name as association_name
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE d.donor_id = ?
        ORDER BY d.donation_date DESC
    ");
    $stmt->execute([$donor_id]);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [
        'total_donated' => 0,
        'projects_supported' => 0,
        'associations_supported' => 0,
        'average_donation' => 0,
        'largest_donation' => 0,
        'first_donation_date' => null,
        'last_donation_date' => null
    ];

    if (!empty($donations)) {
        $total_amount = 0;
        $project_ids = [];
        $assoc_ids = [];
        $amounts = [];
        $dates = [];

        foreach ($donations as $donation) {
            $total_amount += $donation['amount'];
            $project_ids[$donation['project_id']] = true;
            $assoc_ids[$donation['association_name']] = true; 
            $amounts[] = $donation['amount'];
            $dates[] = strtotime($donation['donation_date']);
        }

        $stats['total_donated'] = $total_amount;
        $stats['projects_supported'] = count($project_ids);
        $stats['average_donation'] = round($total_amount / count($donations), 2);
        $stats['largest_donation'] = max($amounts);
        $stats['first_donation_date'] = date('Y-m-d', min($dates));
        $stats['last_donation_date'] = date('Y-m-d', max($dates));
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.assoc_id) as associations_count
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE d.donor_id = ?
    ");
    $stmt->execute([$donor_id]);
    $assoc_count = $stmt->fetch();
    $stats['associations_supported'] = $assoc_count['associations_count'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'donations' => $donations,
            'count' => count($donations),
            'statistics' => $stats
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch donations: ' . $e->getMessage()]);
    exit;
}
?>
