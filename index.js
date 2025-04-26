// Handles password toggle, search/filter triggers, project search/filter

// Wait for page to load before running code
document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIN SYSTEM ---

    // Login form submission is now handled by standard form POST
    // var loginForm = document.getElementById('loginForm');
    // if (loginForm) {
    //     loginForm.addEventListener('submit', function(event) {
    //         event.preventDefault(); // REMOVED - Let the form submit normally
    //         // ... fetch logic removed ...
    //     });
    // }

    // --- UI HELPERS ---

    // Show/hide password toggles (Keep this)
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            // Find the password field using data-target attribute
            var targetSelector = this.getAttribute('data-target'); 
            var passField = targetSelector ? document.querySelector(targetSelector) : null;
            
            // Fallback: try finding input within the same input-group
            if (!passField && this.closest('.input-group')) {
                 passField = this.closest('.input-group').querySelector('input[type="password"], input[type="text"]');
            }

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

    // Handle search buttons (Keep this)
    document.querySelectorAll('[id$="SearchButton"]').forEach(button => {
        button.addEventListener('click', function() {
            var input = this.previousElementSibling || 
                        document.getElementById(this.getAttribute('data-search-input')) || 
                        this.closest('.input-group').querySelector('input');
            
            if (input) searchProjects(input.value);
        });
    });

    // Search on Enter key (Keep this)
    document.querySelectorAll('input[id*="Search"]').forEach(input => {
        input.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') searchProjects(this.value);
        });
    });

    // Filter category buttons (Keep this)
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

    // Category dropdown filter (Keep this)
    var categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterProjects(this.value);
        });
    }

    // --- CONTACT FORM ---

    // Contact form submission is now handled by standard form POST
    // var contactForm = document.getElementById('contactForm');
    // if (contactForm) {
    //     contactForm.addEventListener('submit', function(event) {
    //         event.preventDefault(); // REMOVED - Let the form submit normally
    //         // ... validation and fetch logic removed ...
    //     });
    // }

    // --- DONATION FORM --- (in project-details-donor.html, might be handled here or separate file)

    // Handle donation modal amount buttons (Keep this)
    const amountButtons = document.querySelectorAll('.amount-btn');
    const donationAmountInput = document.getElementById('donationAmount'); // Renamed variable
    
    if (amountButtons.length > 0 && donationAmountInput) {
        amountButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Set the donation amount input value to the clicked button amount
                const amount = this.getAttribute('data-amount');
                donationAmountInput.value = amount;
                
                // Update active state on buttons
                amountButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Remove invalid state if amount is valid now
                if (amount > 0 && amount <= 1800) {
                     donationAmountInput.classList.remove('is-invalid');
                }
            });
        });
        
        // Also validate on manual input change
        donationAmountInput.addEventListener('input', function() {
             const amount = parseFloat(this.value);
             if (!isNaN(amount) && amount > 0 && amount <= 1800) {
                 this.classList.remove('is-invalid');
             }
             // Deactivate preset buttons if manual input doesn't match
             let matchFound = false;
             amountButtons.forEach(btn => {
                 if (btn.getAttribute('data-amount') == amount) {
                     btn.classList.add('active');
                     matchFound = true;
                 } else {
                     btn.classList.remove('active');
                 }
             });
        });
    }
    
    // Donation form submission is now handled by standard form POST
    // const donationForm = document.getElementById('donationForm');
    // if (donationForm) {
    //     donationForm.addEventListener('submit', function(event) {
    //         event.preventDefault(); // REMOVED - Let the form submit normally
    //         // ... validation and fetch logic removed ...
    //     });
    // }

    // --- ADD PROJECT FORM --- (in dashboard-association.html)
    // Add project form submission is now handled by standard form POST
    // const addProjectForm = document.getElementById('addProjectForm');
    // if (addProjectForm) {
         // Add event listener for setting start_date just before submission if needed
         // addProjectForm.addEventListener('submit', function(event) {
         //    const startDateInput = document.getElementById('projectStartDate');
         //    if (startDateInput) {
         //        startDateInput.value = new Date().toISOString().split('T')[0]; // Set to today
         //    }
             // No preventDefault needed
         // });
         // Basic Bootstrap validation can be added via 'needs-validation' and 'novalidate' attributes in HTML
    // }
    
    // Set start date for new projects automatically (if field exists)
    const startDateInput = document.getElementById('projectStartDate');
    if (startDateInput) {
        startDateInput.value = new Date().toISOString().split('T')[0]; // Set to today
    }

});

// Search projects by keywords (Keep this)
function searchProjects(query) {
    query = query.toLowerCase().trim();

    // Find all project cards/items
    var projects = document.querySelectorAll('.project-item'); // More specific selector

    projects.forEach(item => {
        var title = item.querySelector('.card-title')?.textContent.toLowerCase() || '';
        var desc = item.querySelector('.card-text')?.textContent.toLowerCase() || '';
        var category = item.getAttribute('data-category')?.toLowerCase() || '';

        // Show or hide based on search match in title, description, or category
        if (query === '' || title.includes(query) || desc.includes(query) || category.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });

    // Reset filter buttons/dropdown if a search is performed
    var allButton = document.querySelector('.filter-btn[data-filter="all"]');
    if (allButton) {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        allButton.classList.add('active');
    }
    var categoryDropdown = document.getElementById('categoryFilter');
     if (categoryDropdown) {
         categoryDropdown.value = 'all';
     }
}

// Filter projects by category (Keep this)
function filterProjects(category) {
    var projects = document.querySelectorAll('.project-item'); // More specific selector

    projects.forEach(item => {
        var itemCategory = item.getAttribute('data-category')?.toLowerCase();
        
        if (category === 'all' || category === itemCategory) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
    
    // Clear search input if a filter is applied
    document.querySelectorAll('input[id*="Search"]').forEach(input => input.value = '');
}