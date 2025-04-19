// JavaScript for Donor and Association Registration Forms
// This code checks form inputs and shows errors if something is wrong, with shared validation for common fields

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

// Shared validation functions for common fields
function validateName(input, feedbackId) {
    var isValid = input.value.length >= 2;
    showError(input, feedbackId, !isValid && input.value != '');
    return isValid;
}

function validateSurname(input, feedbackId) {
    var isValid = input.value.length >= 2;
    showError(input, feedbackId, !isValid && input.value != '');
    return isValid;
}

function validateEmail(input, feedbackId) {
    var value = input.value;
    var isValid = value.includes('@') && value.includes('.');
    showError(input, feedbackId, !isValid && value != '');
    return isValid;
}

function validatePseudo(input, feedbackId) {
    var value = input.value;
    var isValid = true;
    for (var i = 0; i < value.length; i++) {
        var char = value[i];
        if (!(char >= 'A' && char <= 'Z') && !(char >= 'a' && char <= 'z')) {
            isValid = false;
            break;
        }
    }
    showError(input, feedbackId, !isValid && value != '');
    return isValid && value != '';
}

function validatePassword(input, feedbackId) {
    var value = input.value;
    var isValid = value.length >= 8 && (value.endsWith('$') || value.endsWith('#'));
    showError(input, feedbackId, !isValid && value != '');
    return isValid;
}

function validateTermsCheck(input, feedbackId) {
    var isValid = input.checked;
    showError(input, feedbackId, !isValid);
    return isValid;
}

// Shared password toggle function
function setupPasswordToggle(passwordInput, toggleButton) {
    toggleButton.addEventListener('click', function() {
        var icon = toggleButton.querySelector('i');
        if (passwordInput.type == 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
}

// Donor-specific validation
function validateCtn(input, feedbackId) {
    var value = input.value;
    var isValid = value.length == 8 && !isNaN(value);
    showError(input, feedbackId, !isValid && value != '');
    return isValid;
}

// Association-specific validations
function validateCin(input, feedbackId) {
    var value = input.value;
    var isValid = value.length == 8 && !isNaN(value);
    showError(input, feedbackId, !isValid && value != '');
    return isValid;
}

function validateAssociationName(input, feedbackId) {
    var isValid = input.value.length >= 3;
    showError(input, feedbackId, !isValid && input.value != '');
    return isValid;
}

function validateAssociationAddress(input, feedbackId) {
    var isValid = input.value.length >= 5;
    showError(input, feedbackId, !isValid && input.value != '');
    return isValid;
}

function validateFiscalId(input, feedbackId) {
    var value = input.value;
    var isValid = false;
    if (value.length == 6 && value[0] == '$') {
        var letters = value.slice(1, 4);
        var numbers = value.slice(4, 6);
        var hasLetters = letters.toUpperCase() == letters && letters.length == 3;
        var hasNumbers = !isNaN(numbers) && numbers.length == 2;
        isValid = hasLetters && hasNumbers;
    }
    showError(input, feedbackId, !isValid && value != '');
    return isValid;
}

function validateLogo(input, feedbackId) {
    var file = input.files[0];
    var isValid = file && (file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif');
    showError(input, feedbackId, !isValid);
    return isValid;
}

// Donor Registration Form
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

    // Real-time validation for shared fields
    nameInput.addEventListener('input', function() {
        validateName(nameInput, 'nameFeedback');
    });

    surnameInput.addEventListener('input', function() {
        validateSurname(surnameInput, 'surnameFeedback');
    });

    emailInput.addEventListener('input', function() {
        validateEmail(emailInput, 'emailFeedback');
    });

    pseudoInput.addEventListener('input', function() {
        validatePseudo(pseudoInput, 'pseudoFeedback');
    });

    passwordInput.addEventListener('input', function() {
        validatePassword(passwordInput, 'passwordFeedback');
    });

    termsCheckInput.addEventListener('change', function() {
        validateTermsCheck(termsCheckInput, 'termsCheckFeedback');
    });

    // Real-time validation for donor-specific field
    ctnInput.addEventListener('input', function() {
        validateCtn(ctnInput, 'ctnFeedback');
    });

    // Setup password toggle
    setupPasswordToggle(passwordInput, togglePasswordButton);

    // Form submission
    donorForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var isValid = true;

        // Validate shared fields
        if (!validateName(nameInput, 'nameFeedback')) isValid = false;
        if (!validateSurname(surnameInput, 'surnameFeedback')) isValid = false;
        if (!validateEmail(emailInput, 'emailFeedback')) isValid = false;
        if (!validatePseudo(pseudoInput, 'pseudoFeedback')) isValid = false;
        if (!validatePassword(passwordInput, 'passwordFeedback')) isValid = false;
        if (!validateTermsCheck(termsCheckInput, 'termsCheckFeedback')) isValid = false;

        // Validate donor-specific field
        if (!validateCtn(ctnInput, 'ctnFeedback')) isValid = false;

        // If all fields are valid, show success and redirect
        if (isValid) {
            alert('Registration successful! Redirecting to dashboard...');
            donorForm.reset();
            window.location.href = 'dashboard-donor.html';
        }
    });
}

// Association Registration Form
var associationForm = document.getElementById('associationRegisterForm');
if (associationForm) {
    // Get input fields
    var nameInput = document.getElementById('name');
    var surnameInput = document.getElementById('surname');
    var cinInput = document.getElementById('cin');
    var emailInput = document.getElementById('email');
    var associationNameInput = document.getElementById('associationName');
    var associationAddressInput = document.getElementById('associationAddress');
    var fiscalIdInput = document.getElementById('fiscalId');
    var logoInput = document.getElementById('logo');
    var pseudoInput = document.getElementById('pseudo');
    var passwordInput = document.getElementById('password');
    var termsCheckInput = document.getElementById('termsCheck');
    var togglePasswordButton = document.getElementById('togglePassword');

    // Real-time validation for shared fields
    nameInput.addEventListener('input', function() {
        validateName(nameInput, 'nameFeedback');
    });

    surnameInput.addEventListener('input', function() {
        validateSurname(surnameInput, 'surnameFeedback');
    });

    emailInput.addEventListener('input', function() {
        validateEmail(emailInput, 'emailFeedback');
    });

    pseudoInput.addEventListener('input', function() {
        validatePseudo(pseudoInput, 'pseudoFeedback');
    });

    passwordInput.addEventListener('input', function() {
        validatePassword(passwordInput, 'passwordFeedback');
    });

    termsCheckInput.addEventListener('change', function() {
        validateTermsCheck(termsCheckInput, 'termsCheckFeedback');
    });

    // Real-time validation for association-specific fields
    cinInput.addEventListener('input', function() {
        validateCin(cinInput, 'cinFeedback');
    });

    associationNameInput.addEventListener('input', function() {
        validateAssociationName(associationNameInput, 'associationNameFeedback');
    });

    associationAddressInput.addEventListener('input', function() {
        validateAssociationAddress(associationAddressInput, 'associationAddressFeedback');
    });

    fiscalIdInput.addEventListener('input', function() {
        validateFiscalId(fiscalIdInput, 'fiscalIdFeedback');
    });

    logoInput.addEventListener('change', function() {
        validateLogo(logoInput, 'logoFeedback');
    });

    // Setup password toggle
    setupPasswordToggle(passwordInput, togglePasswordButton);

    // Form submission
    associationForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var isValid = true;

        // Validate shared fields
        if (!validateName(nameInput, 'nameFeedback')) isValid = false;
        if (!validateSurname(surnameInput, 'surnameFeedback')) isValid = false;
        if (!validateEmail(emailInput, 'emailFeedback')) isValid = false;
        if (!validatePseudo(pseudoInput, 'pseudoFeedback')) isValid = false;
        if (!validatePassword(passwordInput, 'passwordFeedback')) isValid = false;
        if (!validateTermsCheck(termsCheckInput, 'termsCheckFeedback')) isValid = false;

        // Validate association-specific fields
        if (!validateCin(cinInput, 'cinFeedback')) isValid = false;
        if (!validateAssociationName(associationNameInput, 'associationNameFeedback')) isValid = false;
        if (!validateAssociationAddress(associationAddressInput, 'associationAddressFeedback')) isValid = false;
        if (!validateFiscalId(fiscalIdInput, 'fiscalIdFeedback')) isValid = false;
        if (!validateLogo(logoInput, 'logoFeedback')) isValid = false;

        // If all fields are valid, show success and redirect
        if (isValid) {
            alert('Registration successful! Redirecting to dashboard...');
            associationForm.reset();
            window.location.href = 'dashboard-association.html';
        }
    });
}