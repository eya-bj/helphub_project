// Handles login, password toggle, search/filter triggers, project search/filter, and contact form

// Wait for page to load before running code
document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIN SYSTEM ---

    // Handle login form
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            var userType = document.getElementById('userType').value;
            var username = document.getElementById('pseudo').value;
            var password = document.getElementById('password').value;

            // Check if all fields are filled
            if (!userType || !username || !password) {
                alert('Please fill out all fields');
                return;
            }

            // Store user type and redirect to dashboard
            localStorage.setItem('userType', userType);
            window.location.href = `dashboard-${userType}.html`;
        });
    }

    // --- UI HELPERS ---

    // Show/hide password toggles
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            // Find the password field
            var passField = document.querySelector(this.getAttribute('data-target'));
            if (!passField) return;

            // Toggle between password and text
            var newType = passField.type === 'password' ? 'text' : 'password';
            passField.type = newType;

            // Update icon
            var icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });

    // Handle search buttons
    document.querySelectorAll('[id$="SearchButton"]').forEach(button => {
        button.addEventListener('click', function() {
            var input = this.previousElementSibling || 
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
    var categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterProjects(this.value);
        });
    }

    // --- CONTACT FORM ---

    // Handle contact form
    var contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Validate form
            if (!contactForm.checkValidity()) {
                event.stopPropagation();
                contactForm.classList.add('was-validated');
                return;
            }

            // Get form values
            var name = document.getElementById('contactName').value;
            var email = document.getElementById('contactEmail').value;
            var message = document.getElementById('contactMessage').value;

            // Simulate submission
            alert('Thank you, ' + name + '! Your message has been sent.');
            contactForm.reset();
            contactForm.classList.remove('was-validated');
            window.location.href = 'index.html';
        });
    }
});

// Search projects by keywords
function searchProjects(query) {
    query = query.toLowerCase().trim();

    // Find all project cards/items
    var projects = document.querySelectorAll('.project-item, [class*="col-lg"][class*="col-md"]');

    projects.forEach(item => {
        var title = item.querySelector('.card-title')?.textContent.toLowerCase() || '';
        var desc = item.querySelector('.card-text')?.textContent.toLowerCase() || '';

        // Show or hide based on search match
        if (query === '' || title.includes(query) || desc.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });

    // Reset filter buttons
    var allButton = document.querySelector('[data-filter="all"]');
    if (allButton) {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        allButton.classList.add('active');
    }
}

// Filter projects by category
function filterProjects(category) {
    var projects = document.querySelectorAll('.project-item, [class*="col-lg"][class*="col-md"]');

    projects.forEach(item => {
        if (category === 'all') {
            item.style.display = '';
        } else {
            var badge = item.querySelector('.badge');
            if (badge && badge.textContent.toLowerCase() === category.toLowerCase()) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        }
    });
}