<?php
/**
 * Admin Dashboard - Main Page
 * Analytics Service Administration Panel
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();
$projects = getUserProjects($user['id']);

// Get summary statistics
$db = Database::getInstance();

// Total events today
$totalEventsToday = $db->fetchOne(
    "SELECT COUNT(*) as count FROM events WHERE DATE(timestamp) = CURDATE()"
)['count'] ?? 0;

// Total sessions today
$totalSessionsToday = $db->fetchOne(
    "SELECT COUNT(*) as count FROM sessions WHERE DATE(started_at) = CURDATE()"
)['count'] ?? 0;

// Conversions today
$conversionsToday = $db->fetchOne(
    "SELECT COUNT(*) as count FROM events WHERE event_type = 'goal_achieved' AND DATE(timestamp) = CURDATE()"
)['count'] ?? 0;

// Recent events
$recentEvents = $db->fetchAll(
    "SELECT e.*, p.name as project_name 
     FROM events e 
     JOIN projects p ON e.project_id = p.id 
     ORDER BY e.timestamp DESC 
     LIMIT 10"
);

// Top projects by events
$topProjects = $db->fetchAll(
    "SELECT p.name, p.domain, COUNT(e.id) as event_count 
     FROM projects p 
     LEFT JOIN events e ON p.id = e.project_id AND DATE(e.timestamp) = CURDATE()
     GROUP BY p.id 
     ORDER BY event_count DESC 
     LIMIT 5"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="flex h-screen overflow-hidden">
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
            <div class="p-4">
                <h1 class="text-2xl font-bold text-blue-400">📊 Analytics</h1>
                <p class="text-xs text-gray-400 mt-1">Pro Service</p>
            </div>
            
            <nav class="mt-6">
                <a href="/admin/index.php" class="flex items-center px-4 py-3 bg-gray-800 text-white border-l-4 border-blue-500">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/admin/projects.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                    <i class="fas fa-globe w-6"></i>
                    <span>Projects</span>
                </a>
                <a href="/admin/goals.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                    <i class="fas fa-bullseye w-6"></i>
                    <span>Goals</span>
                </a>
                <a href="/admin/sessions.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                    <i class="fas fa-clock w-6"></i>
                    <span>Sessions</span>
                </a>
                <a href="/admin/events.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                    <i class="fas fa-list w-6"></i>
                    <span>Events</span>
                </a>
                <a href="/admin/funnels.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                    <i class="fas fa-filter w-6"></i>
                    <span>Funnels</span>
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="/admin/users.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                    <i class="fas fa-users w-6"></i>
                    <span>Users</span>
                </a>
                <a href="/admin/settings.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                    <i class="fas fa-cog w-6"></i>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-4 bg-gray-800">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center">
                        <span class="text-sm font-bold"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?= htmlspecialchars($user['name']) ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                </div>
                <a href="/admin/logout.php" class="mt-3 block text-xs text-red-400 hover:text-red-300">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Dashboard Overview</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500"><?= date('l, F j, Y') ?></span>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="p-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-chart-line text-blue-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Events Today</p>
                                <p class="text-2xl font-bold text-gray-800"><?= number_format($totalEventsToday) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-users text-green-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Sessions Today</p>
                                <p class="text-2xl font-bold text-gray-800"><?= number_format($totalSessionsToday) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-check-circle text-purple-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Conversions Today</p>
                                <p class="text-2xl font-bold text-gray-800"><?= number_format($conversionsToday) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center">
                                <i class="fas fa-globe text-orange-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Active Projects</p>
                                <p class="text-2xl font-bold text-gray-800"><?= count($projects) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Events Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Events (Last 7 Days)</h3>
                        <canvas id="eventsChart" height="200"></canvas>
                    </div>
                    
                    <!-- Device Distribution -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Device Distribution</h3>
                        <canvas id="deviceChart" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Tables Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Events -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Recent Events</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($recentEvents as $event): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs <?= $event['event_type'] === 'goal_achieved' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                                <?= htmlspecialchars($event['event_type']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($event['project_name']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-500"><?= date('H:i:s', strtotime($event['timestamp'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Top Projects -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Top Projects (Today)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Events</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($topProjects as $project): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800"><?= htmlspecialchars($project['name']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($project['domain']) ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-800 text-xs">
                                                <?= number_format($project['event_count']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Events Chart (Last 7 Days) - Real data from database
        const eventsCtx = document.getElementById('eventsChart').getContext('2d');
        
        // Get real data from PHP
        const eventsData = <?= json_encode($db->fetchAll("
            SELECT DATE_SUB(CURDATE(), INTERVAL n DAY) as date, 
                   COUNT(e.id) as count
            FROM (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) n
            LEFT JOIN events e ON DATE(e.timestamp) = DATE_SUB(CURDATE(), INTERVAL n DAY)
            GROUP BY n ORDER BY n DESC
        "), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        
        new Chart(eventsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(fn($i) => date('M j', strtotime("-{$i} days")), range(6, 0))) ?>,
                datasets: [{
                    label: 'Events',
                    data: <?= json_encode(array_column($eventsData, 'count')) ?>,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Device Chart - Real data
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        
        const deviceData = <?= json_encode($db->fetchAll("
            SELECT 
                SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
                SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile,
                SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet
            FROM sessions WHERE DATE(started_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        "), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Desktop', 'Mobile', 'Tablet'],
                datasets: [{
                    data: [
                        <?= (int)($deviceData[0]['desktop'] ?? 0) ?>,
                        <?= (int)($deviceData[0]['mobile'] ?? 0) ?>,
                        <?= (int)($deviceData[0]['tablet'] ?? 0) ?>
                    ],
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
