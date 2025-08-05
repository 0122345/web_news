// Global variables
let currentUser = null;
let currentDate = new Date();
let boards = [];
let todos = [];
let events = [];

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadUserData();
    loadBoards();
    loadTodos();
    loadEvents();
    generateCalendar();
});

// Initialize application
function initializeApp() {
    // Simulate user authentication - replace with real PHP session data
    currentUser = {
        id: 1,
        username: 'eco_admin',
        fullname: 'Ecosystem Guardian',
        role: 'super_admin',
        avatar: 'EG'
    };

    document.getElementById('userName').textContent = currentUser.fullname;
    document.getElementById('userAvatar').innerHTML = currentUser.avatar;
}

// Setup event listeners
function setupEventListeners() {
    // Tab navigation
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            switchTab(tabId);
        });
    });

    // Modal close on backdrop click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Close any open modal
            document.querySelectorAll('.modal.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
}

// Tab switching
function switchTab(tabId) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(tabId).classList.add('active');
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

// Load user data - replace with PHP/AJAX call
function loadUserData() {
    // This would fetch from your PHP backend
    console.log('Loading user data...');
    // Example AJAX call:
    /*
    fetch('api/user.php')
        .then(response => response.json())
        .then(data => {
            currentUser = data;
            updateUserDisplay();
        })
        .catch(error => console.error('Error loading user data:', error));
    */
}

// Board functions
function loadBoards() {
    // Sample data - replace with PHP/AJAX call to your database
    boards = [
        {
            id: 1,
            title: 'Ecosystem Research Project',
            description: 'Collaborative research on local ecosystem conservation',
            category: 'research',
            created_by: 'Ecosystem Guardian',
            created_at: '2024-12-01',
            progress: 75,
            members: 5,
            tasks: 12
        },
        {
            id: 2,
            title: 'Community Garden Planning',
            description: 'Planning and design for the new community garden',
            category: 'planning',
            created_by: 'Project Coordinator',
            created_at: '2024-12-03',
            progress: 45,
            members: 8,
            tasks: 18
        },
        {
            id: 3,
            title: 'Sustainability Workshop Ideas',
            description: 'Brainstorming session for upcoming sustainability workshops',
            category: 'brainstorm',
            created_by: 'Community Steward',
            created_at: '2024-12-05',
            progress: 30,
            members: 12,
            tasks: 6
        }
    ];

    renderBoards();
}

function renderBoards() {
    const boardsGrid = document.getElementById('boardsGrid');
    boardsGrid.innerHTML = '';

    boards.forEach(board => {
        const boardCard = document.createElement('div');
        boardCard.className = 'card';
        boardCard.innerHTML = `
            <div class="card-header">
                <div>
                    <h3 class="card-title">${board.title}</h3>
                    <div class="card-meta">
                        <span><i class="fas fa-user"></i> ${board.created_by}</span>
                        <span><i class="fas fa-calendar"></i> ${new Date(board.created_at).toLocaleDateString()}</span>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="btn-icon" onclick="editBoard(${board.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" onclick="deleteBoard(${board.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <p style="color: var(--medium-gray); margin-bottom: 1rem;">${board.description}</p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${board.progress}%"></div>
            </div>
            <div class="card-meta">
                <span><i class="fas fa-users"></i> ${board.members} members</span>
                <span><i class="fas fa-tasks"></i> ${board.tasks} tasks</span>
                <span><i class="fas fa-chart-line"></i> ${board.progress}% complete</span>
            </div>
        `;
        boardsGrid.appendChild(boardCard);
    });
}

function createBoard(event) {
    event.preventDefault();
    
    const title = document.getElementById('boardTitle').value;
    const description = document.getElementById('boardDescription').value;
    const category = document.getElementById('boardCategory').value;

    // Client-side validation
    if (!title.trim()) {
        alert('Please enter a board title');
        return;
    }

    const newBoard = {}}