# 🌱 EcoComm Authentication System - Setup Instructions

## 🚀 Quick Setup Guide

### Step 1: Database Setup

1. **Start XAMPP**
   - Start Apache and MySQL services

2. **Create Database**
   - Open phpMyAdmin (<http://localhost/phpmyadmin>)
   - Click "SQL" tab
   - Copy and paste the entire content from `database_setup.sql`
   - Click "Go" to execute

   **OR** use MySQL command line:

   ```bash
   mysql -u root -p < database_setup.sql
   ```

### Step 2: Verify Database

After running the SQL script, you should have:

- ✅ Database: `fiacomm`
- ✅ Tables: users, roles, permissions, user_roles, role_permissions, user_sessions, user_activity_logs, security_logs
- ✅ Default admin user created

### Step 3: Test the System

1. **Navigate to signup page:**

   ```bash
   http://localhost/auth/auth/signup.html
   ```

2. **Create a test account:**
   - Fill in all required fields
   - Choose an ecosystem role
   - Submit the form

3. **Test login:**

   ```bash
   http://localhost/auth/auth/login.html
   ```

4. **Use default admin account:**
   - Username: `eco_admin`
   - Password: `EcoAdmin123!`

## 🔧 Troubleshooting

### Common Issues

1. **"Connection failed" error:**
   - Make sure MySQL is running in XAMPP
   - Check database credentials in PHP files
   - Verify database name is `fiacomm`

2. **"Table doesn't exist" error:**
   - Run the `database_setup.sql` script again
   - Make sure all tables were created successfully

3. **"Permission denied" error:**
   - Check file permissions
   - Make sure XAMPP has write access to the directory

4. **JavaScript/CSS not loading:**
   - Check file paths in HTML files
   - Make sure `ecosystem-auth.css` is in the same directory

### Database Connection Settings

All PHP files use these settings:

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fiacomm";
```

If your MySQL setup is different, update these values in:

- `login.php`
- `signup.php`
- `dashboard.php`
- `profile.php`
- `logout.php`

## 🎯 Features Included

### ✅ Authentication Features

- User registration with role selection
- Secure login with password hashing
- Account lockout after failed attempts
- Session management
- Remember me functionality
- Activity logging

### ✅ Role-Based Access Control

- **Ecosystem Guardian** (Super Admin)
- **Ecosystem Manager** (Admin)
- **Community Steward** (Moderator)
- **Project Coordinator**
- **Active Contributor**
- **Community Member**
- **Ecosystem Observer**

### ✅ Security Features

- Password strength validation
- SQL injection protection
- XSS protection
- Session security
- Activity logging
- Failed login tracking

### ✅ UI/UX Features

- Beautiful ecosystem-themed design
- Responsive layout
- Real-time form validation
- Loading states
- Success/error notifications
- Smooth animations

## 📁 File Structure

```bash
/auth/auth/
├── login.html              # Login page
├── login.php              # Login processing
├── signup.html            # Registration page
├── signup.php             # Registration processing
├── dashboard.php          # User dashboard
├── profile.php            # User profile
├── logout.php             # Logout processing
├── ecosystem-auth.css     # Stylesheet
├── database_setup.sql     # Database schema
├── README.md              # Documentation
└── SETUP_INSTRUCTIONS.md  # This file
```

## 🔐 Default Admin Account

- **Username:** `eco_admin`
- **Email:** `admin@ecocomm.local`
- **Password:** `EcoAdmin123!`
- **Role:** Ecosystem Guardian (Super Admin)

**⚠️ Important:** Change the default admin password after first login!

## 🌟 Next Steps

After setup is complete, you can:

1. **Test user registration and login**
2. **Explore the role-based dashboard**
3. **Customize the design and colors**
4. **Add more features as needed**
5. **Implement email verification**
6. **Add password reset functionality**

## 📞 Support

If you encounter any issues:

1. Check the browser console for JavaScript errors
2. Check PHP error logs
3. Verify database connection
4. Make sure all files are in the correct location
5. Ensure XAMPP services are running

---

**🎉 Congratulations!** Your EcoComm Authentication System is now ready to use!
