<!-- Message Handler -->
<script>
/**
 * Message Handler
 * Displays success and error messages from URL parameters
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check for success or error messages
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    // Show message if present
    if (success) {
        showMessage(getSuccessMessage(success, urlParams), 'success');
    } else if (error) {
        showMessage(getErrorMessage(error, urlParams), 'danger');
    }
    
    // Function to show message with Bootstrap alert
    function showMessage(message, type) {
        // Create alert container if it doesn't exist
        let alertContainer = document.getElementById('alert-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'alert-container';
            alertContainer.className = 'container mt-3';
            
            // Insert at the top of the page content, after navbar
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                navbar.parentNode.insertBefore(alertContainer, navbar.nextSibling);
            } else {
                document.body.insertBefore(alertContainer, document.body.firstChild);
            }
        }
        
        // Create the alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.role = 'alert';
        
        // Add icon based on type
        let icon = 'fa-info-circle';
        if (type === 'success') icon = 'fa-check-circle';
        if (type === 'danger') icon = 'fa-exclamation-triangle';
        
        alert.innerHTML = `
            <i class="fas ${icon} me-2"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to container
        alertContainer.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            try {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } catch (e) {
                alert.remove();
            }
        }, 5000);
    }
    
    // Success message mapping
    function getSuccessMessage(code, params) {
        const messages = {
            'donor_registered': `Registration successful! You can now login with your pseudo "${params.get('pseudo') || 'your username'}"`,
            'association_registered': `Association registration successful! You can now login with your credentials`,
            'login_successful': 'Login successful! Welcome to your dashboard',
            'logged_out': 'You have been logged out successfully',
            'project_added': 'Project has been added successfully',
            'project_deleted': 'Project has been deleted successfully',
            'donation_complete': `Thank you for your donation of $${params.get('amount') || ''}!`,
            'profile_updated': 'Your profile has been updated successfully',
            'message_sent': 'Your message has been sent. We will get back to you soon!'
        };
        
        return messages[code] || 'Operation completed successfully';
    }
    
    // Error message mapping
    function getErrorMessage(code, params) {
        const messages = {
            'invalid_method': 'Invalid request method',
            'missing_fields': 'Please fill in all required fields',
            'invalid_user_type': 'Invalid user type selected',
            'user_not_found': 'User not found. Please check your credentials',
            'invalid_password': 'Invalid password. Please try again',
            'database_error': 'A database error occurred. Please try again later',
            'unauthorized': 'You are not authorized to access this resource',
            'email_exists': 'Email already registered. Please use a different email',
            'pseudo_exists': 'Username already taken. Please choose a different username',
            'ctn_exists': 'CTN already registered',
            'cin_exists': 'CIN already registered',
            'fiscal_id_exists': 'Fiscal ID already registered',
            'invalid_file_type': 'Invalid file type. Only JPEG, PNG, and GIF are allowed',
            'upload_failed': 'File upload failed. Please try again',
            'invalid_amount': 'Please enter a valid amount',
            'project_not_found': 'Project not found or does not belong to your association',
            'donation_failed': 'Donation failed. Please try again later'
        };
        
        return messages[code] || 'An error occurred. Please try again';
    }
});
</script>
