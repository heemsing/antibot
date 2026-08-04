<!-- Sidebar component for admin pages -->
<?php
// Убеждаемся, что $user определена
if (!isset($user)) {
    require_once __DIR__ . '/../../includes/auth.php';
    $user = getCurrentUser();
}
?>
<aside class="w-64 bg-gray-900 text-white flex-shrink-0 hidden md:block">
    <div class="p-4">
        <h1 class="text-2xl font-bold text-blue-400">📊 Analytics</h1>
        <p class="text-xs text-gray-400 mt-1">Pro Service</p>
    </div>
    
    <nav class="mt-6">
        <a href="/admin/index.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-gray-800 border-l-4 border-blue-500' : '' ?>">
            <i class="fas fa-home w-6"></i>
            <span>Dashboard</span>
        </a>
        <a href="/admin/projects.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) === 'projects.php' ? 'bg-gray-800 border-l-4 border-blue-500' : '' ?>">
            <i class="fas fa-globe w-6"></i>
            <span>Projects</span>
        </a>
        <a href="/admin/goals.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) === 'goals.php' ? 'bg-gray-800 border-l-4 border-blue-500' : '' ?>">
            <i class="fas fa-bullseye w-6"></i>
            <span>Goals</span>
        </a>
        <a href="/admin/sessions.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) === 'sessions.php' ? 'bg-gray-800 border-l-4 border-blue-500' : '' ?>">
            <i class="fas fa-clock w-6"></i>
            <span>Sessions</span>
        </a>
        <a href="/admin/events.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'bg-gray-800 border-l-4 border-blue-500' : '' ?>">
            <i class="fas fa-list w-6"></i>
            <span>Events</span>
        </a>
        <a href="/admin/funnels.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) === 'funnels.php' ? 'bg-gray-800 border-l-4 border-blue-500' : '' ?>">
            <i class="fas fa-filter w-6"></i>
            <span>Funnels</span>
        </a>
        <?php if ($user && $user['role'] === 'admin'): ?>
        <a href="/admin/users.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'bg-gray-800 border-l-4 border-blue-500' : '' ?>">
            <i class="fas fa-users w-6"></i>
            <span>Users</span>
        </a>
        <a href="/admin/settings.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'bg-gray-800 border-l-4 border-blue-500' : '' ?>">
            <i class="fas fa-cog w-6"></i>
            <span>Settings</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="absolute bottom-0 w-64 p-4 bg-gray-800">
        <div class="flex items-center">
            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center">
                <span class="text-sm font-bold"><?= htmlspecialchars( strtoupper(substr($user['name'] ?? 'U', 0, 1)) ) ?></span>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?= htmlspecialchars($user['name'] ?? 'User') ?></p>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            </div>
        </div>
        <a href="/admin/logout.php" class="mt-3 block text-xs text-red-400 hover:text-red-300">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
