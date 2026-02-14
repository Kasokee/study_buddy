<?php
// User data from session
$userData = [
    'first_name' => $_SESSION['first_name'] ?? 'Unknown',
    'last_name' => $_SESSION['last_name'] ?? 'User',
    'email' => $_SESSION['email'] ?? 'loading...'
];

// Combine first and last name
$fullName = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));

// Prevent redeclaration of getInitials
if (!function_exists('getInitials')) {
    function getInitials($name) {
        if (!$name) return '??';
        $parts = explode(' ', $name);
        $initials = '';
        foreach ($parts as $p) {
            $initials .= strtoupper($p[0]);
        }
        return $initials;
    }
}

// Navigation items
$navItems = [
    ['name' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'dashboard.php'],
    ['name' => 'Book Session', 'icon' => 'bi bi-bookmark-check', 'route' => '#'],
];

$pageItems = [
    ['name' => 'Auth', 'icon' => 'bi-lock', 'route' => '#', 'hasSub' => true],
    ['name' => 'Errors', 'icon' => 'bi-exclamation-triangle', 'route' => '#', 'hasSub' => true],
];

$otherItems = [
    ['name' => 'Settings', 'icon' => 'bi-gear', 'route' => '#', 'hasSub' => true],
    ['name' => 'Help Center', 'icon' => 'bi-question-circle', 'route' => '#'],
];

$currentPath = basename($_SERVER['PHP_SELF']);

?>

<aside class="flex flex-col h-screen bg-white w-64">
    <!-- Logo / Header -->
    <div class="p-6 flex items-center justify-between border-b border-slate-100">
        <div class="flex items-center space-x-2 overflow-hidden">
            <div class="w-9 h-9 rounded-md flex items-center justify-center flex-shrink-0">
                <img src="../assets/DENRicon.png" alt="DENR Logo" class="w-6 h-6 object-contain" />
            </div>
            <div class="truncate">
                <h2 class="text-sm font-bold tracking-tight text-slate-900">Study Buddy</h2>
                <p class="text-[10px] text-slate-500 mt-0.5 font-medium">Management System</p>
            </div>
        </div>
        <i class="bi-chevron-down text-slate-400"></i>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-8 mt-2">
        <!-- General Section -->
        <section>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-4 px-2">General</p>
            <div class="space-y-1">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?php echo $item['route']; ?>" 
                       class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm transition-colors <?php echo ($currentPath === basename($item['route'])) ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                        <div class="flex items-center space-x-3">
                            <i class="bi <?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['name']; ?></span>
                        </div>
                        <?php if (!empty($item['hasSub'])): ?>
                            <i class="bi-chevron-down text-slate-400"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Pages Section -->
        <section>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-4 px-2">Pages</p>
            <div class="space-y-1">
                <?php foreach ($pageItems as $item): ?>
                    <a href="<?php echo $item['route']; ?>" 
                       class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm transition-colors <?php echo ($currentPath === basename($item['route'])) ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                        <div class="flex items-center space-x-3">
                            <i class="bi <?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['name']; ?></span>
                        </div>
                        <?php if (!empty($item['hasSub'])): ?>
                            <i class="bi-chevron-down text-slate-400"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Other Section -->
        <section>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-4 px-2">Other</p>
            <div class="space-y-1">
                <?php foreach ($otherItems as $item): ?>
                    <a href="<?php echo $item['route']; ?>" 
                       class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm transition-colors <?php echo ($currentPath === basename($item['route'])) ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                        <div class="flex items-center space-x-3">
                            <i class="bi <?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['name']; ?></span>
                        </div>
                        <?php if (!empty($item['hasSub'])): ?>
                            <i class="bi-chevron-down text-slate-400"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </nav>

    <!-- Logged-in User Info -->
    <div class="p-4 border-t border-slate-100">
        <div class="flex items-center space-x-3 p-2">
            <div class="relative h-8 w-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold overflow-hidden border border-slate-200 shadow-sm">
                <?php echo getInitials($fullName); ?>
            </div>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-semibold truncate"><?php echo htmlspecialchars($fullName); ?></p>
                <p class="text-[10px] text-slate-500 truncate"><?php echo htmlspecialchars($userData['email']); ?></p>
            </div>
        </div>
    </div>
</aside>

<script src="https://cdn.tailwindcss.com"></script>
