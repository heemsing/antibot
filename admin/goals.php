<?php
/**
 * Goals Management Page
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();
$db = Database::getInstance();

// Получаем проекты пользователя
$projects = getUserProjects($user['id']);
$projectIds = array_column($projects, 'id');

// Получаем цели для всех проектов пользователя
$goals = [];
if (!empty($projectIds)) {
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $goals = $db->fetchAll(
        "SELECT g.*, p.name as project_name, p.domain 
         FROM goals g 
         JOIN projects p ON g.project_id = p.id 
         WHERE g.project_id IN ($placeholders)
         ORDER BY g.created_at DESC",
        $projectIds
    );
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && $user['role'] === 'admin') {
        $projectId = (int)$_POST['project_id'];
        $name = trim($_POST['name'] ?? '');
        $goal_type = $_POST['goal_type'] ?? 'event';
        $conditions = json_encode($_POST['conditions'] ?? []);
        
        if (empty($name)) {
            $message = 'Goal name is required';
            $messageType = 'error';
        } elseif (!in_array($projectId, $projectIds)) {
            $message = 'Invalid project';
            $messageType = 'error';
        } else {
            try {
                $db->insert('goals', [
                    'project_id' => $projectId,
                    'name' => htmlspecialchars($name),
                    'goal_type' => $goal_type,
                    'conditions' => $conditions,
                    'conversions_count' => 0,
                    'is_active' => 1
                ]);
                
                logActivity($user['id'], 'goal_created', 'goals', $db->getConnection()->lastInsertId());
                
                header("Location: /admin/goals.php?success=1");
                exit;
            } catch (Exception $e) {
                $message = 'Error creating goal: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle') {
        $goalId = (int)$_POST['goal_id'];
        $goal = $db->fetchOne("SELECT id, is_active FROM goals WHERE id = ?", [$goalId]);
        
        if ($goal && in_array($goal['id'], array_column($goals, 'id'))) {
            $newStatus = $goal['is_active'] ? 0 : 1;
            $db->update('goals', ['is_active' => $newStatus], 'id = ?', [$goalId]);
            $message = 'Goal status updated';
            $messageType = 'success';
        }
    } elseif ($action === 'delete' && $user['role'] === 'admin') {
        $goalId = (int)$_POST['goal_id'];
        $goal = $db->fetchOne("SELECT id FROM goals WHERE id = ?", [$goalId]);
        
        if ($goal && in_array($goal['id'], array_column($goals, 'id'))) {
            $db->delete('goals', 'id = ?', [$goalId]);
            logActivity($user['id'], 'goal_deleted', 'goals', $goalId);
            $message = 'Goal deleted successfully';
            $messageType = 'success';
        }
    }
}

// Перезагружаем список после изменений
if (!empty($projectIds)) {
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $goals = $db->fetchAll(
        "SELECT g.*, p.name as project_name, p.domain 
         FROM goals g 
         JOIN projects p ON g.project_id = p.id 
         WHERE g.project_id IN ($placeholders)
         ORDER BY g.created_at DESC",
        $projectIds
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goals - Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Conversion Goals</h2>
                <?php if ($user['role'] === 'admin'): ?>
                <button onclick="document.getElementById('newGoalModal').classList.remove('hidden')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>New Goal
                </button>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="p-6">
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                Goal created successfully!
            </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conversions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($goals as $goal): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($goal['name']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($goal['project_name']) ?></div>
                                <div class="text-xs text-gray-400"><?= htmlspecialchars($goal['domain']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($goal['goal_type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= number_format($goal['conversions_count']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full <?= $goal['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $goal['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <?= $goal['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <?php if ($user['role'] === 'admin'): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this goal?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($goals)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-bullseye text-4xl text-gray-300 mb-3"></i>
                                <p>No goals configured yet</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- New Goal Modal -->
    <?php if ($user['role'] === 'admin'): ?>
    <div id="newGoalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-semibold mb-4">Create New Goal</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Project</label>
                    <select name="project_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?> (<?= htmlspecialchars($project['domain']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Goal Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Purchase Completed">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Goal Type</label>
                    <select name="goal_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="event">Event</option>
                        <option value="page_view">Page View</option>
                        <option value="url_contains">URL Contains</option>
                        <option value="time_on_page">Time on Page</option>
                    </select>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="document.getElementById('newGoalModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create Goal
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        document.getElementById('newGoalModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
