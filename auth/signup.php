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
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Input validation
    $errors = [];

    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }

    $check_stmt->close();

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        $sql = "INSERT INTO users (fullname, email, username, password, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $fullname, $email, $username, $hashed_password);

        if ($stmt->execute()) {
            // Set session variables
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;

            // Return JSON response for AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => 'Registration successful']);
                exit;
            }

            // Redirect to profile page
            //header("Location: profile.php");
            echo "<script>
    alert('Login successful! Redirecting to your profile...');
    setTimeout(function() {
        window.location.href = 'profile.php';
    }, 1000);
</script>";
            echo "<a href='/auth/user_management/profile.php'></a>";
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }

        $stmt->close();
    }

    //Return errors for AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // If not AJAX, redirect back with errors
    header("Location: signup.html?error=" . urlencode(implode(', ', $errors)));
    exit;
}

$conn->close();

//profile