<?php
/**
 * Events Log Page
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();
$db = Database::getInstance();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Фильтры
$eventType = $_GET['event_type'] ?? '';
$projectId = (int)($_GET['project_id'] ?? 0);

// Получаем проекты пользователя
$projects = getUserProjects($user['id']);
$projectIds = array_column($projects, 'id');

$events = [];
$totalEvents = 0;

if (!empty($projectIds)) {
    $whereClause = "e.project_id IN (" . implode(',', array_fill(0, count($projectIds), '?')) . ")";
    $params = $projectIds;
    
    if ($eventType) {
        $whereClause .= " AND e.event_type = ?";
        $params[] = $eventType;
    }
    
    if ($projectId && in_array($projectId, $projectIds)) {
        $whereClause .= " AND e.project_id = ?";
        $params[] = $projectId;
    }
    
    // Общее количество для пагинации
    $totalEvents = $db->fetchOne(
        "SELECT COUNT(*) as count FROM events e WHERE {$whereClause}",
        $params
    )['count'];
    
    // Получаем события
    $events = $db->fetchAll(
        "SELECT e.*, p.name as project_name, p.domain 
         FROM events e 
         JOIN projects p ON e.project_id = p.id 
         WHERE {$whereClause}
         ORDER BY e.timestamp DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$perPage, $offset])
    );
}

$totalPages = ceil($totalEvents / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Log - Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Events Log</h2>
                <div class="text-sm text-gray-500">
                    Total: <?= number_format($totalEvents) ?> events
                </div>
            </div>
        </header>
        
        <div class="p-6">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Event Type</label>
                        <select name="event_type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All Types</option>
                            <option value="page_view" <?= $eventType === 'page_view' ? 'selected' : '' ?>>Page View</option>
                            <option value="click" <?= $eventType === 'click' ? 'selected' : '' ?>>Click</option>
                            <option value="scroll" <?= $eventType === 'scroll' ? 'selected' : '' ?>>Scroll</option>
                            <option value="goal_achieved" <?= $eventType === 'goal_achieved' ? 'selected' : '' ?>>Goal Achieved</option>
                            <option value="form_submit" <?= $eventType === 'form_submit' ? 'selected' : '' ?>>Form Submit</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Project</label>
                        <select name="project_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="0">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= $projectId === $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="/admin/events.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm ml-2">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Page</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($events as $event): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div><?= date('Y-m-d', strtotime($event['timestamp'])) ?></div>
                                <div class="text-xs text-gray-400"><?= date('H:i:s', strtotime($event['timestamp'])) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?= $event['event_type'] === 'goal_achieved' ? 'bg-green-100 text-green-800' : 
                                       ($event['event_type'] === 'page_view' ? 'bg-blue-100 text-blue-800' : 
                                       'bg-gray-100 text-gray-800') ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $event['event_type'])) ?>
                                </span>
                                <?php if ($event['event_name']): ?>
                                <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($event['event_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($event['project_name']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                <?= htmlspecialchars(parse_url($event['page_url'], PHP_URL_PATH) ?: '/') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <i class="fas fa-<?= $event['device_type'] === 'mobile' ? 'mobile-alt' : ($event['device_type'] === 'tablet' ? 'tablet-alt' : 'desktop') ?> text-gray-400"></i>
                                <span class="text-xs text-gray-500 ml-1"><?= ucfirst($event['device_type']) ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                <?php
                                $data = json_decode($event['event_data'], true);
                                if ($data): ?>
                                <details class="cursor-pointer">
                                    <summary class="text-blue-600 hover:text-blue-800">View details</summary>
                                    <pre class="mt-2 bg-gray-100 p-2 rounded text-xs overflow-auto max-h-32"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) ?></pre>
                                </details>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                <p>No events found</p>
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
                    <a href="?page=<?= $page - 1 ?>&event_type=<?= urlencode($eventType) ?>&project_id=<?= $projectId ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= min(5, $totalPages); $i++): ?>
                    <a href="?page=<?= $i ?>&event_type=<?= urlencode($eventType) ?>&project_id=<?= $projectId ?>" 
                       class="px-4 py-2 border rounded-lg <?= $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&event_type=<?= urlencode($eventType) ?>&project_id=<?= $projectId ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
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
