<?php
/**
 * Funnels Management Page
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();
$db = Database::getInstance();

$projects = getUserProjects($user['id']);
$projectIds = array_column($projects, 'id');

$funnels = [];
if (!empty($projectIds) && $user['role'] === 'admin') {
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $funnels = $db->fetchAll(
        "SELECT f.*, p.name as project_name 
         FROM funnels f 
         JOIN projects p ON f.project_id = p.id 
         WHERE f.project_id IN ($placeholders)
         ORDER BY f.created_at DESC",
        $projectIds
    );
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'admin') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $projectId = (int)$_POST['project_id'];
        $name = trim($_POST['name'] ?? '');
        $steps = json_encode($_POST['steps'] ?? []);
        
        if (empty($name)) {
            $message = 'Funnel name is required';
            $messageType = 'error';
        } elseif (!in_array($projectId, $projectIds)) {
            $message = 'Invalid project';
            $messageType = 'error';
        } else {
            try {
                $db->insert('funnels', [
                    'project_id' => $projectId,
                    'name' => htmlspecialchars($name),
                    'steps' => $steps,
                    'is_active' => 1
                ]);
                
                logActivity($user['id'], 'funnel_created', 'funnels', $db->getConnection()->lastInsertId());
                header("Location: /admin/funnels.php?success=1");
                exit;
            } catch (Exception $e) {
                $message = 'Error creating funnel: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $funnelId = (int)$_POST['funnel_id'];
        $db->delete('funnels', 'id = ?', [$funnelId]);
        $message = 'Funnel deleted';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funnels - Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Conversion Funnels</h2>
                <?php if ($user['role'] === 'admin'): ?>
                <button onclick="document.getElementById('newFunnelModal').classList.remove('hidden')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>New Funnel
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
            <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">Funnel created!</div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Steps</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($funnels as $funnel): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($funnel['name']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($funnel['project_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                $steps = json_decode($funnel['steps'], true);
                                echo count($steps ?: []) . ' step(s)';
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full <?= $funnel['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $funnel['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('Y-m-d', strtotime($funnel['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['role'] === 'admin'): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="funnel_id" value="<?= $funnel['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($funnels)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-filter text-4xl text-gray-300 mb-3"></i>
                                <p>No funnels configured</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php if ($user['role'] === 'admin'): ?>
    <div id="newFunnelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-semibold mb-4">Create New Funnel</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Project</label>
                    <select name="project_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Funnel Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Purchase Flow">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Steps (JSON)</label>
                    <textarea name="steps" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-xs font-mono" placeholder='[{"type":"page_view","url":"/cart"},{"type":"page_view","url":"/checkout"}]'></textarea>
                    <p class="text-xs text-gray-500 mt-1">Define funnel steps as JSON array</p>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="document.getElementById('newFunnelModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
