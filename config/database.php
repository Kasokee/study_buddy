<?php

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

$dotenvPath = dirname(__DIR__);

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create connection using .env
$conn = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD'],
    $_ENV['DB_NAME']
);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

/* ================= HELPERS ================= */

// Redirect helper
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Get user role
if (!function_exists('getUserRole')) {
    function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
}

// Require login
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            redirect('login.php');
        }
    }
}

// Require role
if (!function_exists('requireRole')) {
    function requireRole($role) {
        requireLogin();
        if (getUserRole() !== $role) {
            redirect('login.php');
        }
    }
}

// Sanitize input
if (!function_exists('sanitize')) {
    function sanitize($conn, $data) {
        return htmlspecialchars(mysqli_real_escape_string($conn, trim($data)));
    }
}
