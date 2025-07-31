<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_email = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Check if user exists by username or email
    $sql = "SELECT id, fullname, username, email, password FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            
            // Set remember me cookie if checked
            if ($remember) {
                setcookie('remember_user', $user['id'], time() + (86400 * 30), "/"); // 30 days
            }
            
            // Return JSON response for AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => 'Login successful']);
                exit;
            }
            
            // Redirect to homepage
           // header("Location: index.html");
           echo "<script>
           alert('Login successful! Redirecting to your profile...');
           setTimeout(function() {
            window.location.href = 'profile.php';
            }, 1000);
           </script>";
           echo "<a href='profile.php'></a>";
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found";
    }
    
    // Return error for AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
    
    $stmt->close();
}

$conn->close();

//If there's an error and it's not AJAX, redirect back to login
if (isset($error)) {
    header("Location: login.html?error=" . urlencode($error));
    exit;
}
?>

 