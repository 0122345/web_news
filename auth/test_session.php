<?php
// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

// Set a test session variable
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;

// Set a test login session
if (isset($_GET['login'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = 'testuser';
    $_SESSION['fullname'] = 'Test User';
    $_SESSION['user_id'] = 999;
    echo "<p style='color: green;'>✅ Login session set!</p>";
}

if (isset($_GET['logout'])) {
    session_destroy();
    echo "<p style='color: red;'>❌ Session destroyed!</p>";
    echo "<script>setTimeout(() => window.location.href = 'test_session.php', 1000);</script>";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        button { background: #059669; color: white; border: none; padding: 10px 20px; margin: 5px; border-radius: 5px; cursor: pointer; }
        button:hover { background: #047857; }
        .status { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .logged-in { background: #d1fae5; color: #065f46; }
        .logged-out { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <h1>Session Test Page</h1>
    
    <div class="info">
        <h3>Session Info:</h3>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <p><strong>Session Status:</strong> <?php echo session_status(); ?></p>
        <p><strong>Test Counter:</strong> <?php echo $_SESSION['test_counter']; ?></p>
        <p><strong>Session Path:</strong> <?php echo session_save_path(); ?></p>
        <p><strong>Cookie Path:</strong> <?php echo ini_get('session.cookie_path'); ?></p>
        <p><strong>Cookie Domain:</strong> <?php echo ini_get('session.cookie_domain'); ?></p>
    </div>
    
    <div class="info">
        <h3>Login Status:</h3>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <div class="status logged-in">
                <strong>✅ LOGGED IN</strong><br>
                Username: <?php echo $_SESSION['username'] ?? 'N/A'; ?><br>
                Full Name: <?php echo $_SESSION['fullname'] ?? 'N/A'; ?><br>
                User ID: <?php echo $_SESSION['user_id'] ?? 'N/A'; ?>
            </div>
        <?php else: ?>
            <div class="status logged-out">
                <strong>❌ NOT LOGGED IN</strong>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="info">
        <h3>Actions:</h3>
        <button onclick="window.location.href='test_session.php'">Refresh Page</button>
        <button onclick="window.location.href='test_session.php?login=1'">Set Login Session</button>
        <button onclick="window.location.href='test_session.php?logout=1'">Destroy Session</button>
        <button onclick="testAPI()">Test API</button>
    </div>
    
    <div class="info">
        <h3>API Test Result:</h3>
        <div id="apiResult">Click "Test API" to check session API</div>
    </div>
    
    <div class="info">
        <h3>All Session Data:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>

    <script>
        async function testAPI() {
            try {
                const response = await fetch('/auth/auth/debug_session.php');
                const data = await response.json();
                document.getElementById('apiResult').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('apiResult').innerHTML = '<span style="color: red;">Error: ' + error.message + '</span>';
            }
        }
    </script>
</body>
</html>