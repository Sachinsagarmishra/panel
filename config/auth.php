<?php
session_start();

// Authentication configuration
define('AUTH_SESSION_NAME', 'freelance_user_session');
define('AUTH_COOKIE_NAME', 'freelance_remember');
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_LIFETIME', 2592000); // 30 days

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        if (isset($_SESSION[AUTH_SESSION_NAME])) {
            return $this->validateSession($_SESSION[AUTH_SESSION_NAME]);
        }
        
        if (isset($_COOKIE[AUTH_COOKIE_NAME])) {
            return $this->validateRememberToken($_COOKIE[AUTH_COOKIE_NAME]);
        }
        
        return false;
    }
    
    // Login user
    public function login($username, $password, $remember = false) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, password_hash, full_name, role, is_active 
                FROM users 
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                return $this->createSession($user, $remember);
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Auth error: " . $e->getMessage());
            return false;
        }
    }
    
    // Create user session
    private function createSession($user, $remember = false) {
        try {
            // Generate session token
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            
            // Store session in database
            $stmt = $this->pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'], 
                $sessionToken, 
                $expiresAt,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // Set session data
            $_SESSION[AUTH_SESSION_NAME] = $sessionToken;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Set remember me cookie if requested
            if ($remember) {
                $rememberToken = bin2hex(random_bytes(32));
                $rememberExpires = time() + REMEMBER_LIFETIME;
                
                setcookie(AUTH_COOKIE_NAME, $rememberToken, $rememberExpires, '/', '', false, true);
                
                // Store remember token in session record
                $updateStmt = $this->pdo->prepare("
                    UPDATE user_sessions SET remember_token = ? WHERE session_token = ?
                ");
                $updateStmt->execute([$rememberToken, $sessionToken]);
            }
            
            // Update last login
            $updateLoginStmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateLoginStmt->execute([$user['id']]);
            
            return true;
        } catch(PDOException $e) {
            error_log("Session creation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Validate session token
    private function validateSession($sessionToken) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT us.*, u.username, u.full_name, u.role 
                FROM user_sessions us 
                JOIN users u ON us.user_id = u.id 
                WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1
            ");
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Update session data
                $_SESSION['user_id'] = $session['user_id'];
                $_SESSION['username'] = $session['username'];
                $_SESSION['full_name'] = $session['full_name'];
                $_SESSION['role'] = $session['role'];
                
                return true;
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Validate remember token
    private function validateRememberToken($rememberToken) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT us.*, u.username, u.full_name, u.role 
                FROM user_sessions us 
                JOIN users u ON us.user_id = u.id 
                WHERE us.remember_token = ? AND u.is_active = 1
            ");
            $stmt->execute([$rememberToken]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Create new session
                return $this->createSession([
                    'id' => $session['user_id'],
                    'username' => $session['username'],
                    'full_name' => $session['full_name'],
                    'role' => $session['role']
                ]);
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Remember token validation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Logout user
    public function logout() {
        try {
            if (isset($_SESSION[AUTH_SESSION_NAME])) {
                // Delete session from database
                $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
                $stmt->execute([$_SESSION[AUTH_SESSION_NAME]]);
            }
            
            // Clear session data
            session_unset();
            session_destroy();
            
            // Clear remember me cookie
            if (isset($_COOKIE[AUTH_COOKIE_NAME])) {
                setcookie(AUTH_COOKIE_NAME, '', time() - 3600, '/', '', false, true);
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get current user data
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? null,
                'full_name' => $_SESSION['full_name'] ?? null,
                'role' => $_SESSION['role'] ?? null
            ];
        }
        return null;
    }
    
    // Clean expired sessions
    public function cleanExpiredSessions() {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $stmt->execute();
        } catch(PDOException $e) {
            error_log("Session cleanup error: " . $e->getMessage());
        }
    }
}

// Initialize Auth
require_once __DIR__ . '/database.php';
$auth = new Auth($pdo);

// Clean expired sessions periodically
if (rand(1, 100) == 1) {
    $auth->cleanExpiredSessions();
}
?>