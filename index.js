// Form Validation
document.addEventListener('DOMContentLoaded', function () {
    // Custom implementations for Bootstrap JS functionality
    
    // 1. Navbar toggler for mobile
    const navbarTogglers = document.querySelectorAll('.navbar-toggler');
    navbarTogglers.forEach(toggler => {
        toggler.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-target')) || 
                            this.nextElementSibling;
            if (target) {
                target.classList.toggle('show');
            }
        });
    });
    
    // 2. Dropdown menus
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdownMenu = this.nextElementSibling;
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                if (menu !== dropdownMenu) {
                    menu.classList.remove('show');
                }
            });
            
            dropdownMenu.classList.toggle('show');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle') && !e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    // 3. Modal functionality
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    const modalClosers = document.querySelectorAll('[data-dismiss="modal"]');
    
    function openModal(modalId) {
        const modal = document.querySelector(modalId);
        if (modal) {
            // Create modal backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
            
            document.body.classList.add('modal-open');
            modal.classList.add('show');
            modal.style.display = 'block';
        }
    }
    
    function closeModal(modal) {
        document.body.classList.remove('modal-open');
        modal.classList.remove('show');
        modal.style.display = 'none';
        
        // Remove backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        // Reset body styles
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';
    }
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-target');
            openModal(modalId);
        });
    });
    
    modalClosers.forEach(closer => {
        closer.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modal when clicking on backdrop
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
    
    // 4. Tab functionality
    const tabLinks = document.querySelectorAll('[data-toggle="tab"]');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get target tab pane
            const targetSelector = this.getAttribute('data-target');
            const targetPane = document.querySelector(targetSelector);
            
            if (!targetPane) return;
            
            // Deactivate all tabs in the same group
            const tabContainer = this.closest('.nav-tabs');
            if (tabContainer) {
                tabContainer.querySelectorAll('.nav-link').forEach(navLink => {
                    navLink.classList.remove('active');
                    navLink.setAttribute('aria-selected', 'false');
                });
            }
            
            // Deactivate all tab panes
            const tabContentContainer = targetPane.closest('.tab-content');
            if (tabContentContainer) {
                tabContentContainer.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
            }
            
            // Activate current tab and pane
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            targetPane.classList.add('show', 'active');
        });
    });
    
    // Existing code that doesn't depend on Bootstrap JS

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
    
    // Project filtering functionality for home page
    const filterButtons = document.querySelectorAll('.filter-btn');
    const projectItems = document.querySelectorAll('.project-item');
    
    if (filterButtons.length > 0 && projectItems.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filterValue = this.getAttribute('data-filter');
                
                projectItems.forEach(item => {
                    if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    }
    
    // Handle project search on home page
    const homeSearchInput = document.getElementById('homeProjectSearch');
    const homeSearchButton = document.getElementById('homeSearchButton');
    
    if (homeSearchButton && homeSearchInput) {
        homeSearchButton.addEventListener('click', function() {
            searchProjects(homeSearchInput.value.toLowerCase());
        });
        
        homeSearchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchProjects(this.value.toLowerCase());
            }
        });
    }
    
    // Handle project search on donor dashboard
    const searchButton = document.getElementById('searchButton');
    const projectSearch = document.getElementById('projectSearch');
    
    if (searchButton && projectSearch) {
        searchButton.addEventListener('click', function() {
            const query = projectSearch.value;
            searchProjects(query);
        });
    }
    
    // Update the links in dashboard-association.html file
    if (window.location.pathname.includes('dashboard-association.html')) {
        document.querySelectorAll('[href*="project-details.html"]').forEach(link => {
            const href = link.getAttribute('href');
            // Replace with the association version
            link.setAttribute('href', href.replace('project-details.html', 'project-details-association.html'));
        });
    }

    // Update the links in dashboard-donor.html file
    if (window.location.pathname.includes('dashboard-donor.html')) {
        document.querySelectorAll('[href*="project-details.html"]').forEach(link => {
            const href = link.getAttribute('href');
            // Replace with the donor version
            link.setAttribute('href', href.replace('project-details.html', 'project-details-donor.html'));
        });
    }

    // Update href references to project details and profile pages
    if (document.querySelectorAll('[href*="profile.html"]').length > 0) {
        const userType = localStorage.getItem('userType') || 'donor';
        
        document.querySelectorAll('[href*="profile.html"]').forEach(link => {
            if (userType === 'donor') {
                link.setAttribute('href', 'profile-donor.html');
            } else {
                link.setAttribute('href', 'profile-association.html');
            }
        });
    }
    
    if (document.querySelectorAll('[href*="project-details.html"]').length > 0) {
        const userType = localStorage.getItem('userType') || 'donor';
        
        document.querySelectorAll('[href*="project-details.html"]').forEach(link => {
            const href = link.getAttribute('href');
            if (userType === 'donor') {
                link.setAttribute('href', href.replace('project-details.html', 'project-details-donor.html'));
            } else {
                link.setAttribute('href', href.replace('project-details.html', 'project-details-association.html'));
            }
        });
    }

    // Handle project details page functionality - update for both versions
    if (window.location.pathname.includes('project-details-donor.html')) {
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
                    alert('Thank you for your donation!');
                    window.location.href = 'dashboard-donor.html';
                }
                donationForm.classList.add('was-validated');
            });
        }
    }

    // Handle project details page functionality
    if (window.location.pathname.includes('project-details.html')) {
        // Determine user type from URL parameter and show appropriate view
        const urlParams = new URLSearchParams(window.location.search);
        const userType = urlParams.get('userType') || localStorage.getItem('userType') || 'donor';
        
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
                    alert('Thank you for your donation!');
                    window.location.href = 'dashboard-donor.html';
                }
                donationForm.classList.add('was-validated');
            });
        }
    }
    
    // Handle profile page functionality
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
        
        // Handle profile form submission
        const associationForm = document.getElementById('associationProfileForm');
        const donorForm = document.getElementById('donorProfileForm');
        
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
        
        // Handle password change form
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
                    const modal = document.getElementById('changePasswordModal');
                    if (modal) closeModal(modal);
                }
                passwordForm.classList.add('was-validated');
            });
        }
        
        // Handle delete account confirmation
        const deleteConfirm = document.getElementById('deleteConfirm');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        
        if (deleteConfirm && confirmDeleteBtn) {
            deleteConfirm.addEventListener('input', function() {
                if (this.value === 'DELETE') {
                    confirmDeleteBtn.disabled = false;
                } else {
                    confirmDeleteBtn.disabled = true;
                }
            });
            
            confirmDeleteBtn.addEventListener('click', function(event) {
                event.preventDefault();
                alert('Account deleted successfully.');
                window.location.href = 'index.html';
            });
        }
    }

    // Handle profile page functionality for both profile types
    if (window.location.pathname.includes('profile-donor.html')) {
        // Handle donor profile form submission
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

    if (window.location.pathname.includes('profile-association.html')) {
        // Handle association profile form submission
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
    }
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

// Search projects function
function searchProjects(query) {
    query = query.toLowerCase().trim();
    const projectItems = document.querySelectorAll('.project-item');
    
    if (projectItems.length > 0) {
        projectItems.forEach(item => {
            const title = item.querySelector('.card-title').textContent.toLowerCase();
            const description = item.querySelector('.card-text').textContent.toLowerCase();
            
            if (title.includes(query) || description.includes(query) || query === '') {
                item.style.display = 'block';
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
