// Simple and direct JavaScript for the homepage
console.log('Script loaded');

// Global variables
let currentTheme = localStorage.getItem('theme') || 'light';

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    // Initialize theme immediately
    initTheme();
    
    // Initialize all functionality
    initSearch();
    initMobileMenu();
    initThemeToggle();
    initHeroButtons();
    initActivityFilters();
    initQuickActions();
    initNewsletterForm();
    initUserDropdown();
    initModals();
    
    // Load content
    loadActivityFeed();
    loadTrendingTopics();
    
    console.log('All initialization complete');
});

// Theme functionality
function initTheme() {
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    
    console.log('Initializing theme, current:', currentTheme);
    
    if (currentTheme === 'dark') {
        body.setAttribute('data-theme', 'dark');
        if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    } else {
        body.setAttribute('data-theme', 'light');
        if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
    }
}

function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    
    if (themeToggle) {
        console.log('Theme toggle found, adding listener');
        themeToggle.addEventListener('click', function() {
            console.log('Theme toggle clicked');
            toggleTheme();
        });
    } else {
        console.warn('Theme toggle button not found');
    }
}

function toggleTheme() {
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    
    console.log('Toggling theme from:', currentTheme);
    
    if (currentTheme === 'light') {
        currentTheme = 'dark';
        body.setAttribute('data-theme', 'dark');
        if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    } else {
        currentTheme = 'light';
        body.setAttribute('data-theme', 'light');
        if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
    }
    
    localStorage.setItem('theme', currentTheme);
    showNotification(`Switched to ${currentTheme} mode`);
    console.log('Theme switched to:', currentTheme);
}

// Search functionality
function initSearch() {
    const searchToggle = document.getElementById('searchToggle');
    const searchOverlay = document.getElementById('searchOverlay');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('searchInput');
    
    if (searchToggle && searchOverlay) {
        console.log('Search elements found, adding listeners');
        
        searchToggle.addEventListener('click', function() {
            console.log('Search toggle clicked');
            searchOverlay.classList.add('active');
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 300);
        });
        
        if (searchClose) {
            searchClose.addEventListener('click', function() {
                console.log('Search close clicked');
                searchOverlay.classList.remove('active');
            });
        }
        
        // Close on overlay click
        searchOverlay.addEventListener('click', function(e) {
            if (e.target === searchOverlay) {
                searchOverlay.classList.remove('active');
            }
        });
        
        // Search on Enter
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }
    } else {
        console.warn('Search elements not found');
    }
}

function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const query = searchInput ? searchInput.value.trim() : '';
    
    if (!query) {
        showNotification('Please enter a search term');
        return;
    }
    
    console.log('Performing search for:', query);
    showNotification(`Searching for "${query}"...`);
    
    // Close search overlay
    const searchOverlay = document.getElementById('searchOverlay');
    if (searchOverlay) {
        searchOverlay.classList.remove('active');
    }
    
    // Simulate search results
    setTimeout(() => {
        showNotification(`Found results for "${query}"`);
    }, 1000);
}

// Mobile menu
function initMobileMenu() {
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.querySelector('.nav-links');
    const floatingNav = document.querySelector('.floating-nav');
    
    if (mobileToggle) {
        console.log('Mobile toggle found, adding listener');
        
        mobileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Mobile toggle clicked');
            
            mobileToggle.classList.toggle('active');
            
            if (navLinks) {
                navLinks.classList.toggle('mobile-active');
            }
            
            if (floatingNav) {
                floatingNav.classList.toggle('mobile-menu-open');
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (floatingNav && !floatingNav.contains(e.target)) {
                closeMobileMenu();
            }
        });
    }
}

function closeMobileMenu() {
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.querySelector('.nav-links');
    const floatingNav = document.querySelector('.floating-nav');
    
    if (mobileToggle) mobileToggle.classList.remove('active');
    if (navLinks) navLinks.classList.remove('mobile-active');
    if (floatingNav) floatingNav.classList.remove('mobile-menu-open');
}

// Hero buttons
function initHeroButtons() {
    const getStartedBtn = document.getElementById('getStartedBtn');
    const watchDemoBtn = document.getElementById('watchDemoBtn');
    
    if (getStartedBtn) {
        getStartedBtn.addEventListener('click', function() {
            console.log('Get started clicked');
            const featuresSection = document.getElementById('features');
            if (featuresSection) {
                featuresSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
    
    if (watchDemoBtn) {
        watchDemoBtn.addEventListener('click', function() {
            console.log('Watch demo clicked');
            showDemoModal();
        });
    }
}

// Activity filters
function initActivityFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = btn.getAttribute('data-filter');
            console.log('Filter clicked:', filter);
            
            // Update active state
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            showNotification(`Filtering by: ${filter}`);
        });
    });
}

// Quick actions
function initQuickActions() {
    const quickActionBtns = document.querySelectorAll('.quick-action');
    
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = btn.getAttribute('data-action');
            console.log('Quick action clicked:', action);
            
            switch (action) {
                case 'start-chat':
                    window.location.href = '/auth/components/chat/chatroom.php';
                    break;
                default:
                    showNotification('Feature coming soon!');
            }
        });
    });
}

// Newsletter form
function initNewsletterForm() {
    const newsletterForm = document.getElementById('newsletterForm');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = e.target.querySelector('input[type="email"]').value;
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            console.log('Newsletter subscription:', email);
            
            // Show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Simulate API call
            setTimeout(() => {
                showNotification('Successfully subscribed to newsletter!');
                e.target.reset();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }, 1000);
        });
    }
}

// User dropdown
function initUserDropdown() {
    const userAvatar = document.getElementById('userAvatar');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userAvatar && userDropdown) {
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && !userAvatar.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }
}

// Modals
function initModals() {
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.active');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function showDemoModal() {
    const modalHtml = `
        <div id="demoModal" class="modal">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Platform Demo</h3>
                    <button class="modal-close" onclick="closeModal('demoModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="demo-container">
                        <div class="demo-preview">
                            <i class="fas fa-play-circle"></i>
                            <h4>Interactive Demo</h4>
                            <p>Experience the power of organizational ecosystem communication</p>
                            <button class="demo-start-btn" onclick="startDemo()">
                                <i class="fas fa-rocket"></i>
                                Start Demo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    showModal('demoModal');
}

function startDemo() {
    showNotification('Demo starting...');
    closeModal('demoModal');
}

// Load activity feed
function loadActivityFeed() {
    const activityFeed = document.getElementById('activityFeed');
    if (!activityFeed) return;
    
    console.log('Loading activity feed');
    
    // Show loading message
    activityFeed.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">Loading ecosystem activity...</div>';
    
    // Simulate loading
    setTimeout(() => {
        const mockActivity = [
            {
                id: 1,
                title: 'New project collaboration started',
                description: 'Team Alpha initiated a new cross-departmental project focusing on digital transformation.',
                author: 'Sarah Johnson',
                time: '2h ago',
                type: 'collaboration',
                avatar: 'SJ',
                likes: 12,
                comments: 5
            },
            {
                id: 2,
                title: 'Best practices guide published',
                description: 'New comprehensive guide on agile methodologies now available in the knowledge hub.',
                author: 'Michael Chen',
                time: '4h ago',
                type: 'knowledge',
                avatar: 'MC',
                likes: 28,
                comments: 8
            },
            {
                id: 3,
                title: 'Quarterly review meeting scheduled',
                description: 'All department heads are invited to the Q4 review meeting next Friday at 2 PM.',
                author: 'Emma Wilson',
                time: '6h ago',
                type: 'announcements',
                avatar: 'EW',
                likes: 15,
                comments: 3
            }
        ];
        
        displayActivityFeed(mockActivity);
    }, 1000);
}

function displayActivityFeed(activities) {
    const activityFeed = document.getElementById('activityFeed');
    if (!activityFeed) return;
    
    activityFeed.innerHTML = '';
    
    activities.forEach(activity => {
        const activityElement = createActivityElement(activity);
        activityFeed.appendChild(activityElement);
    });
}

function createActivityElement(activity) {
    const activityDiv = document.createElement('div');
    activityDiv.className = 'activity-item';
    activityDiv.innerHTML = `
        <div class="activity-header">
            <div class="activity-avatar">${activity.avatar}</div>
            <div class="activity-meta">
                <h4 class="activity-title">${activity.title}</h4>
                <div class="activity-info">
                    <span class="activity-author">${activity.author}</span>
                    <span class="activity-time">${activity.time}</span>
                    <span class="activity-type">${activity.type}</span>
                </div>
            </div>
        </div>
        <p class="activity-description">${activity.description}</p>
        <div class="activity-actions">
            <button class="activity-action-btn" onclick="likeActivity(${activity.id})">
                <i class="far fa-heart"></i>
                <span>${activity.likes}</span>
            </button>
            <button class="activity-action-btn" onclick="showComments(${activity.id})">
                <i class="far fa-comment"></i>
                <span>${activity.comments}</span>
            </button>
            <button class="activity-action-btn" onclick="shareActivity(${activity.id})">
                <i class="fas fa-share"></i>
                <span>Share</span>
            </button>
        </div>
    `;
    
    return activityDiv;
}

// Load trending topics
function loadTrendingTopics() {
    const trendingList = document.getElementById('trendingList');
    if (!trendingList) return;
    
    console.log('Loading trending topics');
    
    const mockTrending = [
        { topic: 'Digital Transformation', count: 45 },
        { topic: 'Remote Collaboration', count: 38 },
        { topic: 'Agile Methodology', count: 32 },
        { topic: 'Innovation Labs', count: 28 },
        { topic: 'Sustainability', count: 24 }
    ];
    
    trendingList.innerHTML = '';
    
    mockTrending.forEach((topic, index) => {
        const topicElement = document.createElement('div');
        topicElement.className = 'trending-item';
        topicElement.innerHTML = `
            <div class="trending-rank">${index + 1}</div>
            <div class="trending-content">
                <div class="trending-topic">${topic.topic}</div>
                <div class="trending-count">${topic.count} discussions</div>
            </div>
        `;
        
        topicElement.addEventListener('click', function() {
            console.log('Trending topic clicked:', topic.topic);
            showNotification(`Searching for "${topic.topic}"`);
        });
        
        trendingList.appendChild(topicElement);
    });
}

// Activity interactions
function likeActivity(activityId) {
    console.log('Like activity:', activityId);
    showNotification('Activity liked!');
}

function showComments(activityId) {
    console.log('Show comments:', activityId);
    showNotification('Comments feature coming soon!');
}

function shareActivity(activityId) {
    console.log('Share activity:', activityId);
    showNotification('Activity shared!');
}

// Logout function
function logout() {
    console.log('Logout clicked');
    showNotification('Logging out...');
    setTimeout(() => {
        window.location.href = '/auth/auth/login.html';
    }, 1000);
}

// Notification system
function showNotification(message, type = 'info') {
    console.log('Showing notification:', message);
    
    const container = document.getElementById('notificationContainer');
    if (!container) {
        console.warn('Notification container not found');
        return;
    }
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="notification-text">
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 400);
        }
    }, 5000);
}

// Add activity styles
const activityStyles = `
    .activity-item {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .activity-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
    }
    
    .activity-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .activity-avatar {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .activity-meta {
        flex: 1;
    }
    
    .activity-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    
    .activity-info {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.875rem;
        color: #64748b;
    }
    
    .activity-author {
        font-weight: 600;
    }
    
    .activity-time {
        color: #94a3b8;
    }
    
    .activity-type {
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .activity-description {
        color: #334155;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .activity-actions {
        display: flex;
        gap: 16px;
        padding-top: 16px;
        border-top: 1px solid #e2e8f0;
    }
    
    .activity-action-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .activity-action-btn:hover {
        background: #f1f5f9;
        color: #2563eb;
        transform: translateY(-1px);
    }
    
    .trending-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 8px;
    }
    
    .trending-item:hover {
        background: rgba(37, 99, 235, 0.05);
        transform: translateX(4px);
    }
    
    .trending-rank {
        width: 24px;
        height: 24px;
        background: #2563eb;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
    }
    
    .trending-content {
        flex: 1;
    }
    
    .trending-topic {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 2px;
    }
    
    .trending-count {
        font-size: 0.8125rem;
        color: #94a3b8;
    }
`;

// Add styles to page
const styleSheet = document.createElement('style');
styleSheet.textContent = activityStyles;
document.head.appendChild(styleSheet);

console.log('Script setup complete');