# Advanced Chat System with File Exchange

A comprehensive real-time chat application built with PHP, MySQL, JavaScript, and modern CSS. Features include real-time messaging, file sharing, image preview, online user tracking, and multiple chat rooms.

## Features

### 🚀 Core Features
- **Real-time messaging** with automatic message polling
- **Multiple chat rooms** with easy room creation
- **File sharing** (documents, images, archives, etc.)
- **Image preview** with modal view
- **Online user tracking** with live status
- **Responsive design** for mobile and desktop
- **User authentication** integration
- **Modern UI/UX** with smooth animations

### 📁 File Sharing Capabilities
- **Supported file types**: Images (jpg, png, gif, etc.), Documents (pdf, doc, docx, etc.), Archives (zip, rar, 7z), Audio/Video files
- **File size limit**: 10MB per file
- **Image preview**: Automatic thumbnail generation and modal view
- **Download functionality**: Direct file download from chat
- **File organization**: Separate folders for images and other files

### 💬 Chat Features
- **Message history**: Persistent message storage
- **User profiles**: Display user avatars and names
- **Typing indicators**: Real-time typing status (framework ready)
- **Message formatting**: Automatic URL linking
- **Timestamps**: Message time display
- **User mentions**: Framework ready for @mentions

## Database Setup

### 1. Run the SQL Setup
Execute the following SQL commands in your MySQL database:

```sql
-- Use your existing user_management database
USE user_management;

-- Create chat_rooms table
CREATE TABLE IF NOT EXISTS chat_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_private BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create chat_messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT,
    message_type ENUM('text', 'file', 'image') DEFAULT 'text',
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create chat_participants table
CREATE TABLE IF NOT EXISTS chat_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_participant (room_id, user_id),
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create online_users table for tracking online status
CREATE TABLE IF NOT EXISTS online_users (
    user_id INT PRIMARY KEY,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert a default general chat room
INSERT INTO chat_rooms (name, description, created_by) 
SELECT 'General Chat', 'Main chat room for all users', 1 
WHERE NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'General Chat');
```

### 2. Directory Structure
The following directories are automatically created:
```
chat/
├── uploads/
│   ├── files/     (for documents, archives, etc.)
│   └── images/    (for image files)
├── chatroom.php   (main chat interface)
├── upload_file.php (file upload handler)
├── create_room.php (room creation handler)
├── chat.js        (core chat functionality)
├── enhanced_chat.js (enhanced features)
├── chat.css       (styling)
└── database_setup.sql (database schema)
```

## Installation & Setup

### 1. Prerequisites
- XAMPP/WAMP server running
- MySQL database named `user_management`
- Existing user authentication system

### 2. Quick Setup
1. **Copy files** to your web directory: `c:\xampp\htdocs\auth\chat\`
2. **Run SQL commands** from `database_setup.sql` in your MySQL database
3. **Set permissions** for upload directories (if needed)
4. **Access the chat** via: `http://localhost/auth/chat/chatroom.php`

### 3. Configuration
The chat system uses the same database connection as your existing auth system:
- **Server**: localhost
- **Username**: root
- **Password**: (empty)
- **Database**: user_management

## Usage Guide

### For Users
1. **Login** to your account through the existing auth system
2. **Access chat** via the chat interface
3. **Join rooms** by clicking on room names in the sidebar
4. **Send messages** by typing and pressing Enter or clicking Send
5. **Share files** by clicking the paperclip icon and selecting files
6. **View online users** by clicking the users icon
7. **Create rooms** by clicking the + button in the sidebar

### For Administrators
- **Monitor chat activity** through the database tables
- **Manage rooms** by modifying the `chat_rooms` table
- **Control file uploads** by adjusting size limits in `upload_file.php`
- **Customize UI** by modifying `chat.css`

## Security Features

### File Upload Security
- **File type validation**: Only allowed file types can be uploaded
- **Size limits**: Maximum 10MB per file
- **Unique naming**: Files are renamed to prevent conflicts
- **Path protection**: Files stored outside web root when possible

### User Authentication
- **Session validation**: All requests validate user sessions
- **SQL injection protection**: Prepared statements used throughout
- **XSS prevention**: All user input is sanitized
- **CSRF protection**: AJAX requests include validation headers

## Customization Options

### Styling
- Modify `chat.css` for custom themes
- Update color schemes in CSS variables
- Adjust responsive breakpoints
- Customize animations and transitions

### Functionality
- Add emoji support in `enhanced_chat.js`
- Implement push notifications
- Add message search functionality
- Create private messaging features

### File Handling
- Adjust file size limits in `upload_file.php`
- Add new file type support
- Implement file compression
- Add virus scanning integration

## Troubleshooting

### Common Issues
1. **Files not uploading**: Check directory permissions and PHP upload limits
2. **Messages not appearing**: Verify database connection and table structure
3. **Styling issues**: Clear browser cache and check CSS file paths
4. **Login redirects**: Ensure session management is working properly

### Performance Optimization
- **Database indexing**: Add indexes on frequently queried columns
- **File cleanup**: Implement automatic cleanup of old files
- **Message archiving**: Archive old messages to improve performance
- **Caching**: Implement Redis or Memcached for session storage

## Browser Compatibility
- **Chrome**: Full support
- **Firefox**: Full support
- **Safari**: Full support
- **Edge**: Full support
- **Mobile browsers**: Responsive design supported

## Future Enhancements
- Push notifications for new messages
- Voice message support
- Video calling integration
- Message encryption
- Advanced moderation tools
- Bot integration capabilities

## Support
For issues or questions, check the database logs and browser console for error messages. Ensure all file permissions are correctly set and the database connection is stable.