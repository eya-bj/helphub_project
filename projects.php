<?php
// Start session
session_start();

// Check if user is logged in as donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header("Location: index.php?error=unauthorized"); // Updated link
    exit;
}

// Include database connection
require_once 'backend/db.php';

// Get search and filter parameters
$category_filter = $_GET['category'] ?? 'all';
$search_query = $_GET['search'] ?? '';

try {
    // Fetch active projects
    $query = "
        SELECT p.*, a.name as association_name 
        FROM project p
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.status = 'active' AND p.end_date >= CURDATE()
    ";
    $params = [];
    
    // Apply category filter
    if ($category_filter !== 'all') {
        $query .= " AND p.category = ?";
        $params[] = $category_filter;
    }
    
    // Apply search filter if provided
    if (!empty($search_query)) {
        $query .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $search_term = '%' . $search_query . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Order by newest first
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all available categories for the filter dropdown
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM project WHERE status = 'active' ORDER BY category");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    // Initialize empty arrays to avoid undefined variable errors
    $projects = [];
    $categories = [];
}

// Helper function to get appropriate Bootstrap color for categories
function getCategoryColor($category) {
    switch(strtolower($category)) {
        case 'education': return 'success';
        case 'environment': return 'info';
        case 'health': return 'danger';
        case 'poverty': return 'warning';
        case 'animals': return 'secondary';
        default: return 'primary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - HelpHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="dashboard-donor.php">
                <span class="text-primary fw-bold">Help</span><span class="text-secondary">Hub</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-donor.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile-donor.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="backend/auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="bg-primary text-white text-center py-5 mt-5">
        <div class="container py-4">
            <h1 class="display-4 fw-bold">Explore Projects</h1>
            <p class="lead mb-0">Discover meaningful projects that need your support</p>
        </div>
    </section>

    <!-- Project Search & Filter -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <!-- Search Bar -->
                <div class="col-lg-6">
                    <form action="projects.php" method="get" class="d-flex">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search projects..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <!-- Category Filter -->
                <div class="col-lg-6">
                    <form action="projects.php" method="get" class="d-flex">
                        <?php if (!empty($search_query)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php endif; ?>
                        <select name="category" class="form-select me-2" onchange="this.form.submit()">
                            <option value="all" <?php echo $category_filter == 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($category)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects Grid -->
    <section class="py-5">
        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($projects)): ?>
                <div class="alert alert-info">
                    <h4>No projects found</h4>
                    <p>Try adjusting your search criteria or check back later for new projects.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($projects as $project): 
                        // Calculate progress percentage
                        $progress = $project['goal_amount'] > 0 
                            ? round(($project['current_amount'] / $project['goal_amount']) * 100) 
                            : 0;
                            
                        // Calculate days remaining
                        $end_date = new DateTime($project['end_date']);
                        $today = new DateTime();
                        $days_remaining = $today <= $end_date ? $end_date->diff($today)->days : 0;
                    ?>
                    <div class="col project-item" data-category="<?php echo htmlspecialchars($project['category']); ?>">
                        <div class="card h-100 border-0 shadow">
                            <?php if (!empty($project['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($project['image_path']); ?>" 
                                     class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($project['title']); ?>">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxzZWFyY2h8NHx8cGVvcGxlJTIwaGVscGluZ3xlbnwwfHwwfHw%3D&auto=format&fit=crop&w=800&q=60" 
                                     class="card-img-top" style="height: 200px; object-fit: cover;" alt="Default Project Image">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-<?php echo getCategoryColor($project['category']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($project['category'])); ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo $days_remaining; ?> days left
                                    </span>
                                </div>
                                <h5 class="card-title mb-3"><?php echo htmlspecialchars($project['title']); ?></h5>
                                <p class="card-text"><?php echo substr(htmlspecialchars($project['description']), 0, 100) . '...'; ?></p>
                                <div class="mt-auto pt-3">
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?php echo getCategoryColor($project['category']); ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $progress; ?>%" 
                                             aria-valuenow="<?php echo $progress; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mb-3">
                                        <span>$<?php echo number_format($project['current_amount'], 2); ?> raised</span>
                                        <span><?php echo $progress; ?>% of $<?php echo number_format($project['goal_amount'], 2); ?></span>
                                    </div>
                                    <a href="project-details-donor.php?id=<?php echo $project['project_id']; ?>" class="btn btn-primary w-100">Learn More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Pagination -->
    <?php if (!empty($projects)): ?>
    <section class="py-3">
        <div class="container">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><span class="text-primary">Help</span><span class="text-light">Hub</span> © <?php echo date('Y'); ?></h5>
                    <p>Connecting hearts and resources for a better world.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="privacy-policy.html" class="text-light">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="terms-of-use.html" class="text-light">Terms of Use</a></li>
                        <li class="list-inline-item"><a href="contact.html" class="text-light">Contact Us</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
