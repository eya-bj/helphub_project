// JavaScript for Donor and Association Registration Forms
// This code checks form inputs and shows errors if something is wrong, with shared validation for common fields

// Function to show or hide error messages
function showError(input, feedbackId, show) {
    var feedback = document.getElementById(feedbackId);
    if (show) {
        input.classList.add('is-invalid');
        if (feedback) feedback.style.display = 'block';
    } else {
        input.classList.remove('is-invalid');
        if (feedback) feedback.style.display = 'none';
    }
}

// Shared validation functions for common fields
function validateName(input, feedbackId) {
    var isValid = input.value.length >= 2;
    showError(input, feedbackId, !isValid && input.value !== ''); // Show error only if not empty and invalid
    return isValid;
}

function validateSurname(input, feedbackId) {
    var isValid = input.value.length >= 2;
    showError(input, feedbackId, !isValid && input.value !== '');
    return isValid;
}

function validateEmail(input, feedbackId) {
    var value = input.value;
    // Basic regex for email validation
    var isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    showError(input, feedbackId, !isValid && value !== '');
    return isValid;
}

function validatePseudo(input, feedbackId) {
    var value = input.value;
    // Allow letters and numbers, minimum 3 chars
    var isValid = /^[a-zA-Z0-9]{3,}$/.test(value);
    showError(input, feedbackId, !isValid && value !== '');
    return isValid;
}

function validatePassword(input, feedbackId) {
    var value = input.value;
    // Minimum 8 characters, must end with $ or #
    var isValid = value.length >= 8 && (value.endsWith('$') || value.endsWith('#'));
    showError(input, feedbackId, !isValid && value !== '');
    return isValid;
}

function validateTermsCheck(input, feedbackId) {
    var isValid = input.checked;
    // Show error differently for checkbox
    var label = input.closest('.form-check').querySelector('.form-check-label');
    if (!isValid) {
        input.classList.add('is-invalid');
        if (label) label.classList.add('text-danger'); // Make label red
        if (document.getElementById(feedbackId)) document.getElementById(feedbackId).style.display = 'block';
    } else {
        input.classList.remove('is-invalid');
        if (label) label.classList.remove('text-danger');
        if (document.getElementById(feedbackId)) document.getElementById(feedbackId).style.display = 'none';
    }
    return isValid;
}


// Shared password toggle function
function setupPasswordToggle(passwordInput, toggleButton) {
    if (!passwordInput || !toggleButton) return; // Add null check
    toggleButton.addEventListener('click', function() {
        // Toggle the type attribute
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        // Toggle the eye icon
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
}

// Donor-specific validation
function validateCtn(input, feedbackId) {
    var value = input.value;
    var isValid = /^\d{8}$/.test(value); // Exactly 8 digits
    showError(input, feedbackId, !isValid && value !== '');
    return isValid;
}

// Association-specific validations
function validateCin(input, feedbackId) {
    var value = input.value;
    var isValid = /^\d{8}$/.test(value); // Exactly 8 digits
    showError(input, feedbackId, !isValid && value !== '');
    return isValid;
}

function validateAssociationName(input, feedbackId) {
    var isValid = input.value.length >= 3;
    showError(input, feedbackId, !isValid && input.value !== '');
    return isValid;
}

function validateAssociationAddress(input, feedbackId) {
    var isValid = input.value.length >= 5;
    showError(input, feedbackId, !isValid && input.value !== '');
    return isValid;
}

function validateFiscalId(input, feedbackId) {
    var value = input.value;
    // Format: $ followed by 3 uppercase letters and 2 digits
    var isValid = /^\$[A-Z]{3}\d{2}$/.test(value);
    showError(input, feedbackId, !isValid && value !== '');
    return isValid;
}

function validateLogo(input, feedbackId) {
    var file = input.files[0];
    if (!file) return true; // Optional field
    var isValid = file && (file.type === 'image/jpeg' || file.type === 'image/png' || file.type === 'image/gif');
    showError(input, feedbackId, !isValid);
    return isValid;
}

// --- Setup Event Listeners ---
document.addEventListener('DOMContentLoaded', function() {
    // Donor Registration Form Setup
    var donorForm = document.getElementById('donorRegisterForm');
    if (donorForm) {
        // Get input fields
        var nameInput = document.getElementById('name');
        var surnameInput = document.getElementById('surname');
        var emailInput = document.getElementById('email');
        var ctnInput = document.getElementById('ctn');
        var pseudoInput = document.getElementById('pseudo');
        var passwordInput = document.getElementById('password');
        var termsCheckInput = document.getElementById('termsCheck');
        var togglePasswordButton = document.getElementById('togglePassword');

        // Real-time validation listeners (Commented out)
        /*
        if (nameInput) nameInput.addEventListener('input', function() { validateName(this, 'nameFeedback'); });
        if (surnameInput) surnameInput.addEventListener('input', function() { validateSurname(this, 'surnameFeedback'); });
        if (emailInput) emailInput.addEventListener('input', function() { validateEmail(this, 'emailFeedback'); });
        if (ctnInput) ctnInput.addEventListener('input', function() { validateCtn(this, 'ctnFeedback'); });
        if (pseudoInput) pseudoInput.addEventListener('input', function() { validatePseudo(this, 'pseudoFeedback'); });
        if (passwordInput) passwordInput.addEventListener('input', function() { validatePassword(this, 'passwordFeedback'); });
        if (termsCheckInput) termsCheckInput.addEventListener('change', function() { validateTermsCheck(this, 'termsCheckFeedback'); });
        */

        // Setup password toggle (Keep this)
        setupPasswordToggle(passwordInput, togglePasswordButton);

        // REMOVED the submit event listener to allow default form submission
        // donorForm.addEventListener('submit', function(event) { ... });
    }

    // Association Registration Form Setup
    var associationForm = document.getElementById('associationRegisterForm');
    if (associationForm) {
        // Get input fields
        var repNameInput = document.getElementById('representative_name');
        var repSurnameInput = document.getElementById('representative_surname');
        var cinInput = document.getElementById('cin');
        var assocEmailInput = document.getElementById('email'); // Reusing emailInput variable name locally
        var associationNameInput = document.getElementById('name'); // Corrected ID based on HTML
        var associationAddressInput = document.getElementById('address'); // Corrected ID based on HTML
        var fiscalIdInput = document.getElementById('fiscal_id'); // Corrected ID based on HTML
        var logoInput = document.getElementById('logo');
        var assocPseudoInput = document.getElementById('pseudo'); // Reusing pseudoInput variable name locally
        var assocPasswordInput = document.getElementById('password'); // Reusing passwordInput variable name locally
        var assocTermsCheckInput = document.getElementById('termsCheck'); // Reusing termsCheckInput variable name locally
        var assocTogglePasswordButton = document.getElementById('togglePassword'); // Assuming only one toggle button on the page with this ID

        // Real-time validation listeners (Commented out)
        /*
        if (repNameInput) repNameInput.addEventListener('input', function() { validateName(this, 'nameFeedback'); });
        if (repSurnameInput) repSurnameInput.addEventListener('input', function() { validateSurname(this, 'surnameFeedback'); });
        if (cinInput) cinInput.addEventListener('input', function() { validateCin(this, 'cinFeedback'); });
        if (assocEmailInput) assocEmailInput.addEventListener('input', function() { validateEmail(this, 'emailFeedback'); });
        if (associationNameInput) associationNameInput.addEventListener('input', function() { validateAssociationName(this, 'associationNameFeedback'); });
        if (associationAddressInput) associationAddressInput.addEventListener('input', function() { validateAssociationAddress(this, 'associationAddressFeedback'); });
        if (fiscalIdInput) fiscalIdInput.addEventListener('input', function() { validateFiscalId(this, 'fiscalIdFeedback'); });
        if (logoInput) logoInput.addEventListener('change', function() { validateLogo(this, 'logoFeedback'); });
        if (assocPseudoInput) assocPseudoInput.addEventListener('input', function() { validatePseudo(this, 'pseudoFeedback'); });
        if (assocPasswordInput) assocPasswordInput.addEventListener('input', function() { validatePassword(this, 'passwordFeedback'); });
        if (assocTermsCheckInput) assocTermsCheckInput.addEventListener('change', function() { validateTermsCheck(this, 'termsCheckFeedback'); });
        */

        // Setup password toggle (Keep this)
        setupPasswordToggle(assocPasswordInput, assocTogglePasswordButton);

        // REMOVED the submit event listener to allow default form submission
        // associationForm.addEventListener('submit', function(event) { ... });
    }
});