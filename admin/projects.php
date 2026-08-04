<?php
/**
 * Projects Management Page
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();
$db = Database::getInstance();
$projects = getUserProjects($user['id']);

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $yandexMetrikaId = $_POST['yandex_metrika_id'] ?? null;
        
        // Валидация имени проекта
        if (empty($name) || strlen($name) < 2) {
            $message = 'Project name must be at least 2 characters long';
            $messageType = 'error';
        } 
        // Валидация домена с проверкой формата
        elseif (empty($domain)) {
            $message = 'Domain is required';
            $messageType = 'error';
        } elseif (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $domain)) {
            $message = 'Invalid domain format. Example: example.com';
            $messageType = 'error';
        } else {
            // Generate unique tracking code
            $trackingCode = bin2hex(random_bytes(16));
            
            try {
                $db->insert('projects', [
                    'user_id' => $user['id'],
                    'name' => htmlspecialchars($name),
                    'domain' => strtolower($domain),
                    'tracking_code' => $trackingCode,
                    'yandex_metrika_id' => $yandexMetrikaId ?: null,
                    'settings' => json_encode([])
                ]);
                
                logActivity($user['id'], 'project_created', 'projects', $db->getConnection()->lastInsertId());
                
                $message = 'Project created successfully!';
                $messageType = 'success';
                header("Location: /admin/projects.php?success=1");
                exit;
            } catch (Exception $e) {
                $message = 'Error creating project: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $projectId = (int)$_POST['project_id'];
        
        // Verify ownership
        $project = $db->fetchOne("SELECT id FROM projects WHERE id = ? AND user_id = ?", [$projectId, $user['id']]);
        
        if ($project && $user['role'] === 'admin') {
            $db->delete('projects', 'id = ?', [$projectId]);
            logActivity($user['id'], 'project_deleted', 'projects', $projectId);
            $message = 'Project deleted successfully';
            $messageType = 'success';
        }
    }
}

// Refresh projects list
$projects = getUserProjects($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Projects</h2>
                <button onclick="document.getElementById('newProjectModal').classList.remove('hidden')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>New Project
                </button>
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
                Project created successfully!
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($projects as $project): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($project['name']) ?></h3>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($project['domain']) ?></p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs <?= $project['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $project['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tracking Code:</span>
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= substr($project['tracking_code'], 0, 16) ?>...</code>
                        </div>
                        <?php if ($project['yandex_metrika_id']): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Yandex Metrika:</span>
                            <span class="text-gray-700"><?= htmlspecialchars($project['yandex_metrika_id']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex space-x-2 pt-4 border-t">
                        <a href="/admin/project-stats.php?id=<?= $project['id'] ?>" 
                           class="flex-1 text-center bg-blue-50 text-blue-600 px-3 py-2 rounded hover:bg-blue-100 text-sm">
                            <i class="fas fa-chart-bar mr-1"></i> Stats
                        </a>
                        <a href="/admin/tracking-code.php?id=<?= $project['id'] ?>" 
                           class="flex-1 text-center bg-gray-50 text-gray-600 px-3 py-2 rounded hover:bg-gray-100 text-sm">
                            <i class="fas fa-code mr-1"></i> Code
                        </a>
                        <?php if ($user['role'] === 'admin'): ?>
                        <form method="POST" class="flex-1" onsubmit="return confirm('Are you sure?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                            <button type="submit" class="w-full bg-red-50 text-red-600 px-3 py-2 rounded hover:bg-red-100 text-sm">
                                <i class="fas fa-trash mr-1"></i> Delete
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($projects)): ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">No projects yet</p>
                    <p class="text-gray-400 text-sm mt-2">Create your first project to start tracking</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- New Project Modal -->
    <div id="newProjectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-semibold mb-4">Create New Project</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Project Name</label>
                    <input type="text" name="name" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="My Website">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Domain</label>
                    <input type="text" name="domain" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="example.com">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Yandex Metrika ID (optional)</label>
                    <input type="text" name="yandex_metrika_id" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="12345678">
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="document.getElementById('newProjectModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Close modal on outside click
        document.getElementById('newProjectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
