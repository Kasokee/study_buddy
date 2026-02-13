<?php
session_start();
if (!isset($_SESSION['google_signup'])) {
    header("Location: login.php");
    exit();
}
?>

<?php include '../../includes/header.php'; ?>

<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg p-8 w-full max-w-md text-center">

        <h2 class="text-2xl font-bold mb-6">Choose Your Role</h2>
        <p class="text-gray-500 mb-6">Continue your Google signup</p>

        <div class="space-y-4">

            <!-- Student -->
            <form action="complete-google-signup.php" method="POST">
                <input type="hidden" name="role" value="student">
                <button class="w-full py-3 rounded-md bg-indigo-600 text-white font-semibold hover:bg-indigo-500">
                    I am a Student
                </button>
            </form>

            <!-- Tutor -->
            <form action="google-tutor-subject.php" method="POST">
                <input type="hidden" name="role" value="tutor">
                <button class="w-full py-3 rounded-md border border-gray-300 font-semibold hover:bg-gray-50">
                    I am a Tutor
                </button>
            </form>

        </div>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>
