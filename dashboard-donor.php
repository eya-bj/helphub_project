<?php
// Start session
session_start();

// Check if user is logged in as donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    // Redirect to login page
    header("Location: index.php?error=unauthorized"); // Updated link
    exit;
}

// Include database connection
require_once 'backend/db.php';

// Get donor ID
$donor_id = $_SESSION['user_id'];

// Get donor statistics
try {
    // Get total donations amount
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_amount
        FROM donation
        WHERE donor_id = ?
    ");
    $stmt->execute([$donor_id]);
    $total_donation = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'] ?? 0;
    
    // Get projects supported count
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT project_id) as projects_count
        FROM donation
        WHERE donor_id = ?
    ");
    $stmt->execute([$donor_id]);
    $projects_count = $stmt->fetch(PDO::FETCH_ASSOC)['projects_count'] ?? 0;
    
    // Get associations supported count
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT a.assoc_id) as associations_count
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE d.donor_id = ?
    ");
    $stmt->execute([$donor_id]);
    $associations_count = $stmt->fetch(PDO::FETCH_ASSOC)['associations_count'] ?? 0;
    
    // Get donor information
    $stmt = $pdo->prepare("
        SELECT name, surname, email, ctn, profile_image
        FROM donor
        WHERE donor_id = ?
    ");
    $stmt->execute([$donor_id]);
    $donor_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get donor's donations with project details
    $stmt = $pdo->prepare("
        SELECT 
            d.donation_id, 
            d.amount, 
            d.donation_date,
            p.project_id,
            p.title as project_title,
            p.status as project_status,
            a.assoc_id,
            a.name as association_name
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE d.donor_id = ?
        ORDER BY d.donation_date DESC
    ");
    $stmt->execute([$donor_id]);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - HelpHub</title>
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
            <a class="navbar-brand" href="dashboard-donor.php">
                <span class="text-primary fw-bold">Help</span><span class="text-secondary">Hub</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-donor.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Projects</a>
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

    <!-- Dashboard Content -->
    <section class="py-5">
        <div class="container">
            <!-- Welcome Banner -->
            <div class="card bg-primary text-white shadow mb-4">
                <div class="card-body p-4">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                    <p class="mb-0">Thank you for your generous support. Your donations are making a difference!</p>
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
                            <?php if (!empty($donor_info['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($donor_info['profile_image']); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-inline-flex mx-auto" style="width: 100px; height: 100px;">
                                    <i class="fas fa-user fa-3x m-auto text-primary"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-10">
                            <h3 class="mb-3"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                            <div class="row">
                                <div class="col-md-4 mb-2"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($donor_info['email'] ?? 'Not available'); ?></div>
                                <div class="col-md-4 mb-2"><i class="fas fa-id-card me-2"></i> CTN: <?php echo htmlspecialchars($donor_info['ctn'] ?? 'Not available'); ?></div>
                                <div class="col-md-4 mb-2"><i class="fas fa-heart me-2"></i> Supporting <?php echo $projects_count; ?> projects</div>
                            </div>
                            <a href="profile-donor.php" class="btn btn-sm btn-outline-primary mt-2">
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
                                    <i class="fas fa-hand-holding-heart fa-2x text-primary"></i>
                                </div>
                                <div class="col-9">
                                    <div class="h5 mb-0">$<?php echo number_format($total_donation, 2); ?></div>
                                    <div class="small text-muted">Total Donations</div>
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
                                    <i class="fas fa-project-diagram fa-2x text-success"></i>
                                </div>
                                <div class="col-9">
                                    <div class="h5 mb-0"><?php echo $projects_count; ?></div>
                                    <div class="small text-muted">Projects Supported</div>
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
                                    <i class="fas fa-building fa-2x text-info"></i>
                                </div>
                                <div class="col-9">
                                    <div class="h5 mb-0"><?php echo $associations_count; ?></div>
                                    <div class="small text-muted">Associations Supported</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Donations -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Donations</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Association</th>
                                    <th>Donation Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($donations)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No donations found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($donations as $donation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($donation['project_title']); ?></td>
                                            <td><?php echo htmlspecialchars($donation['association_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                                            <td>$<?php echo number_format($donation['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $donation['project_status'] == 'completed' ? 'bg-success' : 'bg-info'; ?>">
                                                    <?php echo ucfirst($donation['project_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="project-details-donor.php?id=<?php echo $donation['project_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Total Donations:</td>
                                    <td>$<?php echo number_format($total_donation, 2); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><span class="text-primary">Help</span><span class="text-light">Hub</span> © 2023</h5>
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
    <!-- Custom JS -->
    <script src="index.js"></script>
</body>
</html>
