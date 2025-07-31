// Global variables
let currentUser = null;
let articles = [];
let currentCategory = 'latest';

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
    loadArticles();
    initializeEventListeners();
    
    // Check for URL parameters (error messages)
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    if (error) {
        showNotification(error, 'error');
    }
});

// Check if user is authenticated
function checkAuthStatus() {
    fetch('check_auth.php')
        .then(response => response.json())
        .then(data => {
            if (data.authenticated) {
                currentUser = data.user;
                updateAuthUI(true);
            } else {
                updateAuthUI(false);
            }
        })
        .catch(error => {
            console.error('Auth check failed:', error);
            updateAuthUI(false);
        });
}

// Update authentication UI
function updateAuthUI(isAuthenticated) {
    const authButtons = document.getElementById('authButtons');
    const userMenu = document.getElementById('userMenu');
    const userName = document.getElementById('userName');
    
    if (isAuthenticated && currentUser) {
        authButtons.style.display = 'none';
        userMenu.style.display = 'block';
        userName.textContent = currentUser.fullname;
    } else {
        authButtons.style.display = 'flex';
        userMenu.style.display = 'none';
    }
}


// Load articles from API or database
function loadArticles(category = 'latest') {
    showLoading();
    
    // Try to load from News API first, fallback to local database
    loadFromNewsAPI(category)
        .then(articles => {
            if (articles && articles.length > 0) {
                displayArticles(articles);
            } else {
                return loadFromDatabase(category);
            }
        })
        .catch(error => {
            console.error('News API failed, loading from database:', error);
            return loadFromDatabase(category);
        })
        .then(articles => {
            if (articles) {
                displayArticles(articles);
            }
        })
        .finally(() => {
            hideLoading();
        });
}

// Load articles from News API
async function loadFromNewsAPI(category) {
    const API_KEY = 'YOUR_NEWS_API_KEY'; // Replace with your actual API key
    const baseUrl = 'https://newsapi.org/v2/';
    
    let endpoint = 'top-headlines';
    let params = new URLSearchParams({
        apiKey: API_KEY,
        country: 'us',
        pageSize: 20
    });
    
    if (category !== 'latest') {
        params.append('category', category);
    }
    
    try {
        const response = await fetch(`${baseUrl}${endpoint}?${params}`);
        const data = await response.json();
        
        if (data.status === 'ok') {
            return data.articles.map(article => ({
                id: Math.random().toString(36).substr(2, 9),
                title: article.title,
                content: article.content || article.description,
                excerpt: article.description,
                category: category,
                author: article.author || 'Unknown',
                image_url: article.urlToImage,
                source: article.source.name,
                published_at: article.publishedAt,
                likes_count: Math.floor(Math.random() * 100),
                comments_count: Math.floor(Math.random() * 50),
                shares_count: Math.floor(Math.random() * 25)
            }));
        }
    } catch (error) {
        console.error('News API error:', error);
        return null;
    }
}

// Load articles from local database
async function loadFromDatabase(category) {
    try {
        const response = await fetch(`get_articles.php?category=${category}`);
        const data = await response.json();
        return data.articles || [];
    } catch (error) {
        console.error('Database load error:', error);
        return [];
    }
}

// Display articles in the grid
function displayArticles(articlesData) {
    articles = articlesData;
    const container = document.getElementById('articlesContainer');
    
    if (!container) return;
    
    container.innerHTML = '';
    
    articles.forEach(article => {
        const articleElement = createArticleElement(article);
        container.appendChild(articleElement);
    });
}

// Create article HTML element
function createArticleElement(article) {
    const articleDiv = document.createElement('article');
    articleDiv.className = 'article-card';
    articleDiv.innerHTML = `
        <div class="article-image">
            <img src="${article.image_url || 'https://via.placeholder.com/400x200/667eea/ffffff?text=News'}" 
                 alt="${article.title}" 
                 onerror="this.src='https://via.placeholder.com/400x200/667eea/ffffff?text=News'">
            <div class="category-tag">${article.category}</div>
        </div>
        <div class="article-content">
            <h3>${article.title}</h3>
            <p class="article-excerpt">${article.excerpt || ''}</p>
            <div class="article-meta">
                <span class="author"><i class="fas fa-user"></i> ${article.author}</span>
                <span class="date"><i class="fas fa-calendar"></i> ${formatDate(article.published_at)}</span>
                <span class="read-time"><i class="fas fa-clock"></i> ${getReadTime(article.content)} min read</span>
            </div>
            <div class="article-actions">
                <button class="action-btn like-btn" onclick="toggleLike(this, '${article.id}')">
                    <i class="far fa-heart"></i> <span>${article.likes_count}</span>
                </button>
                <button class="action-btn comment-btn" onclick="showComments('${article.id}')">
                    <i class="far fa-comment"></i> <span>${article.comments_count}</span>
                </button>
                <button class="action-btn share-btn" onclick="shareArticle('${article.id}')">
                    <i class="fas fa-share"></i> Share
                </button>
            </div>
        </div>
    `;
    
    return articleDiv;
}

// Initialize event listeners
function initializeEventListeners() {
    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }
    
    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', handleNewsletterSubmit);
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        const modal = document.getElementById('commentModal');
        if (event.target === modal) {
            closeModal();
        }
    });
}

// Show section based on tab
function showSection(category) {
    currentCategory = category;
    
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Load articles for the category
    loadArticles(category);
}

// Toggle like functionality
function toggleLike(button, articleId) {
    if (!currentUser) {
        showNotification('Please login to like articles', 'warning');
        return;
    }
    
    const likeIcon = button.querySelector('i');
    const likeCount = button.querySelector('span');
    const isLiked = likeIcon.classList.contains('fas');
    
    // Optimistic UI update
    if (isLiked) {
        likeIcon.classList.remove('fas');
        likeIcon.classList.add('far');
        button.classList.remove('liked');
        likeCount.textContent = parseInt(likeCount.textContent) - 1;
    } else {
        likeIcon.classList.remove('far');
        likeIcon.classList.add('fas');
        button.classList.add('liked');
        likeCount.textContent = parseInt(likeCount.textContent) + 1;
    }
    
    // Send to server
    fetch('toggle_like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            article_id: articleId,
            action: isLiked ? 'unlike' : 'like'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Revert UI changes if server request failed
            toggleLike(button, articleId);
            showNotification('Failed to update like', 'error');
        }
    })
    .catch(error => {
        console.error('Like error:', error);
        // Revert UI changes
        toggleLike(button, articleId);
        showNotification('Failed to update like', 'error');
    });
}

// Show comments modal
function showComments(articleId) {
    const modal = document.getElementById('commentModal');
    const commentsContainer = document.getElementById('commentsContainer');
    
    modal.style.display = 'block';
    
    // Load comments
    loadComments(articleId);
    
    // Set up comment form
    const commentForm = document.getElementById('commentForm');
    commentForm.onsubmit = (e) => {
        e.preventDefault();
        submitComment(articleId);
    };
}

// Load comments for an article
function loadComments(articleId) {
    const container = document.getElementById('commentsContainer');
    container.innerHTML = '<div class="loading"></div>';
    
    fetch(`get_comments.php?article_id=${articleId}`)
        .then(response => response.json())
        .then(data => {
            container.innerHTML = '';
            
            if (data.comments && data.comments.length > 0) {
                data.comments.forEach(comment => {
                    const commentElement = createCommentElement(comment);
                    container.appendChild(commentElement);
                });
            } else {
                container.innerHTML = '<p class="text-center">No comments yet. Be the first to comment!</p>';
            }
        })
        .catch(error => {
            console.error('Load comments error:', error);
            container.innerHTML = '<p class="text-center">Failed to load comments</p>';
        });
}

// Create comment HTML element
function createCommentElement(comment) {
    const commentDiv = document.createElement('div');
    commentDiv.className = 'comment-item';
    commentDiv.innerHTML = `
        <div class="comment-header">
            <div class="comment-avatar">${comment.author.charAt(0).toUpperCase()}</div>
            <span class="comment-author">${comment.author}</span>
            <span class="comment-date">${formatDate(comment.created_at)}</span>
        </div>
        <div class="comment-content">${comment.content}</div>
    `;
    
    return commentDiv;
}

// Submit new comment
function submitComment(articleId) {
    if (!currentUser) {
        showNotification('Please login to comment', 'warning');
        return;
    }
    
    const form = document.getElementById('commentForm');
    const textarea = form.querySelector('textarea');
    const content = textarea.value.trim();
    
    if (!content) {
        showNotification('Please enter a comment', 'warning');
        return;
    }
    
    const submitButton = form.querySelector('button');
    submitButton.disabled = true;
    submitButton.innerHTML = '<div class="loading"></div> Posting...';
    
    fetch('add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            article_id: articleId,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            textarea.value = '';
            loadComments(articleId);
            showNotification('Comment posted successfully', 'success');
        } else {
            showNotification(data.message || 'Failed to post comment', 'error');
        }
    })
    .catch(error => {
        console.error('Comment error:', error);
        showNotification('Failed to post comment', 'error');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Post Comment';
    });
}

// Share article functionality
function shareArticle(articleId) {
    const article = articles.find(a => a.id === articleId);
    if (!article) return;
    
    if (navigator.share) {
        navigator.share({
            title: article.title,
            text: article.excerpt,
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        const shareUrl = `${window.location.origin}${window.location.pathname}?article=${articleId}`;
        navigator.clipboard.writeText(shareUrl).then(() => {
            showNotification('Article link copied to clipboard', 'success');
        });
    }
    
    // Track share
    if (currentUser) {
        fetch('track_share.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                article_id: articleId,
                platform: 'web'
            })
        });
    }
}

// Close modal
function closeModal() {
    const modal = document.getElementById('commentModal');
    modal.style.display = 'none';
}

// Newsletter subscription
function handleNewsletterSubmit(e) {
    e.preventDefault();
    const email = e.target.querySelector('input[type="email"]').value;
    
    fetch('subscribe_newsletter.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Successfully subscribed to newsletter', 'success');
            e.target.reset();
        } else {
            showNotification(data.message || 'Subscription failed', 'error');
        }
    })
    .catch(error => {
        console.error('Newsletter error:', error);
        showNotification('Subscription failed', 'error');
    });
}

// Logout functionality
function logout() {
    fetch('logout.php', { method: 'POST' })
        .then(() => {
            currentUser = null;
            updateAuthUI(false);
            showNotification('Logged out successfully', 'success');
            // Redirect to home page
            window.location.href = 'index.html';
        })
        .catch(error => {
            console.error('Logout error:', error);
            showNotification('Logout failed', 'error');
        });
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays} days ago`;
    } else {
        return date.toLocaleDateString();
    }
}

function getReadTime(content) {
    if (!content) return 1;
    const wordsPerMinute = 200;
    const wordCount = content.split(' ').length;
    return Math.ceil(wordCount / wordsPerMinute);
}

function showLoading() {
    const container = document.getElementById('articlesContainer');
    if (container) {
        container.innerHTML = '<div class="text-center"><div class="loading"></div></div>';
    }
}

function hideLoading() {
    // Loading will be hidden when articles are displayed
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 3000;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 400px;
        animation: slideIn 0.3s ease;
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
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

function getNotificationColor(type) {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    return colors[type] || '#17a2b8';
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
}

function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const query = searchInput.value.trim();
    
    if (!query) {
        showNotification('Please enter a search term', 'warning');
        return;
    }
    
    showLoading();
    
    fetch(`search_articles.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayArticles(data.articles);
                showNotification(`Found ${data.articles.length} articles`, 'success');
            } else {
                showNotification('Search failed', 'error');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            showNotification('Search failed', 'error');
        })
        .finally(() => {
            hideLoading();
        });
}

// Weather widget functionality
function loadWeatherWidget() {
    const weatherWidget = document.querySelector('.weather-widget');
    if (!weatherWidget) return;
    
    // Get user's location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                fetchWeatherData(lat, lon);
            },
            error => {
                console.error('Geolocation error:', error);
                // Fallback to default location (New York)
                fetchWeatherData(40.7128, -74.0060);
            }
        );
    } else {
        // Fallback to default location
        fetchWeatherData(40.7128, -74.0060);
    }
}

function fetchWeatherData(lat, lon) {
    const API_KEY = 'YOUR_WEATHER_API_KEY'; // Replace with your OpenWeatherMap API key
    const url = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${API_KEY}&units=metric`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            updateWeatherWidget(data);
        })
        .catch(error => {
            console.error('Weather API error:', error);
            // Show default weather info
            updateWeatherWidget({
                main: { temp: 22 },
                weather: [{ main: 'Clear', icon: '01d' }],
                name: 'New York'
            });
        });
}

function updateWeatherWidget(data) {
    const weatherWidget = document.querySelector('.weather-widget .weather-info');
    if (!weatherWidget) return;
    
    const iconCode = data.weather[0].icon;
    const iconUrl = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;
    
    weatherWidget.innerHTML = `
        <img src="${iconUrl}" alt="${data.weather[0].main}" style="width: 50px; height: 50px;">
        <div class="temp">${Math.round(data.main.temp)}°C</div>
        <div class="location">${data.name}</div>
        <div class="condition">${data.weather[0].main}</div>
    `;
}

// Popular articles widget
function loadPopularArticles() {
    fetch('get_popular_articles.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPopularArticles(data.articles);
            }
        })
        .catch(error => {
            console.error('Popular articles error:', error);
        });
}

function displayPopularArticles(articles) {
    const container = document.querySelector('.popular-articles');
    if (!container) return;
    
    container.innerHTML = '';
    
    articles.slice(0, 5).forEach(article => {
        const item = document.createElement('div');
        item.className = 'popular-item';
        item.innerHTML = `
            <img src="${article.image_url || 'https://via.placeholder.com/80x60/667eea/ffffff?text=News'}" 
                 alt="${article.title}">
            <div class="popular-content">
                <h4>${article.title.substring(0, 60)}...</h4>
                <div class="popular-date">${formatDate(article.published_at)}</div>
            </div>
        `;
        
        item.addEventListener('click', () => {
            // Scroll to article or open in modal
            const articleElement = document.querySelector(`[data-article-id="${article.id}"]`);
            if (articleElement) {
                articleElement.scrollIntoView({ behavior: 'smooth' });
            }
        });
        
        container.appendChild(item);
    });
}

// Infinite scroll functionality
function initializeInfiniteScroll() {
    let isLoading = false;
    let currentPage = 1;
    
    window.addEventListener('scroll', () => {
        if (isLoading) return;
        
        const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
        
        if (scrollTop + clientHeight >= scrollHeight - 5) {
            isLoading = true;
            loadMoreArticles();
        }
    });
    
    function loadMoreArticles() {
        currentPage++;
        
        fetch(`get_articles.php?category=${currentCategory}&page=${currentPage}`)
            .then(response => response.json())
            .then(data => {
                if (data.articles && data.articles.length > 0) {
                    const container = document.getElementById('articlesContainer');
                    data.articles.forEach(article => {
                        const articleElement = createArticleElement(article);
                        container.appendChild(articleElement);
                    });
                    articles.push(...data.articles);
                }
            })
            .catch(error => {
                console.error('Load more error:', error);
            })
            .finally(() => {
                isLoading = false;
            });
    }
}

// Dark mode toggle
function initializeDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (!darkModeToggle) return;
    
    // Check for saved dark mode preference
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
        darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }
    
    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        
        localStorage.setItem('darkMode', isDark);
        darkModeToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
    loadArticles();
    initializeEventListeners();
    initializeSearch();
    loadWeatherWidget();
    loadPopularArticles();
    initializeInfiniteScroll();
    initializeDarkMode();
    
    // Check for URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    if (error) {
        showNotification(decodeURIComponent(error), 'error');
    }
    
    const success = urlParams.get('success');
    if (success) {
        showNotification(decodeURIComponent(success), 'success');
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification button {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        padding: 0;
        margin-left: 10px;
    }
    
    .dark-mode {
        background-color: #1a1a1a;
        color: #ffffff;
    }
    
    .dark-mode .article-card,
    .dark-mode .widget,
    .dark-mode .auth-card {
        background-color: #2d2d2d;
        color: #ffffff;
    }
    
    .dark-mode .main-header {
        background-color: #2d2d2d;
        border-bottom: 1px solid #444;
    }
    
    .dark-mode .form-group input {
        background-color: #3d3d3d;
        border-color: #555;
        color: #ffffff;
    }
    
    .dark-mode .form-group input:focus {
        border-color: #667eea;
    }
`;
document.head.appendChild(style);
