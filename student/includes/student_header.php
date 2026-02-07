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
?>
<header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between sticky top-0 z-10">
    <div class="flex items-center space-x-8">
        <nav class="hidden md:flex items-center space-x-6">
            <a href="#" class="text-sm font-semibold text-slate-900">Overview</a>
            <a href="#" class="text-sm font-medium text-slate-500 hover:text-slate-900">Settings</a>
        </nav>
    </div>

    <div class="flex items-center space-x-4">
        <button class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors">
            <i class="bi bi-bell"></i>
        </button>
        <button class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors">
            <i class="bi bi-moon"></i>
        </button>
        <button class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors">
            <i class="bi bi-gear"></i>
        </button>

        <!-- Profile dropdown -->
        <div class="relative" id="profileDropdown">
            <button id="profileBtn" class="flex items-center space-x-2 p-1 hover:bg-slate-50 rounded-md focus:outline-none">
                <div class="h-6 w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold">
                    <?php echo getInitials($fullName); ?>
                </div>
                <span class="text-sm font-medium"><?php echo htmlspecialchars($fullName); ?></span>
            </button>

            <div id="profileMenu" class="hidden absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-lg shadow-xl z-50 py-1.5">
                <div class="px-4 py-2.5 border-b border-slate-100 mb-1">
                    <p class="text-sm font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($fullName); ?></p>
                    <p class="text-xs text-slate-500 mt-0.5 truncate whitespace-nowrap"><?php echo htmlspecialchars($userData['email']); ?></p>
                </div>
                <div class="py-1">
                    <?php
                    $menuItems = [
                        ['label' => 'Profile', 'shortcut' => '⇧⌘P'],
                        ['label' => 'Settings', 'shortcut' => '⌘S'],
                    ];
                    foreach ($menuItems as $item): ?>
                        <button class="w-full flex items-center justify-between px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors group">
                            <span class="group-hover:text-slate-900"><?php echo $item['label']; ?></span>
                            <span class="text-[10px] text-slate-400 font-mono tracking-tighter bg-slate-50 px-1 rounded border border-slate-100"><?php echo $item['shortcut']; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="border-t border-slate-100 mt-1 pt-1">
                    <button id="signOutBtn" class="w-full flex items-center justify-between px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium">
                        <span>Sign out</span>
                        <span class="text-[10px] text-red-400 font-mono tracking-tighter">⇧⌘Q</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Sign Out Confirmation Modal -->
<div id="signOutModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeSignOutModal()"></div>
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
        <h3 class="text-lg font-semibold text-slate-900">Sign Out</h3>
        <p class="text-sm text-slate-500 mt-2">
            Are you sure you want to sign out? You will need to log back in to access your dashboard.
        </p>
        <div class="flex items-center justify-end space-x-3 mt-6">
            <button onclick="closeSignOutModal()" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-md transition-colors">
                Cancel
            </button>
            <form method="POST" action="../logout.php">
                <button type="submit" class="px-4 py-2 text-sm font-medium bg-red-600 text-white hover:bg-red-700 rounded-md transition-all shadow-sm">
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Toggle dropdown
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');

    profileBtn.addEventListener('click', () => {
        profileMenu.classList.toggle('hidden');
    });

    // Close dropdown if clicked outside
    document.addEventListener('click', (e) => {
        if (!document.getElementById('profileDropdown').contains(e.target)) {
            profileMenu.classList.add('hidden');
        }
    });

    // Sign out modal
    const signOutBtn = document.getElementById('signOutBtn');
    const signOutModal = document.getElementById('signOutModal');

    signOutBtn.addEventListener('click', () => {
        signOutModal.classList.remove('hidden');
        profileMenu.classList.add('hidden'); // close dropdown
    });

    function closeSignOutModal() {
        signOutModal.classList.add('hidden');
    }
</script>
