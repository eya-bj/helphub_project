// HelpHub Core JavaScript - Simple Version

// Wait for page to load before running code
document.addEventListener('DOMContentLoaded', function() {
    // --- FORM HANDLING ---
    
    // Handle all forms with validation
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(event) {
            // Prevent default form submission
            event.preventDefault();
            
            // Check if form is valid
            if (!form.checkValidity()) {
                event.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            
            // Handle different forms based on their ID
            const formId = form.getAttribute('id');
            
            // Registration forms
            if (formId === 'associationRegisterForm' || formId === 'donorRegisterForm') {
                alert('Registration successful! Please log in.');
                window.location.href = 'index.html';
            } 
            // Donation form
            else if (formId === 'donationForm') {
                const amount = form.querySelector('#donationAmount').value;
                alert(`Thank you for your donation of $${amount}!`);
                window.location.href = 'dashboard-donor.html';
            }
            // Profile update forms
            else if (formId && formId.includes('ProfileForm')) {
                alert('Profile updated successfully!');
            }
            // Password change form
            else if (formId === 'changePasswordForm') {
                const pass1 = document.getElementById('newPassword').value;
                const pass2 = document.getElementById('confirmPassword').value;
                
                if (pass1 !== pass2) {
                    document.getElementById('confirmPassword').setCustomValidity('Passwords do not match');
                    event.stopPropagation();
                } else {
                    alert('Password changed successfully!');
                }
            }
            
            form.classList.add('was-validated');
        });
    });
    
    // --- LOGIN SYSTEM ---
    
    // Handle login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const userType = document.getElementById('userType').value;
            const username = document.getElementById('pseudo').value;
            const password = document.getElementById('password').value;
            
            // Simple validation
            if (!userType || !username || !password) {
                alert('Please fill out all fields');
                return;
            }
            
            // Store user type and redirect
            localStorage.setItem('userType', userType);
            window.location.href = `dashboard-${userType}.html`;
        });
    }
    
    // --- UI HELPERS ---
    
    // Show/hide password toggles
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            // Find the password field
            const passField = document.querySelector(this.getAttribute('data-target'));
            if (!passField) return;
            
            // Toggle between password and text
            const newType = passField.type === 'password' ? 'text' : 'password';
            passField.type = newType;
            
            // Update icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
    
    // --- SEARCH & FILTERING ---
    
    // Handle search buttons
    document.querySelectorAll('[id$="SearchButton"]').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling || 
                          document.getElementById(this.getAttribute('data-search-input')) || 
                          this.closest('.input-group').querySelector('input');
            
            if (input) searchProjects(input.value);
        });
    });
    
    // Search on Enter key
    document.querySelectorAll('input[id*="Search"]').forEach(input => {
        input.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') searchProjects(this.value);
        });
    });
    
    // Filter category buttons
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Apply filter
            filterProjects(this.getAttribute('data-filter'));
        });
    });
    
    // Category dropdown filter
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterProjects(this.value);
        });
    }
    
    // --- ACCOUNT ACTIONS ---
    
    // Handle account deletion confirmation
    const deleteConfirm = document.getElementById('deleteConfirm');
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (deleteConfirm && deleteBtn) {
        deleteConfirm.addEventListener('input', function() {
            deleteBtn.disabled = (this.value !== 'DELETE');
        });
        
        deleteBtn.addEventListener('click', function(event) {
            event.preventDefault();
            alert('Account deleted successfully.');
            window.location.href = 'index.html';
        });
    }
    
    // --- PROJECT DETAILS PAGE ---
    
    // Setup project details page
    if (window.location.pathname.includes('project-details-')) {
        const userType = localStorage.getItem('userType') || 'donor';
        
        // Set back button destination
        const backBtn = document.getElementById('backBtn');
        if (backBtn) backBtn.href = `dashboard-${userType}.html`;
        
        // Hide inappropriate views based on page type
        if (window.location.pathname.includes('-donor')) {
            document.querySelectorAll('.association-view').forEach(el => el.style.display = 'none');
        } else {
            document.querySelectorAll('.donor-view').forEach(el => el.style.display = 'none');
        }
        
        // Setup donation buttons
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const amount = this.getAttribute('data-amount');
                const amountInput = document.getElementById('donationAmount');
                
                if (amountInput) amountInput.value = amount;
                
                // Update button styles
                document.querySelectorAll('.amount-btn').forEach(b => {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-outline-primary');
                });
                
                this.classList.add('active', 'btn-primary');
                this.classList.remove('btn-outline-primary');
            });
        });
    }
});

// Search projects by keywords
function searchProjects(query) {
    query = query.toLowerCase().trim();
    
    // Find all project cards/items
    const projects = document.querySelectorAll('.project-item, [class*="col-lg"][class*="col-md"]');
    
    projects.forEach(item => {
        const title = item.querySelector('.card-title')?.textContent.toLowerCase() || '';
        const desc = item.querySelector('.card-text')?.textContent.toLowerCase() || '';
        
        // Show or hide based on search match
        if (query === '' || title.includes(query) || desc.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
    
    // Reset filter buttons
    const allButton = document.querySelector('[data-filter="all"]');
    if (allButton) {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        allButton.classList.add('active');
    }
}

// Filter projects by category
function filterProjects(category) {
    const projects = document.querySelectorAll('.project-item, [class*="col-lg"][class*="col-md"]');
    
    projects.forEach(item => {
        if (category === 'all') {
            item.style.display = '';
        } else {
            const badge = item.querySelector('.badge');
            if (badge && badge.textContent.toLowerCase() === category.toLowerCase()) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        }
    });
}
