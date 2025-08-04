<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fiacomm";

$conn = new mysqli($servername, $username, $password, $dbname);

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Log the logout activity
    if (!$conn->connect_error) {
        $sql = "INSERT INTO user_activity_logs (user_id, activity, ip_address, user_agent, created_at) 
                VALUES (?, 'logout', ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
        
        // Remove user session from database if session token exists
        if (isset($_SESSION['session_token'])) {
            $delete_sql = "DELETE FROM user_sessions WHERE session_token = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            if ($delete_stmt) {
                $delete_stmt->bind_param("s", $_SESSION['session_token']);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
        }
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie if it exists
if (isset($_COOKIE['eco_session'])) {
    setcookie('eco_session', '', time() - 3600, "/");
}

// Destroy the session
session_destroy();

// Close database connection
if (!$conn->connect_error) {
    $conn->close();
}

// Redirect to login page with logout message
header("Location: login.html?message=" . urlencode("You have been successfully logged out from the ecosystem"));
exit;
?>