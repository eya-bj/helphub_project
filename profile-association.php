<?php
session_start();
require_once 'backend/db.php'; // Adjust path as needed

// Check if user is logged in as an association
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'association') {
    header("Location: index.php?error=unauthorized");  // Changed from index.html
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

    // Fetch association stats (projects, funds raised, donors)
    // Count active projects
    $stmt_proj_count = $pdo->prepare("SELECT COUNT(*) FROM project WHERE assoc_id = ? AND end_date >= CURDATE()"); // Assuming active means end_date is in the future
    $stmt_proj_count->execute([$assoc_id]);
    $project_count = $stmt_proj_count->fetchColumn();

    // Calculate total funds raised across all projects
    $stmt_funds = $pdo->prepare("SELECT SUM(d.amount) FROM donation d JOIN project p ON d.project_id = p.project_id WHERE p.assoc_id = ?");
    $stmt_funds->execute([$assoc_id]);
    $total_raised = $stmt_funds->fetchColumn() ?? 0;

    // Count unique supporting donors across all projects
    $stmt_donors = $pdo->prepare("SELECT COUNT(DISTINCT d.donor_id) FROM donation d JOIN project p ON d.project_id = p.project_id WHERE p.assoc_id = ?");
    $stmt_donors->execute([$assoc_id]);
    $donor_count = $stmt_donors->fetchColumn() ?? 0;


} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    $error_message = "Could not load profile data.";
    // Initialize variables
    $assoc = ['name' => '', 'address' => '', 'fiscal_id' => '', 'logo_path' => null, 'pseudo' => '', 'email' => '', 'representative_name' => '', 'representative_surname' => '', 'cin' => ''];
    $project_count = 0;
    $total_raised = 0;
    $donor_count = 0;
}

// Default logo if none is set
$logo_display_path = !empty($assoc['logo_path']) ? htmlspecialchars($assoc['logo_path']) : 'assets/default-logo.png'; // Assuming you have a default logo image

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Profile - HelpHub</title>
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
             <a class="navbar-brand" href="dashboard-association.php"> <!-- Link to dashboard -->
                <span class="text-primary fw-bold">Help</span><span class="text-secondary">Hub</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-association.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile-association.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="backend/auth/logout.php">Logout</a> <!-- Link to logout script -->
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Content -->
    <section class="py-5">
        <div class="container">
            <div class="mb-4">
                <h2><i class="fas fa-building me-2"></i>Association Profile Management</h2>
                <p class="lead">Update your organization information and account settings</p>
            </div>

             <!-- Display Feedback Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        switch ($success_message) {
                            case 'updated': echo 'Profile updated successfully!'; break;
                            case 'password_changed': echo 'Password changed successfully!'; break;
                            // Add more success cases if needed
                        }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                     <?php 
                        // You can create more user-friendly messages based on error codes
                        switch ($error_message) {
                            case 'update_failed': echo 'Failed to update profile. Please try again.'; break;
                            case 'invalid_input': echo 'Invalid input provided.'; break;
                            case 'file_upload_error': echo 'Error uploading logo.'; break;
                            case 'invalid_file_type': echo 'Invalid logo file type.'; break;
                            case 'current_password_incorrect': echo 'Current password incorrect.'; break;
                            case 'password_mismatch': echo 'New passwords do not match.'; break;
                            case 'password_format': echo 'New password does not meet format requirements.'; break;
                            case 'delete_failed': echo 'Failed to delete account.'; break;
                            case 'confirm_delete': echo 'Confirmation text did not match.'; break;
                            default: echo htmlspecialchars($error_message); break; // Default or specific DB errors
                        }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Left sidebar - profile summary -->
                <div class="col-lg-4 mb-4">
                    <!-- Profile Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                             <!-- Display Logo -->
                            <img src="<?php echo $logo_display_path; ?>" alt="<?php echo htmlspecialchars($assoc['name']); ?> Logo" class="profile-image img-thumbnail mb-3" style="width: 100px; height: 100px; object-fit: contain;">
                            <h4><?php echo htmlspecialchars($assoc['name']); ?></h4>
                            <p class="text-muted mb-1">Association</p>
                            <p class="text-muted">Fiscal ID: <?php echo htmlspecialchars($assoc['fiscal_id']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
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
                    
                    <!-- Account Security -->
                    <div class="card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Account Security</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                    <i class="fas fa-trash-alt me-2"></i>Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side - profile form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Edit Profile Information</h6>
                        </div>
                        <div class="card-body">
                            <!-- Association Form -->
                            <form id="associationProfileForm" action="backend/association/update_profile.php" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Representative Name</label>
                                        <input type="text" class="form-control" id="name" name="representative_name" value="<?php echo htmlspecialchars($assoc['representative_name']); ?>" required aria-required="true">
                                        <div class="invalid-feedback" id="nameFeedback">
                                            Please enter your name (minimum 2 characters).
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="surname" class="form-label">Representative Surname</label>
                                        <input type="text" class="form-control" id="surname" name="representative_surname" value="<?php echo htmlspecialchars($assoc['representative_surname']); ?>" required aria-required="true">
                                        <div class="invalid-feedback" id="surnameFeedback">
                                            Please enter your surname (minimum 2 characters).
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cin" class="form-label">CIN</label>
                                        <input type="text" class="form-control" id="cin" value="<?php echo htmlspecialchars($assoc['cin']); ?>" readonly>
                                        <div class="form-text text-muted">CIN cannot be changed.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($assoc['email']); ?>" required aria-required="true">
                                        <div class="invalid-feedback" id="emailFeedback">
                                            Please enter a valid email address.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="associationName" class="form-label">Association Name</label>
                                    <input type="text" class="form-control" id="associationName" name="name" value="<?php echo htmlspecialchars($assoc['name']); ?>" required aria-required="true">
                                    <div class="invalid-feedback" id="associationNameFeedback">
                                        Please enter the association name (minimum 3 characters).
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="associationAddress" class="form-label">Association Address</label>
                                    <textarea class="form-control" id="associationAddress" name="address" rows="2" required aria-required="true"><?php echo htmlspecialchars($assoc['address']); ?></textarea>
                                    <div class="invalid-feedback" id="associationAddressFeedback">
                                        Please enter the association address (minimum 5 characters).
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="fiscalId" class="form-label">Fiscal ID</label>
                                    <input type="text" class="form-control" id="fiscalId" value="<?php echo htmlspecialchars($assoc['fiscal_id']); ?>" readonly>
                                    <div class="form-text text-muted">Fiscal ID cannot be changed.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="logo" class="form-label">Association Logo</label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png,image/gif">
                                    <div class="invalid-feedback" id="logoFeedback">
                                        Please select a valid image file (JPEG, PNG, or GIF).
                                    </div>
                                    <div class="form-text text-muted">Leave empty to keep current logo. Max 2MB.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="pseudo" class="form-label">Pseudo</label>
                                    <input type="text" class="form-control" id="pseudo" value="<?php echo htmlspecialchars($assoc['pseudo']); ?>" readonly>
                                    <div class="form-text text-muted">Pseudo cannot be changed.</div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" type="submit">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Change Password Modal -->
     <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Update form action -->
                    <form id="changePasswordForm" action="backend/common/change_password.php" method="post">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required aria-required="true">
                            <div class="invalid-feedback" id="currentPasswordFeedback">
                                Please enter your current password.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required aria-required="true">
                            <div class="invalid-feedback" id="newPasswordFeedback">
                                Password must be at least 8 characters and end with $ or #.
                            </div>
                            <div class="form-text">At least 8 characters, must end with $ or #</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required aria-required="true">
                            <div class="invalid-feedback" id="confirmPasswordFeedback">
                                Passwords do not match.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="changePasswordForm" class="btn btn-primary">Save New Password</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> Warning: This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete your account? All your data, including projects and donation records associated with this association, will be permanently removed.</p>
                     <!-- Update form action -->
                    <form id="deleteAccountForm" action="backend/common/delete_account.php" method="post">
                        <div class="mb-3">
                            <label for="deleteConfirm" class="form-label">Type "DELETE" to confirm</label>
                            <input type="text" class="form-control" id="deleteConfirm" name="confirm_text" required aria-required="true">
                            <div class="invalid-feedback" id="deleteConfirmFeedback">
                                Please type "DELETE" to confirm.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteAccountForm" class="btn btn-danger" disabled id="confirmDeleteBtn">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
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
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="profile.js"></script>
</body>
</html>
