class EcosystemChatApp {
    constructor() {
        this.currentRoomId = null;
        this.lastMessageId = 0;
        this.currentUserId = document.getElementById('currentUserId').value;
        this.messagePollingInterval = null;
        this.onlineUsersInterval = null;
        this.isTyping = false;
        this.typingTimeout = null;
        this.replyToMessage = null;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.startOnlineUsersPolling();
        this.loadUserStatus();
        
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
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
                
                // Typing indicator
                this.handleTyping();
            });
            
            // Handle Shift+Enter for new lines
            messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.shiftKey) {
                    // Allow default behavior for new line
                    return;
                }
            });
        }
        
        // File input change
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', this.handleFileSelect.bind(this));
        }
        
        // Auto-scroll to bottom when new messages arrive
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            // Use MutationObserver instead of deprecated DOMNodeInserted
            const observer = new MutationObserver(() => {
                this.scrollToBottom();
            });
            observer.observe(chatMessages, { childList: true });
        }
        
        // Handle window focus/blur for activity tracking
        window.addEventListener('focus', () => {
            this.updateUserStatus('online');
        });
        
        window.addEventListener('blur', () => {
            this.updateUserStatus('away');
        });
    }
    
    joinRoom(roomId) {
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
                document.getElementById('currentRoomId').value = roomId;
                
                // Show message input
                document.getElementById('messageInputContainer').style.display = 'block';
                
                // Clear messages and load new ones
                this.clearMessages();
                this.loadMessages();
                this.startMessagePolling();
                
                // Update room header
                this.updateRoomHeader(roomElement);
            } else {
                this.showNotification(data.error || 'Failed to join room', 'error');
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
        
        const requestBody = `action=send_message&room_id=${this.currentRoomId}&message=${encodeURIComponent(message)}`;
        const replyBody = this.replyToMessage ? `&reply_to=${this.replyToMessage.id}` : '';
        
        fetch('chatroom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: requestBody + replyBody
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                this.clearReply();
                this.loadMessages(); // Refresh messages
            } else {
                this.showNotification(data.error || 'Failed to send message', 'error');
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            this.showNotification('Failed to send message', 'error');
        });
    }
    
    loadMessages() {
        if (!this.currentRoomId) return;
        
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
        });
    }
    
    displayMessages(messages) {
        const chatMessages = document.getElementById('chatMessages');
        
        messages.forEach(message => {
            // Check if message already exists to prevent duplication
            const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
            if (!existingMessage) {
                const messageElement = this.createMessageElement(message);
                chatMessages.appendChild(messageElement);
            }
        });
        
        this.scrollToBottom();
    }
    
    createMessageElement(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.user_id == this.currentUserId ? 'own' : ''} new`;
        messageDiv.setAttribute('data-message-id', message.id);
        
        // Avatar with role color
        const avatar = document.createElement('img');
        avatar.className = 'message-avatar';
        avatar.src = message.avatar ? `../../uploads/${message.avatar}` : '../../uploads/default-avatar.svg';
        avatar.alt = message.username;
        if (message.role_color) {
            avatar.style.border = `2px solid ${message.role_color}`;
        }
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        const headerDiv = document.createElement('div');
        headerDiv.className = 'message-header';
        
        const senderSpan = document.createElement('span');
        senderSpan.className = 'message-sender';
        senderSpan.textContent = message.fullname;
        if (message.role_color) {
            senderSpan.style.color = message.role_color;
        }
        
        // Role badge
        if (message.user_role) {
            const roleBadge = document.createElement('span');
            roleBadge.className = 'role-badge';
            roleBadge.textContent = message.user_role;
            roleBadge.style.backgroundColor = message.role_color || '#6b7280';
            senderSpan.appendChild(roleBadge);
        }
        
        const timeSpan = document.createElement('span');
        timeSpan.className = 'message-time';
        timeSpan.textContent = this.formatTime(message.created_at);
        
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'message-actions';
        
        // Reply button
        const replyBtn = document.createElement('button');
        replyBtn.className = 'action-btn';
        replyBtn.innerHTML = '<i class="fas fa-reply"></i>';
        replyBtn.title = 'Reply';
        replyBtn.onclick = () => this.setReplyTo(message);
        
        // React button
        const reactBtn = document.createElement('button');
        reactBtn.className = 'action-btn';
        reactBtn.innerHTML = '<i class="fas fa-smile"></i>';
        reactBtn.title = 'React';
        reactBtn.onclick = () => this.showReactionPicker(message.id);
        
        actionsDiv.appendChild(replyBtn);
        actionsDiv.appendChild(reactBtn);
        
        headerDiv.appendChild(senderSpan);
        headerDiv.appendChild(timeSpan);
        headerDiv.appendChild(actionsDiv);
        
        // Reply indicator
        if (message.reply_message) {
            const replyDiv = document.createElement('div');
            replyDiv.className = 'message-reply';
            replyDiv.innerHTML = `
                <i class="fas fa-reply"></i>
                <span class="reply-author">${message.reply_user_name}</span>
                <span class="reply-text">${this.truncateText(message.reply_message, 50)}</span>
            `;
            contentDiv.appendChild(replyDiv);
        }
        
        if (message.message_type === 'file' || message.message_type === 'image') {
            const fileDiv = this.createFileElement(message);
            contentDiv.appendChild(headerDiv);
            contentDiv.appendChild(fileDiv);
            
            // Also show the message text if it exists
            if (message.message && message.message.trim()) {
                const textDiv = document.createElement('div');
                textDiv.className = 'message-text';
                textDiv.innerHTML = this.formatMessageText(message.message);
                contentDiv.appendChild(textDiv);
            }
        } else {
            const textDiv = document.createElement('div');
            textDiv.className = 'message-text';
            textDiv.innerHTML = this.formatMessageText(message.message);
            
            contentDiv.appendChild(headerDiv);
            contentDiv.appendChild(textDiv);
        }
        
        // Reactions
        if (message.reactions && message.reactions.length > 0) {
            const reactionsDiv = this.createReactionsElement(message.reactions, message.id);
            contentDiv.appendChild(reactionsDiv);
        }
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(contentDiv);
        
        return messageDiv;
    }
    
    createFileElement(message) {
        const fileDiv = document.createElement('div');
        fileDiv.className = 'message-file';
        
        // Handle image files with preview
        if (message.message_type === 'image' && message.file_type && message.file_type.startsWith('image/')) {
            const imagePreview = document.createElement('div');
            imagePreview.className = 'message-image-container';
            
            const img = document.createElement('img');
            img.className = 'message-image';
            img.src = message.file_path || '#';
            img.alt = message.file_name || 'Image';
            img.onclick = () => this.showImageModal(img.src, message.file_name);
            
            imagePreview.appendChild(img);
            fileDiv.appendChild(imagePreview);
            
            if (message.file_name) {
                const caption = document.createElement('div');
                caption.className = 'image-caption';
                caption.textContent = message.file_name;
                fileDiv.appendChild(caption);
            }
        } else {
            // Regular file display
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info';
            
            const fileIcon = document.createElement('i');
            fileIcon.className = this.getFileIcon(message.file_type);
            
            const fileDetails = document.createElement('div');
            fileDetails.className = 'file-details';
            
            const fileName = document.createElement('span');
            fileName.className = 'file-name';
            fileName.textContent = message.file_name || 'Unknown file';
            
            const fileSize = document.createElement('span');
            fileSize.className = 'file-size';
            fileSize.textContent = this.formatFileSize(message.file_size || 0);
            
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
        }
        
        return fileDiv;
    }
    
    createReactionsElement(reactions, messageId) {
        const reactionsDiv = document.createElement('div');
        reactionsDiv.className = 'message-reactions';
        
        reactions.forEach(reaction => {
            const reactionBtn = document.createElement('button');
            reactionBtn.className = 'reaction-btn';
            reactionBtn.innerHTML = `${reaction.reaction} ${reaction.count}`;
            reactionBtn.onclick = () => this.toggleReaction(messageId, reaction.reaction);
            reactionsDiv.appendChild(reactionBtn);
        });
        
        return reactionsDiv;
    }
    
    setReplyTo(message) {
        this.replyToMessage = message;
        
        // Show reply indicator
        const messageInput = document.getElementById('messageInput');
        const container = messageInput.parentElement;
        
        // Remove existing reply indicator
        const existingReply = container.querySelector('.reply-indicator');
        if (existingReply) {
            existingReply.remove();
        }
        
        // Create new reply indicator
        const replyIndicator = document.createElement('div');
        replyIndicator.className = 'reply-indicator';
        replyIndicator.innerHTML = `
            <i class="fas fa-reply"></i>
            <span>Replying to ${message.fullname}</span>
            <button onclick="window.chatApp.clearReply()" class="clear-reply">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.insertBefore(replyIndicator, messageInput);
        messageInput.focus();
    }
    
    clearReply() {
        this.replyToMessage = null;
        const replyIndicator = document.querySelector('.reply-indicator');
        if (replyIndicator) {
            replyIndicator.remove();
        }
    }
    
    showReactionPicker(messageId) {
        const reactions = ['👍', '❤️', '😂', '😮', '😢', '😡'];
        
        // Create reaction picker
        const picker = document.createElement('div');
        picker.className = 'reaction-picker';
        picker.innerHTML = reactions.map(reaction => 
            `<button onclick="window.chatApp.toggleReaction(${messageId}, '${reaction}')">${reaction}</button>`
        ).join('');
        
        // Position picker
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        messageElement.appendChild(picker);
        
        // Remove picker after 3 seconds
        setTimeout(() => {
            if (picker.parentElement) {
                picker.remove();
            }
        }, 3000);
    }
    
    toggleReaction(messageId, reaction) {
        fetch('chatroom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=react_to_message&message_id=${messageId}&reaction=${encodeURIComponent(reaction)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh messages to show updated reactions
                this.loadMessages();
            }
        })
        .catch(error => {
            console.error('Error toggling reaction:', error);
        });
    }
    
    updateUserStatus(status) {
        fetch('chatroom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=update_status&status=${status}`
        })
        .catch(error => {
            console.error('Error updating status:', error);
        });
    }
    
    loadUserStatus() {
        // Set initial status as online
        this.updateUserStatus('online');
    }
    
    clearMessages() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML = '';
    }
    
    startMessagePolling() {
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
        }
        
        this.messagePollingInterval = setInterval(() => {
            this.loadMessages();
        }, 2000); // Poll every 2 seconds
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
            avatar.src = user.avatar ? `../../uploads/${user.avatar}` : '../../uploads/default-avatar.svg';
            avatar.alt = user.username;
            if (user.role_color) {
                avatar.style.border = `2px solid ${user.role_color}`;
            }
            
            const userInfo = document.createElement('div');
            userInfo.className = 'online-user-info';
            
            const userName = document.createElement('span');
            userName.className = 'online-user-name';
            userName.textContent = user.fullname;
            
            const userRole = document.createElement('span');
            userRole.className = 'online-user-role';
            userRole.textContent = user.role || 'Member';
            if (user.role_color) {
                userRole.style.color = user.role_color;
            }
            
            const indicator = document.createElement('div');
            indicator.className = `online-indicator ${user.status || 'online'}`;
            
            userInfo.appendChild(userName);
            userInfo.appendChild(userRole);
            
            userDiv.appendChild(avatar);
            userDiv.appendChild(userInfo);
            userDiv.appendChild(indicator);
            
            onlineUsersList.appendChild(userDiv);
        });
    }
    
    updateRoomHeader(roomElement) {
        const roomName = roomElement.querySelector('.room-name').textContent;
        const chatMessages = document.getElementById('chatMessages');
        
        // Add room header if messages area is empty
        if (chatMessages.children.length === 0) {
            const headerDiv = document.createElement('div');
            headerDiv.className = 'room-header';
            headerDiv.innerHTML = `
                <h3>Welcome to ${roomName}</h3>
                <p>Start chatting with your ecosystem members</p>
            `;
            chatMessages.appendChild(headerDiv);
        }
    }
    
    scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
        
        return date.toLocaleDateString();
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    formatMessageText(text) {
        // Convert URLs to links
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        text = text.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener">$1</a>');
        
        // Convert line breaks to <br>
        text = text.replace(/\n/g, '<br>');
        
        return text;
    }
    
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    getFileIcon(fileType) {
        if (!fileType) return 'fas fa-file';
        
        if (fileType.startsWith('image/')) return 'fas fa-file-image';
        if (fileType.startsWith('video/')) return 'fas fa-file-video';
        if (fileType.startsWith('audio/')) return 'fas fa-file-audio';
        if (fileType.includes('pdf')) return 'fas fa-file-pdf';
        if (fileType.includes('word')) return 'fas fa-file-word';
        if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'fas fa-file-excel';
        if (fileType.includes('powerpoint') || fileType.includes('presentation')) return 'fas fa-file-powerpoint';
        if (fileType.includes('zip') || fileType.includes('rar')) return 'fas fa-file-archive';
        
        return 'fas fa-file';
    }
    
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                this.showNotification('File size must be less than 10MB', 'error');
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
    
    showImageModal(imageSrc, fileName) {
        // Create modal overlay
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        };
        
        const modalContent = document.createElement('div');
        modalContent.className = 'image-modal-content';
        
        const img = document.createElement('img');
        img.src = imageSrc;
        img.alt = fileName;
        
        const caption = document.createElement('div');
        caption.className = 'image-modal-caption';
        caption.textContent = fileName;
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'image-modal-close';
        closeBtn.innerHTML = '×';
        closeBtn.onclick = () => modal.remove();
        
        modalContent.appendChild(img);
        modalContent.appendChild(caption);
        modalContent.appendChild(closeBtn);
        modal.appendChild(modalContent);
        
        document.body.appendChild(modal);
        
        // Close on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#17a2b8'};
            color: white;
            border-radius: 8px;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease;
            max-width: 400px;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
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
        window.chatApp.showNotification('Please select a file', 'error');
        return;
    }
    
    if (!window.chatApp.currentRoomId) {
        window.chatApp.showNotification('Please join a room first', 'error');
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
            window.chatApp.loadMessages();
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
        const roomDescription = prompt('Enter room description (optional):') || '';
        const roomType = confirm('Make this room private?') ? 'private' : 'public';
        
        fetch('chatroom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=create_room&name=${encodeURIComponent(roomName.trim())}&description=${encodeURIComponent(roomDescription)}&type=${roomType}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.chatApp.showNotification('Room created successfully', 'success');
                setTimeout(() => location.reload(), 1000); // Refresh to show new room
            } else {
                window.chatApp.showNotification(data.error || 'Failed to create room', 'error');
            }
        })
        .catch(error => {
            console.error('Error creating room:', error);
            window.chatApp.showNotification('Failed to create room', 'error');
        });
    }
}

// Initialize chat app when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.chatApp = new EcosystemChatApp();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.chatApp) {
        window.chatApp.updateUserStatus('offline');
        
        if (window.chatApp.messagePollingInterval) {
            clearInterval(window.chatApp.messagePollingInterval);
        }
        if (window.chatApp.onlineUsersInterval) {
            clearInterval(window.chatApp.onlineUsersInterval);
        }
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .role-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
        color: white;
        margin-left: 8px;
        font-weight: 500;
    }
    
    .message-reply {
        background: rgba(0,0,0,0.05);
        border-left: 3px solid #059669;
        padding: 8px 12px;
        margin: 4px 0;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    
    .reply-indicator {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px 8px 0 0;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .clear-reply {
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        margin-left: auto;
    }
    
    .message-reactions {
        display: flex;
        gap: 4px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    
    .reaction-btn {
        background: rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 12px;
        padding: 2px 8px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .reaction-btn:hover {
        background: rgba(5, 150, 105, 0.1);
        border-color: #059669;
    }
    
    .reaction-picker {
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        display: flex;
        gap: 4px;
    }
    
    .reaction-picker button {
        background: none;
        border: none;
        font-size: 1.2rem;
        padding: 4px;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s ease;
    }
    
    .reaction-picker button:hover {
        background: rgba(0,0,0,0.1);
    }
    
    .message-actions {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .message:hover .message-actions {
        opacity: 1;
    }
    
    .action-btn {
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .action-btn:hover {
        background: rgba(0,0,0,0.1);
        color: #059669;
    }
    
    .online-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-left: auto;
    }
    
    .online-indicator.online { background: #28a745; }
    .online-indicator.away { background: #ffc107; }
    .online-indicator.busy { background: #dc3545; }
    .online-indicator.invisible { background: #6c757d; }
    
    .room-header {
        text-align: center;
        padding: 20px;
        color: #6c757d;
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }
    
    .notification-close:hover {
        opacity: 1;
    }
`;
document.head.appendChild(style);