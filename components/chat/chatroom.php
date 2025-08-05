<?php
// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: ../../auth/login.html");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fiacomm";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update user's online status with enhanced tracking
$user_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

$update_online = "INSERT INTO online_users (user_id, ip_address, user_agent) 
                  VALUES (?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                  last_activity = CURRENT_TIMESTAMP, 
                  ip_address = VALUES(ip_address),
                  user_agent = VALUES(user_agent)";
$stmt = $conn->prepare($update_online);
$stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
$stmt->execute();

// Get user info with ecosystem role
$user_query = "SELECT u.fullname, u.username, u.avatar, u.ecosystem_role,
                      r.display_name as role_display_name, r.color as role_color, r.icon as role_icon
               FROM users u 
               LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_primary = TRUE
               LEFT JOIN roles r ON ur.role_id = r.id
               WHERE u.id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_info = $user_result->fetch_assoc();

// Get user's permissions for chat features
$permissions_query = "SELECT DISTINCT p.name 
                     FROM user_roles ur 
                     JOIN role_permissions rp ON ur.role_id = rp.role_id 
                     JOIN permissions p ON rp.permission_id = p.id 
                     WHERE ur.user_id = ? AND (ur.expires_at IS NULL OR ur.expires_at > NOW())";
$stmt = $conn->prepare($permissions_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$permissions_result = $stmt->get_result();
$user_permissions = [];
while ($perm = $permissions_result->fetch_assoc()) {
    $user_permissions[] = $perm['name'];
}

// Get available chat rooms based on user's role and permissions
$rooms_query = "SELECT cr.*, u.username as creator_name, u.fullname as creator_fullname,
                       COUNT(DISTINCT cp.user_id) as participant_count,
                       COUNT(DISTINCT cm.id) as message_count,
                       MAX(cm.created_at) as last_message_at,
                       cp_user.role as user_role_in_room,
                       CASE 
                           WHEN cr.room_type = 'private' THEN 
                               CASE WHEN cp_user.user_id IS NOT NULL THEN 1 ELSE 0 END
                           ELSE 1
                       END as can_access
                FROM chat_rooms cr 
                LEFT JOIN users u ON cr.created_by = u.id 
                LEFT JOIN chat_participants cp ON cr.id = cp.room_id 
                LEFT JOIN chat_messages cm ON cr.id = cm.room_id AND cm.is_deleted = FALSE
                LEFT JOIN chat_participants cp_user ON cr.id = cp_user.room_id AND cp_user.user_id = ?
                WHERE cr.is_active = TRUE
                GROUP BY cr.id 
                HAVING can_access = 1
                ORDER BY cr.room_type, cr.created_at DESC";
$stmt = $conn->prepare($rooms_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rooms_result = $stmt->get_result();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_message':
            $room_id = $_POST['room_id'];
            $message = trim($_POST['message']);
            $reply_to = $_POST['reply_to'] ?? null;
            
            if (!empty($message)) {
                // Check if user can send messages in this room
                $check_participant = "SELECT role FROM chat_participants WHERE room_id = ? AND user_id = ?";
                $stmt = $conn->prepare($check_participant);
                $stmt->bind_param("ii", $room_id, $user_id);
                $stmt->execute();
                $participant_result = $stmt->get_result();
                
                if ($participant_result->num_rows > 0) {
                    $insert_msg = "INSERT INTO chat_messages (room_id, user_id, message, reply_to_message_id) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert_msg);
                    $stmt->bind_param("iisi", $room_id, $user_id, $message, $reply_to);
                    
                    if ($stmt->execute()) {
                        // Update participant's last_seen
                        $update_seen = "UPDATE chat_participants SET last_seen = NOW() WHERE room_id = ? AND user_id = ?";
                        $stmt = $conn->prepare($update_seen);
                        $stmt->bind_param("ii", $room_id, $user_id);
                        $stmt->execute();
                        
                        echo json_encode(['success' => true, 'message_id' => $conn->insert_id]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'You are not a participant in this room']);
                }
            }
            exit;
            
        case 'get_messages':
            $room_id = $_POST['room_id'];
            $last_id = $_POST['last_id'] ?? 0;
            
            $messages_query = "SELECT cm.*, u.username, u.fullname, u.avatar,
                                      r.display_name as user_role, r.color as role_color,
                                      reply_msg.message as reply_message,
                                      reply_user.fullname as reply_user_name
                              FROM chat_messages cm 
                              JOIN users u ON cm.user_id = u.id 
                              LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_primary = TRUE
                              LEFT JOIN roles r ON ur.role_id = r.id
                              LEFT JOIN chat_messages reply_msg ON cm.reply_to_message_id = reply_msg.id
                              LEFT JOIN users reply_user ON reply_msg.user_id = reply_user.id
                              WHERE cm.room_id = ? AND cm.id > ? AND cm.is_deleted = FALSE
                              ORDER BY cm.created_at ASC";
            $stmt = $conn->prepare($messages_query);
            $stmt->bind_param("ii", $room_id, $last_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                // Get message reactions
                $reactions_query = "SELECT reaction, COUNT(*) as count 
                                   FROM chat_message_reactions 
                                   WHERE message_id = ? 
                                   GROUP BY reaction";
                $stmt_reactions = $conn->prepare($reactions_query);
                $stmt_reactions->bind_param("i", $row['id']);
                $stmt_reactions->execute();
                $reactions_result = $stmt_reactions->get_result();
                
                $reactions = [];
                while ($reaction = $reactions_result->fetch_assoc()) {
                    $reactions[] = $reaction;
                }
                $row['reactions'] = $reactions;
                
                $messages[] = $row;
            }
            
            echo json_encode($messages);
            exit;
            
        case 'join_room':
            $room_id = $_POST['room_id'];
            
            // Check if room exists and user can join
            $room_check = "SELECT room_type, max_participants FROM chat_rooms WHERE id = ? AND is_active = TRUE";
            $stmt = $conn->prepare($room_check);
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $room_result = $stmt->get_result();
            
            if ($room_result->num_rows > 0) {
                $room_data = $room_result->fetch_assoc();
                
                // Check current participant count
                $count_query = "SELECT COUNT(*) as count FROM chat_participants WHERE room_id = ?";
                $stmt = $conn->prepare($count_query);
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
                $count_result = $stmt->get_result();
                $current_count = $count_result->fetch_assoc()['count'];
                
                if ($room_data['max_participants'] && $current_count >= $room_data['max_participants']) {
                    echo json_encode(['success' => false, 'error' => 'Room is full']);
                    exit;
                }
                
                $join_query = "INSERT INTO chat_participants (room_id, user_id, joined_at, last_seen) 
                              VALUES (?, ?, NOW(), NOW()) 
                              ON DUPLICATE KEY UPDATE last_seen = NOW()";
                $stmt = $conn->prepare($join_query);
                $stmt->bind_param("ii", $room_id, $user_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to join room']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Room not found']);
            }
            exit;
            
        case 'get_online_users':
            $online_query = "SELECT u.id, u.username, u.fullname, u.avatar,
                                    r.display_name as role, r.color as role_color,
                                    ou.status, ou.last_activity
                            FROM users u 
                            JOIN online_users ou ON u.id = ou.user_id 
                            LEFT JOIN user_roles ur ON u.id = ur.user_id AND ur.is_primary = TRUE
                            LEFT JOIN roles r ON ur.role_id = r.id
                            WHERE ou.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                            ORDER BY ou.status, u.fullname";
            $result = $conn->query($online_query);
            
            $online_users = [];
            while ($row = $result->fetch_assoc()) {
                $online_users[] = $row;
            }
            
            echo json_encode($online_users);
            exit;
            
        case 'react_to_message':
            $message_id = $_POST['message_id'];
            $reaction = $_POST['reaction'];
            
            // Toggle reaction (add if not exists, remove if exists)
            $check_reaction = "SELECT id FROM chat_message_reactions WHERE message_id = ? AND user_id = ? AND reaction = ?";
            $stmt = $conn->prepare($check_reaction);
            $stmt->bind_param("iis", $message_id, $user_id, $reaction);
            $stmt->execute();
            $existing = $stmt->get_result();
            
            if ($existing->num_rows > 0) {
                // Remove reaction
                $remove_reaction = "DELETE FROM chat_message_reactions WHERE message_id = ? AND user_id = ? AND reaction = ?";
                $stmt = $conn->prepare($remove_reaction);
                $stmt->bind_param("iis", $message_id, $user_id, $reaction);
                $stmt->execute();
                echo json_encode(['success' => true, 'action' => 'removed']);
            } else {
                // Add reaction
                $add_reaction = "INSERT INTO chat_message_reactions (message_id, user_id, reaction) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($add_reaction);
                $stmt->bind_param("iis", $message_id, $user_id, $reaction);
                $stmt->execute();
                echo json_encode(['success' => true, 'action' => 'added']);
            }
            exit;
            
        case 'create_room':
            $room_name = trim($_POST['name']);
            $room_description = trim($_POST['description'] ?? '');
            $room_type = $_POST['type'] ?? 'public';
            
            if (!empty($room_name) && in_array('content.create', $user_permissions)) {
                $create_room = "INSERT INTO chat_rooms (name, description, created_by, room_type) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($create_room);
                $stmt->bind_param("ssis", $room_name, $room_description, $user_id, $room_type);
                
                if ($stmt->execute()) {
                    $new_room_id = $conn->insert_id;
                    
                    // Add creator as owner
                    $add_owner = "INSERT INTO chat_participants (room_id, user_id, role) VALUES (?, ?, 'owner')";
                    $stmt = $conn->prepare($add_owner);
                    $stmt->bind_param("ii", $new_room_id, $user_id);
                    $stmt->execute();
                    
                    echo json_encode(['success' => true, 'room_id' => $new_room_id]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to create room']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid room name or insufficient permissions']);
            }
            exit;
            
        case 'update_status':
            $status = $_POST['status'] ?? 'online';
            $valid_statuses = ['online', 'away', 'busy', 'invisible'];
            
            if (in_array($status, $valid_statuses)) {
                $update_status = "UPDATE online_users SET status = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_status);
                $stmt->bind_param("si", $status, $user_id);
                $stmt->execute();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid status']);
            }
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
                <img src="<?php echo $user_info['avatar'] ? '../../uploads/' . $user_info['avatar'] : '../../uploads/default-avatar.svg'; ?>" 
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
                <a href="../../auth/logout.php" class="btn-icon" title="Logout">
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
                        <input type="text" id="messageInput" placeholder="Type your message..." maxlength="1000" required>
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

    <script src="chat.js"></script>
</body>
</html>