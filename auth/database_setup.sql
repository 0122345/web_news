-- EcoComm Authentication System Database Setup
-- Theme: Ecosystem Communication Platform

-- Create database
CREATE DATABASE IF NOT EXISTS fiacomm;
USE fiacomm;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS user_activity_logs;
DROP TABLE IF EXISTS security_logs;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS role_permissions;

-- Create roles table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#2563eb',
    icon VARCHAR(50) DEFAULT 'fas fa-user',
    level INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create permissions table
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create role_permissions junction table
CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
);

-- Create users table with ecosystem theme
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    ecosystem_role VARCHAR(100) DEFAULT 'Community Member',
    department VARCHAR(100) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) DEFAULT NULL,
    password_reset_token VARCHAR(255) DEFAULT NULL,
    password_reset_expires TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    preferences JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user_roles junction table
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_by INT DEFAULT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id)
);

-- Create user sessions table
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default roles with ecosystem theme
INSERT INTO roles (name, display_name, description, color, icon, level) VALUES
('super_admin', 'Ecosystem Guardian', 'Ultimate system administrator with full access to all ecosystem functions', '#dc2626', 'fas fa-crown', 10),
('admin', 'Ecosystem Manager', 'Administrative access to manage users and system settings', '#ea580c', 'fas fa-user-shield', 8),
('moderator', 'Community Steward', 'Moderate content and manage community interactions', '#ca8a04', 'fas fa-gavel', 6),
('coordinator', 'Project Coordinator', 'Coordinate projects and manage team communications', '#16a34a', 'fas fa-project-diagram', 5),
('contributor', 'Active Contributor', 'Contribute content and participate in ecosystem activities', '#2563eb', 'fas fa-hands-helping', 4),
('member', 'Community Member', 'Basic member with standard access to ecosystem features', '#7c3aed', 'fas fa-user', 2),
('observer', 'Ecosystem Observer', 'Read-only access to observe ecosystem activities', '#64748b', 'fas fa-eye', 1);

-- Insert permissions with ecosystem modules
INSERT INTO permissions (name, display_name, description, module) VALUES
-- User Management
('users.view', 'View Users', 'View user profiles and information', 'users'),
('users.create', 'Create Users', 'Create new user accounts', 'users'),
('users.edit', 'Edit Users', 'Edit user profiles and information', 'users'),
('users.delete', 'Delete Users', 'Delete user accounts', 'users'),
('users.manage_roles', 'Manage User Roles', 'Assign and remove user roles', 'users'),

-- Content Management
('content.view', 'View Content', 'View ecosystem content and posts', 'content'),
('content.create', 'Create Content', 'Create new content and posts', 'content'),
('content.edit', 'Edit Content', 'Edit existing content', 'content'),
('content.delete', 'Delete Content', 'Delete content and posts', 'content'),
('content.moderate', 'Moderate Content', 'Moderate and review content', 'content'),
('content.publish', 'Publish Content', 'Publish content to the ecosystem', 'content'),

-- Communication
('communication.view', 'View Communications', 'View messages and communications', 'communication'),
('communication.send', 'Send Messages', 'Send messages to other users', 'communication'),
('communication.broadcast', 'Broadcast Messages', 'Send broadcast messages to groups', 'communication'),
('communication.moderate', 'Moderate Communications', 'Moderate messages and discussions', 'communication'),

-- Projects
('projects.view', 'View Projects', 'View ecosystem projects', 'projects'),
('projects.create', 'Create Projects', 'Create new projects', 'projects'),
('projects.edit', 'Edit Projects', 'Edit project details', 'projects'),
('projects.delete', 'Delete Projects', 'Delete projects', 'projects'),
('projects.manage', 'Manage Projects', 'Full project management access', 'projects'),

-- Analytics
('analytics.view', 'View Analytics', 'View ecosystem analytics and reports', 'analytics'),
('analytics.export', 'Export Analytics', 'Export analytics data', 'analytics'),

-- System
('system.settings', 'System Settings', 'Access system configuration', 'system'),
('system.logs', 'View System Logs', 'View system logs and audit trails', 'system'),
('system.backup', 'System Backup', 'Perform system backups', 'system');

-- Assign permissions to roles
-- Super Admin (Ecosystem Guardian) - All permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'super_admin';

-- Admin (Ecosystem Manager) - Most permissions except system backup
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'admin' AND p.name NOT IN ('system.backup');

-- Moderator (Community Steward) - Content and communication moderation
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'moderator' AND p.name IN (
    'users.view', 'content.view', 'content.moderate', 'content.delete',
    'communication.view', 'communication.moderate', 'analytics.view'
);

-- Coordinator (Project Coordinator) - Project management and team coordination
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'coordinator' AND p.name IN (
    'users.view', 'content.view', 'content.create', 'content.edit', 'content.publish',
    'communication.view', 'communication.send', 'communication.broadcast',
    'projects.view', 'projects.create', 'projects.edit', 'projects.manage',
    'analytics.view'
);

-- Contributor (Active Contributor) - Content creation and participation
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'contributor' AND p.name IN (
    'users.view', 'content.view', 'content.create', 'content.edit',
    'communication.view', 'communication.send',
    'projects.view', 'projects.create', 'projects.edit'
);

-- Member (Community Member) - Basic access
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'member' AND p.name IN (
    'users.view', 'content.view', 'content.create',
    'communication.view', 'communication.send',
    'projects.view'
);

-- Observer (Ecosystem Observer) - Read-only access
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'observer' AND p.name IN (
    'users.view', 'content.view', 'communication.view', 'projects.view'
);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX idx_user_roles_role_id ON user_roles(role_id);
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions_expires ON user_sessions(expires_at);

-- Create a default super admin user (password: EcoAdmin123!)
INSERT INTO users (fullname, username, email, password, ecosystem_role, status, email_verified) VALUES
('Ecosystem Guardian', 'eco_admin', 'admin@ecocomm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'active', TRUE);

-- Assign super admin role to the default admin user
INSERT INTO user_roles (user_id, role_id, is_primary) 
SELECT u.id, r.id, TRUE FROM users u, roles r 
WHERE u.username = 'eco_admin' AND r.name = 'super_admin';

-- Create user activity logs table
CREATE TABLE user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create security logs table
CREATE TABLE security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    activity VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for activity logs
CREATE INDEX idx_user_activity_logs_user_id ON user_activity_logs(user_id);
CREATE INDEX idx_user_activity_logs_activity ON user_activity_logs(activity);
CREATE INDEX idx_user_activity_logs_created_at ON user_activity_logs(created_at);
CREATE INDEX idx_security_logs_ip_address ON security_logs(ip_address);
CREATE INDEX idx_security_logs_activity ON security_logs(activity);
CREATE INDEX idx_security_logs_created_at ON security_logs(created_at);

-- Create views for easier querying
CREATE VIEW user_permissions AS
SELECT 
    u.id as user_id,
    u.username,
    u.email,
    r.name as role_name,
    r.display_name as role_display_name,
    p.name as permission_name,
    p.display_name as permission_display_name,
    p.module
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE u.status = 'active' AND (ur.expires_at IS NULL OR ur.expires_at > NOW());

CREATE VIEW user_roles_view AS
SELECT 
    u.id as user_id,
    u.username,
    u.fullname,
    u.email,
    u.ecosystem_role,
    r.id as role_id,
    r.name as role_name,
    r.display_name as role_display_name,
    r.color as role_color,
    r.icon as role_icon,
    r.level as role_level,
    ur.is_primary,
    ur.assigned_at,
    ur.expires_at
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE u.status = 'active' AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
ORDER BY r.level DESC, ur.is_primary DESC;