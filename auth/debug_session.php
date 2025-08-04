<?php
// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Debug information
$debug_info = [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'logged_in_check' => isset($_SESSION['logged_in']),
    'logged_in_value' => $_SESSION['logged_in'] ?? 'not set',
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'username' => $_SESSION['username'] ?? 'not set',
    'fullname' => $_SESSION['fullname'] ?? 'not set',
    'session_status' => session_status(),
    'cookie_params' => session_get_cookie_params()
];

// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // User is logged in, return user data
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'fullname' => $_SESSION['fullname'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'primary_role' => $_SESSION['primary_role'] ?? null,
            'roles' => $_SESSION['roles'] ?? [],
            'permissions' => $_SESSION['permissions'] ?? []
        ],
        'debug' => $debug_info
    ]);
} else {
    // User is not logged in
    echo json_encode([
        'logged_in' => false,
        'user' => null,
        'debug' => $debug_info
    ]);
}
?>