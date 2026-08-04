<?php
/**
 * Project Statistics Page
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();
$db = Database::getInstance();

$projectId = (int)($_GET['id'] ?? 0);
$projects = getUserProjects($user['id']);

// Проверка доступа к проекту
$project = null;
foreach ($projects as $p) {
    if ($p['id'] === $projectId) {
        $project = $p;
        break;
    }
}

if (!$project) {
    header('Location: /admin/projects.php');
    exit;
}

// Период (по умолчанию 7 дней)
$days = min(30, max(1, (int)($_GET['days'] ?? 7)));
$startDate = date('Y-m-d', strtotime("-{$days} days"));

// Получаем статистику по дням
$stats = $db->fetchAll(
    "SELECT DATE(timestamp) as date, 
            COUNT(*) as page_views,
            COUNT(DISTINCT session_id) as sessions,
            COUNT(DISTINCT user_id_hash) as unique_users
     FROM events 
     WHERE project_id = ? AND DATE(timestamp) >= ?
     GROUP BY DATE(timestamp) 
     ORDER BY date ASC",
    [$projectId, $startDate]
);

// Топ страниц
$topPages = $db->fetchAll(
    "SELECT page_url, COUNT(*) as views 
     FROM events 
     WHERE project_id = ? AND event_type = 'page_view' AND DATE(timestamp) >= ?
     GROUP BY page_url 
     ORDER BY views DESC 
     LIMIT 10",
    [$projectId, $startDate]
);

// Устройства
$devices = $db->fetchAll(
    "SELECT device_type, COUNT(*) as count 
     FROM events 
     WHERE project_id = ? AND DATE(timestamp) >= ?
     GROUP BY device_type",
    [$projectId, $startDate]
);

// Браузеры
$browsers = $db->fetchAll(
    "SELECT browser, COUNT(*) as count 
     FROM events 
     WHERE project_id = ? AND DATE(timestamp) >= ? AND browser IS NOT NULL
     GROUP BY browser
     ORDER BY count DESC
     LIMIT 5",
    [$projectId, $startDate]
);

// Цели
$goals = $db->fetchAll(
    "SELECT name, conversions_count 
     FROM goals 
     WHERE project_id = ? AND is_active = 1
     ORDER BY conversions_count DESC",
    [$projectId]
);

// KPI
$kpi = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_events,
        COUNT(DISTINCT session_id) as total_sessions,
        COUNT(DISTINCT user_id_hash) as total_users,
        AVG(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) * 100 as page_view_rate
     FROM events 
     WHERE project_id = ? AND DATE(timestamp) >= ?",
    [$projectId, $startDate]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['name']) ?> - Stats</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($project['name']) ?></h2>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($project['domain']) ?></p>
                </div>
                <a href="/admin/tracking-code.php?id=<?= $projectId ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-code mr-2"></i>Get Tracking Code
                </a>
            </div>
        </header>
        
        <div class="p-6">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-2">Total Events</div>
                    <div class="text-3xl font-bold text-gray-800"><?= number_format($kpi['total_events'] ?? 0) ?></div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-2">Sessions</div>
                    <div class="text-3xl font-bold text-blue-600"><?= number_format($kpi['total_sessions'] ?? 0) ?></div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-2">Unique Users</div>
                    <div class="text-3xl font-bold text-green-600"><?= number_format($kpi['total_users'] ?? 0) ?></div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-2">Conversions</div>
                    <div class="text-3xl font-bold text-purple-600">
                        <?= number_format(array_sum(array_column($goals, 'conversions_count'))) ?>
                    </div>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Traffic Overview (<?= $days ?> days)</h3>
                <canvas id="trafficChart" height="80"></canvas>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Pages -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Top Pages</h3>
                    <div class="space-y-3">
                        <?php foreach ($topPages as $page): ?>
                        <div class="flex justify-between items-center py-2 border-b last:border-0">
                            <span class="text-sm text-gray-700 truncate max-w-xs"><?= htmlspecialchars(parse_url($page['page_url'], PHP_URL_PATH) ?: '/') ?></span>
                            <span class="text-sm font-medium text-gray-900"><?= number_format($page['views']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($topPages)): ?>
                        <p class="text-gray-500 text-sm">No data yet</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Devices -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Devices</h3>
                    <canvas id="deviceChart" height="200"></canvas>
                </div>
                
                <!-- Browsers -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Top Browsers</h3>
                    <div class="space-y-3">
                        <?php foreach ($browsers as $browser): ?>
                        <div class="flex justify-between items-center py-2 border-b last:border-0">
                            <span class="text-sm text-gray-700"><?= htmlspecialchars($browser['browser']) ?></span>
                            <span class="text-sm font-medium text-gray-900"><?= number_format($browser['count']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($browsers)): ?>
                        <p class="text-gray-500 text-sm">No data yet</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Goals -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Goal Conversions</h3>
                    <div class="space-y-3">
                        <?php foreach ($goals as $goal): ?>
                        <div class="flex justify-between items-center py-2 border-b last:border-0">
                            <span class="text-sm text-gray-700"><?= htmlspecialchars($goal['name']) ?></span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm"><?= number_format($goal['conversions_count']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($goals)): ?>
                        <p class="text-gray-500 text-sm">No active goals</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const stats = <?= json_encode($stats) ?>;
        const devices = <?= json_encode($devices) ?>;
        
        // Traffic Chart
        new Chart(document.getElementById('trafficChart'), {
            type: 'line',
            data: {
                labels: stats.map(s => s.date),
                datasets: [{
                    label: 'Page Views',
                    data: stats.map(s => s.page_views),
                    borderColor: '#3B82F6',
                    tension: 0.3,
                    fill: true,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)'
                }, {
                    label: 'Sessions',
                    data: stats.map(s => s.sessions),
                    borderColor: '#10B981',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
        
        // Device Chart
        new Chart(document.getElementById('deviceChart'), {
            type: 'doughnut',
            data: {
                labels: devices.map(d => d.device_type),
                datasets: [{
                    data: devices.map(d => d.count),
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
