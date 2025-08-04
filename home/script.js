// Global variables
let currentUser = null;
let currentTheme = localStorage.getItem('theme') || 'light';
let activityData = [];
let trendingTopics = [];
let isLoading = false;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize all app functionality
function initializeApp() {
    checkAuthStatus();
    initializeTheme();
    initializeEventListeners();
    initializeAnimations();
    loadEcosystemData();
    startRealTimeUpdates();
    
    // Check for URL parameters
    handleUrlParameters();
}

// Check authentication status
async function checkAuthStatus() {
    try {
        const response = await fetch('/auth/test/check_auth.php');
        const data = await response.json();
        
        if (data.authenticated) {
            currentUser = data.user;
            updateAuthUI(true);
            loadUserSpecificData();
        } else {
            updateAuthUI(false);
        }
    } catch (error) {
        console.error('Auth check failed:', error);
        updateAuthUI(false);
    }
}

// Update authentication UI
function updateAuthUI(isAuthenticated) {
    const authButtons = document.getElementById('authButtons');
    const userMenu = document.getElementById('userMenu');
    const userName = document.getElementById('userName');
    
    if (isAuthenticated && currentUser) {
        authButtons.style.display = 'none';
        userMenu.style.display = 'block';
        userName.textContent = currentUser.fullname || currentUser.username;
        
        // Update user avatar with initials
        const userAvatar = document.querySelector('.user-avatar i');
        if (userAvatar && currentUser.fullname) {
            const initials = currentUser.fullname.split(' ').map(n => n[0]).join('').toUpperCase();
            userAvatar.textContent = initials;
            userAvatar.className = '';
        }
    } else {
        authButtons.style.display = 'flex';
        userMenu.style.display = 'none';
    }
}

// Initialize theme system
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    
    if (!themeToggle) {
        console.warn('Theme toggle button not found');
        return;
    }
    
    // Set initial theme
    if (currentTheme === 'dark') {
        body.setAttribute('data-theme', 'dark');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    } else {
        body.setAttribute('data-theme', 'light');
        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
    }
    
    // Theme toggle event listener
    themeToggle.addEventListener('click', toggleTheme);
}

// Toggle theme
function toggleTheme() {
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    
    if (!themeToggle) {
        console.warn('Theme toggle button not found');
        return;
    }
    
    if (currentTheme === 'light') {
        currentTheme = 'dark';
        body.setAttribute('data-theme', 'dark');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    } else {
        currentTheme = 'light';
        body.setAttribute('data-theme', 'light');
        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
    }
    
    localStorage.setItem('theme', currentTheme);
    showNotification(`Switched to ${currentTheme} mode`, 'success');
}

// Initialize event listeners
function initializeEventListeners() {
    // Navigation
    initializeNavigation();
    
    // Search functionality
    initializeSearch();
    
    // Hero actions
    initializeHeroActions();
    
    // Activity filters
    initializeActivityFilters();
    
    // Quick actions
    initializeQuickActions();
    
    // Newsletter form
    initializeNewsletterForm();
    
    // Mobile menu
    initializeMobileMenu();
    
    // Modal handlers
    initializeModals();
    
    // Scroll effects
    initializeScrollEffects();
    
    // User dropdown
    initializeUserDropdown();
}

// Initialize navigation
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-item[data-section]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.getAttribute('data-section');
            navigateToSection(section);
            
            // Update active state
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });
}

// Navigate to section
function navigateToSection(section) {
    const sectionElement = document.getElementById(section);
    if (sectionElement) {
        sectionElement.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
    
    // Update URL without page reload
    history.pushState(null, null, `#${section}`);
}

// Initialize search functionality
function initializeSearch() {
    const searchToggle = document.getElementById('searchToggle');
    const searchOverlay = document.getElementById('searchOverlay');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('searchInput');
    
    if (searchToggle && searchOverlay) {
        searchToggle.addEventListener('click', () => {
            searchOverlay.classList.add('active');
            setTimeout(() => searchInput?.focus(), 300);
        });
    }
    
    if (searchClose && searchOverlay) {
        searchClose.addEventListener('click', () => {
            searchOverlay.classList.remove('active');
        });
    }
    
    if (searchOverlay) {
        searchOverlay.addEventListener('click', (e) => {
            if (e.target === searchOverlay) {
                searchOverlay.classList.remove('active');
            }
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        // Real-time search suggestions
        searchInput.addEventListener('input', debounce(showSearchSuggestions, 300));
    }
}

// Perform search
async function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const query = searchInput.value.trim();
    
    if (!query) {
        showNotification('Please enter a search term', 'warning');
        return;
    }
    
    showLoading();
    
    try {
        const results = await searchEcosystem(query);
        displaySearchResults(results);
        showNotification(`Found ${results.length} results for "${query}"`, 'success');
        
        // Close search overlay
        const searchOverlay = document.getElementById('searchOverlay');
        if (searchOverlay) {
            searchOverlay.classList.remove('active');
        }
    } catch (error) {
        console.error('Search error:', error);
        showNotification('Search failed. Please try again.', 'error');
    } finally {
        hideLoading();
    }
}

// Search ecosystem
async function searchEcosystem(query) {
    // Simulate API call - replace with actual endpoint
    return new Promise((resolve) => {
        setTimeout(() => {
            const mockResults = [
                {
                    id: 1,
                    title: `Collaboration on ${query}`,
                    type: 'collaboration',
                    description: `Recent collaboration activities related to ${query}`,
                    timestamp: new Date().toISOString()
                },
                {
                    id: 2,
                    title: `Knowledge about ${query}`,
                    type: 'knowledge',
                    description: `Knowledge base articles about ${query}`,
                    timestamp: new Date().toISOString()
                }
            ];
            resolve(mockResults);
        }, 1000);
    });
}

// Initialize hero actions
function initializeHeroActions() {
    const getStartedBtn = document.getElementById('getStartedBtn');
    const watchDemoBtn = document.getElementById('watchDemoBtn');
    
    if (getStartedBtn) {
        getStartedBtn.addEventListener('click', () => {
            navigateToSection('features');
            trackUserAction('get_started');
        });
    }
    
    if (watchDemoBtn) {
        watchDemoBtn.addEventListener('click', () => {
            showDemoModal();
            trackUserAction('watch_demo');
        });
    }
}

// Initialize activity filters
function initializeActivityFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.getAttribute('data-filter');
            
            // Update active state
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Filter activity
            filterActivity(filter);
        });
    });
}

// Filter activity
function filterActivity(filter) {
    const filteredData = filter === 'all' 
        ? activityData 
        : activityData.filter(item => item.type === filter);
    
    displayActivityFeed(filteredData);
    trackUserAction('filter_activity', { filter });
}

// Initialize quick actions
function initializeQuickActions() {
    const quickActionBtns = document.querySelectorAll('.quick-action');
    
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.getAttribute('data-action');
            handleQuickAction(action);
        });
    });
}

// Handle quick actions
function handleQuickAction(action) {
    if (!currentUser) {
        showNotification('Please login to perform this action', 'warning');
        return;
    }
    
    switch (action) {
        case 'new-post':
            openNewPostModal();
            break;
        case 'start-chat':
            window.location.href = '/auth/components/chat/chat.html';
            break;
        case 'share-resource':
            openShareResourceModal();
            break;
        case 'create-event':
            openCreateEventModal();
            break;
        default:
            showNotification('Feature coming soon!', 'info');
    }
    
    trackUserAction('quick_action', { action });
}

// Initialize newsletter form
function initializeNewsletterForm() {
    const newsletterForm = document.getElementById('newsletterForm');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = e.target.querySelector('input[type="email"]').value;
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            try {
                await subscribeToNewsletter(email);
                showNotification('Successfully subscribed to newsletter!', 'success');
                e.target.reset();
            } catch (error) {
                console.error('Newsletter subscription error:', error);
                showNotification('Subscription failed. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
        });
    }
}

// Initialize mobile menu
function initializeMobileMenu() {
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.querySelector('.nav-links');
    const floatingNav = document.querySelector('.floating-nav');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            mobileToggle.classList.toggle('active');
            
            if (navLinks) {
                navLinks.classList.toggle('mobile-active');
            }
            
            if (floatingNav) {
                floatingNav.classList.toggle('mobile-menu-open');
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!floatingNav || !floatingNav.contains(e.target)) {
                closeMobileMenu();
            }
        });
        
        // Close menu when clicking on nav links
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', () => {
                closeMobileMenu();
            });
        });
    }
}

// Close mobile menu helper function
function closeMobileMenu() {
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.querySelector('.nav-links');
    const floatingNav = document.querySelector('.floating-nav');
    
    if (mobileToggle) {
        mobileToggle.classList.remove('active');
    }
    if (navLinks) {
        navLinks.classList.remove('mobile-active');
    }
    if (floatingNav) {
        floatingNav.classList.remove('mobile-menu-open');
    }
}

// Initialize modals
function initializeModals() {
    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.active');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

// Initialize scroll effects
function initializeScrollEffects() {
    const floatingNav = document.querySelector('.floating-nav');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            floatingNav?.classList.add('scrolled');
        } else {
            floatingNav?.classList.remove('scrolled');
        }
    });
}

// Initialize animations
function initializeAnimations() {
    // Animate statistics on scroll
    const statNumbers = document.querySelectorAll('.stat-number[data-count]');
    
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    statNumbers.forEach(stat => observer.observe(stat));
    
    // Animate feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        observer.observe(card);
    });
}

// Animate counter
function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-count'));
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        
        element.textContent = Math.floor(current).toLocaleString();
    }, 16);
}

// Load ecosystem data
async function loadEcosystemData() {
    try {
        showLoading();
        
        // Load activity feed
        const activity = await loadActivityFeed();
        activityData = activity;
        displayActivityFeed(activity);
        
        // Load trending topics
        const trending = await loadTrendingTopics();
        trendingTopics = trending;
        displayTrendingTopics(trending);
        
        // Load user-specific data if authenticated
        if (currentUser) {
            await loadUserSpecificData();
        }
        
    } catch (error) {
        console.error('Failed to load ecosystem data:', error);
        showNotification('Failed to load some data. Please refresh the page.', 'error');
    } finally {
        hideLoading();
    }
}

// Load activity feed
async function loadActivityFeed() {
    // Simulate API call - replace with actual endpoint
    return new Promise((resolve) => {
        setTimeout(() => {
            const mockActivity = [
                {
                    id: 1,
                    type: 'collaboration',
                    title: 'New project collaboration started',
                    description: 'Team Alpha initiated a new cross-departmental project focusing on digital transformation.',
                    author: 'Sarah Johnson',
                    timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
                    likes: 12,
                    comments: 5,
                    avatar: 'SJ'
                },
                {
                    id: 2,
                    type: 'knowledge',
                    title: 'Best practices guide published',
                    description: 'New comprehensive guide on agile methodologies now available in the knowledge hub.',
                    author: 'Michael Chen',
                    timestamp: new Date(Date.now() - 4 * 60 * 60 * 1000).toISOString(),
                    likes: 28,
                    comments: 8,
                    avatar: 'MC'
                },
                {
                    id: 3,
                    type: 'announcements',
                    title: 'Quarterly review meeting scheduled',
                    description: 'All department heads are invited to the Q4 review meeting next Friday at 2 PM.',
                    author: 'Emma Wilson',
                    timestamp: new Date(Date.now() - 6 * 60 * 60 * 1000).toISOString(),
                    likes: 15,
                    comments: 3,
                    avatar: 'EW'
                }
            ];
            resolve(mockActivity);
        }, 1000);
    });
}

// Display activity feed
function displayActivityFeed(activities) {
    const activityFeed = document.getElementById('activityFeed');
    if (!activityFeed) return;
    
    activityFeed.innerHTML = '';
    
    activities.forEach(activity => {
        const activityElement = createActivityElement(activity);
        activityFeed.appendChild(activityElement);
    });
}

// Create activity element
function createActivityElement(activity) {
    const activityDiv = document.createElement('div');
    activityDiv.className = 'activity-item fade-in';
    activityDiv.innerHTML = `
        <div class="activity-header">
            <div class="activity-avatar">${activity.avatar}</div>
            <div class="activity-meta">
                <h4 class="activity-title">${activity.title}</h4>
                <div class="activity-info">
                    <span class="activity-author">${activity.author}</span>
                    <span class="activity-time">${formatTimeAgo(activity.timestamp)}</span>
                    <span class="activity-type">${activity.type}</span>
                </div>
            </div>
        </div>
        <p class="activity-description">${activity.description}</p>
        <div class="activity-actions">
            <button class="activity-action-btn" onclick="toggleLike(${activity.id})">
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
async function loadTrendingTopics() {
    // Simulate API call - replace with actual endpoint
    return new Promise((resolve) => {
        setTimeout(() => {
            const mockTrending = [
                { topic: 'Digital Transformation', count: 45 },
                { topic: 'Remote Collaboration', count: 38 },
                { topic: 'Agile Methodology', count: 32 },
                { topic: 'Innovation Labs', count: 28 },
                { topic: 'Sustainability', count: 24 }
            ];
            resolve(mockTrending);
        }, 800);
    });
}

// Display trending topics
function displayTrendingTopics(topics) {
    const trendingList = document.getElementById('trendingList');
    if (!trendingList) return;
    
    trendingList.innerHTML = '';
    
    topics.forEach((topic, index) => {
        const topicElement = document.createElement('div');
        topicElement.className = 'trending-item';
        topicElement.innerHTML = `
            <div class="trending-rank">${index + 1}</div>
            <div class="trending-content">
                <div class="trending-topic">${topic.topic}</div>
                <div class="trending-count">${topic.count} discussions</div>
            </div>
        `;
        
        topicElement.addEventListener('click', () => {
            searchEcosystemByTopic(topic.topic);
        });
        
        trendingList.appendChild(topicElement);
    });
}

// Load user-specific data
async function loadUserSpecificData() {
    try {
        // Load user notifications
        const notifications = await loadUserNotifications();
        updateNotificationBadge(notifications.length);
        
        // Load user's recent activity
        const userActivity = await loadUserActivity();
        // Process user activity...
        
    } catch (error) {
        console.error('Failed to load user data:', error);
    }
}

// Start real-time updates
function startRealTimeUpdates() {
    // Simulate real-time updates - replace with WebSocket or SSE
    setInterval(async () => {
        if (currentUser) {
            try {
                const newNotifications = await checkForNewNotifications();
                if (newNotifications.length > 0) {
                    updateNotificationBadge(newNotifications.length);
                    showNotification(`You have ${newNotifications.length} new notifications`, 'info');
                }
            } catch (error) {
                console.error('Failed to check notifications:', error);
            }
        }
    }, 30000); // Check every 30 seconds
}

// Utility functions
function formatTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diffInSeconds = Math.floor((now - time) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function trackUserAction(action, data = {}) {
    // Track user actions for analytics
    console.log('User action:', action, data);
    
    // Send to analytics service
    if (typeof gtag !== 'undefined') {
        gtag('event', action, data);
    }
}

// Modal functions
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
    // Create and show demo modal
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
                            <button class="demo-start-btn" onclick="startInteractiveDemo()">
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

// Loading functions
function showLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('active');
        isLoading = true;
    }
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('active');
        isLoading = false;
    }
}

// Notification system
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas fa-${getNotificationIcon(type)}"></i>
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
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 400);
        }
    }, duration);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// API functions (replace with actual endpoints)
async function subscribeToNewsletter(email) {
    // Simulate API call
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            if (email.includes('@')) {
                resolve({ success: true });
            } else {
                reject(new Error('Invalid email'));
            }
        }, 1000);
    });
}

async function loadUserNotifications() {
    // Simulate API call
    return new Promise((resolve) => {
        setTimeout(() => {
            resolve([
                { id: 1, message: 'New collaboration request', type: 'collaboration' },
                { id: 2, message: 'Knowledge article updated', type: 'knowledge' },
                { id: 3, message: 'Meeting reminder', type: 'announcement' }
            ]);
        }, 500);
    });
}

async function checkForNewNotifications() {
    // Simulate checking for new notifications
    return new Promise((resolve) => {
        setTimeout(() => {
            const hasNew = Math.random() > 0.8; // 20% chance of new notifications
            resolve(hasNew ? [{ id: Date.now(), message: 'New activity in your network' }] : []);
        }, 500);
    });
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-count');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Handle URL parameters
function handleUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    const error = urlParams.get('error');
    if (error) {
        showNotification(decodeURIComponent(error), 'error');
    }
    
    const success = urlParams.get('success');
    if (success) {
        showNotification(decodeURIComponent(success), 'success');
    }
    
    const section = window.location.hash.substring(1);
    if (section) {
        setTimeout(() => navigateToSection(section), 500);
    }
}

// Logout function
async function logout() {
    try {
        const response = await fetch('/auth/auth/logout.php', { method: 'POST' });
        
        if (response.ok) {
            currentUser = null;
            updateAuthUI(false);
            showNotification('Logged out successfully', 'success');
            
            // Redirect to home
            setTimeout(() => {
                window.location.href = '/auth/home/index.html';
            }, 1000);
        } else {
            throw new Error('Logout failed');
        }
    } catch (error) {
        console.error('Logout error:', error);
        showNotification('Logout failed. Please try again.', 'error');
    }
}

// Activity interaction functions
function toggleLike(activityId) {
    if (!currentUser) {
        showNotification('Please login to like activities', 'warning');
        return;
    }
    
    // Find the activity and toggle like
    const activity = activityData.find(a => a.id === activityId);
    if (activity) {
        activity.likes += activity.liked ? -1 : 1;
        activity.liked = !activity.liked;
        
        // Update UI
        const likeBtn = document.querySelector(`[onclick="toggleLike(${activityId})"]`);
        if (likeBtn) {
            const icon = likeBtn.querySelector('i');
            const count = likeBtn.querySelector('span');
            
            if (activity.liked) {
                icon.className = 'fas fa-heart';
                likeBtn.style.color = '#ef4444';
            } else {
                icon.className = 'far fa-heart';
                likeBtn.style.color = '';
            }
            
            count.textContent = activity.likes;
        }
        
        trackUserAction('toggle_like', { activityId, liked: activity.liked });
    }
}

function showComments(activityId) {
    // Show comments modal for activity
    const activity = activityData.find(a => a.id === activityId);
    if (activity) {
        const modalHtml = `
            <div id="commentsModal" class="modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Comments - ${activity.title}</h3>
                        <button class="modal-close" onclick="closeModal('commentsModal')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="comments-list">
                            <p class="text-center">Comments feature coming soon!</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        showModal('commentsModal');
    }
}

function shareActivity(activityId) {
    const activity = activityData.find(a => a.id === activityId);
    if (activity) {
        if (navigator.share) {
            navigator.share({
                title: activity.title,
                text: activity.description,
                url: window.location.href
            });
        } else {
            // Fallback: copy to clipboard
            const shareUrl = `${window.location.origin}${window.location.pathname}?activity=${activityId}`;
            navigator.clipboard.writeText(shareUrl).then(() => {
                showNotification('Activity link copied to clipboard', 'success');
            });
        }
        
        trackUserAction('share_activity', { activityId });
    }
}

// Search by trending topic
function searchEcosystemByTopic(topic) {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = topic;
        performSearch();
    }
}

// Add additional functionality for ecosystem features
function openNewPostModal() {
    showNotification('New post feature coming soon!', 'info');
}

function openShareResourceModal() {
    showNotification('Share resource feature coming soon!', 'info');
}

function openCreateEventModal() {
    showNotification('Create event feature coming soon!', 'info');
}

function startInteractiveDemo() {
    showNotification('Interactive demo starting...', 'info');
    closeModal('demoModal');
}

function showSearchSuggestions() {
    // Implement search suggestions
    console.log('Showing search suggestions...');
}

function displaySearchResults(results) {
    // Display search results in activity feed
    displayActivityFeed(results);
}

async function loadUserActivity