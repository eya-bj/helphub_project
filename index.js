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
});

// Form submission handler
function handleFormSubmission(form) {
    // Get form ID to determine which form was submitted
    const formId = form.getAttribute('id');
    
    // Handle different form submissions
    if (formId === 'associationRegisterForm') {
        console.log('Association registration form submitted');
        // In a real app, you would send data to server
        // For demo, just redirect to login
        alert('Registration successful! Please log in.');
        window.location.href = 'index.html';
    } 
    else if (formId === 'donorRegisterForm') {
        console.log('Donor registration form submitted');
        // In a real app, you would send data to server
        // For demo, just redirect to login
        alert('Registration successful! Please log in.');
        window.location.href = 'index.html';
    }
    // Add other form handlers as needed
}

// Create dashboard pages (for demonstration)
function createDashboardAssociation() {
    // In a real app, this would fetch data from the server
    // For demo, we create mock data
    const projects = [
        {
            id: 1,
            title: 'Clean Water Initiative',
            description: 'Providing clean water to rural communities',
            totalAmount: 5000,
            amountCollected: 3200,
            deadline: '2023-12-31',
            donations: 8
        },
        {
            id: 2,
            title: 'Education for All',
            description: 'Supporting education in underprivileged areas',
            totalAmount: 10000,
            amountCollected: 4500,
            deadline: '2023-11-30',
            donations: 12
        }
    ];
    
    // Populate projects in dashboard
    // This would be implemented in the dashboard page
}

// Search projects functionality
function searchProjects(query) {
    // In a real app, this would filter projects based on the query
    // For demo, we would just filter a local array
    console.log(`Searching for: ${query}`);
}

// Store user type in localStorage for page transitions
function storeUserType(userType) {
    localStorage.setItem('userType', userType);
}

// Handle donation form submission
function handleDonation(form) {
    const amount = form.querySelector('#donationAmount').value;
    const method = form.querySelector('#paymentMethod').value;
    const anonymous = form.querySelector('#anonymousCheck').checked;
    
    console.log(`Processing donation: $${amount} via ${method}, anonymous: ${anonymous}`);
    // In a real app, this would send data to the server
    
    // For demo, just show success message
    alert(`Thank you for your donation of $${amount}!`);
    window.location.href = 'dashboard-donor.html';
}

// Project details page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get current page
    const currentPage = window.location.pathname.split('/').pop();
    
    // Set user type during login
    const loginForm = document.getElementById('loginForm');
    if (loginForm && currentPage === 'index.html') {
        loginForm.addEventListener('submit', function(event) {
            const userType = document.getElementById('userType').value;
            storeUserType(userType);
        });
    }
    
    // Handle donation form on project details page
    const donationForm = document.getElementById('donationForm');
    if (donationForm && currentPage === 'project-details.html') {
        donationForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (donationForm.checkValidity()) {
                handleDonation(donationForm);
            }
            donationForm.classList.add('was-validated');
        });
    }
    
    // Handle project search
    const searchButton = document.getElementById('searchButton');
    if (searchButton && currentPage === 'dashboard-donor.html') {
        searchButton.addEventListener('click', function() {
            const query = document.getElementById('projectSearch').value;
            searchProjects(query);
        });
    }
    
    // Handle home page project search
    const homeSearchButton = document.getElementById('homeSearchButton');
    const homeProjectSearch = document.getElementById('homeProjectSearch');
    
    if (homeSearchButton && homeProjectSearch) {
        homeSearchButton.addEventListener('click', function() {
            const query = homeProjectSearch.value;
            searchHomeProjects(query);
        });
        
        homeProjectSearch.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                const query = homeProjectSearch.value;
                searchHomeProjects(query);
            }
        });
    }
    
    // Filter home page projects based on category
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Set active state
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Filter projects
                const category = this.getAttribute('data-filter');
                filterHomeProjects(category);
            });
        });
    }
});

// Helper function to search home page projects
function searchHomeProjects(query) {
    query = query.toLowerCase().trim();
    const projectItems = document.querySelectorAll('.project-item');
    
    projectItems.forEach(item => {
        const title = item.querySelector('.card-title').textContent.toLowerCase();
        const description = item.querySelector('.card-text').textContent.toLowerCase();
        
        if (title.includes(query) || description.includes(query) || query === '') {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
    
    // Reset filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const allButton = document.querySelector('[data-filter="all"]');
    if (allButton) {
        allButton.classList.add('active');
    }
}

// Helper function to filter home page projects by category
function filterHomeProjects(category) {
    const projectItems = document.querySelectorAll('.project-item');
    
    projectItems.forEach(item => {
        if (category === 'all' || item.getAttribute('data-category') === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
