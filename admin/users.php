<?php
/**
 * Users Management Page (Admin Only)
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();

// Только админы могут управлять пользователями
if ($user['role'] !== 'admin') {
    header('Location: /admin/index.php');
    exit;
}

$db = Database::getInstance();

$users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'client';
        
        if (empty($name) || empty($email) || empty($password)) {
            $message = 'All fields are required';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format';
            $messageType = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters';
            $messageType = 'error';
        } else {
            try {
                $db->insert('users', [
                    'name' => htmlspecialchars($name),
                    'email' => strtolower($email),
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'is_active' => 1
                ]);
                
                logActivity($user['id'], 'user_created', 'users', $db->getConnection()->lastInsertId());
                header("Location: /admin/users.php?success=1");
                exit;
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle') {
        $userId = (int)$_POST['user_id'];
        $targetUser = $db->fetchOne("SELECT id, is_active FROM users WHERE id = ?", [$userId]);
        
        if ($targetUser && $userId !== $user['id']) {
            $newStatus = $targetUser['is_active'] ? 0 : 1;
            $db->update('users', ['is_active' => $newStatus], 'id = ?', [$userId]);
            $message = 'User status updated';
            $messageType = 'success';
        }
    } elseif ($action === 'delete') {
        $userId = (int)$_POST['user_id'];
        
        if ($userId !== $user['id']) {
            $db->delete('users', 'id = ?', [$userId]);
            logActivity($user['id'], 'user_deleted', 'users', $userId);
            $message = 'User deleted';
            $messageType = 'success';
        } else {
            $message = 'Cannot delete yourself';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">User Management</h2>
                <button onclick="document.getElementById('newUserModal').classList.remove('hidden')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>New User
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
            <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">User created!</div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($u['name']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full <?= $u['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('Y-m-d', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline" onsubmit="return confirm('Toggle status?')">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <?php if ($u['id'] !== $user['id']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete user?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="newUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-semibold mb-4">Create New User</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required minlength="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="client">Client</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="document.getElementById('newUserModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
