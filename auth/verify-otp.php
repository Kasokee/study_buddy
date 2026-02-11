<?php
session_start();
require_once '../config/database.php';

$error = '';

if (!isset($_SESSION['verify_email'])) {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['verify_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);

    $stmt = $conn->prepare(
        "SELECT id, otp_code, otp_expires FROM users WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        $error = 'Account not found.';
    } elseif ($result['otp_code'] !== $otp) {
        $error = 'Invalid OTP code.';
    } elseif (strtotime($result['otp_expires']) < time()) {
        $error = 'OTP expired. Please sign up again.';
    } else {
        $stmt = $conn->prepare(
            "UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();

        unset($_SESSION['verify_email']);
        header('Location: login.php?verified=1');
        exit;
    }
}

$pageTitle = "Verify OTP - StudyBuddy";
include '../includes/header.php';
?>

<div class="flex items-center justify-center min-h-[calc(100vh-140px)] bg-gray-50">
    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-sm">

        <h2 class="text-2xl font-bold text-center text-gray-900">
            Verify Your Email
        </h2>

        <p class="text-sm text-gray-600 text-center mt-2">
            We sent a 6-digit verification code to  
            <span class="font-semibold"><?php echo htmlspecialchars($email); ?></span>
        </p>

        <?php if ($error): ?>
            <div class="mt-4 bg-red-50 text-red-700 p-3 rounded text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-6" onsubmit="combineOTP()">

            <!-- OTP BOXES -->
            <div class="flex justify-center gap-2">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        class="otp-input w-12 h-12 text-center text-xl font-semibold
                               rounded-md border border-gray-300
                               focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                <?php endfor; ?>
            </div>

            <!-- Hidden combined OTP -->
            <input type="hidden" name="otp" id="otp">

            <button
                type="submit"
                class="w-full bg-indigo-600 text-white py-2 rounded-md
                       font-semibold hover:bg-indigo-500 transition"
            >
                Verify
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Didn’t receive the code?
            <a href="signup.php" class="text-indigo-600 font-semibold hover:text-indigo-500">
                Sign up again
            </a>
        </p>
    </div>
</div>

<script>
const inputs = document.querySelectorAll('.otp-input');

inputs.forEach((input, index) => {
    input.addEventListener('input', () => {
        if (input.value && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !input.value && index > 0) {
            inputs[index - 1].focus();
        }
    });
});

function combineOTP() {
    let otp = '';
    inputs.forEach(input => otp += input.value);
    document.getElementById('otp').value = otp;
}
</script>

<?php include '../includes/footer.php'; ?>
