<?php
// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

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
$dbname = "fiacomm";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if this is a file upload request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && isset($_POST['room_id'])) {
    $room_id = $_POST['room_id'];
    $file = $_FILES['file'];
    
    // Validate room access
    $check_participant = "SELECT role FROM chat_participants WHERE room_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_participant);
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $participant_result = $stmt->get_result();
    
    if ($participant_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'You are not a participant in this room']);
        exit;
    }
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit;
    }
    
    // Check file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
        exit;
    }
    
    // Get file info
    $original_name = $file['name'];
    $file_size = $file['size'];
    $file_type = $file['type'];
    $temp_path = $file['tmp_name'];
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../../uploads/chat_files/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $unique_name;
    
    // Move uploaded file
    if (move_uploaded_file($temp_path, $file_path)) {
        // Determine message type based on file type
        $message_type = 'file';
        if (strpos($file_type, 'image/') === 0) {
            $message_type = 'image';
        }
        
        // Insert file message into database
        $insert_msg = "INSERT INTO chat_messages (room_id, user_id, message, message_type, file_name, file_path, file_size, file_type) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_msg);
        $message_text = "Shared a file: " . $original_name;
        $stmt->bind_param("iissssss", $room_id, $user_id, $message_text, $message_type, $original_name, $file_path, $file_size, $file_type);
        
        if ($stmt->execute()) {
            // Update participant's last_seen
            $update_seen = "UPDATE chat_participants SET last_seen = NOW() WHERE room_id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_seen);
            $stmt->bind_param("ii", $room_id, $user_id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true, 
                'message' => 'File uploaded successfully',
                'message_id' => $conn->insert_id,
                'file_name' => $original_name,
                'file_size' => $file_size
            ]);
        } else {
            // Delete uploaded file if database insert fails
            unlink($file_path);
            echo json_encode(['success' => false, 'message' => 'Failed to save file message']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>