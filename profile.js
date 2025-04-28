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

var associationForm = document.getElementById('associationProfileForm');
var donorForm = document.getElementById('donorProfileForm');
var passwordForm = document.getElementById('changePasswordForm');
var deleteForm = document.getElementById('deleteAccountForm');

if (associationForm) {
    var nameInput = document.getElementById('name');
    var surnameInput = document.getElementById('surname');
    var emailInput = document.getElementById('email');
    var associationNameInput = document.getElementById('associationName');
    var associationAddressInput = document.getElementById('associationAddress');
    var logoInput = document.getElementById('logo');

    nameInput?.addEventListener('input', function() {
        if (nameInput.value.length < 2 && nameInput.value != '') {
            showError(nameInput, 'nameFeedback', true);
        } else {
            showError(nameInput, 'nameFeedback', false);
        }
    });

    surnameInput?.addEventListener('input', function() {
        if (surnameInput.value.length < 2 && surnameInput.value != '') {
            showError(surnameInput, 'surnameFeedback', true);
        } else {
            showError(surnameInput, 'surnameFeedback', false);
        }
    });

    emailInput?.addEventListener('input', function() {
        var value = emailInput.value;
        var isValid = value.includes('@') && value.includes('.');
        if (!isValid && value != '') {
            showError(emailInput, 'emailFeedback', true);
        } else {
            showError(emailInput, 'emailFeedback', false);
        }
    });

    associationNameInput?.addEventListener('input', function() {
        if (associationNameInput.value.length < 3 && associationNameInput.value != '') {
            showError(associationNameInput, 'associationNameFeedback', true);
        } else {
            showError(associationNameInput, 'associationNameFeedback', false);
        }
    });

    associationAddressInput?.addEventListener('input', function() {
        if (associationAddressInput.value.length < 5 && associationAddressInput.value != '') {
            showError(associationAddressInput, 'associationAddressFeedback', true);
        } else {
            showError(associationAddressInput, 'associationAddressFeedback', false);
        }
    });

    logoInput?.addEventListener('change', function() {
        var file = logoInput.files[0];
        var isValid = !file || (file && (file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif'));
        var maxSize = 2 * 1024 * 1024;
        var isSizeValid = !file || file.size <= maxSize;

        if (!isValid) {
            showError(logoInput, 'logoFeedback', true);
            logoInput.value = '';
        } else if (!isSizeValid) {
             showError(logoInput, 'logoFeedback', true);
             document.getElementById('logoFeedback').textContent = 'File is too large (Max 2MB).';
             logoInput.value = '';
        }
         else {
            showError(logoInput, 'logoFeedback', false);
             document.getElementById('logoFeedback').textContent = 'Please select a valid image file (JPEG, PNG, or GIF).';
        }
    });

    associationForm.addEventListener('submit', function(event) {

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
        var logoTypeValid = !file || (file && (file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif'));
        var maxSize = 2 * 1024 * 1024;
        var logoSizeValid = !file || file.size <= maxSize;

        if (!logoTypeValid) {
            showError(logoInput, 'logoFeedback', true);
             document.getElementById('logoFeedback').textContent = 'Please select a valid image file (JPEG, PNG, or GIF).';
            isValid = false;
        } else if (!logoSizeValid) {
             showError(logoInput, 'logoFeedback', true);
             document.getElementById('logoFeedback').textContent = 'File is too large (Max 2MB).';
             isValid = false;
        }
         else {
            showError(logoInput, 'logoFeedback', false);
        }

        if (!isValid) {
            event.preventDefault();

        }

    });
}

if (donorForm) {
    var donorNameInput = document.getElementById('donorName');
    var donorSurnameInput = document.getElementById('donorSurname');
    var donorEmailInput = document.getElementById('donorEmail');
    var profileImageInput = document.getElementById('profileImage');

    donorNameInput?.addEventListener('input', function() {
        if (donorNameInput.value.length < 2 && donorNameInput.value != '') {
            showError(donorNameInput, 'donorNameFeedback', true);
        } else {
            showError(donorNameInput, 'donorNameFeedback', false);
        }
    });

    donorSurnameInput?.addEventListener('input', function() {
        if (donorSurnameInput.value.length < 2 && donorSurnameInput.value != '') {
            showError(donorSurnameInput, 'donorSurnameFeedback', true);
        } else {
            showError(donorSurnameInput, 'donorSurnameFeedback', false);
        }
    });

    donorEmailInput?.addEventListener('input', function() {
        var value = donorEmailInput.value;
        var isValid = value.includes('@') && value.includes('.');
        if (!isValid && value != '') {
            showError(donorEmailInput, 'donorEmailFeedback', true);
        } else {
            showError(donorEmailInput, 'donorEmailFeedback', false);
        }
    });

    profileImageInput?.addEventListener('change', function() {
        var file = profileImageInput.files[0];
        var isValid = !file || (file && (file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif'));
        var maxSize = 2 * 1024 * 1024;
        var isSizeValid = !file || file.size <= maxSize;

        if (!isValid) {
            showError(profileImageInput, 'profileImageFeedback', true);
            document.getElementById('profileImageFeedback').textContent = 'Please select a valid image file (JPEG, PNG, or GIF).';
            profileImageInput.value = '';
        } else if (!isSizeValid) {
            showError(profileImageInput, 'profileImageFeedback', true);
            document.getElementById('profileImageFeedback').textContent = 'File is too large (Max 2MB).';
            profileImageInput.value = '';
        } else {
            showError(profileImageInput, 'profileImageFeedback', false);
        }
    });

    donorForm.addEventListener('submit', function(event) {

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

        var file = profileImageInput?.files[0];
        var imageTypeValid = !file || (file && (file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif'));
        var maxSize = 2 * 1024 * 1024;
        var imageSizeValid = !file || file.size <= maxSize;

        if (!imageTypeValid) {
            showError(profileImageInput, 'profileImageFeedback', true);
            document.getElementById('profileImageFeedback').textContent = 'Please select a valid image file (JPEG, PNG, or GIF).';
            isValid = false;
        } else if (!imageSizeValid) {
            showError(profileImageInput, 'profileImageFeedback', true);
            document.getElementById('profileImageFeedback').textContent = 'File is too large (Max 2MB).';
            isValid = false;
        } else {
            showError(profileImageInput, 'profileImageFeedback', false);
        }

        if (!isValid) {
            event.preventDefault();

        }

    });
}

if (passwordForm) {
    var currentPasswordInput = document.getElementById('currentPassword');
    var newPasswordInput = document.getElementById('newPassword');
    var confirmPasswordInput = document.getElementById('confirmPassword');

    currentPasswordInput?.addEventListener('input', function() {
        if (currentPasswordInput.value == '' && currentPasswordInput.touched) {
            showError(currentPasswordInput, 'currentPasswordFeedback', true);
        } else {
            showError(currentPasswordInput, 'currentPasswordFeedback', false);
        }
    });
    currentPasswordInput?.addEventListener('blur', function() { this.touched = true; });


    newPasswordInput?.addEventListener('input', function() {
        var value = newPasswordInput.value;
        var isValid = value.length >= 8 && (value.endsWith('$') || value.endsWith('#'));
        if (!isValid && value != '') {
            showError(newPasswordInput, 'newPasswordFeedback', true);
        } else {
            showError(newPasswordInput, 'newPasswordFeedback', false);
        }

        var confirmIsValid = confirmPasswordInput.value == newPasswordInput.value;
         if (!confirmIsValid && confirmPasswordInput.value != '') {
            showError(confirmPasswordInput, 'confirmPasswordFeedback', true);
        } else {
            showError(confirmPasswordInput, 'confirmPasswordFeedback', false);
        }
    });

    confirmPasswordInput?.addEventListener('input', function() {
        var isValid = confirmPasswordInput.value == newPasswordInput.value;
        if (!isValid && confirmPasswordInput.value != '') {
            showError(confirmPasswordInput, 'confirmPasswordFeedback', true);
        } else {
            showError(confirmPasswordInput, 'confirmPasswordFeedback', false);
        }
    });

    passwordForm.addEventListener('submit', function(event) {

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

        if (!isValid) {
             event.preventDefault();

        }

    });
}

if (deleteForm) {
    var deleteConfirmInput = document.getElementById('deleteConfirm');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    deleteConfirmInput?.addEventListener('input', function() {
        var isValid = deleteConfirmInput.value === 'DELETE';
        if (!isValid && deleteConfirmInput.value !== '') {
            showError(deleteConfirmInput, 'deleteConfirmFeedback', true);
        } else {
            showError(deleteConfirmInput, 'deleteConfirmFeedback', false);
        }
        confirmDeleteBtn.disabled = !isValid;
    });

    deleteForm.addEventListener('submit', function(event) {

        var isValid = deleteConfirmInput.value === 'DELETE';
        if (!isValid) {
            showError(deleteConfirmInput, 'deleteConfirmFeedback', true);
            event.preventDefault();
        } else {
            showError(deleteConfirmInput, 'deleteConfirmFeedback', false);

            if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.')) {
                 event.preventDefault();
            }

        }
    });
}