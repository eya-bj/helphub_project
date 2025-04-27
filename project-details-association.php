<?php
// Start session
session_start();

// Check if user is logged in as association
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header("Location: index.html?error=unauthorized");
    exit;
}

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard-association.php?error=invalid_project");
    exit;
}

$project_id = $_GET['id'];
$assoc_id = $_SESSION['user_id'];

// Include database connection
require_once 'backend/db.php';

try {
    // Fetch project details
    $stmt = $pdo->prepare("
        SELECT p.*, a.name as association_name, a.logo_path as association_logo 
        FROM project p
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.project_id = ? AND p.assoc_id = ?
    ");
    $stmt->execute([$project_id, $assoc_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if project exists and belongs to this association
    if (!$project) {
        header("Location: dashboard-association.php?error=project_not_found_or_unauthorized");
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
    
    // Get donations with donor information
    $stmt = $pdo->prepare("
        SELECT d.donation_id, d.amount, d.anonymous, d.donation_date,
               donor.name, donor.surname, donor.pseudo, donor.profile_image
        FROM donation d
        JOIN donor ON d.donor_id = donor.donor_id
        WHERE d.project_id = ?
        ORDER BY d.donation_date DESC
    ");
    $stmt->execute([$project_id]);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Anonymize donor information for anonymous donations
    foreach ($donations as &$donation) {
        if ($donation['anonymous']) {
            $donation['name'] = 'Anonymous';
            $donation['surname'] = '';
            $donation['pseudo'] = 'Anonymous';
            $donation['profile_image'] = null;
        }
    }
    
    // Get donation statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_donations,
            COUNT(DISTINCT donor_id) as unique_donors,
            SUM(amount) as total_amount,
            MAX(amount) as largest_donation,
            MIN(amount) as smallest_donation,
            AVG(amount) as average_donation
        FROM donation
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format donation statistics
    $total_donations = $stats['total_donations'] ?? 0;
    $unique_donors = $stats['unique_donors'] ?? 0;
    $total_amount = $stats['total_amount'] ?? 0;
    $largest_donation = $stats['largest_donation'] ?? 0;
    $smallest_donation = $stats['smallest_donation'] ?? 0;
    $average_donation = $stats['average_donation'] ?? 0;
    
    // Get association (project owner) details
    $stmt = $pdo->prepare("SELECT * FROM association WHERE assoc_id = ?");
    $stmt->execute([$assoc_id]);
    $association = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    header("Location: dashboard-association.php?error=database_error");
    exit;
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
            <a class="navbar-brand" href="dashboard-association.php">
                <span class="text-primary fw-bold">Help</span><span class="text-secondary">Hub</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-association.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile-association.php">
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
        <div class="mb-3">
            <a href="dashboard-association.php" class="btn btn-outline-primary" id="backBtn">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
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
                        <p class="mb-4"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                        
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
                                    <h4 class="fw-bold mb-0"><?php echo $unique_donors; ?></h4>
                                    <span class="small text-muted">Donors</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="progress mb-2" style="height: 10px">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%" 
                                aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-end mb-4 small"><?php echo $progress; ?>% Complete - 
                            $<?php echo number_format($project['goal_amount'] - $project['current_amount'], 2); ?> to go</p>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary" id="editProjectBtn" data-bs-toggle="modal" data-bs-target="#editProjectModal">
                                <i class="fas fa-edit me-2"></i> Edit Project
                            </button>
                            <?php if ($total_donations === 0): ?>
                                <form id="deleteProjectForm" action="backend/association/delete_project.php" method="post" class="d-inline-block">
                                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                    <button type="submit" class="btn btn-outline-danger" id="deleteProjectBtn" onclick="return confirm('Are you sure you want to delete this project? This action cannot be undone.');">
                                        <i class="fas fa-trash me-2"></i> Delete Project
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary" disabled title="Projects with donations cannot be deleted">
                                    <i class="fas fa-trash me-2"></i> Delete Project
                                </button>
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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="donors-tab" data-bs-toggle="tab" data-bs-target="#donors" 
                                type="button" role="tab" aria-controls="donors" aria-selected="false">
                            <i class="fas fa-users me-2"></i> Donors
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" 
                                type="button" role="tab" aria-controls="stats" aria-selected="false">
                            <i class="fas fa-chart-bar me-2"></i> Statistics
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
                                                <p class="card-text small">This project will make a significant impact in the <?php echo htmlspecialchars($project['category']); ?> sector</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 bg-light h-100">
                                            <div class="card-body text-center">
                                                <div class="rounded-circle bg-success bg-opacity-10 p-3 d-inline-flex mb-3" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-users fa-2x m-auto text-success"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo $unique_donors; ?></h5>
                                                <p class="card-text small">Unique donors supporting this cause</p>
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
                                                <p class="card-text small">Progress toward our funding goal</p>
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
                                                <i class="fas fa-user text-primary me-2"></i> 
                                                <?php echo htmlspecialchars($association['representative_name'] . ' ' . $association['representative_surname']); ?>
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-envelope text-primary me-2"></i> 
                                                <?php echo htmlspecialchars($association['email']); ?>
                                            </li>
                                            <li>
                                                <i class="fas fa-building text-primary me-2"></i> 
                                                <?php echo htmlspecialchars($association['name']); ?>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Donors Tab (Association View) -->
                    <div class="tab-pane fade" id="donors" role="tabpanel" aria-labelledby="donors-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Donor Contributions</h4>
                            <span class="badge bg-info"><?php echo $unique_donors; ?> donors</span>
                        </div>
                        
                        <?php if (empty($donations)): ?>
                            <div class="alert alert-info">No donations received yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Donor</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($donations as $donation): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($donation['anonymous']): ?>
                                                            <div class="bg-secondary bg-opacity-10 rounded-circle p-2 text-center me-3" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-user-secret text-secondary"></i>
                                                            </div>
                                                            <span>Anonymous</span>
                                                        <?php else: ?>
                                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 text-center me-3" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-user text-primary"></i>
                                                            </div>
                                                            <span><?php echo htmlspecialchars($donation['name'] . ' ' . $donation['surname']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><span class="fw-bold">$<?php echo number_format($donation['amount'], 2); ?></span></td>
                                                <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <td class="fw-bold">Total</td>
                                            <td class="fw-bold">$<?php echo number_format($project['current_amount'], 2); ?></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Statistics Tab -->
                    <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                        <h4 class="mb-4">Donation Statistics</h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Overview</h5>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Total Donations
                                                <span class="badge bg-primary rounded-pill"><?php echo $total_donations; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Unique Donors
                                                <span class="badge bg-primary rounded-pill"><?php echo $unique_donors; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Average Donation
                                                <span class="badge bg-primary rounded-pill">$<?php echo number_format($average_donation, 2); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Largest Donation
                                                <span class="badge bg-primary rounded-pill">$<?php echo number_format($largest_donation, 2); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Smallest Donation
                                                <span class="badge bg-primary rounded-pill">$<?php echo number_format($smallest_donation, 2); ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Progress</h5>
                                        <div class="mb-4 mt-4">
                                            <h6>Funding Goal: $<?php echo number_format($project['goal_amount'], 2); ?></h6>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $progress; ?>%" 
                                                     aria-valuenow="<?php echo $progress; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php echo $progress; ?>%
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <div class="text-center">
                                                <h5>$<?php echo number_format($project['current_amount'], 2); ?></h5>
                                                <small class="text-muted">Raised</small>
                                            </div>
                                            <div class="text-center">
                                                <h5><?php echo $days_remaining; ?></h5>
                                                <small class="text-muted">Days Left</small>
                                            </div>
                                            <div class="text-center">
                                                <h5>$<?php echo number_format($project['goal_amount'] - $project['current_amount'], 2); ?></h5>
                                                <small class="text-muted">To Go</small>
                                            </div>
                                        </div>
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
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Support our project: ' . $project['title']); ?>&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-info">
                        <i class="fab fa-twitter me-2"></i> Twitter
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Support our project: ' . $project['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-success">
                        <i class="fab fa-whatsapp me-2"></i> WhatsApp
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode('Support our project: ' . $project['title']); ?>&body=<?php echo urlencode('Check out our project at: ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-envelope me-2"></i> Email
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Project Modal -->
    <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProjectModalLabel">
                        <i class="fas fa-edit me-2"></i> Edit Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProjectForm" action="backend/association/update_project.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                        
                        <div class="mb-3">
                            <label for="editTitle" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="editTitle" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="4" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Category</label>
                            <select class="form-select" id="editCategory" name="category" required>
                                <option value="education" <?php echo $project['category'] == 'education' ? 'selected' : ''; ?>>Education</option>
                                <option value="environment" <?php echo $project['category'] == 'environment' ? 'selected' : ''; ?>>Environment</option>
                                <option value="health" <?php echo $project['category'] == 'health' ? 'selected' : ''; ?>>Health</option>
                                <option value="poverty" <?php echo $project['category'] == 'poverty' ? 'selected' : ''; ?>>Poverty Relief</option>
                                <option value="animals" <?php echo $project['category'] == 'animals' ? 'selected' : ''; ?>>Animal Welfare</option>
                                <option value="other" <?php echo $project['category'] == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="editEndDate" name="end_date" value="<?php echo date('Y-m-d', strtotime($project['end_date'])); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editGoalAmount" class="form-label">Goal Amount ($)</label>
                                <input type="number" class="form-control" id="editGoalAmount" name="goal_amount" value="<?php echo $project['goal_amount']; ?>" min="<?php echo $project['current_amount']; ?>" step="0.01" required>
                                <div class="form-text">Goal amount cannot be less than current amount: $<?php echo number_format($project['current_amount'], 2); ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Project Status</label>
                            <select class="form-select" id="editStatus" name="status">
                                <option value="active" <?php echo $project['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $project['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editImage" class="form-label">Project Image</label>
                            <input type="file" class="form-control" id="editImage" name="image" accept="image/*">
                            <div class="form-text">Leave empty to keep the current image</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editProjectForm" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-4">
        <div class="container text-center">
            <p class="mb-0"><span class="text-primary">Help</span><span class="text-light">Hub</span> © <?php echo date('Y'); ?>. All Rights Reserved.</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable custom functionality if needed
            console.log('Project details page loaded');
        });
    </script>
</body>
</html>

<?php
// Helper functions

// Returns appropriate Bootstrap color class based on category
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

// Returns appropriate icon based on category
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
