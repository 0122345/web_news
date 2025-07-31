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
$dbname = "user_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$article_id = intval($input['article_id']);
$action = $input['action']; // 'like' or 'unlike'
$user_id = $_SESSION['user_id'];

if ($action === 'like') {
    // Add like
    $insert_sql = "INSERT IGNORE INTO likes (article_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ii", $article_id, $user_id);
    $success = $stmt->execute();
} else {
    // Remove like
    $delete_sql = "DELETE FROM likes WHERE article_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $article_id, $user_id);
    $success = $stmt->execute();
}

// Get updated like count
$count_sql = "SELECT COUNT(*) as total FROM likes WHERE article_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $article_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();

// Update article likes count
$update_sql = "UPDATE articles SET likes_count = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $count_data['total'], $article_id);
$update_stmt->execute();

echo json_encode([
    'success' => $success,
    'total_likes' => $count_data['total']
]);

$stmt->close();
$count_stmt->close();
$update_stmt->close();
$conn->close();
?>