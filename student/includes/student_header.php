<?php
// User data from session
$userData = $_SESSION['user'] ?? [
    'first_name' => 'Unknown',
    'last_name' => 'User',
    'email' => 'loading...'
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
?>

<header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between sticky top-0 z-10">
    <div class="flex items-center space-x-8">
        <nav class="hidden md:flex items-center space-x-6">
            <a href="#" class="text-sm font-semibold text-slate-900">Overview</a>
            <a href="#" class="text-sm font-medium text-slate-500 hover:text-slate-900">Loggers</a>
            <a href="#" class="text-sm font-medium text-slate-500 hover:text-slate-900">Permits</a>
            <a href="#" class="text-sm font-medium text-slate-500 hover:text-slate-900">Settings</a>
        </nav>
    </div>

    <div class="flex items-center space-x-4">
        <div class="relative group hidden sm:block">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" placeholder="Search..." 
                   class="bg-slate-100 border-none rounded-md py-1.5 pl-9 pr-4 text-sm w-64 focus:ring-2 focus:ring-slate-900 transition-all outline-none" />
        </div>

        <button class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors">
            <i class="bi bi-bell"></i>
        </button>
        <button class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors">
            <i class="bi bi-moon"></i>
        </button>
        <button class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors">
            <i class="bi bi-gear"></i>
        </button>

        <!-- Profile dropdown simplified -->
        <div class="relative">
            <button class="flex items-center space-x-2 p-1 hover:bg-slate-50 rounded-md">
                <div class="h-6 w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold">
                    <?php echo getInitials($fullName); ?>
                </div>
                <span class="text-sm font-medium"><?php echo htmlspecialchars($fullName); ?></span>
            </button>
        </div>
    </div>
</header>
