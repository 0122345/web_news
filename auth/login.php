<?php
// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fiacomm";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to check if request is AJAX
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Function to send JSON response
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// Function to log user activity
function logUserActivity($user_id, $activity, $ip_address, $user_agent) {
    global $conn;
    $sql = "INSERT INTO user_activity_logs (user_id, activity, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $activity, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

// Function to check account lockout
function isAccountLocked($user_id) {
    global $conn;
    $sql = "SELECT locked_until FROM users WHERE id = ? AND locked_until > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}

// Function to increment login attempts
function incrementLoginAttempts($user_id) {
    global $conn;
    $sql = "UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Check if we need to lock the account (5 failed attempts)
    $sql = "SELECT login_attempts FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user['login_attempts'] >= 5) {
        // Lock account for 30 minutes
        $sql = "UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        return true; // Account is now locked
    }
    
    return false;
}

// Function to reset login attempts
function resetLoginAttempts($user_id) {
    global $conn;
    $sql = "UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Function to get user roles and permissions
function getUserRolesAndPermissions($user_id) {
    global $conn;
    
    // Get user roles
    $roles_sql = "SELECT r.id, r.name, r.display_name, r.color, r.icon, r.level, ur.is_primary
                  FROM user_roles ur 
                  JOIN roles r ON ur.role_id = r.id 
                  WHERE ur.user_id = ? AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
                  ORDER BY r.level DESC, ur.is_primary DESC";
    $stmt = $conn->prepare($roles_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $roles_result = $stmt->get_result();
    $roles = $roles_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get user permissions
    $permissions_sql = "SELECT DISTINCT p.name, p.display_name, p.module
                        FROM user_roles ur 
                        JOIN role_permissions rp ON ur.role_id = rp.role_id 
                        JOIN permissions p ON rp.permission_id = p.id 
                        WHERE ur.user_id = ? AND (ur.expires_at IS NULL OR ur.expires_at > NOW())";
    $stmt = $conn->prepare($permissions_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $permissions_result = $stmt->get_result();
    $permissions = $permissions_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return ['roles' => $roles, 'permissions' => $permissions];
}

// Function to create user session
function createUserSession($user_id, $remember = false) {
    global $conn;
    
    $session_token = bin2hex(random_bytes(32));
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $expires_at = $remember ? date('Y-m-d H:i:s', strtotime('+30 days')) : date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $sql = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $session_token, $ip_address, $user_agent, $expires_at);
    $stmt->execute();
    $stmt->close();
    
    if ($remember) {
        setcookie('eco_session', $session_token, strtotime('+30 days'), "/", "", true, true);
    }
    
    return $session_token;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_email = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Input validation
    if (empty($username_email) || empty($password)) {
        if (isAjaxRequest()) {
            sendJsonResponse(false, "Please fill in all required fields");
        } else {
            header("Location: login.html?error=" . urlencode("Please fill in all required fields"));
            exit;
        }
    }

    // Check if user exists by username or email
    $sql = "SELECT id, fullname, username, email, password, status, email_verified, login_attempts, locked_until 
            FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Check if account is locked
        if (isAccountLocked($user['id'])) {
            if (isAjaxRequest()) {
                sendJsonResponse(false, "Account is temporarily locked due to multiple failed login attempts. Please try again later.");
            } else {
                header("Location: login.html?error=" . urlencode("Account is temporarily locked"));
                exit;
            }
        }
        
        // Skip email verification check for now (can be enabled later)
        // if (!$user['email_verified']) {
        //     if (isAjaxRequest()) {
        //         sendJsonResponse(false, "Please verify your email address before logging in.");
        //     } else {
        //         header("Location: login.html?error=" . urlencode("Email not verified"));
        //         exit;
        //     }
        // }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Reset login attempts on successful login
            resetLoginAttempts($user['id']);
            
            // Update last login
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Get user roles and permissions
            $user_data = getUserRolesAndPermissions($user['id']);
            
            // Set session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['roles'] = $user_data['roles'];
            $_SESSION['permissions'] = $user_data['permissions'];
            $_SESSION['primary_role'] = !empty($user_data['roles']) ? $user_data['roles'][0] : null;
            
            // Create session token
            $session_token = createUserSession($user['id'], $remember);
            $_SESSION['session_token'] = $session_token;
            
            // Log successful login
            logUserActivity($user['id'], 'login_success', $ip_address, $user_agent);
            
            // Redirect to homepage after successful login
            $redirect_url = '../home/index.html';
            
            if (isAjaxRequest()) {
                sendJsonResponse(true, "Welcome to the ecosystem! Redirecting...", [
                    'redirect' => $redirect_url,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'fullname' => $user['fullname'],
                        'primary_role' => $_SESSION['primary_role']
                    ]
                ]);
            } else {
                // JavaScript redirect with success message
                echo "<script>
                    alert('Welcome to the ecosystem! Redirecting to your dashboard...');
                    setTimeout(function() {
                        window.location.href = '$redirect_url';
                    }, 1000);
                </script>";
                exit;
            }
        } else {
            // Increment login attempts
            $is_locked = incrementLoginAttempts($user['id']);
            
            // Log failed login attempt
            logUserActivity($user['id'], 'login_failed', $ip_address, $user_agent);
            
            $error_message = $is_locked ? 
                "Too many failed attempts. Account has been locked for 30 minutes." : 
                "Invalid password. Please try again.";
            
            if (isAjaxRequest()) {
                sendJsonResponse(false, $error_message);
            } else {
                header("Location: login.html?error=" . urlencode($error_message));
                exit;
            }
        }
    } else {
        // User not found - log the attempt
        $activity_sql = "INSERT INTO security_logs (ip_address, user_agent, activity, details, created_at) 
                        VALUES (?, ?, 'login_attempt_invalid_user', ?, NOW())";
        $activity_stmt = $conn->prepare($activity_sql);
        $details = "Attempted login with: " . $username_email;
        $activity_stmt->bind_param("sss", $ip_address, $user_agent, $details);
        $activity_stmt->execute();
        $activity_stmt->close();
        
        if (isAjaxRequest()) {
            sendJsonResponse(false, "Invalid username/email or password");
        } else {
            header("Location: login.html?error=" . urlencode("Invalid credentials"));
            exit;
        }
    }
    
    $stmt->close();
}

$conn->close();

// If we reach here and it's not a POST request, redirect to login page
if (!isset($error)) {
    header("Location: login.html");
    exit;
}
?>