<?php
require_once 'config/database.php';

$error = '';
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = sanitize($conn, $_POST['first_name'] ?? '');
    $last_name  = sanitize($conn, $_POST['last_name'] ?? '');
    $email      = sanitize($conn, $_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role       = sanitize($conn, $_POST['role'] ?? '');
    $subject    = sanitize($conn, $_POST['subject'] ?? '');

    $old = $_POST;

    // ----- RECAPTCHA VALIDATION -----
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_secret = '6LcTOmMsAAAAAAY8D7YhF_ZtPtarB6wxTaELpNLx';

    if (!$recaptcha_response) {
        $error = 'Please complete the reCAPTCHA.';
    } else {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
        $captcha_success = json_decode($verify);

        if (!$captcha_success->success) {
            $error = 'reCAPTCHA verification failed. Please try again.';
        }
    }

    if (!$error) {
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
            $error = 'Please fill in all required fields.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (!in_array($role, ['student', 'tutor'])) {
            $error = 'Invalid role selected.';
        } elseif ($role === 'tutor' && empty($subject)) {
            $error = 'Tutors must specify their subject.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Email address is already registered.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $subjectVal = $role === 'tutor' ? $subject : null;
                $status = ($role === 'tutor') ? 'pending' : 'approved';

                $stmt = $conn->prepare(
                    "INSERT INTO users (first_name, last_name, email, password, role, subject, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "sssssss",
                    $first_name,
                    $last_name,
                    $email,
                    $hashed_password,
                    $role,
                    $subjectVal,
                    $status
                );

                if ($stmt->execute()) {
                    redirect('login.php?success=registered');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }

            $stmt->close();
        }
    }
}

$pageTitle = "Sign Up - StudyBuddy";
include 'includes/header.php';
?>

<!-- CENTER WRAPPER -->
<div class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 140px);">

    <div class="w-full">

        <!-- Heading -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md flex flex-col items-center">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Create your account
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

                    <!-- First & Last Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-900">First name</label>
                            <input name="first_name" type="text" placeholder="Juan" required
                                   value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>"
                                   class="mt-2 block w-full rounded-md border border-gray-300
                                          py-2 px-3 text-gray-900 shadow-sm
                                          focus:outline-none focus:ring-0 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-900">Last name</label>
                            <input name="last_name" type="text" placeholder="Tamad" required
                                   value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>"
                                   class="mt-2 block w-full rounded-md border border-gray-300
                                          py-2 px-3 text-gray-900 shadow-sm
                                          focus:outline-none focus:ring-0 sm:text-sm">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Email address</label>
                        <input name="email" type="email" placeholder="name@catsu.edu.ph" required
                               value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                               class="mt-2 block w-full rounded-md border border-gray-300
                                      py-2 px-3 text-gray-900 shadow-sm
                                      focus:outline-none focus:ring-0 sm:text-sm">
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900">I am a</label>
                        <select name="role" required onchange="toggleSubject()"
                                class="mt-2 block w-full rounded-md border border-gray-300
                                       py-2 px-3 text-gray-900 shadow-sm bg-white
                                       focus:outline-none focus:ring-0 sm:text-sm">
                            <option value="">Select your role</option>
                            <option value="student" <?php echo ($old['role'] ?? '') === 'student' ? 'selected' : ''; ?>>
                                Student
                            </option>
                            <option value="tutor" <?php echo ($old['role'] ?? '') === 'tutor' ? 'selected' : ''; ?>>
                                Peer Tutor
                            </option>
                        </select>
                    </div>

                    <!-- Subject -->
                    <div id="subjectField"
                         style="display: <?php echo ($old['role'] ?? '') === 'tutor' ? 'block' : 'none'; ?>;">
                        <label class="block text-sm font-medium text-gray-900">
                            Subject you tutor
                        </label>
                        <input name="subject" type="text"
                               value="<?php echo htmlspecialchars($old['subject'] ?? ''); ?>"
                               class="mt-2 block w-full rounded-md border border-gray-300
                                      py-2 px-3 text-gray-900 shadow-sm
                                      focus:outline-none focus:ring-0 sm:text-sm">
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Password</label>
                        <input name="password" type="password" placeholder="•••••" required
                               class="mt-2 block w-full rounded-md border border-gray-300
                                      py-2 px-3 text-gray-900 shadow-sm
                                      focus:outline-none focus:ring-0 sm:text-sm">
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Confirm password</label>
                        <input name="confirm_password" type="password" placeholder="•••••" required
                               class="mt-2 block w-full rounded-md border border-gray-300
                                      py-2 px-3 text-gray-900 shadow-sm
                                      focus:outline-none focus:ring-0 sm:text-sm">
                    </div>

                    <!-- Google reCAPTCHA -->
                    <div class="mt-4">
                        <div class="g-recaptcha" data-sitekey="6LcTOmMsAAAAAMfGrqpKUpAaLexaRED3Lnsz3Rb6"></div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full rounded-md bg-indigo-600 py-2 text-sm font-semibold
                                   text-white shadow-sm hover:bg-indigo-500
                                   transition active:scale-[0.98] mt-4">
                        Create account
                    </button>
                </form>
            </div>

            <p class="mt-10 text-center text-sm text-gray-500">
                Already have an account?
                <a href="login.php" class="font-semibold text-indigo-600 hover:text-indigo-500">
                    Sign in
                </a>
            </p>
        </div>

    </div>
</div>

<script>
function toggleSubject() {
    const role = document.querySelector('[name="role"]').value;
    document.getElementById('subjectField').style.display =
        role === 'tutor' ? 'block' : 'none';
}
</script>

<!-- Load reCAPTCHA -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<?php include 'includes/footer.php'; ?>
