<?php
/**
 * My Donations Endpoint
 * 
 * Fetches donation history for the logged-in donor
 * Method: GET
 * Optional params: sort (newest|oldest|amount)
 */

// Start session
session_start();

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in as donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    echo json_encode(['error' => 'Unauthorized access. Please login as a donor']);
    exit;
}

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Only GET method is allowed']);
    exit;
}

// Get database connection
require_once '../db.php';

try {
    $donor_id = $_SESSION['user_id'];
    
    // Apply sorting
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    $order_by = "d.donation_date DESC"; // default newest
    
    switch ($sort) {
        case 'oldest':
            $order_by = "d.donation_date ASC";
            break;
        case 'amount':
            $order_by = "d.amount DESC";
            break;
    }
    
    // Get all donations with project and association details
    $stmt = $pdo->prepare("
        SELECT 
            d.donation_id, d.amount, d.anonymous, d.donation_date,
            p.project_id, p.title as project_title, p.category, p.goal_amount, p.current_amount, p.status,
            a.assoc_id, a.name as association_name
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE d.donor_id = ?
        ORDER BY $order_by
    ");
    $stmt->execute([$donor_id]);
    $donations = $stmt->fetchAll();
    
    // Calculate additional info for each donation
    foreach ($donations as &$donation) {
        // Calculate progress percentage
        $donation['progress'] = $donation['goal_amount'] > 0 
            ? round(($donation['current_amount'] / $donation['goal_amount']) * 100, 2) 
            : 0;
            
        // Format donation date
        $date = new DateTime($donation['donation_date']);
        $donation['formatted_date'] = $date->format('M d, Y');
    }
    
    // Get donation statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_donations,
            SUM(amount) as total_amount,
            COUNT(DISTINCT project_id) as projects_supported,
            MAX(amount) as largest_donation,
            MIN(amount) as smallest_donation,
            AVG(amount) as average_donation
        FROM donation
        WHERE donor_id = ?
    ");
    $stmt->execute([$donor_id]);
    $stats = $stmt->fetch();
    
    // Get associations supported
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT a.assoc_id) as associations_count
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE d.donor_id = ?
    ");
    $stmt->execute([$donor_id]);
    $assoc_count = $stmt->fetch();
    $stats['associations_supported'] = $assoc_count['associations_count'];
    
    // Return success
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
