<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {

        $stmt = $conn->prepare(
            "SELECT id, first_name, last_name, email, password, role, status 
             FROM users WHERE email = ?"
        );

        if (!$stmt) {
            $error = "Database query failed.";
        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {

                    // Session data
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name']  = $user['last_name'];
                    $_SESSION['email']      = $user['email'];
                    $_SESSION['role']       = $user['role'];

                    // login success flag (for toast later)
                    $_SESSION['login_success'] = true;

                    $base_url = "http://localhost/studybuddy/";

                    // ⏳ Pending tutor
                    if ($user['role'] === 'tutor' && $user['status'] === 'pending') {
                        header("Location: {$base_url}tutor/pending.php");
                        exit;
                    }

                    // Redirect by role
                    switch ($user['role']) {
                        case 'student':
                            header("Location: {$base_url}student/dashboard.php");
                            exit;
                        case 'tutor':
                            header("Location: {$base_url}tutor/dashboard.php");
                            exit;
                        case 'admin':
                            header("Location: {$base_url}admin/dashboard.php");
                            exit;
                    }

                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }

            $stmt->close();
        }
    }
}

$pageTitle = "Login - StudyBuddy";
include '../includes/header.php';
?>

<!-- CENTER WRAPPER -->
<div class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 140px);">

    <div class="flex-grow flex items-center justify-center">

        <div class="w-full">

            <!-- Heading -->
            <div class="sm:mx-auto sm:w-full sm:max-w-md flex flex-col items-center">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Sign in to your account
                </h2>
            </div>

            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
                <div class="bg-white py-8 px-6 shadow-sm sm:rounded-lg sm:px-10">

                    <?php if ($error): ?>
                        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-900">
                                Email address
                            </label>
                            <input
                                name="email"
                                type="email"
                                placeholder="name@catsu.edu.ph"
                                required
                                class="mt-2 block w-full rounded-md border border-gray-300
                                       py-2 px-3 text-gray-900 shadow-sm
                                       focus:outline-none focus:ring-0 sm:text-sm">
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-900">
                                Password
                            </label>
                            <input
                                name="password"
                                type="password"
                                placeholder="•••••"
                                required
                                class="mt-2 block w-full rounded-md border border-gray-300
                                       py-2 px-3 text-gray-900 shadow-sm
                                       focus:outline-none focus:ring-0 sm:text-sm">
                        </div>

                        <!-- Remember / Forgot -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300">
                                Remember me
                            </label>

                            <a href="#" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                                Forgot password?
                            </a>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            class="w-full rounded-md bg-indigo-600 py-2 text-sm font-semibold
                                   text-white shadow-sm hover:bg-indigo-500
                                   transition active:scale-[0.98]">
                            Sign in
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="bg-white px-4 text-gray-500">Or continue with</span>
                            </div>
                        </div>

                        <!-- Google (KEPT) -->
                         <a href="google-login.php" class="flex items-center justify-center gap-2 rounded-md border mt-1 py-2 text-sm font-medium hover:bg-gray-50 w-full transition">
                            <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="h-5 w-5">
                            Continue with Google
                         </a>
                    </div>

                </div>

                <p class="mt-10 text-center text-sm text-gray-500">
                    Not a member?
                    <a href="signup.php" class="font-semibold text-indigo-600 hover:text-indigo-500">
                        Sign Up
                    </a>
                </p>

            </div>

        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
