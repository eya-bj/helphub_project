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
$debug_message = "";

try {
    $pdo->query("SELECT 1");

    $stmt = $pdo->prepare("SELECT name, surname, email, ctn, pseudo, created_at, profile_image FROM donor WHERE donor_id = ?");
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        session_destroy();
        header("Location: index.php?error=user_not_found");
        exit;
    }

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
        SELECT p.title, d.amount, d.donation_date, d.anonymous
        FROM donation d
        JOIN project p ON d.project_id = p.project_id
        WHERE d.donor_id = ?
        ORDER BY d.donation_date DESC
        LIMIT 5
    ");
    $stmt->execute([$donor_id]);
    $recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: Could not load profile data.";
    error_log("Donor Profile Load Error: " . $e->getMessage());
    $donor = [];
    $donation_stats = ['projects_supported' => 0, 'total_donated' => 0, 'total_donations' => 0];
    $recent_donations = [];
    $debug_message = "PDO Error: " . $e->getMessage();
}

$profile_image = $donor['profile_image'] ?? 'assets/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Profile - HelpHub</title>
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
                        <a class="nav-link" href="dashboard-donor.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile-donor.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="backend/auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="py-5">
        <div class="container">
            <div class="mb-4">
                <h2 class="fw-bold">Your Profile</h2>
                <p class="text-muted">Manage your personal information and security settings.</p>
            </div>

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
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Error: <?php echo htmlspecialchars($error_message); ?>
                    <?php if ($debug_message): ?>
                        <br><small>Debug: <?php echo htmlspecialchars($debug_message); ?></small>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4 text-center">
                        <div class="card-body">
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile-image img-thumbnail mb-3">
                            <h5 class="card-title"><?php echo htmlspecialchars($donor['name'] . ' ' . $donor['surname']); ?></h5>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($donor['pseudo']); ?></p>
                            <p class="text-muted">Joined: <?php echo date('M j, Y', strtotime($donor['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Donation Stats</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Donated:</span>
                                <span>$<?php echo number_format($donation_stats['total_donated'] ?? 0, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Projects Supported:</span>
                                <span><?php echo $donation_stats['projects_supported'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total Donations:</span>
                                <span><?php echo $donation_stats['total_donations'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Account Security</h6>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-outline-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="fas fa-key me-2"></i> Change Password
                            </button>
                            <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="fas fa-trash-alt me-2"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Edit Profile Information</h6>
                        </div>
                        <div class="card-body">
                            <form id="donorProfileForm" action="backend/donor/update_profile.php" method="post" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="donorName" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="donorName" name="name" value="<?php echo htmlspecialchars($donor['name']); ?>" required>
                                        <div class="invalid-feedback" id="donorNameFeedback">Please enter your first name (minimum 2 characters).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="donorSurname" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="donorSurname" name="surname" value="<?php echo htmlspecialchars($donor['surname']); ?>" required>
                                        <div class="invalid-feedback" id="donorSurnameFeedback">Please enter your last name (minimum 2 characters).</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="donorEmail" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="donorEmail" name="email" value="<?php echo htmlspecialchars($donor['email']); ?>" required>
                                    <div class="invalid-feedback" id="donorEmailFeedback">Please enter a valid email address.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="profileImage" class="form-label">Profile Image</label>
                                    <input type="file" class="form-control" id="profileImage" name="profile_image" accept="image/png, image/jpeg, image/gif">
                                    <small class="form-text text-muted">Optional. Max 2MB. Recommended: Square image (e.g., 150x150px).</small>
                                    <div class="invalid-feedback" id="profileImageFeedback">Please select a valid image file (JPEG, PNG, or GIF, max 2MB).</div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Donation Activity</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_donations)): ?>
                                <p class="text-muted">No recent donations found.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($recent_donations as $donation): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                Donated $<?php echo number_format($donation['amount'], 2); ?> to 
                                                <strong><?php echo htmlspecialchars($donation['title']); ?></strong>
                                                <?php if ($donation['anonymous']): ?> (Anonymous)<?php endif; ?>
                                            </span>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm" action="backend/common/change_password.php" method="post">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            <div class="invalid-feedback" id="currentPasswordFeedback">Please enter your current password.</div>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            <div class="invalid-feedback" id="newPasswordFeedback">Password must be at least 8 characters and end with $ or #.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            <div class="invalid-feedback" id="confirmPasswordFeedback">Passwords do not match.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="changePasswordForm" class="btn btn-primary">Update Password</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger fw-bold">Warning: This action is irreversible!</p>
                    <p>Deleting your account will permanently remove all your personal data and donation history. This cannot be undone.</p>
                    <form id="deleteAccountForm" action="backend/donor/delete_account.php" method="post">
                        <div class="mb-3">
                            <label for="deleteConfirm" class="form-label">To confirm, type "DELETE" below:</label>
                            <input type="text" class="form-control" id="deleteConfirm" name="confirm_delete" required pattern="DELETE">
                            <div class="invalid-feedback" id="deleteConfirmFeedback">Please type "DELETE" to confirm.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteAccountForm" class="btn btn-danger" id="confirmDeleteBtn" disabled>Delete My Account</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><span class="text-primary">Help</span><span class="text-light">Hub</span> © <?php echo date("Y"); ?></h5>
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
    <script src="profile.js"></script>
</body>
</html>
