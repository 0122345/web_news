-- -- =====================================================
-- -- CHAT SYSTEM DATABASE SETUP (FIXED VERSION)
-- -- Run these commands in your MySQL database
-- -- Database: user_management
-- -- =====================================================

-- USE user_management;

-- -- Create chat_rooms table
-- CREATE TABLE IF NOT EXISTS chat_rooms (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     description TEXT,
--     created_by INT NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     is_private BOOLEAN DEFAULT FALSE,
--     FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
-- );

-- -- Create chat_messages table
-- CREATE TABLE IF NOT EXISTS chat_messages (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     room_id INT NOT NULL,
--     user_id INT NOT NULL,
--     message TEXT,
--     message_type ENUM('text', 'file', 'image') DEFAULT 'text',
--     file_name VARCHAR(255),
--     file_path VARCHAR(500),
--     file_size INT,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
-- );

-- -- Create chat_participants table
-- CREATE TABLE IF NOT EXISTS chat_participants (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     room_id INT NOT NULL,
--     user_id INT NOT NULL,
--     joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     UNIQUE KEY unique_participant (room_id, user_id),
--     FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
-- );

-- -- Create online_users table for tracking online status
-- CREATE TABLE IF NOT EXISTS online_users (
--     user_id INT PRIMARY KEY,
--     last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
-- );

-- -- Insert default chat rooms ONLY if there are users in the system
-- -- This prevents the foreign key constraint error
-- INSERT INTO chat_rooms (name, description, created_by) 
-- SELECT 'General Chat', 'Main chat room for all users', u.id
-- FROM users u 
-- WHERE NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'General Chat')
-- LIMIT 1;

-- INSERT INTO chat_rooms (name, description, created_by) 
-- SELECT 'Tech Discussion', 'Discuss technology and programming', u.id
-- FROM users u 
-- WHERE NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'Tech Discussion')
-- LIMIT 1;

-- INSERT INTO chat_rooms (name, description, created_by) 
-- SELECT 'Random', 'Off-topic conversations', u.id
-- FROM users u 
-- WHERE NOT EXISTS (SELECT 1 FROM chat_rooms WHERE name = 'Random')
-- LIMIT 1;

-- -- Show created tables
-- SHOW TABLES LIKE 'chat_%';
-- SHOW TABLES LIKE 'online_users';

-- -- Display table structures
-- DESCRIBE chat_rooms;
-- DESCRIBE chat_messages;
-- DESCRIBE chat_participants;
-- DESCRIBE online_users;

-- -- Show initial data (if any users exist)
-- SELECT cr.*, u.username as creator_username 
-- FROM chat_rooms cr 
-- LEFT JOIN users u ON cr.created_by = u.id;

-- -- Show user count for reference
-- SELECT COUNT(*) as total_users FROM users;

-- -- =====================================================
-- -- SETUP COMPLETE!
-- -- 
-- -- NOTE: If no chat rooms were created, it means you have
-- -- no users in your system yet. Chat rooms will be created
-- -- automatically when the first user registers.
-- --
-- -- You can now access the chat at:
-- -- http://localhost/auth/components/chat/chatroom.php
-- -- =====================================================