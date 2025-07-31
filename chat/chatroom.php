<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: ../login.html");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update user's online status
$user_id = $_SESSION['user_id'];
$update_online = "INSERT INTO online_users (user_id) VALUES (?) ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP";
$stmt = $conn->prepare($update_online);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Get user info
$user_query = "SELECT fullname, username, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_info = $user_result->fetch_assoc();

// Create default rooms if none exist and this is the first user accessing chat
$check_rooms = "SELECT COUNT(*) as room_count FROM chat_rooms";
$room_count_result = $conn->query($check_rooms);
$room_count = $room_count_result->fetch_assoc()['room_count'];

if ($room_count == 0) {
    // Create default rooms using current user as creator
    $default_rooms = [
        ['General Chat', 'Main chat room for all users'],
        ['Tech Discussion', 'Discuss technology and programming'],
        ['Random', 'Off-topic conversations']
    ];
    
    foreach ($default_rooms as $room) {
        $insert_room = "INSERT INTO chat_rooms (name, description, created_by) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_room);
        $stmt->bind_param("ssi", $room[0], $room[1], $user_id);
        $stmt->execute();
    }
}

// Get available chat rooms
$rooms_query = "SELECT cr.*, u.username as creator_name, 
                COUNT(cp.user_id) as participant_count
                FROM chat_rooms cr 
                LEFT JOIN users u ON cr.created_by = u.id 
                LEFT JOIN chat_participants cp ON cr.id = cp.room_id 
                GROUP BY cr.id 
                ORDER BY cr.created_at DESC";
$rooms_result = $conn->query($rooms_query);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_message':
            $room_id = $_POST['room_id'];
            $message = trim($_POST['message']);
            
            if (!empty($message)) {
                $insert_msg = "INSERT INTO chat_messages (room_id, user_id, message) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_msg);
                $stmt->bind_param("iis", $room_id, $user_id, $message);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
                }
            }
            exit;
            
        case 'get_messages':
            $room_id = $_POST['room_id'];
            $last_id = $_POST['last_id'] ?? 0;
            
            $messages_query = "SELECT cm.*, u.username, u.fullname, u.profile_picture 
                              FROM chat_messages cm 
                              JOIN users u ON cm.user_id = u.id 
                              WHERE cm.room_id = ? AND cm.id > ? 
                              ORDER BY cm.created_at ASC";
            $stmt = $conn->prepare($messages_query);
            $stmt->bind_param("ii", $room_id, $last_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            
            echo json_encode($messages);
            exit;
            
        case 'join_room':
            $room_id = $_POST['room_id'];
            
            $join_query = "INSERT IGNORE INTO chat_participants (room_id, user_id) VALUES (?, ?)";
            $stmt = $conn->prepare($join_query);
            $stmt->bind_param("ii", $room_id, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
            
        case 'get_online_users':
            $online_query = "SELECT u.id, u.username, u.fullname, u.profile_picture 
                            FROM users u 
                            JOIN online_users ou ON u.id = ou.user_id 
                            WHERE ou.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                            ORDER BY u.username";
            $result = $conn->query($online_query);
            
            $online_users = [];
            while ($row = $result->fetch_assoc()) {
                $online_users[] = $row;
            }
            
            echo json_encode($online_users);
            exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Room - Organization Chat</title>
    <link rel="stylesheet" href="chat.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <div class="user-info">
                <img src="<?php echo $user_info['profile_picture'] ? '../' . $user_info['profile_picture'] : '../uploads/default-avatar.png'; ?>" 
                     alt="Profile" class="profile-pic">
                <div class="user-details">
                    <span class="fullname"><?php echo htmlspecialchars($user_info['fullname']); ?></span>
                    <span class="username">@<?php echo htmlspecialchars($user_info['username']); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <button onclick="toggleFileUpload()" class="btn-icon" title="Upload File">
                    <i class="fas fa-paperclip"></i>
                </button>
                <button onclick="toggleOnlineUsers()" class="btn-icon" title="Online Users">
                    <i class="fas fa-users"></i>
                </button>
                <a href="../logout.php" class="btn-icon" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main">
            <!-- Sidebar -->
            <div class="chat-sidebar">
                <div class="sidebar-header">
                    <h3>Chat Rooms</h3>
                    <button onclick="createRoom()" class="btn-create">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="rooms-list">
                    <?php while ($room = $rooms_result->fetch_assoc()): ?>
                        <div class="room-item" onclick="joinRoom(<?php echo $room['id']; ?>)" data-room-id="<?php echo $room['id']; ?>">
                            <div class="room-info">
                                <span class="room-name"><?php echo htmlspecialchars($room['name']); ?></span>
                                <span class="room-participants"><?php echo $room['participant_count']; ?> members</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Chat Content -->
            <div class="chat-content">
                <div class="chat-messages" id="chatMessages">
                    <div class="welcome-message">
                        <h2>Welcome to the Chat!</h2>
                        <p>Select a room to start chatting</p>
                    </div>
                </div>
                
                <!-- Message Input -->
                <div class="message-input-container" id="messageInputContainer" style="display: none;">
                    <div class="file-upload-area" id="fileUploadArea" style="display: none;">
                        <form id="fileUploadForm" enctype="multipart/form-data">
                            <input type="file" id="fileInput" name="file" accept="*/*">
                            <button type="button" onclick="uploadFile()" class="btn-upload">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                            <button type="button" onclick="toggleFileUpload()" class="btn-cancel">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="message-input">
                        <input type="text" id="messageInput" placeholder="Type your message..." maxlength="1000">
                        <button onclick="sendMessage()" class="btn-send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Online Users Panel -->
            <div class="online-users-panel" id="onlineUsersPanel" style="display: none;">
                <div class="panel-header">
                    <h3>Online Users</h3>
                    <button onclick="toggleOnlineUsers()" class="btn-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="online-users-list" id="onlineUsersList">
                    <!-- Online users will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden inputs -->
    <input type="hidden" id="currentUserId" value="<?php echo $_SESSION['user_id']; ?>">
    <input type="hidden" id="currentRoomId" value="">
    <input type="hidden" id="lastMessageId" value="0">

    <script src="chat_fixed.js"></script>
    <script src="enhanced_chat.js"></script>
</body>
</html>