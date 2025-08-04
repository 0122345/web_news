# EcoComm Authentication System

A modern, role-based authentication system with an "Ecosystem Communication" theme. This system provides secure user authentication, role-based access control, and a beautiful, responsive interface.

## Features

### 🌱 Ecosystem Theme

- Nature-inspired design with green gradients and organic elements
- Floating background animations
- Glassmorphism effects with backdrop blur
- Responsive design for all devices

### 🔐 Security Features

- Password hashing with PHP's `password_hash()`
- Account lockout after 5 failed login attempts
- Session management with database storage
- CSRF protection ready
- Input validation and sanitization
- Activity logging for security auditing

### 👥 Role-Based Access Control

- **Ecosystem Guardian** (Super Admin) - Full system access
- **Ecosystem Manager** (Admin) - Administrative access
- **Community Steward** (Moderator) - Content moderation
- **Project Coordinator** - Project management
- **Active Contributor** - Content creation
- **Community Member** - Basic access
- **Ecosystem Observer** - Read-only access

### 📊 Permission System

- Granular permissions for different modules
- User management, content, communication, projects, analytics, system
- Easy permission checking in PHP
- Role-based dashboard customization

## Installation

### 1. Database Setup

1. Create a new MySQL database named `ecocomm_auth`
2. Run the SQL script to create tables and insert default data:

```bash
mysql -u root -p ecocomm_auth < database_setup.sql
```

Or import via phpMyAdmin:

- Open phpMyAdmin
- Create database `ecocomm_auth`
- Import the `database_setup.sql` file

### 2. Database Configuration

Update the database connection settings in all PHP files:

```php
$servername = "localhost";
$username = "root";        // Your MySQL username
$password = "";            // Your MySQL password
$dbname = "fiacomm";  // Database name
```

### 3. File Structure

Ensure your files are organized as follows:

```
/auth/
├── ecosystem-auth.css     # Main stylesheet
├── login.html            # Login page
├── login.php             # Login processing
├── signup.html           # Registration page
├── signup.php            # Registration processing
├── dashboard.php         # User dashboard
├── profile.php           # User profile
├── logout.php            # Logout processing
├── database_setup.sql    # Database schema
└── README.md             # This file
```

### 4. Default Admin Account

A default super admin account is created during setup:

- **Username:** `eco_admin`
- **Email:** `admin@ecocomm.local`
- **Password:** `EcoAdmin123!`

**⚠️ Important:** Change this password immediately after first login!

## Usage

### User Registration

1. Navigate to `signup.html`
2. Fill in the registration form
3. Select your ecosystem role:
   - **Community Member** - Basic access
   - **Active Contributor** - Content creation
   - **Project Coordinator** - Project management
   - **Ecosystem Observer** - Read-only access
4. Complete registration

### User Login

1. Navigate to `login.html`
2. Enter username/email and password
3. Optionally check "Remember me"
4. Login redirects to role-appropriate dashboard

### Role Management

Roles are automatically assigned based on user selection during registration. Administrators can modify roles through the database or by building an admin interface.

### Permission Checking

In your PHP files, check permissions like this:

```php
// Check if user has specific permission
function hasPermission($permission_name) {
    if (!isset($_SESSION['permissions'])) return false;
    
    foreach ($_SESSION['permissions'] as $permission) {
        if ($permission['name'] === $permission_name) {
            return true;
        }
    }
    return false;
}

// Usage
if (hasPermission('content.create')) {
    // Show create content button
}
```

## Database Schema

### Main Tables

- **users** - User accounts and profile information
- **roles** - System roles with display names and colors
- **permissions** - Granular permissions for different actions
- **user_roles** - Junction table linking users to roles
- **role_permissions** - Junction table linking roles to permissions
- **user_sessions** - Active user sessions for security
- **user_activity_logs** - User activity tracking
- **security_logs** - Security event logging

### Key Features

- **Email Verification** - Users can have unverified emails
- **Account Locking** - Automatic lockout after failed attempts
- **Session Management** - Database-stored sessions with expiration
- **Activity Logging** - Track user actions for security
- **Flexible Roles** - Easy to add new roles and permissions

## Customization

### Adding New Roles

1. Insert into `roles` table:

```sql
INSERT INTO roles (name, display_name, description, color, icon, level) 
VALUES ('new_role', 'New Role', 'Description', '#color', 'fas fa-icon', 5);
```

2. Assign permissions:

```sql
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'new_role' AND p.name IN ('permission1', 'permission2');
```

### Adding New Permissions

1. Insert into `permissions` table:

```sql
INSERT INTO permissions (name, display_name, description, module) 
VALUES ('module.action', 'Action Name', 'Description', 'module');
```

2. Assign to roles as needed

### Styling Customization

The CSS uses CSS custom properties (variables) for easy theming:

```css
:root {
    --eco-primary: #059669;        /* Primary green */
    --eco-secondary: #0ea5e9;      /* Secondary blue */
    --eco-accent: #f59e0b;         /* Accent orange */
    /* ... more variables */
}
```

## Security Considerations

1. **Change Default Admin Password** immediately
2. **Use HTTPS** in production
3. **Configure proper session settings** in php.ini
4. **Set up email verification** for production use
5. **Implement rate limiting** for login attempts
6. **Regular security audits** of activity logs
7. **Keep PHP and MySQL updated**

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Dependencies

- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- Modern web browser with CSS Grid support

## License

This project is open source and available under the MIT License.

## Support

For issues or questions:

1. Check the database connection settings
2. Verify file permissions
3. Check PHP error logs
4. Ensure all required PHP extensions are installed

## Future Enhancements

- Email verification system
- Password reset functionality
- Two-factor authentication
- OAuth integration (Google, Facebook)
- Admin panel for role management
- API endpoints for mobile apps
- Advanced user analytics
- Notification system
