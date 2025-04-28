<?php
// Start session
session_start();

// Check if user is logged in as donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header("Location: index.php?error=unauthorized"); // Updated link
    exit;
}

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: projects.php?error=invalid_project");
    exit;
}

$project_id = $_GET['id'];
$donor_id = $_SESSION['user_id'];
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// Include database connection
require_once 'backend/db.php';

try {
    // Fetch project details
    $stmt = $pdo->prepare("
        SELECT p.*, a.name as association_name, a.logo_path as association_logo, a.email as association_email 
        FROM project p
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.project_id = ? AND p.status = 'active'
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if project exists
    if (!$project) {
        header("Location: projects.php?error=project_not_found");
        exit;
    }
    
    // Calculate progress percentage
    $progress = $project['goal_amount'] > 0 
        ? round(($project['current_amount'] / $project['goal_amount']) * 100) 
        : 0;
    
    // Format dates
    $start_date = date("F j, Y", strtotime($project['start_date']));
    $end_date = date("F j, Y", strtotime($project['end_date']));
    
    // Calculate days remaining
    $end_date_obj = new DateTime($project['end_date']);
    $today = new DateTime();
    $days_remaining = $today <= $end_date_obj ? $end_date_obj->diff($today)->days : 0;
    
    // Get donation count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $donations_count = $stmt->fetchColumn();
    
    // Get unique donors count
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT donor_id) FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $donors_count = $stmt->fetchColumn();
    
    // Check if the logged-in donor has already donated to this project
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM donation 
        WHERE donor_id = ? AND project_id = ?
    ");
    $stmt->execute([$donor_id, $project_id]);
    $has_donated = ($stmt->fetchColumn() > 0);
    
    // Get similar projects (same category, excluding current)
    $stmt = $pdo->prepare("
        SELECT p.project_id, p.title, p.description, p.category, p.goal_amount, p.current_amount, p.end_date, p.image_path 
        FROM project p
        WHERE p.category = ? AND p.project_id != ? AND p.status = 'active' AND p.end_date >= CURDATE()
        LIMIT 2
    ");
    $stmt->execute([$project['category'], $project_id]);
    $similar_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get donor's information for the donation form
    $stmt = $pdo->prepare("SELECT name, surname, email FROM donor WHERE donor_id = ?");
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    header("Location: projects.php?error=database_error");
    exit;
}

// Helper functions
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

function getCategoryIcon($category) {
    switch(strtolower($category)) {
        case 'education': return 'graduation-cap';
        case 'environment': return 'tree';
        case 'health': return 'heartbeat';
        case 'poverty': return 'home';
        case 'animals': return 'paw';
        default: return 'hands-helping';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details - <?php echo htmlspecialchars($project['title']); ?> - HelpHub</title>
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
                        <a class="nav-link" href="dashboard-donor.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile-donor.php">
                            <i class="fas fa-user-circle me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="backend/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Project Details -->
    <div class="container py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    switch($error) {
                        case 'invalid_amount':
                            echo 'Please enter a valid donation amount.';
                            break;
                        case 'insufficient_funds':
                            echo 'You do not have sufficient funds for this donation.';
                            break;
                        case 'donation_failed':
                            echo 'There was an error processing your donation. Please try again.';
                            break;
                        default:
                            echo 'An error occurred. Please try again.';
                    }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    switch($success) {
                        case 'donation_complete':
                            echo 'Your donation has been successfully processed. Thank you for your generosity!';
                            break;
                        default:
                            echo 'Operation completed successfully.';
                    }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="mb-3">
            <a href="projects.php" class="btn btn-outline-primary" id="backBtn">
                <i class="fas fa-arrow-left me-2"></i> Back to Projects
            </a>
        </div>
        
        <!-- Project Header Card -->
        <div class="card mb-4">
            <div class="row g-0">
                <div class="col-md-5">
                    <?php if (!empty($project['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($project['image_path']); ?>" 
                             alt="Project Image" class="img-fluid rounded" style="width: 100%; height: 100%; max-height: 400px; object-fit: cover;">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1588421357574-87938a86fa28?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80" 
                             alt="Default Project Image" class="img-fluid rounded" style="width: 100%; height: 100%; max-height: 400px; object-fit: cover;">
                    <?php endif; ?>
                </div>
                <div class="col-md-7">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge bg-<?php echo getCategoryColor($project['category']); ?>">
                                <?php echo ucfirst(htmlspecialchars($project['category'])); ?>
                            </span>
                            <span class="badge bg-light text-dark border">
                                <?php echo $days_remaining > 0 ? "{$days_remaining} days left" : "Ended"; ?>
                            </span>
                        </div>
                        <h1 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($project['title']); ?></h1>
                        <p class="text-muted mb-3">By <a href="#" class="text-decoration-none fw-bold">
                            <?php echo htmlspecialchars($project['association_name']); ?></a>
                        </p>
                        <p class="mb-4"><?php echo nl2br(htmlspecialchars(substr($project['description'], 0, 200))); ?>...</p>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4 mb-2">
                                <div class="bg-light p-3 text-center rounded h-100">
                                    <h4 class="fw-bold text-primary mb-0">$<?php echo number_format($project['current_amount'], 2); ?></h4>
                                    <span class="small text-muted">Raised</span>
                                </div>
                            </div>
                            <div class="col-sm-4 mb-2">
                                <div class="bg-light p-3 text-center rounded">
                                    <h4 class="fw-bold mb-0">$<?php echo number_format($project['goal_amount'], 2); ?></h4>
                                    <span class="small text-muted">Goal</span>
                                </div>
                            </div>
                            <div class="col-sm-4 mb-2">
                                <div class="bg-light p-3 text-center rounded">
                                    <h4 class="fw-bold mb-0"><?php echo $donors_count; ?></h4>
                                    <span class="small text-muted">Donors</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="progress mb-2" style="height: 10px">
                            <div class="progress-bar bg-<?php echo getCategoryColor($project['category']); ?>" role="progressbar" 
                                style="width: <?php echo $progress; ?>%" 
                                aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-end mb-4 small"><?php echo $progress; ?>% Complete - 
                            $<?php echo number_format($project['goal_amount'] - $project['current_amount'], 2); ?> to go</p>
                        
                        <div class="d-grid">
                            <?php if ($days_remaining > 0 && $project['goal_amount'] > $project['current_amount']): ?>
                                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#donationModal">
                                    <i class="fas fa-hand-holding-heart me-2"></i> Donate Now
                                </button>
                            <?php elseif ($project['goal_amount'] <= $project['current_amount']): ?>
                                <button class="btn btn-success btn-lg" disabled>
                                    <i class="fas fa-check-circle me-2"></i> Funding Goal Achieved
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg" disabled>
                                    <i class="fas fa-clock me-2"></i> Project Ended
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($has_donated): ?>
                                <div class="alert alert-success mt-3 mb-0">
                                    <i class="fas fa-heart me-2"></i> You've already supported this project. Thank you!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Project Details Tabs -->
        <div class="card mb-4">
            <div class="card-body">
                <ul class="nav nav-tabs" id="projectTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" 
                                type="button" role="tab" aria-controls="details" aria-selected="true">
                            <i class="fas fa-info-circle me-2"></i> Details
                        </button>
                    </li>
                </ul>
                <div class="tab-content p-3" id="projectTabContent">
                    <!-- Details Tab -->
                    <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                        <h4 class="mb-4">About This Project</h4>
                        <div class="row">
                            <div class="col-lg-8">
                                <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                                
                                <?php if (strpos($project['description'], 'Approach') === false): ?>
                                <h5 class="mt-4 mb-3">Our Approach</h5>
                                <p>Our approach focuses on sustainability and community involvement.</p>
                                <?php endif; ?>
                                
                                <h5 class="mt-4 mb-3">Expected Impact</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="card border-0 bg-light h-100">
                                            <div class="card-body text-center">
                                                <div class="rounded-circle bg-primary bg-opacity-10 p-3 d-inline-flex mb-3" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-<?php echo getCategoryIcon($project['category']); ?> fa-2x m-auto text-primary"></i>
                                                </div>
                                                <h5 class="card-title">Impact</h5>
                                                <p class="card-text small">Making a difference in the <?php echo htmlspecialchars($project['category']); ?> sector</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 bg-light h-100">
                                            <div class="card-body text-center">
                                                <div class="rounded-circle bg-success bg-opacity-10 p-3 d-inline-flex mb-3" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-users fa-2x m-auto text-success"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo $donors_count; ?></h5>
                                                <p class="card-text small">Donors supporting this cause</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 bg-light h-100">
                                            <div class="card-body text-center">
                                                <div class="rounded-circle bg-info bg-opacity-10 p-3 d-inline-flex mb-3" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-dollar-sign fa-2x m-auto text-info"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo $progress; ?>%</h5>
                                                <p class="card-text small">Progress toward funding goal</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Project Details</h5>
                                        <ul class="list-group list-group-flush bg-transparent">
                                            <li class="list-group-item bg-transparent px-0 py-2 border-0 d-flex justify-content-between">
                                                <span class="text-muted">Location:</span>
                                                <span class="fw-medium"><?php echo htmlspecialchars($project['location'] ?? 'Not specified'); ?></span>
                                            </li>
                                            <li class="list-group-item bg-transparent px-0 py-2 border-0 d-flex justify-content-between">
                                                <span class="text-muted">Category:</span>
                                                <span class="fw-medium"><?php echo ucfirst(htmlspecialchars($project['category'])); ?></span>
                                            </li>
                                            <li class="list-group-item bg-transparent px-0 py-2 border-0 d-flex justify-content-between">
                                                <span class="text-muted">Start Date:</span>
                                                <span class="fw-medium"><?php echo $start_date; ?></span>
                                            </li>
                                            <li class="list-group-item bg-transparent px-0 py-2 border-0 d-flex justify-content-between">
                                                <span class="text-muted">End Date:</span>
                                                <span class="fw-medium"><?php echo $end_date; ?></span>
                                            </li>
                                            <li class="list-group-item bg-transparent px-0 py-2 border-0 d-flex justify-content-between">
                                                <span class="text-muted">Status:</span>
                                                <span class="badge bg-<?php echo $project['status'] == 'active' ? 'info' : 'secondary'; ?>">
                                                    <?php echo ucfirst($project['status']); ?>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Contact Information</h5>
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2">
                                                <i class="fas fa-building text-primary me-2"></i> 
                                                <?php echo htmlspecialchars($project['association_name']); ?>
                                            </li>
                                            <li>
                                                <i class="fas fa-envelope text-primary me-2"></i> 
                                                <?php echo htmlspecialchars($project['association_email']); ?>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Share Project -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-share-alt me-2"></i> Share This Project</h5>
                <p class="text-muted">Help spread the word about this project to reach more potential donors.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="fab fa-facebook-f me-2"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Support this project: ' . $project['title']); ?>&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-info">
                        <i class="fab fa-twitter me-2"></i> Twitter
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Support this project: ' . $project['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-success">
                        <i class="fab fa-whatsapp me-2"></i> WhatsApp
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode('Check out this project: ' . $project['title']); ?>&body=<?php echo urlencode('I thought you might be interested in supporting this project: ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-envelope me-2"></i> Email
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Similar Projects -->
        <?php if (!empty($similar_projects)): ?>
        <div class="card">
            <div class="card-body">
                <h5 class="mb-4"><i class="fas fa-project-diagram me-2"></i> Similar Projects You May Like</h5>
                <div class="row">
                    <?php foreach ($similar_projects as $similar): 
                        $similar_progress = $similar['goal_amount'] > 0 
                            ? round(($similar['current_amount'] / $similar['goal_amount']) * 100) 
                            : 0;
                        $similar_end_date = new DateTime($similar['end_date']);
                        $similar_days_remaining = $today <= $similar_end_date ? $similar_end_date->diff($today)->days : 0;
                    ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <?php if (!empty($similar['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($similar['image_path']); ?>" 
                                     class="card-img-top" style="height: 180px; object-fit: cover;" alt="<?php echo htmlspecialchars($similar['title']); ?>">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1594608661623-aa0bd3a69d98?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTJ8fGVkdWNhdGlvbnxlbnwwfHwwfHw%3D&auto=format&fit=crop&w=800&q=60" 
                                     class="card-img-top" style="height: 180px; object-fit: cover;" alt="Default Image">
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="badge bg-<?php echo getCategoryColor($similar['category']); ?> mb-2">
                                    <?php echo ucfirst(htmlspecialchars($similar['category'])); ?>
                                </span>
                                <h5 class="card-title"><?php echo htmlspecialchars($similar['title']); ?></h5>
                                <p class="card-text small"><?php echo substr(htmlspecialchars($similar['description']), 0, 100); ?>...</p>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small">$<?php echo number_format($similar['current_amount'], 2); ?> raised</span>
                                    <span class="small">Goal: $<?php echo number_format($similar['goal_amount'], 2); ?></span>
                                </div>
                                <div class="progress mb-3" style="height: 6px;">
                                    <div class="progress-bar bg-<?php echo getCategoryColor($similar['category']); ?>" role="progressbar" 
                                        style="width: <?php echo $similar_progress; ?>%" 
                                        aria-valuenow="<?php echo $similar_progress; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Ends on <?php echo date('M j, Y', strtotime($similar['end_date'])); ?></small>
                                    <a href="project-details-donor.php?id=<?php echo $similar['project_id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Donation Modal -->
    <div class="modal fade" id="donationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-hand-holding-heart me-2"></i> Make a Donation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-5 mb-4 mb-md-0">
                            <h5 class="mb-3"><?php echo htmlspecialchars($project['title']); ?></h5>
                            <p>Your donation will help support this project. Every contribution, no matter how small, makes a difference.</p>
                            
                            <div class="bg-light p-3 rounded mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Raised so far:</span>
                                    <span class="fw-bold">$<?php echo number_format($project['current_amount'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Goal:</span>
                                    <span class="fw-bold">$<?php echo number_format($project['goal_amount'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Remaining:</span>
                                    <span class="fw-bold">$<?php echo number_format($project['goal_amount'] - $project['current_amount'], 2); ?></span>
                                </div>
                            </div>
                            
                            <h6>Suggested Amounts</h6>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="25">$25</button>
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="50">$50</button>
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="100">$100</button>
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="250">$250</button>
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="500">$500</button>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <form id="donationForm" class="needs-validation" action="backend/donor/donate.php" method="post">
                                <input type="hidden" name="project_id" id="projectId" value="<?php echo $project_id; ?>">
                                
                                <div class="mb-3">
                                    <label for="donationAmount" class="form-label">Donation Amount ($)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="donationAmount" name="amount" 
                                            min="1" 
                                            max="<?php echo max(1, $project['goal_amount'] - $project['current_amount']); ?>" 
                                            step="1" 
                                            pattern="\d+"
                                            onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                            onchange="this.value=Math.floor(this.value)" 
                                            required>
                                        <div class="invalid-feedback">
                                            Please enter a valid whole dollar amount.
                                        </div>
                                    </div>
                                    <div class="form-text">Please enter whole dollar amounts only (no cents).</div>
                                </div>

                                <div class="mb-3">
                                    <label for="fullName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="fullName" name="full_name" value="<?php echo htmlspecialchars($donor['name'] . ' ' . $donor['surname']); ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($donor['email']); ?>" readonly>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="anonymousCheck" name="anonymous" value="1">
                                    <label class="form-check-label" for="anonymousCheck">Make donation anonymous</label>
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-heart me-2"></i> Donate Now
                                    </button>
                                </div>
                                <p class="text-center mt-3 small text-muted">Your donation will be processed securely.</p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><span class="text-primary">Help</span><span class="text-light">Hub</span> © 2025</h5>
                    <p>Connecting hearts and resources for a better world.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="privacy-policy.html" class="text-light">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="terms-of-use.html" class="text-light">Terms of Use</a></li>
                        <li class="list-inline-item"><a href="contact.html" class="text-light">Contact Us</a></li>
                        <li class="list-inline-item"><a href="index.php" class="text-light">Home</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle suggested amount buttons
            const amountButtons = document.querySelectorAll('.amount-btn');
            const amountInput = document.getElementById('donationAmount');
            
            amountButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const amount = this.getAttribute('data-amount');
                    amountInput.value = amount;
                    
                    // Remove active class from all buttons
                    amountButtons.forEach(btn => btn.classList.remove('active', 'btn-primary'));
                    amountButtons.forEach(btn => btn.classList.add('btn-outline-primary'));
                    
                    // Add active class to clicked button
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('active', 'btn-primary');
                });
            });
            
            // Form validation (simplified as payment fields removed)
            const form = document.getElementById('donationForm');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>
