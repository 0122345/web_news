<?php
// Debug script to check login issues
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

echo "<h2>Login Debug Information</h2>";

// Check if users table exists and has data
echo "<h3>1. Users Table Check:</h3>";
$result = $conn->query("SELECT COUNT(*) as user_count FROM users");
if ($result) {
    $count = $result->fetch_assoc()['user_count'];
    echo "Total users in database: <strong>$count</strong><br>";
} else {
    echo "Error checking users table: " . $conn->error . "<br>";
}

// Show first few users (without passwords)
echo "<h3>2. Sample Users:</h3>";
$result = $conn->query("SELECT id, fullname, username, email, created_at FROM users LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No users found or error: " . $conn->error . "<br>";
}

// Check password format for first user
echo "<h3>3. Password Format Check:</h3>";
$result = $conn->query("SELECT id, username, password FROM users LIMIT 1");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Sample user: <strong>" . htmlspecialchars($user['username']) . "</strong><br>";
    echo "Password starts with: <strong>" . substr($user['password'], 0, 10) . "...</strong><br>";
    echo "Password length: <strong>" . strlen($user['password']) . "</strong> characters<br>";
    
    if (substr($user['password'], 0, 4) === '$2y$') {
        echo "✅ Password is properly hashed (bcrypt)<br>";
    } else {
        echo "❌ Password might not be properly hashed<br>";
    }
}

// Test form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h3>4. Login Attempt Debug:</h3>";
    $username_email = $_POST['username'];
    $password_input = $_POST['password'];
    
    echo "Attempting login with:<br>";
    echo "Username/Email: <strong>" . htmlspecialchars($username_email) . "</strong><br>";
    echo "Password length: <strong>" . strlen($password_input) . "</strong> characters<br>";
    
    // Check if user exists
    $sql = "SELECT id, fullname, username, email, password FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        echo "✅ User found: <strong>" . htmlspecialchars($user['username']) . "</strong><br>";
        
        // Test password verification
        if (password_verify($password_input, $user['password'])) {
            echo "✅ Password verification successful!<br>";
            echo "Login should work. Check for JavaScript errors or redirects.<br>";
        } else {
            echo "❌ Password verification failed!<br>";
            echo "The password you entered doesn't match the stored hash.<br>";
            
            // Test if password is stored as plain text (old data)
            if ($password_input === $user['password']) {
                echo "⚠️ Password is stored as plain text! This is a security issue.<br>";
                echo "You need to re-hash your passwords.<br>";
            }
        }
    } else {
        echo "❌ User not found with username/email: <strong>" . htmlspecialchars($username_email) . "</strong><br>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { margin: 10px 0; }
        th, td { padding: 8px; text-align: left; }
        .debug-form { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="debug-form">
        <h3>Test Login Here:</h3>
        <form method="POST">
            <label>Username or Email:</label><br>
            <input type="text" name="username" required style="width: 300px; padding: 5px;"><br><br>
            
            <label>Password:</label><br>
            <input type="password" name="password" required style="width: 300px; padding: 5px;"><br><br>
            
            <button type="submit" style="padding: 10px 20px;">Test Login</button>
        </form>
    </div>
    
    <p><a href="/auth/auth/login.html">← Back to Login Page</a></p>
</body>
</html>