// Handles login, password toggle, search/filter triggers, project search/filter, and contact form

document.addEventListener('DOMContentLoaded', function() {
    
    // --- ERROR HANDLING from URL ---
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const loginErrorMsgDiv = document.getElementById('loginErrorMsg'); 

    if (loginErrorMsgDiv) {
        let errorMessage = '';
        if (error === 'user_not_found') {
            errorMessage = 'User not found.';
        } else if (error === 'invalid_password') {
            errorMessage = 'Invalid password.';
        } else if (error === 'missing_fields') {
            errorMessage = 'Please fill in all fields.';
        } else if (error === 'invalid_user_type') {
            errorMessage = 'Invalid user type selected.';
        } else if (error === 'login_failed') {
            errorMessage = 'Login failed. Please try again.';
        }

        if (errorMessage) {
            loginErrorMsgDiv.textContent = errorMessage;
            loginErrorMsgDiv.classList.remove('d-none'); 

            //ensure the login modal is open if there's an error
            const loginModalElement = document.getElementById('loginModal');
            if (loginModalElement && window.location.hash === '#loginModal') {
                const loginModal = new bootstrap.Modal(loginModalElement);
                loginModal.show();
            }
        } else {
            loginErrorMsgDiv.classList.add('d-none'); 
        }
    }


    // --- LOGIN SYSTEM ---

    // Handle login form
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            var userType = document.getElementById('userType').value;
            var username = document.getElementById('pseudo').value;
            var password = document.getElementById('password').value;


            if (!userType || !username || !password) {
                alert('Please fill out all fields');
                event.preventDefault(); 
                return;
            }

            
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
            window.location.href = 'index.php';  
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
   
    console.log("Filtering by:", category); 
}

// Add event listener for search button if needed 
const homeSearchButton = document.getElementById('homeSearchButton');
const homeProjectSearch = document.getElementById('homeProjectSearch');


// Handle login form error display
const loginForm = document.getElementById('loginForm');
const loginErrorMsg = document.getElementById('loginErrorMsg');
const urlParams = new URLSearchParams(window.location.search);
const loginError = urlParams.get('error');
const registerSuccess = urlParams.get('register');

if (loginError && loginErrorMsg) {
    let message = 'An unknown error occurred.';
    switch (loginError) {
        case 'empty_fields':
            message = 'Please enter both pseudo and password.';
            break;
        case 'invalid_login':
            message = 'Invalid pseudo or password.';
            break;
        case 'database':
            message = 'Database error. Please try again later.';
            break;
        case 'unauthorized':
             message = 'You must be logged in to access that page.';
             break;
    }
    loginErrorMsg.textContent = message;
    loginErrorMsg.classList.remove('d-none');
    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
}

if (registerSuccess && loginErrorMsg) {
     let message = 'Registration successful! Please log in.';
     if (registerSuccess === 'success_association') {
         message = 'Association registration successful! Please log in.';
     } else if (registerSuccess === 'success_donor') {
         message = 'Donor registration successful! Please log in.';
     }
     loginErrorMsg.textContent = message;
     loginErrorMsg.classList.remove('alert-danger');
     loginErrorMsg.classList.add('alert-success'); 
     loginErrorMsg.classList.remove('d-none');
     const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
     loginModal.show();
}


// Password toggle visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const passwordInput = document.querySelector(targetId);
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});