// Authentication Check for Homepage
class AuthChecker {
    constructor() {
        this.checkAuthStatus();
    }

    async checkAuthStatus() {
        try {
            console.log('Checking authentication status...');
            
            // Try the debug endpoint first to see what's happening
            const debugResponse = await fetch('/auth/auth/debug_session.php');
            const debugData = await debugResponse.json();
            console.log('Debug session data:', debugData);
            
            // Now try the regular endpoint
            const response = await fetch('/auth/auth/check_session.php');
            const data = await response.json();
            console.log('Session check response:', data);
            
            if (data.logged_in) {
                console.log('User is logged in, updating UI...');
                this.showLoggedInState(data.user);
            } else {
                console.log('User is not logged in, showing logged out state...');
                this.showLoggedOutState();
            }
        } catch (error) {
            console.error('Error checking auth status:', error);
            this.showLoggedOutState();
        }
    }

    showLoggedInState(user) {
        console.log('Updating UI for logged in user:', user);
        
        // Hide auth buttons
        const authButtons = document.getElementById('authButtons');
        if (authButtons) {
            console.log('Hiding auth buttons');
            authButtons.style.display = 'none';
        } else {
            console.log('Auth buttons element not found');
        }

        // Show user menu
        const userMenu = document.getElementById('userMenu');
        if (userMenu) {
            console.log('Showing user menu');
            userMenu.style.display = 'flex';
        } else {
            console.log('User menu element not found');
        }

        // Show welcome message
        const welcomeMessage = document.getElementById('welcomeMessage');
        if (welcomeMessage) {
            console.log('Showing welcome message');
            welcomeMessage.style.display = 'block';
        } else {
            console.log('Welcome message element not found');
        }

        // Update username in navigation
        const navUsername = document.getElementById('navUsername');
        if (navUsername) {
            const displayName = user.fullname || user.username;
            console.log('Updating nav username to:', displayName);
            navUsername.textContent = displayName;
        } else {
            console.log('Nav username element not found');
        }

        // Update user name in dropdown
        const userName = document.getElementById('userName');
        if (userName) {
            const displayName = user.fullname || user.username;
            console.log('Updating dropdown username to:', displayName);
            userName.textContent = displayName;
        } else {
            console.log('Dropdown username element not found');
        }

        // Update user role in dropdown
        const userRole = document.querySelector('.user-role');
        if (userRole && user.primary_role) {
            const roleName = user.primary_role.display_name || 'Ecosystem Member';
            console.log('Updating user role to:', roleName);
            userRole.textContent = roleName;
        } else {
            console.log('User role element not found or no primary role');
        }

        // Update profile link
        const profileLink = document.querySelector('a[href="/auth/user_management/profile.php"]');
        if (profileLink) {
            console.log('Updating profile link');
            profileLink.href = '/auth/auth/profile.php';
        }

        // Update hero section for logged in users
        this.updateHeroForLoggedInUser(user);

        console.log('✅ UI update completed for logged in user');
    }

    updateHeroForLoggedInUser(user) {
        // Update hero badge to show personalized greeting
        const heroBadge = document.querySelector('.hero-badge span');
        if (heroBadge) {
            heroBadge.innerHTML = `Welcome back, ${user.fullname || user.username}!`;
        }

        // Update hero title for logged in users
        const heroTitle = document.querySelector('.hero-title');
        if (heroTitle) {
            heroTitle.innerHTML = `
                <span class="title-line">Welcome to Your</span>
                <span class="title-line gradient-text">Organizational</span>
                <span class="title-line">Ecosystem</span>
            `;
        }

        // Update hero description
        const heroDescription = document.querySelector('.hero-description');
        if (heroDescription) {
            heroDescription.textContent = `Continue collaborating and innovating with your team. Access your personalized dashboard and explore new opportunities within your ecosystem.`;
        }

        // Update CTA button
        const primaryCTA = document.getElementById('getStartedBtn');
        if (primaryCTA) {
            primaryCTA.innerHTML = `
                <span>Go to Dashboard</span>
                <i class="fas fa-arrow-right"></i>
            `;
            primaryCTA.onclick = () => {
                window.location.href = '/auth/auth/dashboard.php';
            };
        }
    }

    updateNavigationGreeting(user) {
        // Add a subtle greeting in the navigation area
        const navBrand = document.querySelector('.nav-brand');
        if (navBrand && !document.querySelector('.nav-greeting')) {
            const greeting = document.createElement('div');
            greeting.className = 'nav-greeting';
            greeting.innerHTML = `
                <span class="greeting-text">Hello, ${user.fullname || user.username}</span>
            `;
            navBrand.appendChild(greeting);
        }
    }

    showLoggedOutState() {
        // Show auth buttons
        const authButtons = document.getElementById('authButtons');
        if (authButtons) {
            authButtons.style.display = 'flex';
        }

        // Hide user menu
        const userMenu = document.getElementById('userMenu');
        if (userMenu) {
            userMenu.style.display = 'none';
        }

        // Hide welcome message
        const welcomeMessage = document.getElementById('welcomeMessage');
        if (welcomeMessage) {
            welcomeMessage.style.display = 'none';
        }

        console.log('User not logged in');
    }
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '/auth/auth/logout.php';
    }
}

// Initialize auth checker when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing auth checker...');
    
    // Add a small delay to ensure all elements are rendered
    setTimeout(() => {
        new AuthChecker();
        window.authCheckerInitialized = true;
    }, 100);
    
    // Add click handler for user avatar to toggle dropdown
    const userAvatar = document.getElementById('userAvatar');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userAvatar && userDropdown) {
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userDropdown.classList.remove('show');
        });

        // Prevent dropdown from closing when clicking inside it
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});

// Also try to initialize when window is fully loaded as backup
window.addEventListener('load', function() {
    console.log('Window fully loaded, checking if auth checker needs to run...');
    // Only run if we haven't already run the auth check
    if (!window.authCheckerInitialized) {
        console.log('Running backup auth checker initialization...');
        new AuthChecker();
        window.authCheckerInitialized = true;
    }
});

// Add CSS for dropdown functionality
const style = document.createElement('style');
style.textContent = `
    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.1);
        min-width: 250px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
        margin-top: 8px;
    }

    .user-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .user-info {
        padding: 16px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .user-name {
        font-weight: 600;
        color: #1e293b;
        display: block;
        margin-bottom: 4px;
    }

    .user-role {
        font-size: 0.875rem;
        color: #64748b;
    }

    .dropdown-menu {
        padding: 8px 0;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #374151;
        text-decoration: none;
        transition: all 0.2s ease;
        position: relative;
    }

    .dropdown-item:hover {
        background: #f8fafc;
        color: #059669;
    }

    .dropdown-item i {
        width: 16px;
        text-align: center;
        opacity: 0.7;
    }

    .dropdown-divider {
        height: 1px;
        background: rgba(0, 0, 0, 0.1);
        margin: 8px 0;
    }

    .logout-item {
        color: #ef4444 !important;
    }

    .logout-item:hover {
        background: #fef2f2 !important;
        color: #dc2626 !important;
    }

    .notification-count {
        background: #ef4444;
        color: white;
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: auto;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #059669, #10b981);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .user-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    }

    .user-section {
        position: relative;
    }

    .nav-greeting {
        margin-left: 12px;
        padding: 4px 12px;
        background: rgba(5, 150, 105, 0.1);
        border-radius: 20px;
        border: 1px solid rgba(5, 150, 105, 0.2);
    }

    .greeting-text {
        font-size: 0.875rem;
        color: #059669;
        font-weight: 500;
    }

    .welcome-message {
        margin-right: 16px;
        padding: 8px 16px;
        background: linear-gradient(135deg, rgba(5, 150, 105, 0.1), rgba(16, 185, 129, 0.1));
        border-radius: 25px;
        border: 1px solid rgba(5, 150, 105, 0.2);
        backdrop-filter: blur(10px);
    }

    .welcome-text {
        font-size: 0.875rem;
        color: #059669;
        font-weight: 600;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .nav-greeting {
            display: none;
        }
        
        .welcome-message {
            display: none !important;
        }
    }

    @media (max-width: 1024px) {
        .welcome-message {
            margin-right: 8px;
            padding: 6px 12px;
        }
        
        .welcome-text {
            font-size: 0.8rem;
        }
    }
`;
document.head.appendChild(style);