<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['google_signup'])) {
    header("Location: login.php");
    exit();
}

$googleData = $_SESSION['google_signup'];
$role = $_POST['role'] ?? '';
$subject = $_POST['subject'] ?? null;

if (!in_array($role, ['student', 'tutor'])) {
    die("Invalid role");
}

if ($role === 'tutor' && empty($subject)) {
    die("Subject is required for tutors.");
}

$status = ($role === 'tutor') ? 'pending' : 'approved';
$password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users (first_name, last_name, email, password, role, subject, status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssss",
    $googleData['first_name'],
    $googleData['last_name'],
    $googleData['email'],
    $password,
    $role,
    $subject,
    $status
);

$stmt->execute();
$userId = $stmt->insert_id;

// Login session
$_SESSION['user_id'] = $userId;
$_SESSION['email']   = $googleData['email'];
$_SESSION['first_name'] = $googleData['first_name'];
$_SESSION['last_name']  = $googleData['last_name'];
$_SESSION['role'] = $role;
$_SESSION['login_success'] = true;

unset($_SESSION['google_signup']);

$base_url = "http://localhost/studybuddy/";

if ($role === 'tutor') {
    // Redirect tutors to pending page until approved by admin
    header("Location: {$base_url}tutor/pending.php");
} else {
    header("Location: {$base_url}student/dashboard.php");
}
exit();
