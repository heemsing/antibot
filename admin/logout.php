<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/../includes/auth.php';

// Сохраняем информацию для логирования перед уничтожением сессии
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
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

// Редирект на страницу входа
header('Location: /admin/login.php');
exit;
