// JavaScript for Donor and Association Registration Forms
// This code checks form inputs and shows errors if something is wrong, with shared validation for common fields

// Function to show or hide error messages
function showError(input, feedbackId, show) {
    var feedback = document.getElementById(feedbackId);
    if (show) {
        input.classList.add('is-invalid');
        if (feedback) feedback.style.display = 'block'; // Show feedback message
    } else {
        input.classList.remove('is-invalid');
        if (feedback) feedback.style.display = 'none'; // Hide feedback message
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
    // Allow letters and numbers, minimum 3 chars (Matches PHP)
    var isValid = /^[a-zA-Z0-9]{3,}$/.test(value);
    showError(input, feedbackId, !isValid && value !== '');
    return isValid;
}

function validatePassword(input, feedbackId) {
    var value = input.value;
    var isValid = value.length >= 8 && (value.endsWith('$') || value.endsWith('#'));
    showError(input, feedbackId, !isValid && value != '');
    return isValid;
}

function validateTermsCheck(input, feedbackId) {
    var isValid = input.checked;
    // Show error differently for checkbox
    var label = input.closest('.form-check').querySelector('.form-check-label');
    var feedback = document.getElementById(feedbackId); // Get feedback element
    if (!isValid) {
        input.classList.add('is-invalid');
        if (label) label.classList.add('text-danger'); // Optionally make label red
        if (feedback) feedback.style.display = 'block'; // Show feedback
    } else {
        input.classList.remove('is-invalid');
        if (label) label.classList.remove('text-danger');
        if (feedback) feedback.style.display = 'none'; // Hide feedback
    }
    return isValid;
}

// Shared password toggle function
function setupPasswordToggle(passwordInput, toggleButton) {
    if (!passwordInput || !toggleButton) return; // Exit if elements not found

    toggleButton.addEventListener('click', function() {
        // Toggle the type attribute
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Toggle the icon
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
}

// Donor-specific validation - renamed function from validateCtn to validateCin
function validateCin(input, feedbackId) {
    var value = input.value;
    // Accept only 8 digits
    var isValid = /^[0-9]{8}$/.test(value);
    
    // Show custom message based on format
    if (!isValid && value != '') {
        var feedback = document.getElementById(feedbackId);
        if (feedback) {
            feedback.textContent = "Please enter exactly 8 digits";
        }
        input.classList.add('is-invalid');
    } else {
        input.classList.remove('is-invalid');
        var feedback = document.getElementById(feedbackId);
        if (feedback) {
            feedback.style.display = 'none';
        }
    }
    
    return isValid;
}

// Association-specific validations - rename to associationCinValidation to avoid duplicate function
function validateAssociationCin(input, feedbackId) {
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

// --- Setup Event Listeners ---
document.addEventListener('DOMContentLoaded', function() {
    // Donor Registration Form Setup
    var donorForm = document.getElementById('donorRegisterForm');
    if (donorForm) {
        const nameInput = document.getElementById('name');
        const surnameInput = document.getElementById('surname');
        const emailInput = document.getElementById('email');
        const cinInput = document.getElementById('ctn'); // Keep ID as 'ctn' to match HTML
        const pseudoInput = document.getElementById('pseudo');
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePassword'); // Use specific ID if available
        const termsCheck = document.getElementById('termsCheck');

        // Setup password toggle
        setupPasswordToggle(passwordInput, togglePasswordBtn);

        // Add input event listeners for real-time validation
        nameInput?.addEventListener('input', () => validateName(nameInput, 'nameFeedback'));
        surnameInput?.addEventListener('input', () => validateSurname(surnameInput, 'surnameFeedback'));
        emailInput?.addEventListener('input', () => validateEmail(emailInput, 'emailFeedback'));
        cinInput?.addEventListener('input', () => validateCin(cinInput, 'ctnFeedback'));
        pseudoInput?.addEventListener('input', () => validatePseudo(pseudoInput, 'pseudoFeedback'));
        passwordInput?.addEventListener('input', () => validatePassword(passwordInput, 'passwordFeedback'));
        termsCheck?.addEventListener('change', () => validateTermsCheck(termsCheck, 'termsCheckFeedback'));

        donorForm.addEventListener('submit', function(event) {
            // Run all validations on submit
            const isNameValid = validateName(nameInput, 'nameFeedback');
            const isSurnameValid = validateSurname(surnameInput, 'surnameFeedback');
            const isEmailValid = validateEmail(emailInput, 'emailFeedback');
            const isCinValid = validateCin(cinInput, 'ctnFeedback');
            const isPseudoValid = validatePseudo(pseudoInput, 'pseudoFeedback');
            const isPasswordValid = validatePassword(passwordInput, 'passwordFeedback');
            const isTermsValid = validateTermsCheck(termsCheck, 'termsCheckFeedback');

            // Prevent submission if any validation fails
            if (!isNameValid || !isSurnameValid || !isEmailValid || !isCinValid || !isPseudoValid || !isPasswordValid || !isTermsValid) {
                event.preventDefault();
                // Optionally scroll to the first error or show a general message
                alert('Please correct the errors in the form.');
            }
        });
    }

    // Association Registration Form Setup
    var associationForm = document.getElementById('associationRegisterForm');
    if (associationForm) {
        const repNameInput = document.getElementById('representative_name');
        const repSurnameInput = document.getElementById('representative_surname');
        const cinInput = document.getElementById('cin');
        const emailInput = document.getElementById('email'); // Shared ID, ensure it's the correct one
        const assocNameInput = document.getElementById('name'); // Shared ID
        const assocAddressInput = document.getElementById('address');
        const fiscalIdInput = document.getElementById('fiscal_id');
        const logoInput = document.getElementById('logo');
        const pseudoInput = document.getElementById('pseudo'); // Shared ID
        const passwordInput = document.getElementById('password'); // Shared ID
        const togglePasswordBtn = associationForm.querySelector('.toggle-password'); // Find within form
        const termsCheck = document.getElementById('termsCheck'); // Shared ID

        // Setup password toggle
        setupPasswordToggle(passwordInput, togglePasswordBtn);

        // Add input event listeners
        repNameInput?.addEventListener('input', () => validateName(repNameInput, 'nameFeedback')); // Reusing nameFeedback ID, might need unique IDs
        repSurnameInput?.addEventListener('input', () => validateSurname(repSurnameInput, 'surnameFeedback')); // Reusing surnameFeedback ID
        cinInput?.addEventListener('input', () => validateAssociationCin(cinInput, 'cinFeedback'));
        emailInput?.addEventListener('input', () => validateEmail(emailInput, 'emailFeedback')); // Reusing emailFeedback ID
        assocNameInput?.addEventListener('input', () => validateAssociationName(assocNameInput, 'associationNameFeedback'));
        assocAddressInput?.addEventListener('input', () => validateAssociationAddress(assocAddressInput, 'associationAddressFeedback'));
        fiscalIdInput?.addEventListener('input', () => validateFiscalId(fiscalIdInput, 'fiscalIdFeedback'));
        logoInput?.addEventListener('change', () => validateLogo(logoInput, 'logoFeedback')); // Use 'change' for file inputs
        pseudoInput?.addEventListener('input', () => validatePseudo(pseudoInput, 'pseudoFeedback')); // Reusing pseudoFeedback ID
        passwordInput?.addEventListener('input', () => validatePassword(passwordInput, 'passwordFeedback')); // Reusing passwordFeedback ID
        termsCheck?.addEventListener('change', () => validateTermsCheck(termsCheck, 'termsCheckFeedback')); // Reusing termsCheckFeedback ID

        associationForm.addEventListener('submit', function(event) {
            // Run all validations
            const isRepNameValid = validateName(repNameInput, 'nameFeedback');
            const isRepSurnameValid = validateSurname(repSurnameInput, 'surnameFeedback');
            const isCinValid = validateAssociationCin(cinInput, 'cinFeedback');
            const isEmailValid = validateEmail(emailInput, 'emailFeedback');
            const isAssocNameValid = validateAssociationName(assocNameInput, 'associationNameFeedback');
            const isAssocAddressValid = validateAssociationAddress(assocAddressInput, 'associationAddressFeedback');
            const isFiscalIdValid = validateFiscalId(fiscalIdInput, 'fiscalIdFeedback');
            const isLogoValid = logoInput.files.length > 0 ? validateLogo(logoInput, 'logoFeedback') : true; // Logo might be optional
            const isPseudoValid = validatePseudo(pseudoInput, 'pseudoFeedback');
            const isPasswordValid = validatePassword(passwordInput, 'passwordFeedback');
            const isTermsValid = validateTermsCheck(termsCheck, 'termsCheckFeedback');

            // Prevent submission if invalid
            if (!isRepNameValid || !isRepSurnameValid || !isCinValid || !isEmailValid || !isAssocNameValid || !isAssocAddressValid || !isFiscalIdValid || !isLogoValid || !isPseudoValid || !isPasswordValid || !isTermsValid) {
                event.preventDefault();
                alert('Please correct the errors in the form.');
            }
        });
    }

    // Also setup password toggles globally if they exist outside forms (like login modal)
    document.querySelectorAll('.toggle-password').forEach(button => {
        // Find the target input using data-target or by proximity
        const targetSelector = button.getAttribute('data-target');
        let passwordField = targetSelector ? document.querySelector(targetSelector) : null;

        if (!passwordField && button.closest('.input-group')) {
            passwordField = button.closest('.input-group').querySelector('input[type="password"], input[type="text"]');
        }

        if (passwordField) {
            // Check if a listener is already attached by the form-specific setup
            // This is a simple check; more robust checks might be needed if complexity increases
            if (!passwordField.dataset.toggleListenerAttached) {
                 setupPasswordToggle(passwordField, button);
                 passwordField.dataset.toggleListenerAttached = 'true'; // Mark as attached
            }
        }
    });

    // Update error handling for URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        const error = urlParams.get('error');
        let errorMessage = '';
        
        switch(error) {
            case 'invalid_cin':
                errorMessage = 'Please enter a valid CIN (8 digits or CIN followed by 8 digits).';
                break;
            case 'cin_exists':
                errorMessage = 'This CIN is already registered.';
                break;
            case 'database':
                errorMessage = 'A database error occurred. Please try again later.';
                break;
            case 'missing_fields':
                errorMessage = 'Please fill in all required fields.';
                break;
            // Add other error cases as needed
            default:
                errorMessage = 'An error occurred during registration.';
        }
        
        // Create and insert error message at the top of the form
        if (errorMessage) {
            const form = document.querySelector('#donorRegisterForm, #associationRegisterForm');
            if (form) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger mb-4';
                errorDiv.innerHTML = errorMessage;
                form.parentNode.insertBefore(errorDiv, form);
            }
        }
    }
});
