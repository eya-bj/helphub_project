// JavaScript for Association and Donor Profile Forms and Modals

// Function to show or hide error messages
function showError(input, feedbackId, show) {
    var feedback = document.getElementById(feedbackId);
    if (show) {
        input.classList.add('is-invalid'); // Red border
        feedback.style.display = 'block'; // Show error message
    } else {
        input.classList.remove('is-invalid'); // Normal border
        feedback.style.display = 'none'; // Hide error message
    }
}

// Get all forms
var associationForm = document.getElementById('associationProfileForm');
var donorForm = document.getElementById('donorProfileForm');
var passwordForm = document.getElementById('changePasswordForm');
var deleteForm = document.getElementById('deleteAccountForm');

// Association Profile Form Validation
if (associationForm) {
    // Get inputs
    var nameInput = document.getElementById('name');
    var surnameInput = document.getElementById('surname');
    var emailInput = document.getElementById('email');
    var associationNameInput = document.getElementById('associationName');
    var associationAddressInput = document.getElementById('associationAddress');
    var logoInput = document.getElementById('logo');

    // Real-time validation
    nameInput.addEventListener('input', function() {
        if (nameInput.value.length < 2 && nameInput.value != '') {
            showError(nameInput, 'nameFeedback', true);
        } else {
            showError(nameInput, 'nameFeedback', false);
        }
    });

    surnameInput.addEventListener('input', function() {
        if (surnameInput.value.length < 2 && surnameInput.value != '') {
            showError(surnameInput, 'surnameFeedback', true);
        } else {
            showError(surnameInput, 'surnameFeedback', false);
        }
    });

    emailInput.addEventListener('input', function() {
        var value = emailInput.value;
        var isValid = value.includes('@') && value.includes('.');
        if (!isValid && value != '') {
            showError(emailInput, 'emailFeedback', true);
        } else {
            showError(emailInput, 'emailFeedback', false);
        }
    });

    associationNameInput.addEventListener('input', function() {
        if (associationNameInput.value.length < 3 && associationNameInput.value != '') {
            showError(associationNameInput, 'associationNameFeedback', true);
        } else {
            showError(associationNameInput, 'associationNameFeedback', false);
        }
    });

    associationAddressInput.addEventListener('input', function() {
        if (associationAddressInput.value.length < 5 && associationAddressInput.value != '') {
            showError(associationAddressInput, 'associationAddressFeedback', true);
        } else {
            showError(associationAddressInput, 'associationAddressFeedback', false);
        }
    });

    logoInput.addEventListener('change', function() {
        var file = logoInput.files[0];
        var isValid = !file || (file && (file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif'));
        if (!isValid) {
            showError(logoInput, 'logoFeedback', true);
        } else {
            showError(logoInput, 'logoFeedback', false);
        }
    });

    // Form submission
    associationForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var isValid = true;

        if (nameInput.value.length < 2) {
            showError(nameInput, 'nameFeedback', true);
            isValid = false;
        } else {
            showError(nameInput, 'nameFeedback', false);
        }

        if (surnameInput.value.length < 2) {
            showError(surnameInput, 'surnameFeedback', true);
            isValid = false;
        } else {
            showError(surnameInput, 'surnameFeedback', false);
        }

        var emailValue = emailInput.value;
        if (!emailValue.includes('@') || !emailValue.includes('.')) {
            showError(emailInput, 'emailFeedback', true);
            isValid = false;
        } else {
            showError(emailInput, 'emailFeedback', false);
        }

        if (associationNameInput.value.length < 3) {
            showError(associationNameInput, 'associationNameFeedback', true);
            isValid = false;
        } else {
            showError(associationNameInput, 'associationNameFeedback', false);
        }

        if (associationAddressInput.value.length < 5) {
            showError(associationAddressInput, 'associationAddressFeedback', true);
            isValid = false;
        } else {
            showError(associationAddressInput, 'associationAddressFeedback', false);
        }

        var file = logoInput.files[0];
        var logoValid = !file || (file && (file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif'));
        if (!logoValid) {
            showError(logoInput, 'logoFeedback', true);
            isValid = false;
        } else {
            showError(logoInput, 'logoFeedback', false);
        }

        if (isValid) {
            alert('Profile updated successfully!');
            associationForm.reset();
        }
    });
}

// Donor Profile Form Validation
if (donorForm) {
    // Get inputs
    var donorNameInput = document.getElementById('donorName');
    var donorSurnameInput = document.getElementById('donorSurname');
    var donorEmailInput = document.getElementById('donorEmail');

    // Real-time validation
    donorNameInput.addEventListener('input', function() {
        if (donorNameInput.value.length < 2 && donorNameInput.value != '') {
            showError(donorNameInput, 'donorNameFeedback', true);
        } else {
            showError(donorNameInput, 'donorNameFeedback', false);
        }
    });

    donorSurnameInput.addEventListener('input', function() {
        if (donorSurnameInput.value.length < 2 && donorSurnameInput.value != '') {
            showError(donorSurnameInput, 'donorSurnameFeedback', true);
        } else {
            showError(donorSurnameInput, 'donorSurnameFeedback', false);
        }
    });

    donorEmailInput.addEventListener('input', function() {
        var value = donorEmailInput.value;
        var isValid = value.includes('@') && value.includes('.');
        if (!isValid && value != '') {
            showError(donorEmailInput, 'donorEmailFeedback', true);
        } else {
            showError(donorEmailInput, 'donorEmailFeedback', false);
        }
    });

    // Form submission
    donorForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var isValid = true;

        if (donorNameInput.value.length < 2) {
            showError(donorNameInput, 'donorNameFeedback', true);
            isValid = false;
        } else {
            showError(donorNameInput, 'donorNameFeedback', false);
        }

        if (donorSurnameInput.value.length < 2) {
            showError(donorSurnameInput, 'donorSurnameFeedback', true);
            isValid = false;
        } else {
            showError(donorSurnameInput, 'donorSurnameFeedback', false);
        }

        var emailValue = donorEmailInput.value;
        if (!emailValue.includes('@') || !emailValue.includes('.')) {
            showError(donorEmailInput, 'donorEmailFeedback', true);
            isValid = false;
        } else {
            showError(donorEmailInput, 'donorEmailFeedback', false);
        }

        if (isValid) {
            alert('Profile updated successfully!');
            donorForm.reset();
        }
    });
}

// Change Password Form Validation (used in both profiles)
if (passwordForm) {
    // Get inputs
    var currentPasswordInput = document.getElementById('currentPassword');
    var newPasswordInput = document.getElementById('newPassword');
    var confirmPasswordInput = document.getElementById('confirmPassword');

    // Real-time validation
    currentPasswordInput.addEventListener('input', function() {
        if (currentPasswordInput.value == '') {
            showError(currentPasswordInput, 'currentPasswordFeedback', true);
        } else {
            showError(currentPasswordInput, 'currentPasswordFeedback', false);
        }
    });

    newPasswordInput.addEventListener('input', function() {
        var value = newPasswordInput.value;
        var isValid = value.length >= 8 && (value.endsWith('$') || value.endsWith('#'));
        if (!isValid && value != '') {
            showError(newPasswordInput, 'newPasswordFeedback', true);
        } else {
            showError(newPasswordInput, 'newPasswordFeedback', false);
        }
    });

    confirmPasswordInput.addEventListener('input', function() {
        var isValid = confirmPasswordInput.value == newPasswordInput.value;
        if (!isValid && confirmPasswordInput.value != '') {
            showError(confirmPasswordInput, 'confirmPasswordFeedback', true);
        } else {
            showError(confirmPasswordInput, 'confirmPasswordFeedback', false);
        }
    });

    // Form submission
    passwordForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var isValid = true;

        if (currentPasswordInput.value == '') {
            showError(currentPasswordInput, 'currentPasswordFeedback', true);
            isValid = false;
        } else {
            showError(currentPasswordInput, 'currentPasswordFeedback', false);
        }

        var newPasswordValue = newPasswordInput.value;
        var newPasswordValid = newPasswordValue.length >= 8 && (newPasswordValue.endsWith('$') || newPasswordValue.endsWith('#'));
        if (!newPasswordValid) {
            showError(newPasswordInput, 'newPasswordFeedback', true);
            isValid = false;
        } else {
            showError(newPasswordInput, 'newPasswordFeedback', false);
        }

        var confirmPasswordValid = confirmPasswordInput.value == newPasswordInput.value;
        if (!confirmPasswordValid) {
            showError(confirmPasswordInput, 'confirmPasswordFeedback', true);
            isValid = false;
        } else {
            showError(confirmPasswordInput, 'confirmPasswordFeedback', false);
        }

        if (isValid) {
            alert('Password changed successfully!');
            passwordForm.reset();
            // Close modal
            var modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
            modal.hide();
        }
    });
}

// Delete Account Form Validation (used in both profiles)
if (deleteForm) {
    // Get inputs
    var deleteConfirmInput = document.getElementById('deleteConfirm');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    // Real-time validation and button enable/disable
    deleteConfirmInput.addEventListener('input', function() {
        var isValid = deleteConfirmInput.value == 'DELETE';
        if (!isValid) {
            showError(deleteConfirmInput, 'deleteConfirmFeedback', true);
            confirmDeleteBtn.disabled = true;
        } else {
            showError(deleteConfirmInput, 'deleteConfirmFeedback', false);
            confirmDeleteBtn.disabled = false;
        }
    });

    // Form submission
    deleteForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var isValid = deleteConfirmInput.value == 'DELETE';
        if (!isValid) {
            showError(deleteConfirmInput, 'deleteConfirmFeedback', true);
        } else {
            showError(deleteConfirmInput, 'deleteConfirmFeedback', false);
            alert('Account deleted successfully! Redirecting to homepage...');
            deleteForm.reset();
            // Close modal
            var modal = bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal'));
            modal.hide();
            // Redirect to homepage
            window.location.href = 'index.html';
        }
    });
}