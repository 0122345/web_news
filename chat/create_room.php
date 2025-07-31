<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $room_name = trim($_POST['name'] ?? '');
    $room_description = trim($_POST['description'] ?? '');
    
    if (empty($room_name)) {
        echo json_encode(['success' => false, 'message' => 'Room name is required']);
        exit;
    }
    
    // Check if room name already exists
    $check_sql = "SELECT id FROM chat_rooms WHERE name = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $room_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Room name already exists']);
        exit;
    }
    
    // Create new room
    $insert_sql = "INSERT INTO chat_rooms (name, description, created_by) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssi", $room_name, $room_description, $user_id);
    
    if ($stmt->execute()) {
        $room_id = $conn->insert_id;
        
        // Auto-join creator to the room
        $join_sql = "INSERT INTO chat_participants (room_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($join_sql);
        $stmt->bind_param("ii", $room_id, $user_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Room created successfully',
            'room_id' => $room_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create room']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>