<?php
/**
 * Settings Page
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$user = getCurrentUser();
$db = Database::getInstance();

$message = '';
$messageType = '';

// Сохранение настроек профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            $message = 'Name is required';
            $messageType = 'error';
        } else {
            $db->update('users', ['name' => htmlspecialchars($name)], 'id = ?', [$user['id']]);
            logActivity($user['id'], 'profile_updated', 'users', $user['id']);
            $message = 'Profile updated successfully';
            $messageType = 'success';
            $user = getCurrentUser(true); // Перезагружаем данные пользователя
        }
    } elseif ($action === 'change_password' && $user['role'] === 'admin') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            $message = 'All password fields are required';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = 'New password must be at least 6 characters';
            $messageType = 'error';
        } else {
            // Проверяем текущий пароль
            $userData = $db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
            
            if (password_verify($currentPassword, $userData['password_hash'])) {
                $db->update('users', [
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
                ], 'id = ?', [$user['id']]);
                
                logActivity($user['id'], 'password_changed', 'users', $user['id']);
                $message = 'Password changed successfully';
                $messageType = 'success';
            } else {
                $message = 'Current password is incorrect';
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4">
                <h2 class="text-xl font-semibold text-gray-800">Settings</h2>
            </div>
        </header>
        
        <div class="p-6">
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <div class="max-w-2xl">
                <!-- Profile Settings -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-user mr-2"></i>Profile Settings
                    </h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="name" required 
                                   value="<?= htmlspecialchars($user['name']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" disabled 
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500">
                            <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                            <span class="px-3 py-1 rounded-full text-sm <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </div>
                        
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Change Password (Admin Only) -->
                <?php if ($user['role'] === 'admin'): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-lock mr-2"></i>Change Password
                    </h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" name="current_password" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" name="new_password" required minlength="6"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Change Password
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- System Info -->
                <div class="bg-white rounded-lg shadow p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>System Information
                    </h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">PHP Version:</span>
                            <span class="font-mono"><?= phpversion() ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">MySQL Extension:</span>
                            <span class="font-mono"><?= extension_loaded('pdo_mysql') ? 'Enabled' : 'Not Installed' ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Server Time:</span>
                            <span class="font-mono"><?= date('Y-m-d H:i:s') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
