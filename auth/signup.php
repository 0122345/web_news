<?php
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

// Function to generate verification token
function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

// Function to send verification email (placeholder)
function sendVerificationEmail($email, $fullname, $token) {
    // In a real application, you would send an actual email here
    // For now, we'll just log it or return the verification link
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/auth/verify-email.php?token=" . $token;
    
    // Log the verification link (in production, send via email)
    error_log("Verification link for $email: $verification_link");
    
    return true;
}

// Function to assign default role to user
function assignDefaultRole($user_id, $role_name) {
    global $conn;
    
    // Get role ID
    $role_sql = "SELECT id FROM roles WHERE name = ?";
    $role_stmt = $conn->prepare($role_sql);
    $role_stmt->bind_param("s", $role_name);
    $role_stmt->execute();
    $role_result = $role_stmt->get_result();
    
    if ($role_result->num_rows > 0) {
        $role = $role_result->fetch_assoc();
        
        // Assign role to user
        $assign_sql = "INSERT INTO user_roles (user_id, role_id, is_primary, assigned_at) VALUES (?, ?, TRUE, NOW())";
        $assign_stmt = $conn->prepare($assign_sql);
        $assign_stmt->bind_param("ii", $user_id, $role['id']);
        $assign_stmt->execute();
        $assign_stmt->close();
    }
    
    $role_stmt->close();
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

// Function to validate password strength
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $ecosystem_role = isset($_POST['ecosystem_role']) ? $_POST['ecosystem_role'] : 'member';
    $department = trim($_POST['department']) ?: null;
    $newsletter = isset($_POST['newsletter']);
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Input validation
    $errors = [];

    // Validate full name
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    } elseif (strlen($fullname) < 2) {
        $errors[] = "Full name must be at least 2 characters";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
        $errors[] = "Full name should only contain letters and spaces";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username must be less than 50 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } else {
        $password_errors = validatePasswordStrength($password);
        $errors = array_merge($errors, $password_errors);
    }

    // Validate password confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Validate ecosystem role
    $valid_roles = ['member', 'contributor', 'coordinator', 'observer'];
    if (!in_array($ecosystem_role, $valid_roles)) {
        $ecosystem_role = 'member'; // Default to member if invalid
    }

    // Check if username or email already exists
    if (empty($errors)) {
        $check_sql = "SELECT id, username, email FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $existing_user = $check_result->fetch_assoc();
            if ($existing_user['username'] === $username) {
                $errors[] = "Username is already taken";
            }
            if ($existing_user['email'] === $email) {
                $errors[] = "Email address is already registered";
            }
        }
        $check_stmt->close();
    }

    // If no errors, create the user
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate email verification token
        $verification_token = generateVerificationToken();
        
        // Set user preferences
        $preferences = json_encode([
            'newsletter' => $newsletter,
            'theme' => 'light',
            'notifications' => [
                'email' => true,
                'browser' => true,
                'mobile' => false
            ],
            'privacy' => [
                'profile_visibility' => 'public',
                'show_email' => false,
                'show_department' => true
            ]
        ]);

        // Insert user into database
        $sql = "INSERT INTO users (fullname, email, username, password, ecosystem_role, department, 
                email_verification_token, preferences, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $fullname, $email, $username, $hashed_password, 
                         $ecosystem_role, $department, $verification_token, $preferences);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Assign default role based on ecosystem role selection
            assignDefaultRole($user_id, $ecosystem_role);
            
            // Log user registration
            logUserActivity($user_id, 'user_registered', $ip_address, $user_agent);
            
            // Send verification email
            sendVerificationEmail($email, $fullname, $verification_token);
            
            // Set session variables for immediate login (optional - you might want email verification first)
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['logged_in'] = true;
            
            // Get user roles for session
            $roles_sql = "SELECT r.id, r.name, r.display_name, r.color, r.icon, r.level, ur.is_primary
                         FROM user_roles ur 
                         JOIN roles r ON ur.role_id = r.id 
                         WHERE ur.user_id = ?";
            $roles_stmt = $conn->prepare($roles_sql);
            $roles_stmt->bind_param("i", $user_id);
            $roles_stmt->execute();
            $roles_result = $roles_stmt->get_result();
            $roles = $roles_result->fetch_all(MYSQLI_ASSOC);
            $roles_stmt->close();
            
            $_SESSION['roles'] = $roles;
            $_SESSION['primary_role'] = !empty($roles) ? $roles[0] : null;
            
            // Get user permissions
            $permissions_sql = "SELECT DISTINCT p.name, p.display_name, p.module
                               FROM user_roles ur 
                               JOIN role_permissions rp ON ur.role_id = rp.role_id 
                               JOIN permissions p ON rp.permission_id = p.id 
                               WHERE ur.user_id = ?";
            $permissions_stmt = $conn->prepare($permissions_sql);
            $permissions_stmt->bind_param("i", $user_id);
            $permissions_stmt->execute();
            $permissions_result = $permissions_stmt->get_result();
            $permissions = $permissions_result->fetch_all(MYSQLI_ASSOC);
            $permissions_stmt->close();
            
            $_SESSION['permissions'] = $permissions;

            // Redirect to homepage after successful signup
            $redirect_url = '../home/index.html';

            if (isAjaxRequest()) {
                sendJsonResponse(true, "Welcome to the EcoComm ecosystem! Your account has been created successfully.", [
                    'redirect' => $redirect_url,
                    'user' => [
                        'id' => $user_id,
                        'username' => $username,
                        'fullname' => $fullname,
                        'ecosystem_role' => $ecosystem_role,
                        'primary_role' => $_SESSION['primary_role']
                    ]
                ]);
            } else {
                // JavaScript redirect with success message
                echo "<script>
                    alert('Welcome to the EcoComm ecosystem! Your account has been created successfully.');
                    setTimeout(function() {
                        window.location.href = '$redirect_url';
                    }, 1500);
                </script>";
                exit;
            }
        } else {
            $errors[] = "Registration failed. Please try again.";
            
            // Log registration failure
            $error_details = "Registration failed for email: $email, username: $username";
            $log_sql = "INSERT INTO security_logs (ip_address, user_agent, activity, details, created_at) 
                       VALUES (?, ?, 'registration_failed', ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("sss", $ip_address, $user_agent, $error_details);
            $log_stmt->execute();
            $log_stmt->close();
        }

        $stmt->close();
    }

    // Return errors for AJAX or redirect with errors profile
    if (!empty($errors)) {
        if (isAjaxRequest()) {
            sendJsonResponse(false, implode(', ', $errors));
        } else {
            header("Location: signup.html?error=" . urlencode(implode(', ', $errors)));
            exit;
        }
    }
}

$conn->close();

// If we reach here and it's not a POST request, redirect to signup page
header("Location: signup.html");
exit;
?>