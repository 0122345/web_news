<?php
session_start();

// Destroy all session data
session_destroy();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

header("Location: login.html");
exit;
?>