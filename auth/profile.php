<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.html");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fiacomm"; // Updated to match recent edits

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Fiacomm Ecosystem</title>
    <link rel="stylesheet" href="ecosystem-auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 2rem;
        }
        
        .profile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--eco-gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
        }
        
        .profile-name {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: black;
            margin-bottom: 0.5rem;
        }
        
        .profile-role {
            color: var(--eco-gray-600);
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--eco-primary);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--eco-gray-600);
        }
        
        .profile-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .profile-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: gray;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--eco-gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .info-value {
            font-size: 1rem;
            color: black;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-secondary {
            background: var(--eco-white);
            color: var(--eco-gray-700);
            border: 2px solid var(--eco-gray-200);
        }
        
        .btn-secondary:hover {
            border-color: var(--eco-primary);
            color: var(--eco-primary);
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['fullname']); ?></h1>
            <p class="profile-role"><?php echo htmlspecialchars($user['ecosystem_role']); ?></p>
            
            <div class="profile-stats">
                <div class="stat">
                    <div class="stat-value"><?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                    <div class="stat-label">Member Since</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo $user['last_login'] ? date('M j', strtotime($user['last_login'])) : 'Never'; ?></div>
                    <div class="stat-label">Last Login</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo ucfirst($user['status']); ?></div>
                    <div class="stat-label">Status</div>
                </div>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="profile-section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Personal Information
                </h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['fullname']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value">@<?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?php echo $user['department'] ? htmlspecialchars($user['department']) : 'Not specified'; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Location</div>
                        <div class="info-value"><?php echo $user['location'] ? htmlspecialchars($user['location']) : 'Not specified'; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not specified'; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="profile-section">
                <h2 class="section-title">
                    <i class="fas fa-shield-alt"></i>
                    Account Security
                </h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Email Verified</div>
                        <div class="info-value">
                            <?php if ($user['email_verified']): ?>
                                <span style="color: var(--eco-success);"><i class="fas fa-check-circle"></i> Verified</span>
                            <?php else: ?>
                                <span style="color: var(--eco-warning);"><i class="fas fa-exclamation-circle"></i> Not Verified</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Account Created</div>
                        <div class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($user['created_at'])); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Last Updated</div>
                        <div class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($user['updated_at'])); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($user['bio']): ?>
            <div class="profile-section">
                <h2 class="section-title">
                    <i class="fas fa-quote-left"></i>
                    Bio
                </h2>
                <p style="color: var(--eco-gray-700); line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <button class="btn btn-secondary" onclick="alert('Edit profile functionality coming soon!')">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </button>
                <a href="logout.php" class="btn btn-secondary" style="border-color: var(--eco-error); color: var(--eco-error);">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
</body>
</html>