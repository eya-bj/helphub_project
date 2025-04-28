<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$assoc_id = $_SESSION['user_id'];
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null;

try {
    $stmt = $pdo->prepare("SELECT name, logo_path FROM association WHERE assoc_id = ?");
    $stmt->execute([$assoc_id]);
    $assoc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assoc) {
        session_destroy();
        header("Location: index.php?error=user_not_found");
        exit;
    }

    $assoc_name = $assoc['name'];
    $logo_path = $assoc['logo_path'] ?? 'assets/default-logo.png';

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_projects,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects,
            SUM(CASE WHEN status = 'funded' THEN 1 ELSE 0 END) as funded_projects,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
            SUM(current_amount) as total_raised
        FROM project 
        WHERE assoc_id = ?
    ");
    $stmt->execute([$assoc_id]);
    $project_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT donor_id) as unique_donors
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        WHERE p.assoc_id = ?
    ");
    $stmt->execute([$assoc_id]);
    $donor_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM donation WHERE project_id = p.project_id) as donation_count,
               (SELECT COUNT(DISTINCT donor_id) FROM donation WHERE project_id = p.project_id) as unique_donor_count
        FROM project p 
        WHERE p.assoc_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$assoc_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = new DateTime();

} catch (PDOException $e) {
    $error_message = "Database error: Could not load dashboard data.";
    error_log("Association Dashboard Error: " . $e->getMessage());
    $project_stats = ['total_projects' => 0, 'active_projects' => 0, 'funded_projects' => 0, 'completed_projects' => 0, 'total_raised' => 0];
    $donor_stats = ['unique_donors' => 0];
    $projects = [];
}

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
    <title>Association Dashboard - HelpHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard-association.php">
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($assoc_name); ?> Logo" style="height: 30px; width: auto; margin-right: 8px;" class="d-inline-block align-text-top rounded-circle">
                <span class="text-primary fw-bold">Help</span><span class="text-secondary">Hub</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-association.php">
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

    <section class="bg-light py-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Welcome, <?php echo htmlspecialchars($assoc_name); ?>!</h1>
                <a href="create-project.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i> Create New Project
                </a>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                        if ($success_message === 'project_created') echo 'Project created successfully!';
                        elseif ($success_message === 'project_updated') echo 'Project updated successfully!';
                        elseif ($success_message === 'project_deleted') echo 'Project deleted successfully!';
                        else echo htmlspecialchars($success_message);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-project-diagram fa-3x text-primary mb-3"></i>
                            <h5 class="card-title"><?php echo $project_stats['total_projects'] ?? 0; ?></h5>
                            <p class="card-text text-muted">Total Projects</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-tasks fa-3x text-info mb-3"></i>
                            <h5 class="card-title"><?php echo $project_stats['active_projects'] ?? 0; ?></h5>
                            <p class="card-text text-muted">Active Projects</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-hand-holding-usd fa-3x text-success mb-3"></i>
                            <h5 class="card-title">$<?php echo number_format($project_stats['total_raised'] ?? 0, 2); ?></h5>
                            <p class="card-text text-muted">Total Raised</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-warning mb-3"></i>
                            <h5 class="card-title"><?php echo $donor_stats['unique_donors'] ?? 0; ?></h5>
                            <p class="card-text text-muted">Unique Donors</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i> Your Projects</h5>
                    <span class="badge bg-secondary rounded-pill"><?php echo count($projects); ?> Projects</span>
                </div>
                <div class="card-body">
                    <?php if (empty($projects)): ?>
                        <div class="alert alert-info text-center">
                            You haven't created any projects yet. 
                            <a href="create-project.php" class="alert-link">Create your first project</a> to start fundraising!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Title</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Goal</th>
                                        <th scope="col">Raised</th>
                                        <th scope="col">Progress</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">End Date</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): 
                                        $progress = $project['goal_amount'] > 0 ? round(($project['current_amount'] / $project['goal_amount']) * 100) : 0;
                                        $end_date = new DateTime($project['end_date']);
                                        $is_expired = $today > $end_date && $project['status'] == 'active';
                                        $status = $is_expired ? 'expired' : $project['status'];
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="project-details-association.php?id=<?php echo $project['project_id']; ?>" class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getCategoryColor($project['category']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($project['category'])); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($project['goal_amount'], 2); ?></td>
                                        <td>$<?php echo number_format($project['current_amount'], 2); ?></td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-<?php echo getCategoryColor($project['category']); ?>" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $progress; ?>%</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadge($status); ?>">
                                                <?php echo ucfirst(htmlspecialchars($status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $end_date->format('M j, Y'); ?></td>
                                        <td>
                                            <a href="project-details-association.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-project.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Project">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete Project" data-bs-toggle="modal" data-bs-target="#deleteProjectModal" data-project-id="<?php echo $project['project_id']; ?>" data-project-title="<?php echo htmlspecialchars($project['title']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProjectModalLabel">Confirm Project Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the project "<strong id="projectTitleToDelete"></strong>"? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteProjectForm" action="backend/association/delete_project.php" method="post" style="display: inline;">
                        <input type="hidden" name="project_id" id="projectIdToDelete" value="">
                        <button type="submit" class="btn btn-danger">Delete Project</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var deleteProjectModal = document.getElementById('deleteProjectModal');
            if (deleteProjectModal) {
                deleteProjectModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var projectId = button.getAttribute('data-project-id');
                    var projectTitle = button.getAttribute('data-project-title');
                    
                    var modalTitle = deleteProjectModal.querySelector('#projectTitleToDelete');
                    var modalInput = deleteProjectModal.querySelector('#projectIdToDelete');

                    modalTitle.textContent = projectTitle;
                    modalInput.value = projectId;
                });
            }
        });
    </script>
    <script src="index.js"></script>
</body>
</html>
