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
    showError(input, feedbackId, !isValid);
    return isValid;
}

function setupPasswordToggle(passwordInput, toggleButton) {
    if (!passwordInput || !toggleButton) return;

    toggleButton.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
}

function validateCtn(input, feedbackId) {
    var value = input.value;
    var isValid = value.length == 8 && !isNaN(value);
    showError(input, feedbackId, !isValid && value != '');
    return isValid;
}

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
    var isValid = true;
    var feedback = document.getElementById(feedbackId);

    if (file) {
        var allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        var maxSize = 2 * 1024 * 1024; // 2MB

        if (!allowedTypes.includes(file.type)) {
            feedback.textContent = 'Please select a valid image file (JPEG, PNG, or GIF).';
            isValid = false;
        } else if (file.size > maxSize) {
            feedback.textContent = 'File is too large (Max 2MB).';
            isValid = false;
        }
    }
    showError(input, feedbackId, !isValid);
    return isValid;
}

document.addEventListener('DOMContentLoaded', function() {
    var donorForm = document.getElementById('donorRegisterForm');
    if (donorForm) {
        const nameInput = document.getElementById('name');
        const surnameInput = document.getElementById('surname');
        const emailInput = document.getElementById('email');
        const ctnInput = document.getElementById('ctn');
        const pseudoInput = document.getElementById('pseudo');
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const termsCheck = document.getElementById('termsCheck');

        setupPasswordToggle(passwordInput, togglePasswordBtn);

        nameInput?.addEventListener('input', () => validateName(nameInput, 'nameFeedback'));
        surnameInput?.addEventListener('input', () => validateSurname(surnameInput, 'surnameFeedback'));
        emailInput?.addEventListener('input', () => validateEmail(emailInput, 'emailFeedback'));
        ctnInput?.addEventListener('input', () => validateCtn(ctnInput, 'ctnFeedback'));
        pseudoInput?.addEventListener('input', () => validatePseudo(pseudoInput, 'pseudoFeedback'));
        passwordInput?.addEventListener('input', () => validatePassword(passwordInput, 'passwordFeedback'));
        termsCheck?.addEventListener('change', () => validateTermsCheck(termsCheck, 'termsCheckFeedback'));

        donorForm.addEventListener('submit', function(event) {
            const isNameValid = validateName(nameInput, 'nameFeedback');
            const isSurnameValid = validateSurname(surnameInput, 'surnameFeedback');
            const isEmailValid = validateEmail(emailInput, 'emailFeedback');
            const isCtnValid = validateCtn(ctnInput, 'ctnFeedback');
            const isPseudoValid = validatePseudo(pseudoInput, 'pseudoFeedback');
            const isPasswordValid = validatePassword(passwordInput, 'passwordFeedback');
            const isTermsValid = validateTermsCheck(termsCheck, 'termsCheckFeedback');

            if (!isNameValid || !isSurnameValid || !isEmailValid || !isCtnValid || !isPseudoValid || !isPasswordValid || !isTermsValid) {
                event.preventDefault();
                alert('Please correct the errors in the form.');
            }
        });
    }

    var associationForm = document.getElementById('associationRegisterForm');
    if (associationForm) {
        const repNameInput = document.getElementById('representative_name');
        const repSurnameInput = document.getElementById('representative_surname');
        const cinInput = document.getElementById('cin');
        const emailInput = document.getElementById('email');
        const assocNameInput = document.getElementById('name');
        const assocAddressInput = document.getElementById('address');
        const fiscalIdInput = document.getElementById('fiscal_id');
        const logoInput = document.getElementById('logo');
        const pseudoInput = document.getElementById('pseudo');
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = associationForm.querySelector('.toggle-password');
        const termsCheck = document.getElementById('termsCheck');

        setupPasswordToggle(passwordInput, togglePasswordBtn);

        repNameInput?.addEventListener('input', () => validateName(repNameInput, 'nameFeedback'));
        repSurnameInput?.addEventListener('input', () => validateSurname(repSurnameInput, 'surnameFeedback'));
        cinInput?.addEventListener('input', () => validateCin(cinInput, 'cinFeedback'));
        emailInput?.addEventListener('input', () => validateEmail(emailInput, 'emailFeedback'));
        assocNameInput?.addEventListener('input', () => validateAssociationName(assocNameInput, 'associationNameFeedback'));
        assocAddressInput?.addEventListener('input', () => validateAssociationAddress(assocAddressInput, 'associationAddressFeedback'));
        fiscalIdInput?.addEventListener('input', () => validateFiscalId(fiscalIdInput, 'fiscalIdFeedback'));
        logoInput?.addEventListener('change', () => validateLogo(logoInput, 'logoFeedback'));
        pseudoInput?.addEventListener('input', () => validatePseudo(pseudoInput, 'pseudoFeedback'));
        passwordInput?.addEventListener('input', () => validatePassword(passwordInput, 'passwordFeedback'));
        termsCheck?.addEventListener('change', () => validateTermsCheck(termsCheck, 'termsCheckFeedback'));

        associationForm.addEventListener('submit', function(event) {
            const isRepNameValid = validateName(repNameInput, 'nameFeedback');
            const isRepSurnameValid = validateSurname(repSurnameInput, 'surnameFeedback');
            const isCinValid = validateCin(cinInput, 'cinFeedback');
            const isEmailValid = validateEmail(emailInput, 'emailFeedback');
            const isAssocNameValid = validateAssociationName(assocNameInput, 'associationNameFeedback');
            const isAssocAddressValid = validateAssociationAddress(assocAddressInput, 'associationAddressFeedback');
            const isFiscalIdValid = validateFiscalId(fiscalIdInput, 'fiscalIdFeedback');
            const isLogoValid = logoInput.files.length > 0 ? validateLogo(logoInput, 'logoFeedback') : true;
            const isPseudoValid = validatePseudo(pseudoInput, 'pseudoFeedback');
            const isPasswordValid = validatePassword(passwordInput, 'passwordFeedback');
            const isTermsValid = validateTermsCheck(termsCheck, 'termsCheckFeedback');

            if (!isRepNameValid || !isRepSurnameValid || !isCinValid || !isEmailValid || !isAssocNameValid || !isAssocAddressValid || !isFiscalIdValid || !isLogoValid || !isPseudoValid || !isPasswordValid || !isTermsValid) {
                event.preventDefault();
                alert('Please correct the errors in the form.');
            }
        });
    }

    document.querySelectorAll('.toggle-password').forEach(button => {
        const targetSelector = button.getAttribute('data-target');
        let passwordField = targetSelector ? document.querySelector(targetSelector) : null;

        if (!passwordField && button.closest('.input-group')) {
            passwordField = button.closest('.input-group').querySelector('input[type="password"], input[type="text"]');
        }

        if (passwordField) {
            if (!passwordField.dataset.toggleListenerAttached) {
                 setupPasswordToggle(passwordField, button);
                 passwordField.dataset.toggleListenerAttached = 'true';
            }
        }
    });
});
