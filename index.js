// Form Validation
document.addEventListener('DOMContentLoaded', function () {
    // Fetch all forms that need validation
    const forms = document.querySelectorAll('.needs-validation');

    // Loop through them and prevent submission
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // If form is valid, handle submission
                event.preventDefault();
                handleFormSubmission(form);
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Handle login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const userType = document.getElementById('userType').value;
            const pseudo = document.getElementById('pseudo').value;
            const password = document.getElementById('password').value;
            
            // Simple validation
            if (userType && pseudo && password) {
                // Store user type for later use
                localStorage.setItem('userType', userType);
                
                // Redirect to appropriate dashboard
                if (userType === 'donor') {
                    window.location.href = 'dashboard-donor.html';
                } else if (userType === 'association') {
                    window.location.href = 'dashboard-association.html';
                }
            } else {
                alert('Please fill out all fields');
            }
        });
    }

    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.querySelector(targetId);
            
            // Toggle type attribute
            const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
            targetInput.setAttribute('type', type);
            
            // Toggle eye icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
    
    // Handle project search
    const searchButtons = document.querySelectorAll('[id$="SearchButton"]');
    searchButtons.forEach(button => {
        button.addEventListener('click', function() {
            const searchInput = this.previousElementSibling || 
                                document.getElementById(this.getAttribute('data-search-input')) || 
                                this.closest('.input-group').querySelector('input');
            if (searchInput) {
                searchProjects(searchInput.value.toLowerCase());
            }
        });
    });
    
    // Search on Enter key press
    const searchInputs = document.querySelectorAll('input[id*="Search"]');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchProjects(this.value.toLowerCase());
            }
        });
    });
    
    // Category filter handling
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const category = this.value;
            filterProjectsByCategory(category);
        });
    }
    
    // Project filtering buttons 
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filterValue = this.getAttribute('data-filter');
                filterProjectsByCategory(filterValue);
            });
        });
    }
    
    // Update href references to project details and profile pages
    updatePageReferences();
    
    // Handle project details page functionality
    setupProjectDetailsPage();
    
    // Handle profile page functionality
    setupProfilePage();
    
    // Handle donation form and amount selection
    setupDonationForm();
    
    // Handle password form validations
    setupPasswordValidation();
    
    // Handle account deletion confirmation
    setupAccountDeletion();
});

// Form submission handler
function handleFormSubmission(form) {
    // Get form ID to determine which form was submitted
    const formId = form.getAttribute('id');
    
    // Handle different form submissions
    if (formId === 'associationRegisterForm') {
        console.log('Association registration form submitted');
        // For demo, just redirect to login
        alert('Registration successful! Please log in.');
        window.location.href = 'index.html';
    } 
    else if (formId === 'donorRegisterForm') {
        console.log('Donor registration form submitted');
        // For demo, just redirect to login
        alert('Registration successful! Please log in.');
        window.location.href = 'index.html';
    }
    else if (formId === 'donationForm') {
        handleDonation(form);
    }
}

// Search projects function
function searchProjects(query) {
    query = query.toLowerCase().trim();
    const projectItems = document.querySelectorAll('.project-item, [class*="col-lg"][class*="col-md"]');
    
    if (projectItems.length > 0) {
        projectItems.forEach(item => {
            const title = item.querySelector('.card-title')?.textContent.toLowerCase() || '';
            const description = item.querySelector('.card-text')?.textContent.toLowerCase() || '';
            
            if (title.includes(query) || description.includes(query) || query === '') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
        
        // Reset filter buttons if they exist
        const filterButtons = document.querySelectorAll('.filter-btn');
        if (filterButtons.length > 0) {
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            const allButton = document.querySelector('[data-filter="all"]');
            if (allButton) {
                allButton.classList.add('active');
            }
        }
    }
}

// Filter projects by category
function filterProjectsByCategory(category) {
    const projectItems = document.querySelectorAll('.project-item, [class*="col-lg"][class*="col-md"]');
    
    projectItems.forEach(item => {
        if (category === 'all') {
            item.style.display = '';
        } else {
            const badge = item.querySelector('.badge');
            if (badge && badge.textContent.toLowerCase() === category) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        }
    });
}

// Update references to profile and project details pages
function updatePageReferences() {
    // Update href references based on user type
    const userType = localStorage.getItem('userType') || 'donor';
    
    document.querySelectorAll('[href*="profile.html"]').forEach(link => {
        if (userType === 'donor') {
            link.setAttribute('href', 'profile-donor.html');
        } else {
            link.setAttribute('href', 'profile-association.html');
        }
    });
    
    document.querySelectorAll('[href*="project-details.html"]').forEach(link => {
        const href = link.getAttribute('href');
        if (userType === 'donor') {
            link.setAttribute('href', href.replace('project-details.html', 'project-details-donor.html'));
        } else {
            link.setAttribute('href', href.replace('project-details.html', 'project-details-association.html'));
        }
    });
}

// Setup project details page functionality
function setupProjectDetailsPage() {
    if (window.location.pathname.includes('project-details')) {
        // Determine user type
        const urlParams = new URLSearchParams(window.location.search);
        const userType = urlParams.get('userType') || localStorage.getItem('userType') || 'donor';
        
        // Show/hide appropriate views
        if (userType === 'association') {
            document.querySelectorAll('.donor-view').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.association-view').forEach(el => el.style.display = 'block');
            if (document.querySelector('#backBtn')) {
                document.querySelector('#backBtn').href = 'dashboard-association.html';
            }
        } else {
            document.querySelectorAll('.association-view').forEach(el => el.style.display = 'none');
            if (document.querySelector('#backBtn')) {
                document.querySelector('#backBtn').href = 'dashboard-donor.html';
            }
        }
    }
}

// Setup profile page functionality
function setupProfilePage() {
    if (window.location.pathname.includes('profile.html')) {
        // Determine user type
        const userType = localStorage.getItem('userType') || 'association';
        
        if (userType === 'donor') {
            document.querySelector('.association-view').style.display = 'none';
            document.querySelector('.donor-view').style.display = 'block';
            document.querySelector('.association-nav').style.display = 'none';
        } else {
            document.querySelector('.donor-view').style.display = 'none';
            document.querySelector('.donor-nav').style.display = 'none';
        }
    }
    
    // Handle profile form submission
    const associationForm = document.getElementById('associationProfileForm');
    if (associationForm) {
        associationForm.addEventListener('submit', function(event) {
            if (!associationForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                event.preventDefault();
                alert('Profile updated successfully!');
            }
            associationForm.classList.add('was-validated');
        });
    }
    
    const donorForm = document.getElementById('donorProfileForm');
    if (donorForm) {
        donorForm.addEventListener('submit', function(event) {
            if (!donorForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                event.preventDefault();
                alert('Profile updated successfully!');
            }
            donorForm.classList.add('was-validated');
        });
    }
}

// Setup donation form and amount selection
function setupDonationForm() {
    // Handle donation amount buttons on project details page
    const amountBtns = document.querySelectorAll('.amount-btn');
    const donationAmountInput = document.getElementById('donationAmount');
    
    if (amountBtns.length > 0 && donationAmountInput) {
        amountBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const amount = this.getAttribute('data-amount');
                donationAmountInput.value = amount;
                
                // Toggle active state
                amountBtns.forEach(b => {
                    b.classList.remove('active');
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-primary');
                });
                this.classList.add('active', 'btn-primary');
                this.classList.remove('btn-outline-primary');
            });
        });
    }
    
    // Form validation for donation form
    const donationForm = document.getElementById('donationForm');
    if (donationForm) {
        donationForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (donationForm.checkValidity()) {
                handleDonation(donationForm);
            }
            donationForm.classList.add('was-validated');
        });
    }
}

// Setup password validation
function setupPasswordValidation() {
    const passwordForm = document.getElementById('changePasswordForm');
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    
    if (passwordForm && newPassword && confirmPassword) {
        passwordForm.addEventListener('submit', function(event) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
            
            if (!passwordForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                event.preventDefault();
                alert('Password changed successfully!');
                // The modal will be closed by Bootstrap's dismiss attribute
            }
            passwordForm.classList.add('was-validated');
        });
    }
}

// Setup account deletion confirmation
function setupAccountDeletion() {
    const deleteConfirm = document.getElementById('deleteConfirm');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (deleteConfirm && confirmDeleteBtn) {
        deleteConfirm.addEventListener('input', function() {
            confirmDeleteBtn.disabled = this.value !== 'DELETE';
        });
        
        confirmDeleteBtn.addEventListener('click', function(event) {
            event.preventDefault();
            alert('Account deleted successfully.');
            window.location.href = 'index.html';
        });
    }
}

// Handle donation form submission
function handleDonation(form) {
    const amount = form.querySelector('#donationAmount').value;
    const method = form.querySelector('#paymentMethod')?.value || 'creditCard';
    const anonymous = form.querySelector('#anonymousCheck')?.checked || false;
    
    // For demo, just show success message
    alert(`Thank you for your donation of $${amount}!`);
    window.location.href = 'dashboard-donor.html';
}
