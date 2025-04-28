<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$assoc_id = $_SESSION['user_id'];
$project_id = $_GET['id'] ?? null;
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null;

if (!$project_id || !is_numeric($project_id)) {
    header("Location: dashboard-association.php?error=invalid_project");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, a.name as association_name, a.logo_path as association_logo
        FROM project p 
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.project_id = ? AND p.assoc_id = ?
    ");
    $stmt->execute([$project_id, $assoc_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        header("Location: dashboard-association.php?error=project_not_found");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(donation_id) as total_donations,
            COUNT(DISTINCT donor_id) as unique_donors,
            AVG(amount) as average_donation,
            MAX(amount) as largest_donation,
            MIN(amount) as smallest_donation
        FROM donation 
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    $donation_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_donations = $donation_stats['total_donations'] ?? 0;
    $unique_donors = $donation_stats['unique_donors'] ?? 0;
    $average_donation = $donation_stats['average_donation'] ?? 0;
    $largest_donation = $donation_stats['largest_donation'] ?? 0;
    $smallest_donation = $donation_stats['smallest_donation'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT d.amount, d.donation_date, d.anonymous, dn.name as donor_name, dn.surname as donor_surname, dn.profile_image as donor_image
        FROM donation d
        LEFT JOIN donor dn ON d.donor_id = dn.donor_id
        WHERE d.project_id = ?
        ORDER BY d.donation_date DESC
    ");
    $stmt->execute([$project_id]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $progress = $project['goal_amount'] > 0 ? round(($project['current_amount'] / $project['goal_amount']) * 100) : 0;
    $today = new DateTime();
    $end_date = new DateTime($project['end_date']);
    $days_remaining = $today <= $end_date ? $end_date->diff($today)->days : 0;
    $is_expired = $today > $end_date && $project['status'] == 'active';
    $status = $is_expired ? 'expired' : $project['status'];

} catch (PDOException $e) {
    $error_message = "Database error: Could not load project details.";
    error_log("Project Details Assoc Error: " . $e->getMessage());
    $project = null;
    $donors = [];
    $total_donations = 0;
    $unique_donors = 0;
    $average_donation = 0;
    $largest_donation = 0;
    $smallest_donation = 0;
    $progress = 0;
    $days_remaining = 0;
    $status = 'unknown';
}

if (!$project) {
    header("Location: dashboard-association.php?error=project_load_failed");
    exit;
}

$assoc_logo = $project['association_logo'] ?? 'assets/default-logo.png';

function getStatusBadge($status) {
    switch(strtolower($status)) {
        case 'active': return 'primary';
        case 'funded': return 'success';
        case 'completed': return 'secondary';
        case 'expired': return 'warning';
        default: return 'light text-dark';
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

    <div class="container py-4">
        <div class="mb-3">
            <a href="dashboard-association.php" class="btn btn-outline-primary" id="backBtn">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                    if ($success_message === 'project_updated') echo 'Project details updated successfully!';
                    else echo htmlspecialchars($success_message);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Error: <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="row g-0">
                <div class="col-md-5">
                    <?php if (!empty($project['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($project['image_path']); ?>"
                             alt="Project Image" class="img-fluid rounded-start" style="width: 100%; height: 100%; max-height: 450px; object-fit: cover;">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1588421357574-87938a86fa28?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80"
                             alt="Default Project Image" class="img-fluid rounded-start" style="width: 100%; height: 100%; max-height: 450px; object-fit: cover;">
                    <?php endif; ?>
                </div>
                <div class="col-md-7">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-<?php echo getCategoryColor($project['category']); ?> me-2">
                                    <?php echo ucfirst(htmlspecialchars($project['category'])); ?>
                                </span>
                                <span class="badge bg-<?php echo getStatusBadge($status); ?>">
                                    <?php echo ucfirst(htmlspecialchars($status)); ?>
                                </span>
                            </div>
                            <a href="edit-project.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit me-1"></i> Edit Project
                            </a>
                        </div>

                        <h2 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($project['title']); ?></h2>

                        <div class="d-flex align-items-center mb-3 text-muted">
                            <img src="<?php echo htmlspecialchars($assoc_logo); ?>" alt="<?php echo htmlspecialchars($project['association_name']); ?> Logo" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                            <span>By <?php echo htmlspecialchars($project['association_name']); ?></span>
                        </div>

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
                            <div class="progress-bar bg-<?php echo getCategoryColor($project['category']); ?>" role="progressbar"
                                style="width: <?php echo $progress; ?>%"
                                aria-valuenow="<?php echo $progress; ?>"
                                aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-3">
                            <span><?php echo $progress; ?>% Funded</span>
                            <span>
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

                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Created: <?php echo date('M j, Y', strtotime($project['created_at'])); ?></small>
                            <small class="text-muted">Ends: <?php echo $end_date->format('M j, Y'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
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
                                                <span class="badge bg-<?php echo getStatusBadge($status); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($status)); ?>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Share This Project</h5>
                                        <div class="d-flex flex-column gap-2 mt-3">
                                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                <i class="fab fa-facebook-f me-2"></i> Facebook
                                            </a>
                                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('Support this project: ' . $project['title']); ?>" target="_blank" class="btn btn-outline-info btn-sm">
                                                <i class="fab fa-twitter me-2"></i> Twitter
                                            </a>
                                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Support this project: ' . $project['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-success btn-sm">
                                                <i class="fab fa-whatsapp me-2"></i> WhatsApp
                                            </a>
                                            <a href="mailto:?subject=<?php echo urlencode('Check out this project: ' . $project['title']); ?>&body=<?php echo urlencode('I thought you might be interested in supporting this project: ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-envelope me-2"></i> Email
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="donors" role="tabpanel" aria-labelledby="donors-tab">
                        <h4 class="mb-4">Donors (<?php echo $unique_donors; ?>)</h4>
                        <?php if (empty($donors)): ?>
                            <div class="alert alert-info">No donations received for this project yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">Donor</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($donors as $donor):
                                            $donor_name = $donor['anonymous'] ? 'Anonymous' : htmlspecialchars($donor['donor_name'] . ' ' . $donor['donor_surname']);
                                            $donor_image = $donor['anonymous'] || empty($donor['donor_image']) ? 'assets/default-avatar.png' : htmlspecialchars($donor['donor_image']);
                                        ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $donor_image; ?>" alt="Donor" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                                                <?php echo $donor_name; ?>
                                            </td>
                                            <td>$<?php echo number_format($donor['amount'], 2); ?></td>
                                            <td><?php echo date('M j, Y, g:i A', strtotime($donor['donation_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                        <h4 class="mb-4">Donation Statistics</h4>
                        <div class="row g-4">
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Overview</h5>
                                        <ul class="list-group list-group-flush mt-3">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Total Donations
                                                <span class="badge bg-primary rounded-pill"><?php echo $total_donations; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Unique Donors
                                                <span class="badge bg-info rounded-pill"><?php echo $unique_donors; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Average Donation
                                                <span class="badge bg-success rounded-pill">$<?php echo number_format($average_donation, 2); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Largest Donation
                                                <span class="badge bg-warning rounded-pill">$<?php echo number_format($largest_donation, 2); ?></span>
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
                                            <span>Amount Raised:</span>
                                            <span class="fw-bold">$<?php echo number_format($project['current_amount'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Amount Remaining:</span>
                                            <span class="fw-bold">$<?php echo number_format(max(0, $project['goal_amount'] - $project['current_amount']), 2); ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span>Days Remaining:</span>
                                            <span class="fw-bold"><?php echo $days_remaining; ?></span>
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

    <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProjectModalLabel">Edit Project Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProjectForm" action="backend/association/update_project.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                        <div class="mb-3">
                            <label for="editTitle" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="editTitle" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="5" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editCategory" class="form-label">Category</label>
                                <select class="form-select" id="editCategory" name="category" required>
                                    <option value="education" <?php echo $project['category'] == 'education' ? 'selected' : ''; ?>>Education</option>
                                    <option value="environment" <?php echo $project['category'] == 'environment' ? 'selected' : ''; ?>>Environment</option>
                                    <option value="health" <?php echo $project['category'] == 'health' ? 'selected' : ''; ?>>Health</option>
                                    <option value="poverty" <?php echo $project['category'] == 'poverty' ? 'selected' : ''; ?>>Poverty</option>
                                    <option value="animals" <?php echo $project['category'] == 'animals' ? 'selected' : ''; ?>>Animals</option>
                                    <option value="other" <?php echo !in_array($project['category'], ['education', 'environment', 'health', 'poverty', 'animals']) ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editGoalAmount" class="form-label">Goal Amount ($)</label>
                                <input type="number" class="form-control" id="editGoalAmount" name="goal_amount" value="<?php echo htmlspecialchars($project['goal_amount']); ?>" min="1" step="0.01" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editStartDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="editStartDate" name="start_date" value="<?php echo htmlspecialchars($project['start_date']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="editEndDate" name="end_date" value="<?php echo htmlspecialchars($project['end_date']); ?>" required>
                            </div>
                        </div>
                         <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="active" <?php echo $project['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="funded" <?php echo $project['status'] == 'funded' ? 'selected' : ''; ?>>Funded</option>
                                <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="on_hold" <?php echo $project['status'] == 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editImage" class="form-label">Project Image</label>
                            <input type="file" class="form-control" id="editImage" name="image" accept="image/png, image/jpeg, image/gif">
                            <small class="form-text text-muted">Optional: Replace current image. Max 2MB.</small>
                            <?php if (!empty($project['image_path'])): ?>
                                <div class="mt-2">
                                    <small>Current Image:</small><br>
                                    <img src="<?php echo htmlspecialchars($project['image_path']); ?>" alt="Current Image" style="max-height: 100px; width: auto;">
                                </div>
                            <?php endif; ?>
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

    <footer class="bg-dark text-light py-4 mt-4">
        <div class="container text-center">
            <p class="mb-0"><span class="text-primary">Help</span><span class="text-light">Hub</span> © <?php echo date('Y'); ?>. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Project details page loaded');
        });
    </script>
</body>
</html>

<?php
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
