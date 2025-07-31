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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'];
    $room_id = $_POST['room_id'] ?? null;
    
    if (!$room_id) {
        echo json_encode(['success' => false, 'message' => 'Room ID required']);
        exit;
    }
    
    $file = $_FILES['file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit;
    }
    
    // Check file size (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
        exit;
    }
    
    // Get file info
    $originalName = $file['name'];
    $fileSize = $file['size'];
    $tmpName = $file['tmp_name'];
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Allowed file types
    $allowedTypes = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', // Images
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', // Documents
        'txt', 'rtf', 'csv', // Text files
        'zip', 'rar', '7z', // Archives
        'mp3', 'wav', 'ogg', // Audio
        'mp4', 'avi', 'mov', 'wmv' // Video
    ];
    
    if (!in_array($fileExtension, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'File type not allowed']);
        exit;
    }
    
    // Create upload directories if they don't exist
    $uploadDir = 'uploads/';
    $filesDir = $uploadDir . 'files/';
    $imagesDir = $uploadDir . 'images/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!file_exists($filesDir)) {
        mkdir($filesDir, 0755, true);
    }
    if (!file_exists($imagesDir)) {
        mkdir($imagesDir, 0755, true);
    }
    
    // Determine if it's an image
    $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $isImage = in_array($fileExtension, $imageTypes);
    
    // Generate unique filename
    $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetDir = $isImage ? $imagesDir : $filesDir;
    $targetPath = $targetDir . $uniqueName;
    
    // Move uploaded file
    if (move_uploaded_file($tmpName, $targetPath)) {
        // Determine message type
        $messageType = $isImage ? 'image' : 'file';
        
        // Insert into database
        $sql = "INSERT INTO chat_messages (room_id, user_id, message, message_type, file_name, file_path, file_size) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $message = $isImage ? "Shared an image: $originalName" : "Shared a file: $originalName";
        
        $stmt->bind_param("iissssi", $room_id, $user_id, $message, $messageType, $originalName, $targetPath, $fileSize);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'File uploaded successfully',
                'file_type' => $messageType,
                'file_name' => $originalName
            ]);
        } else {
            // Delete uploaded file if database insert fails
            unlink($targetPath);
            echo json_encode(['success' => false, 'message' => 'Failed to save file info']);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}

$conn->close();
?>