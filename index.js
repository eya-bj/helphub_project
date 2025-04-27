// Handles login, password toggle, search/filter triggers, project search/filter, and contact form

// Wait for page to load before running code
document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIN SYSTEM ---

    // Handle login form
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            // event.preventDefault(); // REMOVED - Let the form submit normally
            var userType = document.getElementById('userType').value;
            var username = document.getElementById('pseudo').value;
            var password = document.getElementById('password').value;

            // Basic client-side validation (optional, server should always validate)
            if (!userType || !username || !password) {
                alert('Please fill out all fields');
                event.preventDefault(); // Prevent submission if fields are empty
                return;
            }

            // Form will now submit to the action specified in the HTML
            // Remove client-side redirection:
            // localStorage.setItem('userType', userType);
            // window.location.href = `dashboard-${userType}.html`;
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

    // Handle donation modal amount buttons
    const amountButtons = document.querySelectorAll('.amount-btn');
    const donationAmount = document.getElementById('donationAmount');
    
    if (amountButtons.length > 0 && donationAmount) {
        amountButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Set the donation amount input value to the clicked button amount
                const amount = this.getAttribute('data-amount');
                donationAmount.value = amount;
                
                // Update active state on buttons
                amountButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
    
    // Handle donation form submission
    const donationForm = document.getElementById('donationForm');
    if (donationForm) {
        donationForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Validate form fields
            let isValid = true;
            const amount = document.getElementById('donationAmount').value;
            
            // Check for valid amount
            if (!amount || isNaN(amount) || amount <= 0 || amount > 1800) {
                document.getElementById('donationAmount').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('donationAmount').classList.remove('is-invalid');
            }
            
            if (isValid) {
                // Display success message
                alert('Thank you for your donation of $' + amount + '!');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('donationModal'));
                modal.hide();
                
                // Refresh page or update UI as needed
                // For demo purposes, we'll just reset the form
                donationForm.reset();
            }
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