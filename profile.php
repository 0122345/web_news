<?php
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.html");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $message = handleProfileUpdate($conn);
                break;
            case 'upload_picture':
                $message = handlePictureUpload($conn);
                break;
            case 'delete_account':
                handleAccountDeletion($conn);
                break;
        }
    }
}

// Retrieve user data
$sql = "SELECT fullname, email, username, created_at, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$user_id = $_SESSION['user_id'];
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $username, $created_at, $profile_picture);
$stmt->fetch();
$stmt->close();

// Get user interaction statistics using your existing tables
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM likes WHERE user_id = ?) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE user_id = ?) as comments_count,
    (SELECT COUNT(*) FROM shares WHERE user_id = ?) as shares_count";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Get recent interactions using UNION to combine all interaction types
$interactions_sql = "
    (SELECT 'like' as interaction_type, l.created_at, a.title as article_title, a.category, NULL as content
     FROM likes l 
     JOIN articles a ON l.article_id = a.id 
     WHERE l.user_id = ?)
    UNION ALL
    (SELECT 'comment' as interaction_type, c.created_at, a.title as article_title, a.category, c.content
     FROM comments c 
     JOIN articles a ON c.article_id = a.id 
     WHERE c.user_id = ?)
    UNION ALL
    (SELECT 'share' as interaction_type, s.created_at, a.title as article_title, a.category, s.platform as content
     FROM shares s 
     JOIN articles a ON s.article_id = a.id 
     WHERE s.user_id = ?)
    ORDER BY created_at DESC 
    LIMIT 10";

$interactions_stmt = $conn->prepare($interactions_sql);
$interactions_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$interactions_stmt->execute();
$interactions_result = $interactions_stmt->get_result();
$recent_interactions = [];
while ($row = $interactions_result->fetch_assoc()) {
    $recent_interactions[] = $row;
}
$interactions_stmt->close();

// Functions for handling form submissions
function handleProfileUpdate($conn) {
    $user_id = $_SESSION['user_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $new_username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($fullname) || empty($email) || empty($new_username)) {
        return ['message' => 'All fields are required', 'type' => 'error'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['message' => 'Invalid email format', 'type' => 'error'];
    }
    
    // Check if username/email already exists (excluding current user)
    $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ssi", $new_username, $email, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        return ['message' => 'Username or email already exists', 'type' => 'error'];
    }
    
    // If password change is requested
    if (!empty($new_password)) {
        if (empty($current_password)) {
            return ['message' => 'Current password is required to change password', 'type' => 'error'];
        }
        
        // Verify current password
        $pass_sql = "SELECT password FROM users WHERE id = ?";
        $pass_stmt = $conn->prepare($pass_sql);
        $pass_stmt->bind_param("i", $user_id);
        $pass_stmt->execute();
        $pass_result = $pass_stmt->get_result();
        $pass_data = $pass_result->fetch_assoc();
        
        if (!password_verify($current_password, $pass_data['password'])) {
            return ['message' => 'Current password is incorrect', 'type' => 'error'];
        }
        
        if ($new_password !== $confirm_password) {
            return ['message' => 'New passwords do not match', 'type' => 'error'];
        }
        
        if (strlen($new_password) < 6) {
            return ['message' => 'New password must be at least 6 characters', 'type' => 'error'];
        }
        
        // Update with new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET fullname = ?, email = ?, username = ?, password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $fullname, $email, $new_username, $hashed_password, $user_id);
    } else {
        // Update without password change
        $update_sql = "UPDATE users SET fullname = ?, email = ?, username = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $fullname, $email, $new_username, $user_id);
    }
    
    if ($update_stmt->execute()) {
        // Update session variables
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $new_username;
        
        return ['message' => 'Profile updated successfully!', 'type' => 'success'];
    } else {
        return ['message' => 'Failed to update profile', 'type' => 'error'];
    }
}

function handlePictureUpload($conn) {
    $user_id = $_SESSION['user_id'];
    
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        return ['message' => 'Please select a valid image file', 'type' => 'error'];
    }
    
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['message' => 'Only JPEG, PNG, and GIF files are allowed', 'type' => 'error'];
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        return ['message' => 'File size must be less than 5MB', 'type' => 'error'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
    $upload_path = 'uploads/profile_pictures/' . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir('uploads/profile_pictures')) {
        mkdir('uploads/profile_pictures', 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Delete old profile picture if exists
        $old_pic_sql = "SELECT profile_picture FROM users WHERE id = ?";
        $old_pic_stmt = $conn->prepare($old_pic_sql);
        $old_pic_stmt->bind_param("i", $user_id);
        $old_pic_stmt->execute();
        $old_pic_result = $old_pic_stmt->get_result();
        $old_pic_data = $old_pic_result->fetch_assoc();
        
        if ($old_pic_data['profile_picture'] && file_exists($old_pic_data['profile_picture'])) {
            unlink($old_pic_data['profile_picture']);
        }
        
        // Update database
        $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $upload_path, $user_id);
        
        if ($update_stmt->execute()) {
            return ['message' => 'Profile picture updated successfully!', 'type' => 'success'];
        } else {
            return ['message' => 'Failed to update profile picture in database', 'type' => 'error'];
        }
    } else {
        return ['message' => 'Failed to upload file', 'type' => 'error'];
    }
}

function handleAccountDeletion($conn) {
    $user_id = $_SESSION['user_id'];
    $password = $_POST['delete_password'];
    $reason = trim($_POST['delete_reason']);
    
    // Verify password
    $pass_sql = "SELECT password, fullname, email, username FROM users WHERE id = ?";
    $pass_stmt = $conn->prepare($pass_sql);
    $pass_stmt->bind_param("i", $user_id);
    $pass_stmt->execute();
    $pass_result = $pass_stmt->get_result();
    $user_data = $pass_result->fetch_assoc();
    
    if (!password_verify($password, $user_data['password'])) {
        echo "<script>alert('Incorrect password. Account deletion cancelled.'); window.location.href='profile.php';</script>";
        return;
    }
    
    // Store deletion record
    $delete_record_sql = "INSERT INTO deleted_accounts (original_user_id, fullname, email, username, deleted_by, reason) VALUES (?, ?, ?, ?, ?, ?)";
    $delete_record_stmt = $conn->prepare($delete_record_sql);
    $delete_record_stmt->bind_param("isssss", $user_id, $user_data['fullname'], $user_data['email'], $user_data['username'], $user_id, $reason);
    $delete_record_stmt->execute();
    
    // Delete profile picture if exists
    if ($user_data['profile_picture'] && file_exists($user_data['profile_picture'])) {
        unlink($user_data['profile_picture']);
    }
    
    // Delete user account (this will cascade delete related records due to foreign keys)
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    
    if ($delete_stmt->execute()) {
        // Destroy session
        session_destroy();
        
        echo "<script>
            alert('Your account has been successfully deleted. We\\'re sorry to see you go!');
            window.location.href = 'index.html';
        </script>";
        exit;
    } else {
        echo "<script>alert('Failed to delete account. Please try again.'); window.location.href='profile.php';</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo htmlspecialchars($fullname); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        header h1 {
            text-align: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 300;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .profile-card {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 25px 25px 0 0;
        }

        .profile-header {
            text-align: center;
            position: relative;
            z-index: 2;
            margin-bottom: 30px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 6px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            position: relative;
            top: -75px;
            overflow: hidden;
            cursor: pointer;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-image i {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .profile-image:hover::after {
            content: 'Change Photo';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            border-radius: 50%;
        }

        .profile-name {
            font-size: 2.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            margin-top: -50px;
        }

        .profile-subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .info-item i {
            font-size: 1.5rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.2rem;
            color: #555;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .interactions-section {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .interaction-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }

        .interaction-item:hover {
            transform: translateX(5px);
        }

        .interaction-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .interaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .interaction-icon.like {
            background: #e74c3c;
        }

        .interaction-icon.comment {
            background: #3498db;
        }

        .interaction-icon.share {
            background: #2ecc71;
        }

        .interaction-content {
            flex: 1;
        }

        .interaction-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .interaction-meta {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            gap: 15px;
        }

        .interaction-text {
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            font-style: italic;
        }

        .no-interactions {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-body {
            padding: 30px;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            opacity: 0.7;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        footer {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            color: white;
            margin-top: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .profile-card, .interactions-section {
                padding: 20px;
            }
            
            .profile-image {
                width: 120px;
                height: 120px;
                font-size: 3rem;
            }
            
            .profile-name {
                font-size: 1.8rem;
            }
            
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>User Profile Dashboard</h1>
        </header>
        
        <main>
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message['type']; ?>">
                    <?php echo htmlspecialchars($message['message']); ?>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-image" onclick="openPictureModal()">
                        <?php if ($profile_picture && file_exists($profile_picture)): ?>
                            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($fullname); ?></h2>
                    <p class="profile-subtitle">Welcome to your profile dashboard</p>
                </div>
                
                <div class="profile-info">
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-user-circle"></i>
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($username); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="info-label">Member Since</div>
                                                 <div class="info-value"><?php echo date('F Y', strtotime($created_at)); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-shield-alt"></i>
                        <div class="info-label">Account Status</div>
                        <div class="info-value">Active</div>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['likes_count'] ?? 0; ?></div>
                        <div class="stat-label">Articles Liked</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['comments_count'] ?? 0; ?></div>
                        <div class="stat-label">Comments Made</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['shares_count'] ?? 0; ?></div>
                        <div class="stat-label">Articles Shared</div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="index.html" class="btn btn-primary">
                        <i class="fas fa-home"></i>
                        Back to News
                    </a>
                    <button onclick="openEditModal()" class="btn btn-secondary">
                        <i class="fas fa-edit"></i>
                        Edit Profile
                    </button>
                    <button onclick="openPictureModal()" class="btn btn-secondary">
                        <i class="fas fa-camera"></i>
                        Change Photo
                    </button>
                    <button onclick="openDeleteModal()" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete Account
                    </button>
                    <a href="logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>

            <div class="interactions-section">
                <h3 class="section-title">Recent Activity</h3>
                
                <?php if (empty($recent_interactions)): ?>
                    <div class="no-interactions">
                        <i class="fas fa-newspaper" style="font-size: 3rem; color: #ddd; margin-bottom: 20px;"></i>
                        <p>No interactions yet. Start reading and engaging with articles!</p>
                        <a href="index.html" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-newspaper"></i>
                            Browse Articles
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_interactions as $interaction): ?>
                        <div class="interaction-item">
                            <div class="interaction-header">
                                <div class="interaction-icon <?php echo $interaction['interaction_type']; ?>">
                                    <?php
                                    switch($interaction['interaction_type']) {
                                        case 'like':
                                            echo '<i class="fas fa-heart"></i>';
                                            break;
                                        case 'comment':
                                            echo '<i class="fas fa-comment"></i>';
                                            break;
                                        case 'share':
                                            echo '<i class="fas fa-share"></i>';
                                            break;
                                    }
                                    ?>
                                </div>
                                <div class="interaction-content">
                                    <div class="interaction-title">
                                        <?php echo ucfirst($interaction['interaction_type']); ?>d: 
                                        <?php echo htmlspecialchars($interaction['article_title'] ?? 'Unknown Article'); ?>
                                    </div>
                                    <div class="interaction-meta">
                                        <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($interaction['category'] ?? 'General'); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($interaction['created_at'])); ?></span>
                                    </div>
                                    <?php if ($interaction['interaction_type'] === 'comment' && !empty($interaction['content'])): ?>
                                        <div class="interaction-text">
                                            "<?php echo htmlspecialchars($interaction['content']); ?>"
                                        </div>
                                    <?php elseif ($interaction['interaction_type'] === 'share' && !empty($interaction['content'])): ?>
                                        <div class="interaction-text">
                                            Shared on: <?php echo htmlspecialchars($interaction['content']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Ntwari Fiacre | All rights reserved</p>
        </footer>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Profile</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    
                    <hr style="margin: 30px 0; border: 1px solid #eee;">
                    <h4 style="margin-bottom: 20px; color: #667eea;">Change Password (Optional)</h4>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password to change">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="New password (min 6 chars)">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Picture Modal -->
    <div id="pictureModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-camera"></i> Change Profile Picture</h3>
                <span class="close" onclick="closeModal('pictureModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_picture">
                    
                    <div class="form-group">
                        <label for="profile_picture">Select New Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Supported formats: JPEG, PNG, GIF. Maximum size: 5MB
                        </small>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Picture
                        </button>
                        <button type="button" onclick="closeModal('pictureModal')" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: #dc3545;">
                <h3><i class="fas fa-exclamation-triangle"></i> Delete Account</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="alert alert-error">
                    <strong>Warning!</strong> This action cannot be undone. All your data, including comments, likes, and shares will be permanently deleted.
                </div>
                
                <form method="POST" action="" onsubmit="return confirmDelete()">
                    <input type="hidden" name="action" value="delete_account">
                    
                    <div class="form-group">
                        <label for="delete_password">Enter Your Password to Confirm</label>
                        <input type="password" id="delete_password" name="delete_password" required placeholder="Your current password">
                    </div>
                    
                    <div class="form-group">
                        <label for="delete_reason">Reason for Leaving (Optional)</label>
                        <textarea id="delete_reason" name="delete_reason" rows="3" placeholder="Help us improve by telling us why you're leaving..."></textarea>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete My Account
                        </button>
                        <button type="button" onclick="closeModal('deleteModal')" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
        }

        function openPictureModal() {
            document.getElementById('pictureModal').style.display = 'block';
        }

        function openDeleteModal() {
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = ['editModal', 'pictureModal', 'deleteModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Confirm delete function
        function confirmDelete() {
            return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!');
        }

        // Password validation
        document.getElementById('new_password').addEventListener('input', function() {
            const newPassword = this.value;
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword.length > 0 && newPassword.length < 6) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ddd';
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0 && newPassword !== confirmPassword) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ddd';
            }
        });

        // File upload preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You could add a preview here if desired
                    console.log('File selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
