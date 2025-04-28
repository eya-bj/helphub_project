<?php
session_start();

require_once 'backend/db.php';

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

$projects = [];
$error_message = null;

try {
    $stmt = $pdo->prepare("
        SELECT p.*, a.name as association_name 
        FROM project p
        JOIN association a ON p.assoc_id = a.assoc_id
        WHERE p.status = 'active' AND p.end_date >= CURDATE()
        ORDER BY p.created_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: Could not fetch projects.";

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpHub - Connect & Donate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="text-primary fw-bold">Help</span><span class="text-secondary">Hub</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#projects">Projects</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">Register</a>
                        <ul class="dropdown-menu" aria-labelledby="registerDropdown">
                            <li><a class="dropdown-item" href="register-association.html">Register as Association</a></li>
                            <li><a class="dropdown-item" href="register-donor.html">Register as Donor</a></li>
                        </ul>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section-bg" id="home">
        <div class="hero-overlay"></div>
        <div class="container position-relative">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center text-white">
                    <div class="hero-badge mb-3">Making A Difference Together</div>
                    <h1 class="hero-title mb-4">Connect. Donate. <span class="text-highlight">Impact.</span></h1>
                    <p class="hero-text mb-5">Connecting generous hearts with charitable associations to create positive change in our communities, one donation at a time.</p>
                    <div class="hero-buttons d-flex justify-content-center flex-wrap gap-3">
                        <a href="#projects" class="btn btn-light btn-lg">
                            <i class="fas fa-heart me-2"></i> Donate Now
                        </a>
                        <a href="register-association.html" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-building me-2"></i> Register Association
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white" id="how-it-works">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold">How HelpHub Works</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Our platform makes charitable giving simple, transparent, and impactful</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-4">
                                <i class="fas fa-hands-helping fa-3x text-primary"></i>
                            </div>
                            <h4>1. Connect</h4>
                            <p class="text-muted">Associations create profiles and share their impactful projects with potential donors.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-4">
                                <i class="fas fa-donate fa-3x text-success"></i>
                            </div>
                            <h4>2. Contribute</h4>
                            <p class="text-muted">Donors browse projects and make secure donations to causes they care about.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-4">
                                <i class="fas fa-chart-line fa-3x text-info"></i>
                            </div>
                            <h4>3. Track Progress</h4>
                            <p class="text-muted">Watch your donations make an impact with transparent project tracking and updates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light" id="projects">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Find Projects to Support</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Browse through our curated selection of charitable projects and make a difference today</p>
            </div>

            <div class="row mb-4">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <form action="projects.php" method="get" class="input-group">
                        <input type="text" class="form-control" placeholder="Search projects by keywords..." name="search" id="homeProjectSearch">
                        <button class="btn btn-primary" type="submit" id="homeSearchButton">
                            <i class="fas fa-search me-2"></i> Search
                        </button>
                    </form>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <div class="btn-group" role="group">
                        <a href="projects.php?category=all" class="btn btn-outline-primary active">All</a>
                        <a href="projects.php?category=environment" class="btn btn-outline-primary">Environment</a>
                        <a href="projects.php?category=education" class="btn btn-outline-primary">Education</a>
                        <a href="projects.php?category=health" class="btn btn-outline-primary">Health</a>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <?php if ($error_message): ?>
                    <div class="col-12">
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    </div>
                <?php elseif (empty($projects)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">No active projects found at the moment. Check back soon!</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($projects as $project):
                        $progress = $project['goal_amount'] > 0
                            ? round(($project['current_amount'] / $project['goal_amount']) * 100)
                            : 0;
                        $end_date = new DateTime($project['end_date']);
                        $today = new DateTime();
                        $days_remaining = $today <= $end_date ? $end_date->diff($today)->days : 0;
                    ?>
                    <div class="col-lg-4 col-md-6 project-item">
                        <div class="card h-100 shadow-sm">
                            <?php if (!empty($project['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($project['image_path']); ?>"
                                     class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($project['title']); ?>">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxzZWFyY2h8NHx8cGVvcGxlJTIwaGVscGluZ3xlbnwwfHwwfHw%3D&auto=format&fit=crop&w=800&q=60"
                                     class="card-img-top" style="height: 200px; object-fit: cover;" alt="Default Project Image">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between mb-3">
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
                                    <a href="projects.php" class="btn btn-primary w-100">Learn More & Donate</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="text-center mt-5">
                <a href="projects.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-th-large me-2"></i> Explore All Projects
                </a>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-4">Our Impact</h2>
                    <p class="lead mb-4">Since 2020, HelpHub has been connecting donors with causes that need support. With your help, we've made significant impacts across multiple sectors.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Transparent tracking of every donation</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> 100% of donations go directly to projects</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Verified associations and regular updates</li>
                    </ul>
                    <a href="register-donor.html" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-right me-2"></i> Join Our Community
                    </a>
                </div>
                <div class="col-lg-7">
                    <div class="row g-4">
                        <div class="col-6">
                            <div class="card h-100 text-center bg-primary text-white border-0 shadow">
                                <div class="card-body p-4">
                                    <i class="fas fa-heart fa-3x mb-3"></i>
                                    <h3 class="fw-bold">120+</h3>
                                    <p class="mb-0">Projects Funded</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card h-100 text-center bg-success text-white border-0 shadow">
                                <div class="card-body p-4">
                                    <i class="fas fa-hand-holding-usd fa-3x mb-3"></i>
                                    <h3 class="fw-bold">$5.2M</h3>
                                    <p class="mb-0">Total Donations</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card h-100 text-center bg-info text-white border-0 shadow">
                                <div class="card-body p-4">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <h3 class="fw-bold">75K+</h3>
                                    <p class="mb-0">People Helped</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card h-100 text-center bg-warning text-white border-0 shadow">
                                <div class="card-body p-4">
                                    <i class="fas fa-globe-americas fa-3x mb-3"></i>
                                    <h3 class="fw-bold">35</h3>
                                    <p class="mb-0">Countries Reached</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="card border-0 shadow">
                <div class="card-body p-5 text-center">
                    <h2 class="fw-bold mb-3">Ready to make a difference?</h2>
                    <p class="lead text-muted mb-4">Join thousands of donors and associations already using HelpHub to create positive change.</p>
                    <div class="d-flex justify-content-center flex-wrap gap-3">
                        <a href="register-donor.html" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i> Sign Up as Donor
                        </a>
                        <a href="register-association.html" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-building me-2"></i> Register Association
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="text-center">
                <p class="mb-0"><span class="text-primary">Help</span><span class="text-light">Hub</span> © <?php echo date('Y'); ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="loginModalLabel">Login to HelpHub</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="loginForm" action="backend/auth/login.php" method="post">
                        <div class="mb-3">
                            <label for="userType" class="form-label">I am a:</label>
                            <select class="form-select" id="userType" name="user_type" required>
                                <option value="" selected disabled>Select user type</option>
                                <option value="donor">Donor</option>
                                <option value="association">Association Representative</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="pseudo" class="form-label">Pseudo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="pseudo" name="pseudo" placeholder="Enter your pseudo" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div id="loginErrorMsg" class="alert alert-danger d-none mt-2" role="alert">

                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </button>
                        </div>
                        <div class="text-center mt-4">
                            <p class="mb-0">Don't have an account?</p>
                            <div class="mt-2">
                                <a href="register-donor.html" class="btn btn-sm btn-outline-primary me-2">Register as Donor</a>
                                <a href="register-association.html" class="btn btn-sm btn-outline-primary">Register as Association</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="index.js"></script>
</body>
</html>