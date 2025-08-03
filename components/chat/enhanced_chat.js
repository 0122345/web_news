// Enhanced Chat Application JavaScript with Image Preview
class EnhancedChatApp extends ChatApp {
    constructor() {
        super();
        this.setupImagePreview();
    }
    
    setupImagePreview() {
        // Override the createMessageElement to handle images
        this.originalCreateMessageElement = this.createMessageElement;
        this.createMessageElement = this.createEnhancedMessageElement;
    }
    
    createEnhancedMessageElement(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.user_id == this.currentUserId ? 'own' : ''} new`;
        
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
        
        if (message.message_type === 'image') {
            const imageDiv = this.createImageElement(message);
            contentDiv.appendChild(headerDiv);
            contentDiv.appendChild(imageDiv);
        } else if (message.message_type === 'file') {
            const fileDiv = this.createFileElement(message);
            contentDiv.appendChild(headerDiv);
            contentDiv.appendChild(fileDiv);
        } else {
            const textDiv = document.createElement('div');
            textDiv.className = 'message-text';
            textDiv.innerHTML = this.formatMessageText(message.message);
            
            contentDiv.appendChild(headerDiv);
            contentDiv.appendChild(textDiv);
        }
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(contentDiv);
        
        return messageDiv;
    }
    
    createImageElement(message) {
        const imageContainer = document.createElement('div');
        imageContainer.className = 'message-image-container';
        
        const image = document.createElement('img');
        image.className = 'message-image';
        image.src = message.file_path;
        image.alt = message.file_name;
        image.onclick = () => this.openImageModal(message.file_path, message.file_name);
        
        const imageCaption = document.createElement('div');
        imageCaption.className = 'image-caption';
        imageCaption.textContent = message.file_name;
        
        imageContainer.appendChild(image);
        imageContainer.appendChild(imageCaption);
        
        return imageContainer;
    }
    
    openImageModal(imageSrc, imageName) {
        // Create modal overlay
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.onclick = () => modal.remove();
        
        const modalContent = document.createElement('div');
        modalContent.className = 'image-modal-content';
        modalContent.onclick = (e) => e.stopPropagation();
        
        const modalImage = document.createElement('img');
        modalImage.src = imageSrc;
        modalImage.alt = imageName;
        
        const modalCaption = document.createElement('div');
        modalCaption.className = 'image-modal-caption';
        modalCaption.textContent = imageName;
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'image-modal-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = () => modal.remove();
        
        modalContent.appendChild(closeBtn);
        modalContent.appendChild(modalImage);
        modalContent.appendChild(modalCaption);
        modal.appendChild(modalContent);
        
        document.body.appendChild(modal);
    }
    
    formatMessageText(text) {
        // Convert URLs to clickable links
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        text = text.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        
        // Convert line breaks to <br>
        text = text.replace(/\n/g, '<br>');
        
        return text;
    }
    
    // Enhanced file upload with preview
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                this.showNotification('File size must be less than 10MB', 'error');
                event.target.value = '';
                return;
            }
            
            // Show file preview if it's an image
            if (file.type.startsWith('image/')) {
                this.showImagePreview(file);
            }
            
            console.log('File selected:', file.name, file.size);
        }
    }
    
    showImagePreview(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            // Remove existing preview
            const existingPreview = document.querySelector('.file-preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            // Create preview element
            const preview = document.createElement('div');
            preview.className = 'file-preview';
            
            const previewImage = document.createElement('img');
            previewImage.src = e.target.result;
            previewImage.style.maxWidth = '200px';
            previewImage.style.maxHeight = '150px';
            previewImage.style.borderRadius = '8px';
            
            const previewText = document.createElement('div');
            previewText.textContent = `Preview: ${file.name}`;
            previewText.style.fontSize = '12px';
            previewText.style.marginTop = '5px';
            previewText.style.color = '#6c757d';
            
            preview.appendChild(previewImage);
            preview.appendChild(previewText);
            
            // Insert preview after file input
            const fileUploadArea = document.getElementById('fileUploadArea');
            fileUploadArea.appendChild(preview);
        };
        reader.readAsDataURL(file);
    }
}

// Enhanced global functions
function enhancedUploadFile() {
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
            
            // Remove preview
            const preview = document.querySelector('.file-preview');
            if (preview) {
                preview.remove();
            }
            
            toggleFileUpload();
            window.chatApp.loadMessages();
            window.chatApp.showNotification(`${data.file_type === 'image' ? 'Image' : 'File'} uploaded successfully`, 'success');
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

// Override the uploadFile function
window.uploadFile = enhancedUploadFile;

// Initialize enhanced chat app when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.chatApp = new EnhancedChatApp();
});