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

// Function to check if user has permission
function hasPermission($permission_name) {
    if (!isset($_SESSION['permissions'])) return false;
    
    foreach ($_SESSION['permissions'] as $permission) {
        if ($permission['name'] === $permission_name) {
            return true;
        }
    }
    return false;
}

// Get user's primary role info
$primary_role = $_SESSION['primary_role'] ?? null;
$user_roles = $_SESSION['roles'] ?? [];
$user_permissions = $_SESSION['permissions'] ?? [];

// Get some ecosystem stats (example)
$stats = [
    'total_members' => 0,
    'active_projects' => 0,
    'messages_today' => 0,
    'ecosystem_health' => 85
];

// Get member count
$member_sql = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
$member_result = $conn->query($member_sql);
if ($member_result) {
    $stats['total_members'] = $member_result->fetch_assoc()['count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoComm Dashboard - Ecosystem Communication Platform</title>
    <link rel="stylesheet" href="ecosystem-auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 2rem;
        }
        
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .welcome-text h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--eco-gray-900);
            margin-bottom: 0.5rem;
        }
        
        .welcome-text p {
            color: var(--eco-gray-600);
            font-size: 1.125rem;
        }
        
        .user-role-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-weight: 600;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .role-icon {
            font-size: 1.25rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-title {
            font-weight: 600;
            color: var(--eco-gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--eco-gray-900);
            margin-bottom: 0.5rem;
        }
        
        .stat-description {
            color: var(--eco-gray-600);
            font-size: 0.875rem;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .main-content, .sidebar-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--eco-gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .permissions-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--eco-gray-50);
            border-radius: 0.75rem;
            border: 1px solid var(--eco-gray-200);
        }
        
        .permission-icon {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: white;
            background: var(--eco-gradient-primary);
        }
        
        .permission-text {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--eco-gray-700);
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: var(--eco-gradient-primary);
            color: white;
            text-decoration: none;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.2);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.3);
            color: white;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ef4444, #f87171);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
        }
        
        .logout-btn:hover {
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .welcome-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="welcome-section">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h1>
                    <p>Your ecosystem dashboard is ready</p>
                </div>
                <?php if ($primary_role): ?>
                <div class="user-role-badge" style="background: <?php echo $primary_role['color']; ?>;">
                    <i class="<?php echo $primary_role['icon']; ?> role-icon"></i>
                    <span><?php echo htmlspecialchars($primary_role['display_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--eco-gradient-primary);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Community Members</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_members']); ?></div>
                <div class="stat-description">Active ecosystem participants</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--eco-gradient-secondary);">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-title">Active Projects</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['active_projects']); ?></div>
                <div class="stat-description">Ongoing collaborations</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--eco-gradient-sunset);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-title">Messages Today</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['messages_today']); ?></div>
                <div class="stat-description">Community interactions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--eco-success);">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="stat-title">Ecosystem Health</div>
                </div>
                <div class="stat-value"><?php echo $stats['ecosystem_health']; ?>%</div>
                <div class="stat-description">Overall system vitality</div>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="main-content">
                <h2 class="section-title">
                    <i class="fas fa-key"></i>
                    Your Permissions
                </h2>
                
                <?php if (!empty($user_permissions)): ?>
                <div class="permissions-list">
                    <?php foreach ($user_permissions as $permission): ?>
                    <div class="permission-item">
                        <div class="permission-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="permission-text">
                            <?php echo htmlspecialchars($permission['display_name']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p>No specific permissions assigned. Contact your administrator for access.</p>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-content">
                <h2 class="section-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h2>
                
                <div class="quick-actions">
                    <?php if (hasPermission('content.create')): ?>
                    <a href="#" class="action-btn">
                        <i class="fas fa-plus"></i>
                        Create Content
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('projects.view')): ?>
                    <a href="#" class="action-btn">
                        <i class="fas fa-project-diagram"></i>
                        View Projects
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('communication.send')): ?>
                    <a href="#" class="action-btn">
                        <i class="fas fa-envelope"></i>
                        Send Message
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('users.view')): ?>
                    <a href="#" class="action-btn">
                        <i class="fas fa-users"></i>
                        View Members
                    </a>
                    <?php endif; ?>
                    
                    <a href="profile.php" class="action-btn">
                        <i class="fas fa-user"></i>
                        Edit Profile
                    </a>
                    
                    <a href="logout.php" class="action-btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
                
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--eco-gray-200);">
                    <h3 style="font-size: 1rem; font-weight: 600; color: var(--eco-gray-700); margin-bottom: 1rem;">
                        Your Roles
                    </h3>
                    <?php foreach ($user_roles as $role): ?>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding: 0.75rem; background: var(--eco-gray-50); border-radius: 0.5rem;">
                        <i class="<?php echo $role['icon']; ?>" style="color: <?php echo $role['color']; ?>;"></i>
                        <span style="font-weight: 500; color: var(--eco-gray-700);">
                            <?php echo htmlspecialchars($role['display_name']); ?>
                        </span>
                        <?php if ($role['is_primary']): ?>
                        <span style="background: var(--eco-primary); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600;">
                            PRIMARY
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>