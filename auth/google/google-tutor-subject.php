<?php
session_start();
if (!isset($_SESSION['google_signup'])) {
    header("Location: login.php");
    exit();
}

$role = $_POST['role'] ?? 'tutor'; // default to tutor
?>

<?php include '../../includes/header.php'; ?>

<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg p-8 w-full max-w-md">

        <h2 class="text-2xl font-bold mb-4 text-center">Tutor Subject</h2>
        <p class="text-gray-500 mb-6 text-center">What subject will you be tutoring?</p>

        <form action="complete-google-signup.php" method="POST" class="space-y-4">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">

            <div>
                <label class="block text-sm font-medium text-gray-900">Subject</label>
                <input type="text" name="subject" required
                       placeholder="e.g. Mathematics, Programming"
                       class="mt-2 block w-full rounded-md border border-gray-300
                              py-2 px-3 text-gray-900 shadow-sm focus:outline-none sm:text-sm">
            </div>

            <button class="w-full py-3 rounded-md bg-indigo-600 text-white font-semibold hover:bg-indigo-500">
                Continue
            </button>
        </form>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>
