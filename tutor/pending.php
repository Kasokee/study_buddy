<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Only allow tutors to see this page
if ($_SESSION['role'] !== 'tutor') {
    header('Location: ../login.php');
    exit;
}

// User data for sidebar/header
$userData = [
    'first_name' => $_SESSION['first_name'] ?? 'Unknown',
    'last_name' => $_SESSION['last_name'] ?? 'User',
    'email' => $_SESSION['email'] ?? 'loading...'
];

// Full name
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

$pageTitle = "Pending Account - StudyBuddy";
include '../includes/header.php';
?>

<div class="flex">

    <div class="flex-1 p-6 bg-gray-50 min-h-screen">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-sm text-center">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Account Pending</h1>
            <p class="text-gray-700 mb-6">
                Your tutor account is currently <span class="font-semibold text-yellow-600">pending</span>.
                Please wait for the Administrator to approve your account.
            </p>
            <div class="flex justify-center gap-4">
                <a href="dashboard.php" class="inline-block px-6 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-500 transition">
                    Go to Dashboard
                </a>
                <form method="POST" action="../logout.php">
                    <button type="submit" class="px-6 py-2 rounded-md bg-red-600 text-white hover:bg-red-700 transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
