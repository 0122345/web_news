<?php
// Debug script to check login issues
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

echo "<h2>Database Connection Test</h2>";
echo "✅ Connected to database: $dbname<br><br>";

// Check if tables exist
$tables = ['users', 'roles', 'user_roles', 'permissions', 'role_permissions', 'user_activity_logs', 'security_logs'];

echo "<h3>Table Check:</h3>";
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' missing<br>";
    }
}

// Check users table
echo "<br><h3>Users in Database:</h3>";
$result = $conn->query("SELECT id, username, email, fullname, status, email_verified, created_at FROM users LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Status</th><th>Email Verified</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['fullname'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . ($row['email_verified'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No users found in database.";
}

// Check roles
echo "<br><h3>Roles in Database:</h3>";
$result = $conn->query("SELECT id, name, display_name FROM roles");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Display Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td><td>" . $row['display_name'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No roles found in database.";
}

// Test login with a sample user (if exists)
echo "<br><h3>Login Test:</h3>";
if (isset($_POST['test_username']) && isset($_POST['test_password'])) {
    $test_username = $_POST['test_username'];
    $test_password = $_POST['test_password'];
    
    $sql = "SELECT id, fullname, username, email, password, status, email_verified FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $test_username, $test_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        echo "✅ User found: " . $user['username'] . " (" . $user['email'] . ")<br>";
        echo "Email verified: " . ($user['email_verified'] ? 'Yes' : 'No') . "<br>";
        
        if (password_verify($test_password, $user['password'])) {
            echo "✅ Password verification successful!<br>";
        } else {
            echo "❌ Password verification failed!<br>";
            echo "Stored hash: " . substr($user['password'], 0, 20) . "...<br>";
        }
    } else {
        echo "❌ User not found with username/email: $test_username<br>";
    }
    $stmt->close();
}

$conn->close();
?>

<br><h3>Test Login:</h3>
<form method="POST">
    <label>Username/Email:</label><br>
    <input type="text" name="test_username" required><br><br>
    <label>Password:</label><br>
    <input type="password" name="test_password" required><br><br>
    <button type="submit">Test Login</button>
</form>

<br>
<p><strong>Instructions:</strong></p>
<ol>
    <li>First, make sure you've run the database_setup.sql script</li>
    <li>Create a test user through signup.html</li>
    <li>Use this form to test if login credentials work</li>
    <li>Check if all tables exist and have data</li>
</ol>