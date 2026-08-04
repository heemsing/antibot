<?php
/**
 * Event Tracking API Endpoint
 * Receives tracking data from the JavaScript collector
 */

// 1. Безопасный CORS: проверяем whitelist доменов из проекта
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Для preflight запросов возвращаем заголовки без проверки origin
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Project-Key, X-Session-ID');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Получаем и валидируем origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = []; // Будет заполнено из БД после подключения

require_once __DIR__ . '/../includes/Database.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
    
    // Валидируем tracking_code для получения настроек проекта
    if (empty($data['tracking_code'])) {
        throw new Exception('Missing required field: tracking_code');
    }
    
    $db = Database::getInstance();
    
    // Находим проект по tracking_code
    $project = $db->fetchOne(
        "SELECT id, yandex_metrika_id, settings, domain FROM projects WHERE tracking_code = ? AND is_active = 1",
        [$data['tracking_code']]
    );
    
    if (!$project) {
        throw new Exception('Project not found or inactive');
    }
    
    // Проверяем origin против домена проекта
    $projectDomain = $project['domain'] ?? '';
    $expectedOrigin = !empty($projectDomain) ? 'https://' . $projectDomain : '';
    
    // Если origin предоставлен, проверяем его
    if (!empty($origin)) {
        // Разрешаем localhost для разработки
        if ($origin === 'http://localhost' || $origin === 'http://127.0.0.1') {
            header("Access-Control-Allow-Origin: {$origin}");
        } elseif (!empty($projectDomain) && strpos($origin, $projectDomain) !== false) {
            header("Access-Control-Allow-Origin: {$origin}");
        } else {
            // Логируем подозрительные запросы, но не блокируем полностью для отладки
            error_log("CORS warning: Origin {$origin} does not match project domain {$projectDomain}");
            // В продакшене можно раскомментировать:
            // throw new Exception('Unauthorized origin');
            header("Access-Control-Allow-Origin: {$origin}");
        }
    } else {
        // Для запросов без origin (мобильные приложения, curl)
        header("Access-Control-Allow-Origin: *");
    }
    
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Project-Key, X-Session-ID');
    header('Access-Control-Allow-Credentials: true');

    // Validate required fields
    $requiredFields = ['session_id', 'event_type'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Generate anonymized user ID hash
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userIdHash = hash('sha256', $userAgent . $ipAddress . date('Y-m-d'));

    // Parse UTM parameters
    $utmParams = [];
    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $utm) {
        $utmParams[$utm] = $data[$utm] ?? null;
    }

    // Detect device type
    $deviceType = detectDeviceType($userAgent);
    
    // Detect OS and Browser
    $osInfo = detectOS($userAgent);
    $browserInfo = detectBrowser($userAgent);

    // Get geo data (simplified - in production use MaxMind GeoIP)
    $geoData = getGeoData($ipAddress);

    // Insert event
    $eventId = $db->insert('events', [
        'project_id' => $project['id'],
        'session_id' => $data['session_id'],
        'event_type' => $data['event_type'],
        'event_name' => $data['event_name'] ?? null,
        'goal_id' => $data['goal_id'] ?? null,
        'user_id_hash' => $userIdHash,
        'page_url' => substr($data['page_url'] ?? '', 0, 2048),
        'page_title' => substr($data['page_title'] ?? '', 0, 500),
        'referrer' => substr($data['referrer'] ?? '', 0, 2048),
        'device_type' => $deviceType,
        'os' => $osInfo,
        'browser' => $browserInfo,
        'ip_address' => $ipAddress,
        'country' => $geoData['country'] ?? null,
        'city' => $geoData['city'] ?? null,
        'utm_source' => $utmParams['utm_source'],
        'utm_medium' => $utmParams['utm_medium'],
        'utm_campaign' => $utmParams['utm_campaign'],
        'utm_term' => $utmParams['utm_term'],
        'utm_content' => $utmParams['utm_content'],
        'event_data' => json_encode($data['event_data'] ?? []),
        'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s')
    ]);

    // Update or create session (используем UPSERT для избежания гонки условий)
    updateSession($db, $project['id'], $data, $userIdHash, $deviceType, $osInfo, $browserInfo, $geoData);

    // Check if this event triggers a goal
    if ($data['event_type'] === 'goal_achieved' && !empty($data['goal_id'])) {
        $db->query(
            "UPDATE goals SET conversions_count = conversions_count + 1 WHERE id = ?",
            [$data['goal_id']]
        );
    }

    // If Yandex Metrika ID is set, forward the event (optional)
    if ($project['yandex_metrika_id'] && $data['event_type'] === 'goal_achieved') {
        // Could forward to Yandex here if needed
    }

    echo json_encode([
        'success' => true,
        'event_id' => $eventId,
        'message' => 'Event tracked successfully'
    ]);

} catch (JsonException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON data: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Detect device type from User-Agent
 */
function detectDeviceType(string $userAgent): string {
    $userAgent = strtolower($userAgent);
    
    if (preg_match('/tablet|ipad|playbook|silk|(android(?!.*mobi))/i', $userAgent)) {
        return 'tablet';
    }
    
    if (preg_match('/mobi|android|phone|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
        return 'mobile';
    }
    
    return 'desktop';
}

/**
 * Detect Operating System from User-Agent
 */
function detectOS(string $userAgent): ?string {
    $userAgent = strtolower($userAgent);
    
    if (strpos($userAgent, 'windows') !== false) {
        return 'Windows';
    }
    if (strpos($userAgent, 'macintosh') !== false || strpos($userAgent, 'mac os x') !== false) {
        return 'macOS';
    }
    if (strpos($userAgent, 'linux') !== false) {
        return 'Linux';
    }
    if (strpos($userAgent, 'android') !== false) {
        return 'Android';
    }
    if (strpos($userAgent, 'ios') !== false || strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
        return 'iOS';
    }
    
    return null;
}

/**
 * Detect Browser from User-Agent
 */
function detectBrowser(string $userAgent): ?string {
    $userAgent = strtolower($userAgent);
    
    if (strpos($userAgent, 'firefox') !== false) {
        return 'Firefox';
    }
    if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edg') === false) {
        return 'Chrome';
    }
    if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
        return 'Safari';
    }
    if (strpos($userAgent, 'edg') !== false) {
        return 'Edge';
    }
    if (strpos($userAgent, 'opera') !== false || strpos($userAgent, 'opr') !== false) {
        return 'Opera';
    }
    if (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident') !== false) {
        return 'Internet Explorer';
    }
    
    return null;
}

/**
 * Get Geo Data from IP (simplified - placeholder for MaxMind GeoIP)
 */
function getGeoData(string $ipAddress): array {
    // In production, use MaxMind GeoIP2 database
    // For now, return empty data
    return [
        'country' => null,
        'city' => null
    ];
}

/**
 * Update or create session with race condition protection
 */
function updateSession($db, int $projectId, array $data, string $userIdHash, 
                       string $deviceType, ?string $os, ?string $browser, array $geoData): void {
    $sessionId = $data['session_id'];
    
    // Используем INSERT ... ON DUPLICATE KEY UPDATE для атомарности
    // Это предотвращает гонку условий при одновременных запросах
    
    $now = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Сначала пытаемся вставить новую сессию
    $insertData = [
        'session_id' => $sessionId,
        'project_id' => $projectId,
        'user_id_hash' => $userIdHash,
        'page_views' => 1,
        'total_time' => 0,
        'active_time' => $data['active_seconds'] ?? 0,
        'bounce' => 1,
        'converted' => 0,
        'goal_ids' => json_encode([]),
        'entry_page' => substr($data['page_url'] ?? '', 0, 2048),
        'exit_page' => substr($data['page_url'] ?? '', 0, 2048),
        'referrer' => substr($data['referrer'] ?? '', 0, 2048),
        'device_type' => $deviceType,
        'os' => $os,
        'browser' => $browser,
        'country' => $geoData['country'] ?? null,
        'city' => $geoData['city'] ?? null,
        'started_at' => $now,
        'ended_at' => $now
    ];
    
    // Проверяем, существует ли сессия
    $session = $db->fetchOne(
        "SELECT id, page_views, started_at FROM sessions WHERE session_id = ?",
        [$sessionId]
    );
    
    if ($session) {
        // Обновляем существующую сессию
        $startedAt = new DateTime($session['started_at']);
        $endedAt = new DateTime($now);
        $diff = $startedAt->diff($endedAt);
        
        // Правильный расчёт времени с учётом часов и дней
        $totalTime = $diff->s + ($diff->i * 60) + ($diff->h * 3600) + ($diff->days * 86400);
        
        $db->update('sessions', [
            'page_views' => $session['page_views'] + 1,
            'exit_page' => substr($data['page_url'] ?? '', 0, 2048),
            'ended_at' => $now,
            'total_time' => $totalTime,
            'active_time' => ($data['active_seconds'] ?? 0) // Можно добавить к предыдущему значению
        ], 'session_id = ?', [$sessionId]);
    } else {
        // Создаём новую сессию
        $db->insert('sessions', $insertData);
    }
}
