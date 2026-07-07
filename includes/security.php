<?php
/**
 * Security Module - CSRF, XSS Prevention, Rate Limiting
 */

class Security {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }
    
    /**
     * Sanitize input (XSS prevention)
     */
    public function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Check rate limit for login attempts
     */
    public function checkLoginAttempts($email) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts FROM system_logs 
                                      WHERE action = 'login_failed' 
                                      AND details LIKE ? 
                                      AND timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $email_json = '%' . $email . '%';
        $stmt->bind_param("s", $email_json);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['attempts'] < 5;
    }
    
    /**
     * Log failed login attempt
     */
    public function logFailedLogin($email) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $this->conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) 
                                      VALUES (0, 'login_failed', ?, ?)");
        $details = json_encode(['email' => $email]);
        $stmt->bind_param("ss", $details, $ip);
        $stmt->execute();
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        return $errors;
    }
    
    /**
     * Validate email format
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Generate secure session ID
     */
    public function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_activity'] = time();
    }
}
?>