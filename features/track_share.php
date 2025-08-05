<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fiacomm";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$article_id = intval($input['article_id']);
$platform = $input['platform'] ?? 'web';
$user_id = $_SESSION['user_id'];

// Insert share record
$insert_sql = "INSERT INTO shares (article_id, user_id, platform) VALUES (?, ?, ?)";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("iis", $article_id, $user_id, $platform);

if ($stmt->execute()) {
    // Update share count in articles table
    $count_sql = "SELECT COUNT(*) as total FROM shares WHERE article_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $article_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    
    $update_sql = "UPDATE articles SET shares_count = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $count_data['total'], $article_id);
    $update_stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Share tracked successfully']);
    
    $count_stmt->close();
    $update_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to track share']);
}

$stmt->close();
$conn->close();
?>