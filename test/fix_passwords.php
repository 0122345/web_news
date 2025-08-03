<?php
// Script to fix plain text passwords in database
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

echo "<h2>Password Fix Utility</h2>";

// Check current password format
$result = $conn->query("SELECT id, username, password FROM users LIMIT 5");
echo "<h3>Current Password Status:</h3>";

$needsFix = false;
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password Format</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $isHashed = substr($row['password'], 0, 4) === '$2y$';
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . ($isHashed ? "Hashed (✅)" : "Plain Text (❌)") . "</td>";
        echo "<td>" . ($isHashed ? "OK" : "NEEDS FIX") . "</td>";
        echo "</tr>";
        
        if (!$isHashed) {
            $needsFix = true;
        }
    }
    echo "</table>";
} else {
    echo "No users found.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fix_passwords'])) {
    echo "<h3>Fixing Passwords...</h3>";
    
    // Get all users with plain text passwords
    $result = $conn->query("SELECT id, username, password FROM users");
    $fixed = 0;
    
    while ($row = $result->fetch_assoc()) {
        $isHashed = substr($row['password'], 0, 4) === '$2y$';
        
        if (!$isHashed) {
            // Hash the plain text password
            $hashedPassword = password_hash($row['password'], PASSWORD_DEFAULT);
            
            // Update in database
            $updateSql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $hashedPassword, $row['id']);
            
            if ($stmt->execute()) {
                echo "✅ Fixed password for user: " . htmlspecialchars($row['username']) . "<br>";
                $fixed++;
            } else {
                echo "❌ Failed to fix password for user: " . htmlspecialchars($row['username']) . "<br>";
            }
        }
    }
    
    echo "<br><strong>Fixed $fixed passwords.</strong><br>";
    echo "<a href='debug_login.php'>← Test Login Again</a><br>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_test_user'])) {
    echo "<h3>Creating Test User...</h3>";
    
    $testUsername = "testuser";
    $testEmail = "test@example.com";
    $testPassword = "password123";
    $testFullname = "Test User";
    
    // Check if test user already exists
    $checkSql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ss", $testUsername, $testEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "❌ Test user already exists.<br>";
    } else {
        // Create test user
        $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
        $insertSql = "INSERT INTO users (fullname, email, username, password, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("ssss", $testFullname, $testEmail, $testUsername, $hashedPassword);
        
        if ($stmt->execute()) {
            echo "✅ Test user created successfully!<br>";
            echo "<strong>Login credentials:</strong><br>";
            echo "Username: <code>testuser</code><br>";
            echo "Password: <code>password123</code><br>";
            echo "<a href='login.html'>← Try Login</a><br>";
        } else {
            echo "❌ Failed to create test user: " . $conn->error . "<br>";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Password Fix Utility</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { margin: 10px 0; }
        th, td { padding: 8px; text-align: left; }
        .action-form { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .fix-btn { background: #dc3545; color: white; border: none; border-radius: 5px; }
        .create-btn { background: #28a745; color: white; border: none; border-radius: 5px; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <?php if ($needsFix): ?>
    <div class="action-form">
        <h3>⚠️ Password Fix Required</h3>
        <p>Some users have plain text passwords. This is a security risk and will prevent login.</p>
        <form method="POST">
            <button type="submit" name="fix_passwords" class="fix-btn">Fix All Passwords</button>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="action-form">
        <h3>Create Test User</h3>
        <p>Create a test user account to verify login functionality.</p>
        <form method="POST">
            <button type="submit" name="create_test_user" class="create-btn">Create Test User</button>
        </form>
    </div>
    
    <p><a href="debug_login.php">← Back to Debug</a></p>
    <p><a href="/auth/auth/login.html">← Back to Login</a></p>
</body>
</html>