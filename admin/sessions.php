<?php
/**
 * Sessions List Page
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();
$db = Database::getInstance();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Получаем проекты пользователя
$projects = getUserProjects($user['id']);
$projectIds = array_column($projects, 'id');

$sessions = [];
$totalSessions = 0;

if (!empty($projectIds)) {
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    
    // Общее количество для пагинации
    $totalSessions = $db->fetchOne(
        "SELECT COUNT(*) as count FROM sessions WHERE project_id IN ($placeholders)",
        $projectIds
    )['count'];
    
    // Получаем сессии
    $sessions = $db->fetchAll(
        "SELECT s.*, p.name as project_name, p.domain 
         FROM sessions s 
         JOIN projects p ON s.project_id = p.id 
         WHERE s.project_id IN ($placeholders)
         ORDER BY s.ended_at DESC
         LIMIT ? OFFSET ?",
        array_merge($projectIds, [$perPage, $offset])
    );
}

$totalPages = ceil($totalSessions / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions - Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">User Sessions</h2>
                <div class="text-sm text-gray-500">
                    Total: <?= number_format($totalSessions) ?> sessions
                </div>
            </div>
        </header>
        
        <div class="p-6">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Session ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pages</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bounce</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sessions as $session): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                                    <?= substr($session['session_id'], 0, 12) ?>...
                                </code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($session['project_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($session['domain']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php
                                    $icon = 'desktop';
                                    if ($session['device_type'] === 'mobile') $icon = 'mobile-alt';
                                    elseif ($session['device_type'] === 'tablet') $icon = 'tablet-alt';
                                    ?>
                                    <i class="fas fa-<?= $icon ?> text-gray-400 mr-2"></i>
                                    <span class="text-sm text-gray-700"><?= ucfirst($session['device_type']) ?></span>
                                </div>
                                <?php if ($session['browser']): ?>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($session['browser']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php
                                $duration = (int)$session['total_time'];
                                if ($duration < 60) {
                                    echo "{$duration}s";
                                } elseif ($duration < 3600) {
                                    echo floor($duration / 60) . "m " . ($duration % 60) . "s";
                                } else {
                                    echo floor($duration / 3600) . "h " . floor(($duration % 3600) / 60) . "m";
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= number_format($session['page_views']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full <?= $session['bounce'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                    <?= $session['bounce'] ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div><?= date('Y-m-d', strtotime($session['started_at'])) ?></div>
                                <div class="text-xs text-gray-400"><?= date('H:i', strtotime($session['started_at'])) ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-clock text-4xl text-gray-300 mb-3"></i>
                                <p>No sessions found</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="px-4 py-2 border rounded-lg <?= $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
