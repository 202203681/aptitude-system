<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aptitude_system');

// Security settings
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// System settings
define('SYSTEM_NAME', 'Smart Aptitude Testing System');
define('SYSTEM_VERSION', '2.0');
define('OFFLINE_MODE_ENABLED', true);

// Establish connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout check
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Function to log system actions
function logSystemAction($conn, $user_id, $action, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $details_json = $details ? json_encode($details) : null;
    
    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $action, $details_json, $ip, $user_agent);
    $stmt->execute();
}

// Function to check rate limiting
function checkRateLimit($conn, $user_id, $action, $limit = 100, $window = 3600) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM system_logs WHERE user_id = ? AND action = ? AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->bind_param("isi", $user_id, $action, $window);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'] < $limit;
}
?>