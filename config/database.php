<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'studybuddy');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check user role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Helper function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Helper function to require specific role
function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        redirect('login.php');
    }
}

// Helper function to sanitize input
function sanitize($conn, $data) {
    return htmlspecialchars(mysqli_real_escape_string($conn, trim($data)));
}
?>