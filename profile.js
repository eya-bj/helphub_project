// Handles profile updates, password change, and account deletion interactions

document.addEventListener('DOMContentLoaded', function() {
    
    // --- PROFILE UPDATE FORMS ---
    // Profile update forms (donor & association) are now handled by standard form POST
    // const donorProfileForm = document.getElementById('donorProfileForm');
    // if (donorProfileForm) {
    //     donorProfileForm.addEventListener('submit', function(event) {
    //         event.preventDefault(); // REMOVED
    //         // ... validation and fetch logic removed ...
    //     });
    // }
    // const associationProfileForm = document.getElementById('associationProfileForm');
    // if (associationProfileForm) {
    //     associationProfileForm.addEventListener('submit', function(event) {
    //         event.preventDefault(); // REMOVED
    //         // ... validation and fetch logic removed ...
    //     });
    // }

    // --- CHANGE PASSWORD MODAL ---
    const changePasswordForm = document.getElementById('changePasswordForm');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const currentPasswordInput = document.getElementById('currentPassword'); // Added

    if (changePasswordForm && newPasswordInput && confirmPasswordInput && currentPasswordInput) {
        // Real-time validation for password match
        confirmPasswordInput.addEventListener('input', function() {
            if (newPasswordInput.value !== confirmPasswordInput.value && confirmPasswordInput.value !== '') {
                confirmPasswordInput.classList.add('is-invalid');
                document.getElementById('confirmPasswordFeedback').style.display = 'block';
            } else {
                confirmPasswordInput.classList.remove('is-invalid');
                document.getElementById('confirmPasswordFeedback').style.display = 'none';
            }
        });

        // Real-time validation for new password format
        newPasswordInput.addEventListener('input', function() {
            const value = newPasswordInput.value;
            const isValid = value.length >= 8 && (value.endsWith('$') || value.endsWith('#'));
            if (!isValid && value !== '') {
                newPasswordInput.classList.add('is-invalid');
                document.getElementById('newPasswordFeedback').style.display = 'block';
            } else {
                newPasswordInput.classList.remove('is-invalid');
                document.getElementById('newPasswordFeedback').style.display = 'none';
            }
            // Re-check confirmation if new password changes
            if (confirmPasswordInput.value !== '' && value !== confirmPasswordInput.value) {
                 confirmPasswordInput.classList.add('is-invalid');
                 document.getElementById('confirmPasswordFeedback').style.display = 'block';
            } else if (confirmPasswordInput.value !== '') {
                 confirmPasswordInput.classList.remove('is-invalid');
                 document.getElementById('confirmPasswordFeedback').style.display = 'none';
            }
        });
        
        // Change password form submission is now handled by standard form POST
        // Need to add action and method to the form in HTML and create backend/auth/change_password.php
        // changePasswordForm.addEventListener('submit', function(event) {
        //     event.preventDefault(); // REMOVED
        //     // ... validation and fetch logic removed ...
        // });
    }

    // --- DELETE ACCOUNT MODAL ---
    const deleteAccountForm = document.getElementById('deleteAccountForm');
    const deleteConfirmInput = document.getElementById('deleteConfirm');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    if (deleteAccountForm && deleteConfirmInput && confirmDeleteBtn) {
        // Enable delete button only when "DELETE" is typed correctly
        deleteConfirmInput.addEventListener('input', function() {
            if (deleteConfirmInput.value === 'DELETE') {
                confirmDeleteBtn.disabled = false;
                deleteConfirmInput.classList.remove('is-invalid');
                document.getElementById('deleteConfirmFeedback').style.display = 'none';
            } else {
                confirmDeleteBtn.disabled = true;
                if (deleteConfirmInput.value !== '') {
                    deleteConfirmInput.classList.add('is-invalid');
                    document.getElementById('deleteConfirmFeedback').style.display = 'block';
                } else {
                     deleteConfirmInput.classList.remove('is-invalid');
                     document.getElementById('deleteConfirmFeedback').style.display = 'none';
                }
            }
        });

        // Delete account form submission is now handled by standard form POST
        // Need to add action and method to the form in HTML and create backend/auth/delete_account.php
        // deleteAccountForm.addEventListener('submit', function(event) {
        //     event.preventDefault(); // REMOVED
        //     // ... fetch logic removed ...
        // });
    }
});

// Note: You will need to create the corresponding PHP endpoints:
// - backend/auth/change_password.php
// - backend/auth/delete_account.php
// And add `action` and `method="post"` attributes to the changePasswordForm and deleteAccountForm in the HTML files.