<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header("Location: index.html?error=unauthorized");
    exit;
}

$assoc_id = $_SESSION['user_id'];
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null;

try {
    // Fetch association details
    $stmt = $pdo->prepare("SELECT name, address, fiscal_id, logo_path, pseudo, email, representative_name, representative_surname, cin, created_at FROM association WHERE assoc_id = ?");
    $stmt->execute([$assoc_id]);
    $assoc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assoc) {
        session_destroy();
        header("Location: index.html?error=user_not_found");
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project WHERE assoc_id = ? AND status = 'active'");
    $stmt->execute([$assoc_id]);
    $project_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT SUM(current_amount) FROM project WHERE assoc_id = ?");
    $stmt->execute([$assoc_id]);
    $total_raised = $stmt->fetchColumn() ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT d.donor_id) FROM donation d JOIN project p ON d.project_id = p.project_id WHERE p.assoc_id = ?");
    $stmt->execute([$assoc_id]);
    $donor_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    $error_message = "Database error: Could not load profile data.";
    error_log("Association Profile Load Error: " . $e->getMessage());
    $assoc = [];
    $project_count = 0;
    $total_raised = 0;
    $donor_count = 0;
}

$logo_path = $assoc['logo_path'] ?? 'assets/default-logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Profile - HelpHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
                        <a class="nav-link" href="dashboard-association.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile-association.php">
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

    <section class="py-5">
        <div class="container">
            <div class="mb-4">
                <h2 class="fw-bold">Association Profile</h2>
                <p class="text-muted">Manage your association's information and security settings.</p>
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
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4 text-center">
                        <div class="card-body">
                            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Association Logo" class="profile-image img-thumbnail mb-3">
                            <h5 class="card-title"><?php echo htmlspecialchars($assoc['name']); ?></h5>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($assoc['email']); ?></p>
                            <p class="text-muted">Fiscal ID: <?php echo htmlspecialchars($assoc['fiscal_id']); ?></p>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Account Stats</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Active Projects:</span>
                                <span><?php echo $project_count; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Raised:</span>
                                <span>$<?php echo number_format($total_raised, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Supporting Donors:</span>
                                <span><?php echo $donor_count; ?></span>
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
                    <div class="card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Edit Profile Information</h6>
                        </div>
                        <div class="card-body">
                            <form id="associationProfileForm" action="backend/association/update_profile.php" method="post" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Representative Name</label>
                                        <input type="text" class="form-control" id="name" name="representative_name" value="<?php echo htmlspecialchars($assoc['representative_name']); ?>" required>
                                        <div class="invalid-feedback" id="nameFeedback">Please enter the representative's name (minimum 2 characters).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="surname" class="form-label">Representative Surname</label>
                                        <input type="text" class="form-control" id="surname" name="representative_surname" value="<?php echo htmlspecialchars($assoc['representative_surname']); ?>" required>
                                        <div class="invalid-feedback" id="surnameFeedback">Please enter the representative's surname (minimum 2 characters).</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($assoc['email']); ?>" required>
                                    <div class="invalid-feedback" id="emailFeedback">Please enter a valid email address.</div>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label for="associationName" class="form-label">Association Name</label>
                                    <input type="text" class="form-control" id="associationName" name="name" value="<?php echo htmlspecialchars($assoc['name']); ?>" required>
                                    <div class="invalid-feedback" id="associationNameFeedback">Please enter the association name (minimum 3 characters).</div>
                                </div>
                                <div class="mb-3">
                                    <label for="associationAddress" class="form-label">Association Address</label>
                                    <textarea class="form-control" id="associationAddress" name="address" rows="3" required><?php echo htmlspecialchars($assoc['address']); ?></textarea>
                                    <div class="invalid-feedback" id="associationAddressFeedback">Please enter the association address (minimum 5 characters).</div>
                                </div>
                                <div class="mb-3">
                                    <label for="logo" class="form-label">Association Logo</label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/png, image/jpeg, image/gif">
                                    <small class="form-text text-muted">Optional. Max 2MB. Recommended: Square image (e.g., 200x200px).</small>
                                    <div class="invalid-feedback" id="logoFeedback">Please select a valid image file (JPEG, PNG, or GIF, max 2MB).</div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                            </form>
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
                    <p>Deleting your account will permanently remove all your association data, including projects and donation records associated with them. This cannot be undone.</p>
                    <form id="deleteAccountForm" action="backend/association/delete_account.php" method="post">
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
