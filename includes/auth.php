<?php
/**
 * Authentication Helper Functions
 */

// Запускаем сессию только если она ещё не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    return $db->fetchOne(
        "SELECT id, email, name, role, is_active FROM users WHERE id = ?",
        [$_SESSION['user_id']]
    );
}

/**
 * Login user
 */
function login(string $email, string $password): array {
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    $user = $db->fetchOne(
        "SELECT id, email, password_hash, name, role, is_active FROM users WHERE email = ?",
        [$email]
    );
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    if (!$user['is_active']) {
        return ['success' => false, 'error' => 'Account is deactivated'];
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // Log activity
    logActivity($user['id'], 'login', null, null);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logout(): void {
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        logActivity($userId, 'logout', null, null);
    }
    
    // Очищаем все данные сессии
    $_SESSION = [];
    
    // Удаляем cookie сессии
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Уничтожаем сессию
    session_destroy();
}

/**
 * Register new user
 */
function register(string $email, string $password, string $name, string $role = 'client'): array {
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    // Check if email already exists
    $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format'];
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters'];
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    try {
        $userId = $db->insert('users', [
            'email' => $email,
            'password_hash' => $passwordHash,
            'name' => $name,
            'role' => $role
        ]);
        
        logActivity($userId, 'register', 'users', $userId);
        
        return ['success' => true, 'user_id' => $userId];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin(): void {
    requireAuth();
    
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

/**
 * Log user activity
 */
function logActivity(?int $userId, string $action, ?string $entityType, ?int $entityId, 
                     $oldValues = null, $newValues = null): void {
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    $db->insert('activity_log', [
        'user_id' => $userId,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'old_values' => $oldValues ? json_encode($oldValues) : null,
        'new_values' => $newValues ? json_encode($newValues) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Alias for backward compatibility
function verifyCsrfToken(string $token): bool {
    return validateCsrfToken($token);
}

/**
 * Get user's projects
 */
function getUserProjects(int $userId): array {
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    $user = $db->fetchOne("SELECT role FROM users WHERE id = ?", [$userId]);
    
    if ($user['role'] === 'admin') {
        return $db->fetchAll("SELECT * FROM projects ORDER BY created_at DESC");
    }
    
    return $db->fetchAll(
        "SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC",
        [$userId]
    );
}
