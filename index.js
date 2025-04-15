// HelpHub Main JavaScript - Simplified Version

// Wait for DOM to be fully loaded before attaching event handlers
document.addEventListener('DOMContentLoaded', function () {
    // Initialize all key components
    initFormValidation();
    initLoginSystem();
    initPasswordToggles();
    initSearchFunctions();
    initFilterFunctions();
    initProjectDetails();
    initProfilePages();
    initDonationSystem();
});

// Form Validation
function initFormValidation() {
    // Get all forms that need validation
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                // Form is invalid
                event.preventDefault();
                event.stopPropagation();
            } else {
                // Form is valid - handle submission
                event.preventDefault();
                const formId = form.getAttribute('id');
                
                // Handle different form types
                if (formId === 'associationRegisterForm' || formId === 'donorRegisterForm') {
                    alert('Registration successful! Please log in.');
                    window.location.href = 'index.html';
                } else if (formId === 'donationForm') {
                    const amount = form.querySelector('#donationAmount').value;
                    alert(`Thank you for your donation of $${amount}!`);
                    window.location.href = 'dashboard-donor.html';
                } else if (formId.includes('ProfileForm')) {
                    alert('Profile updated successfully!');
                } else if (formId === 'changePasswordForm') {
                    const newPassword = document.getElementById('newPassword');
                    const confirmPassword = document.getElementById('confirmPassword');
                    
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        confirmPassword.setCustomValidity('');
                        alert('Password changed successfully!');
                    }
                }
            }
            
            form.classList.add('was-validated');
        });
    });
    
    // Setup account deletion confirmation
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

// Login System
function initLoginSystem() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const userType = document.getElementById('userType').value;
            const pseudo = document.getElementById('pseudo').value;
            const password = document.getElementById('password').value;
            
            if (userType && pseudo && password) {
                // Store user type and redirect to appropriate dashboard
                localStorage.setItem('userType', userType);
                window.location.href = `dashboard-${userType}.html`;
            } else {
                alert('Please fill out all fields');
            }
        });
    }
    
    // Update page references based on user type
    const userType = localStorage.getItem('userType') || 'donor';
    
    document.querySelectorAll('[href*="profile.html"]').forEach(link => {
        link.setAttribute('href', `profile-${userType}.html`);
    });
    
    document.querySelectorAll('[href*="project-details.html"]').forEach(link => {
        const href = link.getAttribute('href');
        link.setAttribute('href', href.replace('project-details.html', `project-details-${userType}.html`));
    });
}

// Password Toggle Visibility
function initPasswordToggles() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.querySelector(targetId);
            
            // Toggle between password and text type
            const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
            targetInput.setAttribute('type', type);
            
            // Toggle eye icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
}

// Search Functions
function initSearchFunctions() {
    // Handle search button clicks
    const searchButtons = document.querySelectorAll('[id$="SearchButton"]');
    searchButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the associated input element
            const searchInput = this.previousElementSibling || 
                              document.getElementById(this.getAttribute('data-search-input')) || 
                              this.closest('.input-group').querySelector('input');
            
            if (searchInput) {
                performSearch(searchInput.value.toLowerCase());
            }
        });
    });
    
    // Handle search on enter key press
    const searchInputs = document.querySelectorAll('input[id*="Search"]');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                performSearch(this.value.toLowerCase());
            }
        });
    });
}

// Perform search across project items
function performSearch(query) {
    query = query.trim();
    const projectItems = document.querySelectorAll('.project-item, [class*="col-lg"][class*="col-md"]');
    
    projectItems.forEach(item => {
        const title = item.querySelector('.card-title')?.textContent.toLowerCase() || '';
        const description = item.querySelector('.card-text')?.textContent.toLowerCase() || '';
        
        // Show or hide based on search match
        if (query === '' || title.includes(query) || description.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
    
    // Reset filter buttons if they exist
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons.length > 0) {
        filterButtons.forEach(btn => btn.classList.remove('active'));
        const allButton = document.querySelector('[data-filter="all"]');
        if (allButton) allButton.classList.add('active');
    }
}

// Filter Functions
function initFilterFunctions() {
    // Category filter dropdown
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterByCategory(this.value);
        });
    }
    
    // Filter buttons (e.g., All, Environment, Education...)
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Update active state visually
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Filter projects
                filterByCategory(this.getAttribute('data-filter'));
            });
        });
    }
}

// Filter projects by selected category
function filterByCategory(category) {
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

// Project Detail Page Setup
function initProjectDetails() {
    if (!window.location.pathname.includes('project-details')) return;
    
    // Determine user type to show appropriate view
    const urlParams = new URLSearchParams(window.location.search);
    const userType = urlParams.get('userType') || localStorage.getItem('userType') || 'donor';
    
    // Show appropriate view based on user type
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

// Profile Pages Setup
function initProfilePages() {
    if (!window.location.pathname.includes('profile')) return;
    
    // Show appropriate view based on user type for profile.html page
    if (window.location.pathname.includes('profile.html')) {
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
}

// Donation System
function initDonationSystem() {
    // Donation amount button selection
    const amountBtns = document.querySelectorAll('.amount-btn');
    const donationAmountInput = document.getElementById('donationAmount');
    
    if (!amountBtns.length || !donationAmountInput) return;
    
    amountBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const amount = this.getAttribute('data-amount');
            donationAmountInput.value = amount;
            
            // Update visual state
            amountBtns.forEach(b => {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-outline-primary');
            });
            
            this.classList.add('active', 'btn-primary');
            this.classList.remove('btn-outline-primary');
        });
    });
}
