<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$donor_id = $_SESSION['user_id'];
$project_id = $_GET['id'] ?? null;
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null;

if (!$project_id || !is_numeric($project_id)) {
    header("Location: projects.php?error=invalid_project");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, a.name as association_name, a.logo_path as association_logo 
        FROM project p 
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.project_id = ? AND p.status IN ('active', 'funded') 
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        header("Location: projects.php?error=project_not_found");
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donation WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $donors_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donation WHERE project_id = ? AND donor_id = ?");
    $stmt->execute([$project_id, $donor_id]);
    $has_donated = $stmt->fetchColumn() > 0;

    $stmt = $pdo->prepare("
        SELECT d.amount, d.donation_date, d.anonymous, dn.name as donor_name, dn.surname as donor_surname, dn.profile_image as donor_image
        FROM donation d
        LEFT JOIN donor dn ON d.donor_id = dn.donor_id
        WHERE d.project_id = ?
        ORDER BY d.donation_date DESC
        LIMIT 10 
    ");
    $stmt->execute([$project_id]);
    $recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM project p
        WHERE p.category = ? AND p.project_id != ? AND p.status = 'active' AND p.end_date >= CURDATE()
        ORDER BY RAND() 
        LIMIT 2
    ");
    $stmt->execute([$project['category'], $project_id]);
    $similar_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $progress = $project['goal_amount'] > 0 ? round(($project['current_amount'] / $project['goal_amount']) * 100) : 0;
    $today = new DateTime();
    $end_date = new DateTime($project['end_date']);
    $days_remaining = $today <= $end_date ? $end_date->diff($today)->days : 0;
    $is_expired = $today > $end_date && $project['status'] == 'active';
    $status = $is_expired ? 'expired' : $project['status'];

} catch (PDOException $e) {
    $error_message = "Database error: Could not load project details.";
    error_log("Project Details Donor Error: " . $e->getMessage());
    $project = null;
    $recent_donations = [];
    $similar_projects = [];
    $donors_count = 0;
    $has_donated = false;
    $progress = 0;
    $days_remaining = 0;
    $status = 'unknown';
}

if (!$project) {
    header("Location: projects.php?error=project_load_failed");
    exit;
}

$assoc_logo = $project['association_logo'] ?? 'assets/default-logo.png';

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
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

    <div class="container py-4">
        <div class="mb-3">
            <a href="projects.php" class="btn btn-outline-primary" id="backBtn">
                <i class="fas fa-arrow-left me-2"></i> Back to Projects
            </a>
        </div>

        <?php if ($success_message === 'donation_complete'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Thank you for your generous donation!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($success_message): ?>
             <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Error: <?php
                    if ($error_message === 'invalid_input') echo 'Invalid donation amount or project ID.';
                    elseif ($error_message === 'project_inactive') echo 'This project is no longer active or has ended.';
                    elseif ($error_message === 'donation_failed') echo 'Donation failed due to a server error. Please try again.';
                    else echo htmlspecialchars($error_message);
                 ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="row g-0">
                <div class="col-md-5">
                    <?php if (!empty($project['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($project['image_path']); ?>"
                             alt="Project Image" class="img-fluid rounded-start" style="width: 100%; height: 100%; max-height: 400px; object-fit: cover;">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1588421357574-87938a86fa28?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80"
                             alt="Default Project Image" class="img-fluid rounded-start" style="width: 100%; height: 100%; max-height: 400px; object-fit: cover;">
                    <?php endif; ?>
                </div>
                <div class="col-md-7">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge bg-<?php echo getCategoryColor($project['category']); ?>">
                                <?php echo ucfirst(htmlspecialchars($project['category'])); ?>
                            </span>
                            <span class="badge bg-light text-dark border">
                                <?php if ($status === 'active'): ?>
                                    <i class="fas fa-clock me-1"></i> <?php echo $days_remaining; ?> days left
                                <?php elseif ($status === 'funded'): ?>
                                    <i class="fas fa-check-circle text-success me-1"></i> Goal Reached!
                                <?php elseif ($status === 'expired'): ?>
                                    <i class="fas fa-times-circle text-danger me-1"></i> Expired
                                <?php else: ?>
                                    <i class="fas fa-info-circle me-1"></i> <?php echo ucfirst($status); ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <h2 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($project['title']); ?></h2>

                        <div class="d-flex align-items-center mb-3 text-muted">
                            <img src="<?php echo htmlspecialchars($assoc_logo); ?>" alt="<?php echo htmlspecialchars($project['association_name']); ?> Logo" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                            <span>By <?php echo htmlspecialchars($project['association_name']); ?></span>
                        </div>

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
                                aria-valuenow="<?php echo $progress; ?>"
                                aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-4">
                            <span><?php echo $progress; ?>% Funded</span>
                            <span>Ends: <?php echo $end_date->format('M j, Y'); ?></span>
                        </div>

                        <div class="d-grid gap-2">
                            <?php if ($status === 'active'): ?>
                                <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#donateModal">
                                    <i class="fas fa-heart me-2"></i> Donate Now
                                </button>
                            <?php elseif ($status === 'funded'): ?>
                                 <button class="btn btn-secondary btn-lg disabled">
                                    <i class="fas fa-check-circle me-2"></i> Funding Goal Reached!
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg disabled">
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
                        <button class="nav-link" id="donations-tab" data-bs-toggle="tab" data-bs-target="#donations"
                                type="button" role="tab" aria-controls="donations" aria-selected="false">
                            <i class="fas fa-users me-2"></i> Recent Donations
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-3" id="projectTabContent">
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
                                            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-0 px-0">
                                                Category:
                                                <span class="badge bg-<?php echo getCategoryColor($project['category']); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($project['category'])); ?>
                                                </span>
                                            </li>
                                            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-0 px-0">
                                                Start Date:
                                                <span><?php echo date('M j, Y', strtotime($project['start_date'])); ?></span>
                                            </li>
                                            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-0 px-0">
                                                End Date:
                                                <span><?php echo $end_date->format('M j, Y'); ?></span>
                                            </li>
                                            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-0 px-0">
                                                Status:
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo ucfirst(htmlspecialchars($status)); ?>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="donations" role="tabpanel" aria-labelledby="donations-tab">
                        <h4 class="mb-4">Recent Donations</h4>
                        <?php if (empty($recent_donations)): ?>
                            <div class="alert alert-info">Be the first to donate to this project!</div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_donations as $donation):
                                    $donor_name = $donation['anonymous'] ? 'Anonymous Donor' : htmlspecialchars($donation['donor_name'] . ' ' . $donation['donor_surname']);
                                    $donor_image = $donation['anonymous'] || empty($donation['donor_image']) ? 'assets/default-avatar.png' : htmlspecialchars($donation['donor_image']);
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $donor_image; ?>" alt="Donor" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <span class="fw-bold"><?php echo $donor_name; ?></span>
                                            <small class="d-block text-muted">Donated $<?php echo number_format($donation['amount'], 2); ?></small>
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></small>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-share-alt me-2"></i> Share This Project</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="fab fa-facebook-f me-2"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('Support this project: ' . $project['title']); ?>" target="_blank" class="btn btn-outline-info">
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

    <div class="modal fade" id="donateModal" tabindex="-1" aria-labelledby="donateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="donateModalLabel"><i class="fas fa-heart me-2"></i> Support <?php echo htmlspecialchars($project['title']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="donationForm" action="backend/donor/donate.php" method="post" class="needs-validation" novalidate>
                    <div class="modal-body p-4">
                        <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">

                        <div class="mb-4">
                            <label for="donationAmount" class="form-label fs-5">Choose Donation Amount ($)</label>
                            <div class="input-group input-group-lg mb-3">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="donationAmount" name="amount" placeholder="Enter amount" min="1" step="0.01" required>
                                <div class="invalid-feedback">Please enter a valid donation amount (minimum $1.00).</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="10">$10</button>
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="25">$25</button>
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="50">$50</button>
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="100">$100</button>
                                <button type="button" class="btn btn-outline-primary amount-btn" data-amount="250">$250</button>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" value="1" id="anonymousCheck" name="anonymous">
                            <label class="form-check-label" for="anonymousCheck">
                                Make my donation anonymous
                            </label>
                            <small class="d-block text-muted">If checked, your name will not be publicly displayed on the donor list.</small>
                        </div>

                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-1"></i> You will be redirected to confirm your donation. HelpHub ensures secure processing.
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-donate me-2"></i> Proceed to Donate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-4">
        <div class="container text-center">
            <p class="mb-0"><span class="text-primary">Help</span><span class="text-light">Hub</span> © <?php echo date('Y'); ?>. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountButtons = document.querySelectorAll('.amount-btn');
            const amountInput = document.getElementById('donationAmount');

            amountButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const amount = this.getAttribute('data-amount');
                    amountInput.value = amount;

                    amountButtons.forEach(btn => btn.classList.remove('active', 'btn-primary'));
                    amountButtons.forEach(btn => btn.classList.add('btn-outline-primary'));

                    this.classList.remove('btn-outline-primary');
                    this.classList.add('active', 'btn-primary');
                });
            });

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
