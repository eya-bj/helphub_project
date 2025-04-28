<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$donor_id = $_SESSION['user_id'];
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null;

try {
    $stmt = $pdo->prepare("SELECT name, profile_image FROM donor WHERE donor_id = ?");
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        session_destroy();
        header("Location: index.php?error=user_not_found");
        exit;
    }

    $donor_name = $donor['name'];
    $profile_image = $donor['profile_image'] ?? 'assets/default-avatar.png';

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT d.project_id) as projects_supported,
            SUM(d.amount) as total_donated,
            COUNT(d.donation_id) as total_donations
        FROM donation d
        WHERE d.donor_id = ?
    ");
    $stmt->execute([$donor_id]);
    $donation_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT p.project_id, p.title, p.category, p.image_path, a.name as association_name, d.amount, d.donation_date, d.anonymous
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE d.donor_id = ?
        ORDER BY d.donation_date DESC
        LIMIT 5
    ");
    $stmt->execute([$donor_id]);
    $recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT p.project_id, p.title, p.category, p.image_path, p.description, p.goal_amount, p.current_amount, p.end_date, a.name as association_name
        FROM project p
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.status = 'active' AND p.end_date >= CURDATE()
        AND p.project_id NOT IN (SELECT project_id FROM donation WHERE donor_id = ?)
        ORDER BY p.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$donor_id]);
    $suggested_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = new DateTime();

} catch (PDOException $e) {
    $error_message = "Database error: Could not load dashboard data.";
    error_log("Donor Dashboard Error: " . $e->getMessage());
    $donation_stats = ['projects_supported' => 0, 'total_donated' => 0, 'total_donations' => 0];
    $recent_donations = [];
    $suggested_projects = [];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - HelpHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
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

    <section class="bg-light py-4">
        <div class="container">
            <div class="d-flex align-items-center">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                <h1 class="h3 mb-0">Welcome back, <?php echo htmlspecialchars($donor_name); ?>!</h1>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                        if ($success_message === 'profile_updated') echo 'Profile updated successfully!';
                        elseif ($success_message === 'password_changed') echo 'Password changed successfully!';
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
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-donate fa-3x text-success mb-3"></i>
                            <h5 class="card-title">$<?php echo number_format($donation_stats['total_donated'] ?? 0, 2); ?></h5>
                            <p class="card-text text-muted">Total Donated</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-project-diagram fa-3x text-primary mb-3"></i>
                            <h5 class="card-title"><?php echo $donation_stats['projects_supported'] ?? 0; ?></h5>
                            <p class="card-text text-muted">Projects Supported</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-receipt fa-3x text-info mb-3"></i>
                            <h5 class="card-title"><?php echo $donation_stats['total_donations'] ?? 0; ?></h5>
                            <p class="card-text text-muted">Total Donations Made</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i> Recent Donations</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_donations)): ?>
                                <div class="alert alert-info text-center">
                                    You haven't made any donations yet. 
                                    <a href="projects.php" class="alert-link">Find a project to support!</a>
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($recent_donations as $donation): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="project-details-donor.php?id=<?php echo $donation['project_id']; ?>" class="fw-bold text-decoration-none">
                                                <?php echo htmlspecialchars($donation['title']); ?>
                                            </a>
                                            <small class="d-block text-muted">
                                                Donated $<?php echo number_format($donation['amount'], 2); ?> 
                                                on <?php echo date('M j, Y', strtotime($donation['donation_date'])); ?>
                                                <?php if ($donation['anonymous']): ?> (Anonymous)<?php endif; ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo getCategoryColor($donation['category']); ?> rounded-pill">
                                            <?php echo ucfirst(htmlspecialchars($donation['category'])); ?>
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (count($recent_donations) >= 5): ?>
                                <div class="text-center mt-3">
                                    <a href="donation-history.php" class="btn btn-sm btn-outline-primary">View All Donations</a>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Suggested Projects</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($suggested_projects)): ?>
                                <div class="alert alert-info text-center">
                                    No new project suggestions right now. You've supported many great causes!
                                </div>
                            <?php else: ?>
                                <?php foreach ($suggested_projects as $project): 
                                    $progress = $project['goal_amount'] > 0 ? round(($project['current_amount'] / $project['goal_amount']) * 100) : 0;
                                    $end_date = new DateTime($project['end_date']);
                                    $days_remaining = $today <= $end_date ? $end_date->diff($today)->days : 0;
                                ?>
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                    <?php if (!empty($project['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($project['image_path']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-image text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <a href="project-details-donor.php?id=<?php echo $project['project_id']; ?>" class="fw-bold text-decoration-none mb-1 d-block">
                                            <?php echo htmlspecialchars($project['title']); ?>
                                        </a>
                                        <small class="text-muted d-block mb-1">By <?php echo htmlspecialchars($project['association_name']); ?></small>
                                        <div class="progress mb-1" style="height: 5px;">
                                            <div class="progress-bar bg-<?php echo getCategoryColor($project['category']); ?>" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $days_remaining; ?> days left</small>
                                    </div>
                                    <a href="project-details-donor.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-primary ms-2">View</a>
                                </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="projects.php" class="btn btn-sm btn-outline-primary">Explore More Projects</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-light py-4 mt-4">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="index.js"></script>
</body>
</html>
