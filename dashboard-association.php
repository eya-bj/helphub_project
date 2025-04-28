<?php
// Start session
session_start();

// Check if user is logged in as association
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    // Redirect to login page
    header("Location: index.php?error=unauthorized"); // Changed from index.html
    exit;
}

// Include database connection
require_once 'backend/db.php';

// Get association ID
$assoc_id = $_SESSION['user_id'];

// Get association statistics
try {
    // Get active projects count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_projects
        FROM project
        WHERE assoc_id = ? AND status = 'active'
    ");
    $stmt->execute([$assoc_id]);
    $active_projects = $stmt->fetch(PDO::FETCH_ASSOC)['active_projects'] ?? 0;
    
    // Get total funds raised
    $stmt = $pdo->prepare("
        SELECT SUM(d.amount) as total_raised
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        WHERE p.assoc_id = ?
    ");
    $stmt->execute([$assoc_id]);
    $total_raised = $stmt->fetch(PDO::FETCH_ASSOC)['total_raised'] ?? 0;
    
    // Get donors count
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT d.donor_id) as donors_count
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        WHERE p.assoc_id = ?
    ");
    $stmt->execute([$assoc_id]);
    $donors_count = $stmt->fetch(PDO::FETCH_ASSOC)['donors_count'] ?? 0;
    
    // Get association information
    $stmt = $pdo->prepare("
        SELECT name, fiscal_id, representative_name , representative_surname, email
        FROM association
        WHERE assoc_id = ?
    ");
    $stmt->execute([$assoc_id]);
    $assoc_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get association's projects
    $stmt = $pdo->prepare("
        SELECT 
            project_id,
            title,
            description,
            goal_amount,
            current_amount,
            start_date,
            end_date,
            status,
            category
        FROM project
        WHERE assoc_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$assoc_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Dashboard - HelpHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
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
                        <a class="nav-link active" href="dashboard-association.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile-association.php">Profile</a> <!-- Updated link -->
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="backend/auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <section class="py-5">
        <div class="container">
            <!-- Welcome Banner -->
            <div class="card bg-primary text-white shadow mb-4">
                <div class="card-body p-4">
                    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                    <p class="mb-0">Manage your projects and track donations from your dashboard.</p>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Summary Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center mb-3 mb-md-0">
                            <!-- Display Logo -->
                            <?php 
                                // Fetch logo path again or use session if stored
                                $stmt_logo = $pdo->prepare("SELECT logo_path FROM association WHERE assoc_id = ?");
                                $stmt_logo->execute([$assoc_id]);
                                $logo_path = $stmt_logo->fetchColumn();
                                $logo_display_path = !empty($logo_path) ? htmlspecialchars($logo_path) : 'assets/default-logo.png'; // Default logo
                            ?>
                            <img src="<?php echo $logo_display_path; ?>" alt="<?php echo htmlspecialchars($_SESSION['user_name']); ?> Logo" class="profile-image img-thumbnail" style="width: 100px; height: 100px; object-fit: contain;">
                        </div>
                        <div class="col-md-10">
                            <h3 class="mb-3"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                            <div class="row">
                                <div class="col-md-4 mb-2"><i class="fas fa-user me-2"></i> <?php echo htmlspecialchars($_SESSION['user_contact']); ?></div>
                                <div class="col-md-4 mb-2"><i class="fas fa-id-card me-2"></i> Fiscal ID: <?php echo htmlspecialchars($_SESSION['user_fiscal_id']); ?></div>
                                <div class="col-md-4 mb-2"><i class="fas fa-project-diagram me-2"></i> <?php echo $active_projects; ?> active projects</div>
                            </div>
                            <a href="profile-association.php" class="btn btn-sm btn-outline-primary mt-2"> <!-- Updated link -->
                                <i class="fas fa-edit me-2"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card text-dark bg-light h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <i class="fas fa-project-diagram fa-2x text-primary"></i>
                                </div>
                                <div class="col-9">
                                    <div class="h5 mb-0"><?php echo $active_projects; ?></div>
                                    <div class="small text-muted">Active Projects</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-dark bg-light h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <i class="fas fa-dollar-sign fa-2x text-success"></i>
                                </div>
                                <div class="col-9">
                                    <div class="h5 mb-0">$<?php echo number_format($total_raised, 2); ?></div>
                                    <div class="small text-muted">Total Funds Raised</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-dark bg-light h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <i class="fas fa-users fa-2x text-info"></i>
                                </div>
                                <div class="col-9">
                                    <div class="h5 mb-0"><?php echo $donors_count; ?></div>
                                    <div class="small text-muted">Supporting Donors</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-project-diagram me-2"></i> Your Projects</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2 mb-3">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                            <i class="fas fa-plus fa-sm me-2"></i> Add New Project
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Project</th>
                                    <th>Description</th>
                                    <th>Total</th>
                                    <th>Collected</th>
                                    <th>Progress</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($projects)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No projects found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($projects as $project): ?>
                                        <?php 
                                        $progress = 0;
                                        if ($project['goal_amount'] > 0) {
                                            $progress = round(($project['current_amount'] / $project['goal_amount']) * 100);
                                        }
                                        ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($project['title']); ?></td>
                                            <td><?php echo substr(htmlspecialchars($project['description']), 0, 50) . '...'; ?></td>
                                            <td>$<?php echo number_format($project['goal_amount'], 2); ?></td>
                                            <td>$<?php echo number_format($project['current_amount'], 2); ?></td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?php echo $progress; ?>%</small>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($project['end_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $project['status'] == 'active' ? 'info' : 'secondary'; ?>">
                                                    <?php echo ucfirst($project['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="project-details-association.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-outline-primary"> <!-- Ensure this file exists or is created -->
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form action="backend/association/delete_project.php" method="post" style="display:inline;">
                                                        <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger project-delete-btn" onclick="return confirm('Are you sure you want to delete this project?');">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProjectModalLabel">
                        <i class="fas fa-plus-circle me-2 text-primary"></i> Add New Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addProjectForm" class="needs-validation" action="backend/association/add_project.php" method="post" enctype="multipart/form-data" novalidate>
                        <!-- Add hidden input for start_date -->
                        <input type="hidden" name="start_date" id="projectStartDate" value="<?php echo date('Y-m-d'); ?>">

                        <div class="mb-3">
                            <label for="projectTitle" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="projectTitle" name="title" required>
                            <div class="invalid-feedback">
                                Please enter a project title.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="projectDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="projectDescription" name="description" rows="4" required></textarea>
                            <div class="invalid-feedback">
                                Please enter a project description.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="projectImage" class="form-label">Project Image</label>
                            <input type="file" class="form-control" id="projectImage" name="image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="projectCategory" class="form-label">Category</label>
                            <select class="form-select" id="projectCategory" name="category" required>
                                <option value="" selected disabled>Select a category</option>
                                <option value="education">Education</option>
                                <option value="environment">Environment</option>
                                <option value="health">Health</option>
                                <option value="poverty">Poverty Relief</option>
                                <option value="animals">Animal Welfare</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a category.
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="projectAmount" class="form-label">Goal Amount ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="projectAmount" name="goal_amount" min="1" step="0.01" required>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter a valid goal amount (e.g., 100.00).
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="projectDeadline" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="projectDeadline" name="end_date" required>
                                <div class="invalid-feedback">
                                    Please select an end date.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addProjectForm" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save Project
                    </button>
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

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="index.js"></script>
</body>
</html>
