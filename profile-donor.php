<?php
session_start();
require_once 'backend/db.php'; // Adjust path as needed

// Check if user is logged in as a donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'donor') {
    header("Location: index.html?error=unauthorized");
    exit;
}

$donor_id = $_SESSION['user_id'];
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null;
$debug_message = ""; // For debugging

try {
    // Test database connection first
    $pdo->query("SELECT 1");
    
    // Fetch donor details
    $stmt = $pdo->prepare("SELECT name, surname, email, ctn, pseudo, created_at, profile_image FROM donor WHERE donor_id = ?");
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        // Handle case where donor data is not found (should not happen if session is valid)
        $debug_message = "No donor found with ID: $donor_id";
        error_log($debug_message);
        // Don't destroy session yet - show error instead
    }

    // Fetch donation stats - only if donor was found
    if ($donor) {
        $stmt_stats = $pdo->prepare("
            SELECT 
                COUNT(donation_id) as total_donations_count, 
                SUM(amount) as total_donations_amount,
                COUNT(DISTINCT d.project_id) as projects_supported_count,
                COUNT(DISTINCT p.assoc_id) as associations_supported_count
            FROM donation d
            LEFT JOIN project p ON d.project_id = p.project_id
            WHERE d.donor_id = ?
        ");
        $stmt_stats->execute([$donor_id]);
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

        // Format dates or numbers if needed
        $member_since = date("F Y", strtotime($donor['created_at']));
        $total_donations_amount = $stats['total_donations_amount'] ?? 0;
        $projects_supported_count = $stats['projects_supported_count'] ?? 0;
        $associations_supported_count = $stats['associations_supported_count'] ?? 0;
    } else {
        // Initialize variables to avoid errors in HTML
        $donor = ['name' => 'Unknown', 'surname' => 'User', 'email' => 'not.available@example.com', 'ctn' => 'N/A', 'pseudo' => 'unknown', 'profile_image' => null];
        $member_since = 'N/A';
        $total_donations_amount = 0;
        $projects_supported_count = 0;
        $associations_supported_count = 0;
    }

} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    $debug_message = "Database error: " . $e->getMessage();
    
    // Initialize variables to avoid errors in HTML
    $donor = ['name' => 'Error', 'surname' => 'Loading', 'email' => 'error@example.com', 'ctn' => 'N/A', 'pseudo' => 'error', 'profile_image' => null];
    $member_since = 'N/A';
    $total_donations_amount = 0;
    $projects_supported_count = 0;
    $associations_supported_count = 0;
    
    // Set error message
    $error_message = "Could not load profile data. Please try again later.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Profile - HelpHub</title>
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
            <a class="navbar-brand" href="dashboard-donor.php"> <!-- Link to dashboard -->
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
                        <a class="nav-link" href="projects.php">Projects</a> <!-- Link to projects page -->
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile-donor.php">Profile</a>
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
                <h2><i class="fas fa-user-circle me-2"></i>Donor Profile Management</h2>
                <p class="lead">Update your personal information and account settings</p>
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
            
            <?php if (!empty($debug_message) && (isset($_GET['debug']) || true)): ?>
                <div class="alert alert-warning">
                    <h5>Debug Information (Admin only)</h5>
                    <p><?php echo htmlspecialchars($debug_message); ?></p>
                    <p>User ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
                    <p>User Type: <?php echo htmlspecialchars($_SESSION['user_type']); ?></p>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Left sidebar - profile summary -->
                <div class="col-lg-4 mb-4">
                    <!-- Profile Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                            <div class="profile-image-container mb-3">
                                <?php if (!empty($donor['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($donor['profile_image']); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-inline-flex mx-auto" style="width: 100px; height: 100px;">
                                        <i class="fas fa-user fa-3x m-auto text-primary"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo htmlspecialchars($donor['name'] . ' ' . $donor['surname']); ?></h4>
                            <p class="text-muted mb-1">Donor</p>
                            <p class="text-muted">Member since <?php echo htmlspecialchars($member_since); ?></p>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Account Stats</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Donations:</span>
                                <span>$<?php echo number_format($total_donations_amount, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Projects Supported:</span>
                                <span><?php echo $projects_supported_count; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Associations Supported:</span>
                                <span><?php echo $associations_supported_count; ?></span>
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
                            <!-- Donor Form -->
                            <form id="donorProfileForm" action="backend/donor/update_profile.php" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="donorName" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="donorName" name="name" value="<?php echo htmlspecialchars($donor['name']); ?>" required aria-required="true">
                                        <div class="invalid-feedback" id="donorNameFeedback">
                                            Please enter your name (minimum 2 characters).
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="donorSurname" class="form-label">Surname</label>
                                        <input type="text" class="form-control" id="donorSurname" name="surname" value="<?php echo htmlspecialchars($donor['surname']); ?>" required aria-required="true">
                                        <div class="invalid-feedback" id="donorSurnameFeedback">
                                            Please enter your surname (minimum 2 characters).
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="donorEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="donorEmail" name="email" value="<?php echo htmlspecialchars($donor['email']); ?>" required aria-required="true">
                                        <div class="invalid-feedback" id="donorEmailFeedback">
                                            Please enter a valid email address.
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="ctn" class="form-label">CTN</label>
                                        <input type="text" class="form-control" id="ctn" value="<?php echo htmlspecialchars($donor['ctn']); ?>" readonly>
                                        <div class="form-text text-muted">CTN cannot be changed.</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="donorPseudo" class="form-label">Pseudo</label>
                                    <input type="text" class="form-control" id="donorPseudo" value="<?php echo htmlspecialchars($donor['pseudo']); ?>" readonly>
                                    <div class="form-text text-muted">Pseudo cannot be changed.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="profileImage" class="form-label">Profile Picture</label>
                                    <input type="file" class="form-control" id="profileImage" name="profile_image" accept="image/jpeg,image/png,image/gif">
                                    <div class="invalid-feedback" id="profileImageFeedback">
                                        Please select a valid image file (JPEG, PNG, or GIF).
                                    </div>
                                    <div class="form-text text-muted">Leave empty to keep current picture. Max 2MB.</div>
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
                    <p>Are you sure you want to delete your account? All your data will be permanently removed.</p>
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
