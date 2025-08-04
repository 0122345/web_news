-- =====================================================
-- CHAT SYSTEM DATABASE SETUP FOR FIACOMM
-- Run these commands in your MySQL database
-- Database: fiacomm (matches our authentication system)
-- =====================================================

USE fiacomm;

-- Create chat_rooms table
CREATE TABLE IF NOT EXISTS chat_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    room_type ENUM('public', 'private', 'group', 'direct') DEFAULT 'public',
    max_participants INT DEFAULT NULL,
    room_avatar VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_type (room_type),
    INDEX idx_created_by (created_by),
    INDEX idx_is_active (is_active)
);

-- Create chat_messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT,
    message_type ENUM('text', 'file', 'image', 'system', 'announcement') DEFAULT 'text',
    file_name VARCHAR(255) DEFAULT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    file_size INT DEFAULT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    reply_to_message_id INT DEFAULT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_message_id) REFERENCES chat_messages(id) ON DELETE SET NULL,
    INDEX idx_room_messages (room_id, created_at),
    INDEX idx_user_messages (user_id, created_at),
    INDEX idx_message_type (message_type),
    FULLTEXT KEY ft_message_content (message)
);

-- Create chat_participants table
CREATE TABLE IF NOT EXISTS chat_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'admin', 'moderator', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_read_id INT DEFAULT NULL,
    is_muted BOOLEAN DEFAULT FALSE,
    muted_until TIMESTAMP NULL,
    notification_settings JSON DEFAULT NULL,
    UNIQUE KEY unique_participant (room_id, user_id),
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (last_message_read_id) REFERENCES chat_messages(id) ON DELETE SET NULL,
    INDEX idx_room_participants (room_id),
    INDEX idx_user_rooms (user_id),
    INDEX idx_participant_role (role)
);

-- Create online_users table for tracking online status
CREATE TABLE IF NOT EXISTS online_users (
    user_id INT PRIMARY KEY,
    status ENUM('online', 'away', 'busy', 'invisible') DEFAULT 'online',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    socket_id VARCHAR(255) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_last_activity (last_activity)
);

-- Create chat_room_invites table for managing room invitations
CREATE TABLE IF NOT EXISTS chat_room_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    invited_by INT NOT NULL,
    invited_user_id INT NOT NULL,
    invite_token VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_invite_status (status),
    INDEX idx_invited_user (invited_user_id),
    INDEX idx_expires_at (expires_at)
);

-- Create chat_message_reactions table for message reactions
CREATE TABLE IF NOT EXISTS chat_message_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_reaction (message_id, user_id, reaction),
    FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_message_reactions (message_id),
    INDEX idx_user_reactions (user_id)
);

-- Create chat_user_blocks table for blocking users
CREATE TABLE IF NOT EXISTS chat_user_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_user_id INT NOT NULL,
    blocked_user_id INT NOT NULL,
    reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_block (blocker_user_id, blocked_user_id),
    FOREIGN KEY (blocker_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_blocker (blocker_user_id),
    INDEX idx_blocked (blocked_user_id)
);

-- Insert default chat rooms based on ecosystem roles
-- Only insert if users exist and rooms don't already exist

-- General Ecosystem Chat (for all users)
INSERT INTO chat_rooms (name, description, created_by, room_type) 
SELECT 
    'Ecosystem Hub', 
    'Main communication hub for all ecosystem members', 
    u.id,
    'public'
FROM users u 
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE r.name IN ('super_admin', 'admin') 
AND NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'Ecosystem Hub')
ORDER BY r.level DESC
LIMIT 1;

-- Contributors Chat
INSERT INTO chat_rooms (name, description, created_by, room_type) 
SELECT 
    'Contributors Circle', 
    'Dedicated space for active contributors to collaborate and share ideas', 
    u.id,
    'public'
FROM users u 
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE r.name IN ('super_admin', 'admin', 'coordinator') 
AND NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'Contributors Circle')
ORDER BY r.level DESC
LIMIT 1;

-- Project Coordination Chat
INSERT INTO chat_rooms (name, description, created_by, room_type) 
SELECT 
    'Project Coordination', 
    'Coordinate projects and manage team communications', 
    u.id,
    'public'
FROM users u 
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE r.name IN ('super_admin', 'admin', 'coordinator') 
AND NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'Project Coordination')
ORDER BY r.level DESC
LIMIT 1;

-- Knowledge Sharing Chat
INSERT INTO chat_rooms (name, description, created_by, room_type) 
SELECT 
    'Knowledge Exchange', 
    'Share insights, best practices, and innovative ideas', 
    u.id,
    'public'
FROM users u 
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE r.name IN ('super_admin', 'admin') 
AND NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'Knowledge Exchange')
ORDER BY r.level DESC
LIMIT 1;

-- Admin/Moderator Chat (private)
INSERT INTO chat_rooms (name, description, created_by, room_type) 
SELECT 
    'Ecosystem Management', 
    'Private channel for administrators and moderators', 
    u.id,
    'private'
FROM users u 
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE r.name IN ('super_admin', 'admin') 
AND NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'Ecosystem Management')
ORDER BY r.level DESC
LIMIT 1;

-- Auto-add users to appropriate rooms based on their roles
-- Add all users to Ecosystem Hub
INSERT INTO chat_participants (room_id, user_id, role)
SELECT cr.id, u.id, 
    CASE 
        WHEN r.name = 'super_admin' THEN 'owner'
        WHEN r.name = 'admin' THEN 'admin'
        WHEN r.name = 'moderator' THEN 'moderator'
        ELSE 'member'
    END as participant_role
FROM chat_rooms cr
CROSS JOIN users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE cr.name = 'Ecosystem Hub'
AND NOT EXISTS (
    SELECT 1 FROM chat_participants cp 
    WHERE cp.room_id = cr.id AND cp.user_id = u.id
);

-- Add contributors to Contributors Circle
INSERT INTO chat_participants (room_id, user_id, role)
SELECT cr.id, u.id, 
    CASE 
        WHEN r.name IN ('super_admin', 'admin') THEN 'admin'
        WHEN r.name = 'moderator' THEN 'moderator'
        ELSE 'member'
    END as participant_role
FROM chat_rooms cr
CROSS JOIN users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE cr.name = 'Contributors Circle'
AND r.name IN ('super_admin', 'admin', 'moderator', 'coordinator', 'contributor')
AND NOT EXISTS (
    SELECT 1 FROM chat_participants cp 
    WHERE cp.room_id = cr.id AND cp.user_id = u.id
);

-- Add coordinators and above to Project Coordination
INSERT INTO chat_participants (room_id, user_id, role)
SELECT cr.id, u.id, 
    CASE 
        WHEN r.name IN ('super_admin', 'admin') THEN 'admin'
        WHEN r.name = 'moderator' THEN 'moderator'
        ELSE 'member'
    END as participant_role
FROM chat_rooms cr
CROSS JOIN users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE cr.name = 'Project Coordination'
AND r.name IN ('super_admin', 'admin', 'moderator', 'coordinator')
AND NOT EXISTS (
    SELECT 1 FROM chat_participants cp 
    WHERE cp.room_id = cr.id AND cp.user_id = u.id
);

-- Add admins and moderators to Ecosystem Management (private)
INSERT INTO chat_participants (room_id, user_id, role)
SELECT cr.id, u.id, 
    CASE 
        WHEN r.name = 'super_admin' THEN 'owner'
        WHEN r.name = 'admin' THEN 'admin'
        WHEN r.name = 'moderator' THEN 'moderator'
        ELSE 'member'
    END as participant_role
FROM chat_rooms cr
CROSS JOIN users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE cr.name = 'Ecosystem Management'
AND r.name IN ('super_admin', 'admin', 'moderator')
AND NOT EXISTS (
    SELECT 1 FROM chat_participants cp 
    WHERE cp.room_id = cr.id AND cp.user_id = u.id
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_chat_messages_created_at ON chat_messages(created_at);
CREATE INDEX IF NOT EXISTS idx_chat_participants_last_seen ON chat_participants(last_seen);
CREATE INDEX IF NOT EXISTS idx_online_users_last_activity ON online_users(last_activity);

-- Create a view for easy room access with participant counts
CREATE OR REPLACE VIEW chat_rooms_with_stats AS
SELECT 
    cr.*,
    u.username as creator_username,
    u.fullname as creator_fullname,
    COUNT(DISTINCT cp.user_id) as participant_count,
    COUNT(DISTINCT cm.id) as message_count,
    MAX(cm.created_at) as last_message_at
FROM chat_rooms cr
LEFT JOIN users u ON cr.created_by = u.id
LEFT JOIN chat_participants cp ON cr.id = cp.room_id
LEFT JOIN chat_messages cm ON cr.id = cm.room_id AND cm.is_deleted = FALSE
WHERE cr.is_active = TRUE
GROUP BY cr.id, u.username, u.fullname;

-- Create a view for user chat statistics
CREATE OR REPLACE VIEW user_chat_stats AS
SELECT 
    u.id as user_id,
    u.username,
    u.fullname,
    COUNT(DISTINCT cp.room_id) as rooms_joined,
    COUNT(DISTINCT cm.id) as messages_sent,
    MAX(cm.created_at) as last_message_sent,
    ou.status as online_status,
    ou.last_activity
FROM users u
LEFT JOIN chat_participants cp ON u.id = cp.user_id
LEFT JOIN chat_messages cm ON u.id = cm.user_id AND cm.is_deleted = FALSE
LEFT JOIN online_users ou ON u.id = ou.user_id
GROUP BY u.id, u.username, u.fullname, ou.status, ou.last_activity;

-- Show created tables
SHOW TABLES LIKE 'chat_%';
SHOW TABLES LIKE 'online_users';

-- Display table structures
DESCRIBE chat_rooms;
DESCRIBE chat_messages;
DESCRIBE chat_participants;
DESCRIBE online_users;

-- Show initial data with ecosystem context
SELECT 
    cr.name as room_name,
    cr.description,
    cr.room_type,
    u.username as creator,
    r.display_name as creator_role,
    cr.created_at
FROM chat_rooms cr 
LEFT JOIN users u ON cr.created_by = u.id
LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_primary = TRUE
LEFT JOIN roles r ON ur.role_id = r.id
ORDER BY cr.created_at;

-- Show room participants with their ecosystem roles
SELECT 
    cr.name as room_name,
    u.username,
    u.fullname,
    r.display_name as ecosystem_role,
    cp.role as chat_role,
    cp.joined_at
FROM chat_participants cp
JOIN chat_rooms cr ON cp.room_id = cr.id
JOIN users u ON cp.user_id = u.id
LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_primary = TRUE
LEFT JOIN roles r ON ur.role_id = r.id
ORDER BY cr.name, r.level DESC, u.username;

-- Show user count and role distribution
SELECT 
    r.display_name as role,
    COUNT(ur.user_id) as user_count
FROM roles r
LEFT JOIN user_roles ur ON r.id = ur.role_id AND ur.is_primary = TRUE
GROUP BY r.id, r.display_name
ORDER BY r.level DESC;

 
-- http://localhost/auth/components/chat/chatroom.php
-- =====================================================