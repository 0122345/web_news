// Fixed Chat Application JavaScript - No Message Duplication
class ChatApp {
    constructor() {
        this.currentRoomId = null;
        this.lastMessageId = 0;
        this.currentUserId = document.getElementById('currentUserId').value;
        this.messagePollingInterval = null;
        this.onlineUsersInterval = null;
        this.isTyping = false;
        this.typingTimeout = null;
        this.displayedMessages = new Set(); // Track displayed message IDs
        this.isLoadingMessages = false; // Prevent concurrent message loading
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.startOnlineUsersPolling();
        
        // Auto-join first room if available
        const firstRoom = document.querySelector('.room-item');
        if (firstRoom) {
            const roomId = firstRoom.getAttribute('data-room-id');
            this.joinRoom(roomId);
        }
    }
    
    setupEventListeners() {
        // Message input enter key
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendMessage();
                }
                
                // Typing indicator (future enhancement)
                this.handleTyping();
            });
        }
        
        // File input change
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', this.handleFileSelect);
        }
    }
    
    joinRoom(roomId) {
        // Stop current polling
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
        }
        
        // Update UI
        document.querySelectorAll('.room-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const roomElement = document.querySelector(`[data-room-id="${roomId}"]`);
        if (roomElement) {
            roomElement.classList.add('active');
        }
        
        // Join room via AJAX
        fetch('chatroom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=join_room&room_id=${roomId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.currentRoomId = roomId;
                this.lastMessageId = 0;
                this.displayedMessages.clear(); // Clear tracked messages
                document.getElementById('currentRoomId').value = roomId;
                
                // Show message input
                document.getElementById('messageInputContainer').style.display = 'block';
                
                // Clear messages and load new ones
                this.clearMessages();
                this.loadMessages();
                this.startMessagePolling();
            }
        })
        .catch(error => {
            console.error('Error joining room:', error);
            this.showNotification('Failed to join room', 'error');
        });
    }
    
    sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message || !this.currentRoomId) return;
        
        // Disable input while sending
        messageInput.disabled = true;
        
        fetch('chatroom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=send_message&room_id=${this.currentRoomId}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                // Don't call loadMessages() immediately - let polling handle it
                // This prevents the duplicate message issue
            } else {
                this.showNotification('Failed to send message', 'error');
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            this.showNotification('Failed to send message', 'error');
        })
        .finally(() => {
            messageInput.disabled = false;
            messageInput.focus();
        });
    }
    
    loadMessages() {
        if (!this.currentRoomId || this.isLoadingMessages) return;
        
        this.isLoadingMessages = true;
        
        fetch('chatroom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=get_messages&room_id=${this.currentRoomId}&last_id=${this.lastMessageId}`
        })
        .then(response => response.json())
        .then(messages => {
            if (messages.length > 0) {
                this.displayMessages(messages);
                this.lastMessageId = Math.max(...messages.map(m => m.id));
            }
        })
        .catch(error => {
            console.error('Error loading messages:', error);
        })
        .finally(() => {
            this.isLoadingMessages = false;
        });
    }
    
    displayMessages(messages) {
        const chatMessages = document.getElementById('chatMessages');
        let newMessagesAdded = false;
        
        messages.forEach(message => {
            // Check if message is already displayed
            if (!this.displayedMessages.has(message.id)) {
                const messageElement = this.createMessageElement(message);
                chatMessages.appendChild(messageElement);
                this.displayedMessages.add(message.id);
                newMessagesAdded = true;
            }
        });
        
        // Only scroll if new messages were added
        if (newMessagesAdded) {
            this.scrollToBottom();
        }
    }
    
    createMessageElement(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.user_id == this.currentUserId ? 'own' : ''} new`;
        messageDiv.setAttribute('data-message-id', message.id); // Add unique identifier
        
        const avatar = document.createElement('img');
        avatar.className = 'message-avatar';
        avatar.src = message.profile_picture ? `../${message.profile_picture}` : '../uploads/default-avatar.png';
        avatar.alt = message.username;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        const headerDiv = document.createElement('div');
        headerDiv.className = 'message-header';
        
        const senderSpan = document.createElement('span');
        senderSpan.className = 'message-sender';
        senderSpan.textContent = message.fullname;
        
        const timeSpan = document.createElement('span');
        timeSpan.className = 'message-time';
        timeSpan.textContent = this.formatTime(message.created_at);
        
        headerDiv.appendChild(senderSpan);
        headerDiv.appendChild(timeSpan);
        
        if (message.message_type === 'file' || message.message_type === 'image') {
            const fileDiv = this.createFileElement(message);
            contentDiv.appendChild(headerDiv);
            contentDiv.appendChild(fileDiv);
        } else {
            const textDiv = document.createElement('div');
            textDiv.className = 'message-text';
            textDiv.textContent = message.message;
            
            contentDiv.appendChild(headerDiv);
            contentDiv.appendChild(textDiv);
        }
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(contentDiv);
        
        return messageDiv;
    }
    
    createFileElement(message) {
        const fileDiv = document.createElement('div');
        fileDiv.className = 'message-file';
        
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        
        const fileIcon = document.createElement('i');
        fileIcon.className = 'fas fa-file file-icon';
        
        const fileDetails = document.createElement('div');
        fileDetails.className = 'file-details';
        
        const fileName = document.createElement('span');
        fileName.className = 'file-name';
        fileName.textContent = message.file_name;
        
        const fileSize = document.createElement('span');
        fileSize.className = 'file-size';
        fileSize.textContent = this.formatFileSize(message.file_size);
        
        const downloadBtn = document.createElement('button');
        downloadBtn.className = 'file-download';
        downloadBtn.innerHTML = '<i class="fas fa-download"></i>';
        downloadBtn.onclick = () => this.downloadFile(message.file_path, message.file_name);
        
        fileDetails.appendChild(fileName);
        fileDetails.appendChild(fileSize);
        
        fileInfo.appendChild(fileIcon);
        fileInfo.appendChild(fileDetails);
        fileInfo.appendChild(downloadBtn);
        
        fileDiv.appendChild(fileInfo);
        
        return fileDiv;
    }
    
    clearMessages() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML = '';
        this.displayedMessages.clear(); // Clear tracked messages
    }
    
    startMessagePolling() {
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
        }
        
        this.messagePollingInterval = setInterval(() => {
            this.loadMessages();
        }, 3000); // Poll every 3 seconds (increased from 2 to reduce server load)
    }
    
    startOnlineUsersPolling() {
        this.loadOnlineUsers();
        
        this.onlineUsersInterval = setInterval(() => {
            this.loadOnlineUsers();
        }, 30000); // Update every 30 seconds
    }
    
    loadOnlineUsers() {
        fetch('chatroom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=get_online_users'
        })
        .then(response => response.json())
        .then(users => {
            this.displayOnlineUsers(users);
        })
        .catch(error => {
            console.error('Error loading online users:', error);
        });
    }
    
    displayOnlineUsers(users) {
        const onlineUsersList = document.getElementById('onlineUsersList');
        onlineUsersList.innerHTML = '';
        
        users.forEach(user => {
            const userDiv = document.createElement('div');
            userDiv.className = 'online-user';
            
            const avatar = document.createElement('img');
            avatar.src = user.profile_picture ? `../${user.profile_picture}` : '../uploads/default-avatar.png';
            avatar.alt = user.username;
            
            const userInfo = document.createElement('div');
            userInfo.className = 'online-user-info';
            
            const userName = document.createElement('span');
            userName.className = 'online-user-name';
            userName.textContent = user.fullname;
            
            const userUsername = document.createElement('span');
            userUsername.className = 'online-user-username';
            userUsername.textContent = `@${user.username}`;
            
            const indicator = document.createElement('div');
            indicator.className = 'online-indicator';
            
            userInfo.appendChild(userName);
            userInfo.appendChild(userUsername);
            
            userDiv.appendChild(avatar);
            userDiv.appendChild(userInfo);
            userDiv.appendChild(indicator);
            
            onlineUsersList.appendChild(userDiv);
        });
    }
    
    scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                event.target.value = '';
                return;
            }
            
            // Show file info
            console.log('File selected:', file.name, file.size);
        }
    }
    
    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            // Send typing indicator (future enhancement)
        }
        
        clearTimeout(this.typingTimeout);
        this.typingTimeout = setTimeout(() => {
            this.isTyping = false;
            // Stop typing indicator (future enhancement)
        }, 1000);
    }
    
    downloadFile(filePath, fileName) {
        const link = document.createElement('a');
        link.href = filePath;
        link.download = fileName;
        link.click();
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'error' ? '#dc3545' : '#28a745'};
            color: white;
            border-radius: 5px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Global functions for HTML onclick events
function joinRoom(roomId) {
    if (window.chatApp) {
        window.chatApp.joinRoom(roomId);
    }
}

function sendMessage() {
    if (window.chatApp) {
        window.chatApp.sendMessage();
    }
}

function toggleFileUpload() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const isVisible = fileUploadArea.style.display !== 'none';
    fileUploadArea.style.display = isVisible ? 'none' : 'block';
    
    if (!isVisible) {
        document.getElementById('fileInput').focus();
    }
}

function uploadFile() {
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a file');
        return;
    }
    
    if (!window.chatApp.currentRoomId) {
        alert('Please join a room first');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload_file');
    formData.append('room_id', window.chatApp.currentRoomId);
    
    // Show upload progress
    const uploadBtn = document.querySelector('.btn-upload');
    const originalText = uploadBtn.innerHTML;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    uploadBtn.disabled = true;
    
    fetch('upload_file.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fileInput.value = '';
            toggleFileUpload();
            // Don't call loadMessages() immediately - let polling handle it
            window.chatApp.showNotification('File uploaded successfully', 'success');
        } else {
            window.chatApp.showNotification(data.message || 'Upload failed', 'error');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        window.chatApp.showNotification('Upload failed', 'error');
    })
    .finally(() => {
        uploadBtn.innerHTML = originalText;
        uploadBtn.disabled = false;
    });
}

function toggleOnlineUsers() {
    const panel = document.getElementById('onlineUsersPanel');
    const isVisible = panel.style.display !== 'none';
    panel.style.display = isVisible ? 'none' : 'block';
    
    if (!isVisible && window.chatApp) {
        window.chatApp.loadOnlineUsers();
    }
}

function createRoom() {
    const roomName = prompt('Enter room name:');
    if (roomName && roomName.trim()) {
        fetch('create_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `name=${encodeURIComponent(roomName.trim())}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh to show new room
            } else {
                alert(data.message || 'Failed to create room');
            }
        })
        .catch(error => {
            console.error('Error creating room:', error);
            alert('Failed to create room');
        });
    }
}

// Initialize chat app when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.chatApp = new ChatApp();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.chatApp) {
        if (window.chatApp.messagePollingInterval) {
            clearInterval(window.chatApp.messagePollingInterval);
        }
        if (window.chatApp.onlineUsersInterval) {
            clearInterval(window.chatApp.onlineUsersInterval);
        }
    }
});