<?php
require_once '../db.php';

try {
    $query = "
        SELECT p.*, a.name as association_name 
        FROM project p
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.status = 'active'
        AND p.end_date >= CURDATE()
    ";
    $params = [];

    if (!empty($_GET['category'])) {
        $query .= " AND p.category = ?";
        $params[] = htmlspecialchars($_GET['category']);
    }

    if (!empty($_GET['search'])) {
        $search_term = '%' . htmlspecialchars($_GET['search']) . '%';
        $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR a.name LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    switch ($sort) {
        case 'goal':
            $query .= " ORDER BY p.goal_amount DESC";
            break;
        case 'progress':
            $query .= " ORDER BY (p.current_amount / p.goal_amount) DESC";
            break;
        case 'ending_soon':
            $query .= " ORDER BY p.end_date ASC";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY p.created_at DESC";
            break;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($projects);

} catch (PDOException $e) {
    error_log("Error fetching projects: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error occurred.']);
}
?>
