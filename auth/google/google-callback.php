<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);

if (!isset($_GET['code'])) {
    header('Location: login.php');
    exit();
}

// Exchange code for access token
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if (isset($token['error'])) {
    die('Google Authentication Failed');
}

$client->setAccessToken($token);

// Get user info from Google
$oauth = new Google_Service_Oauth2($client);
$userInfo = $oauth->userinfo->get();

$email      = $userInfo->email;
$first_name = $userInfo->givenName;
$last_name  = $userInfo->familyName ?? '';

// Check if user already exists
$stmt = $conn->prepare('SELECT id, first_name, last_name, role, status FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

$base_url = "http://localhost/studybuddy/";

if ($result->num_rows == 0) {

    // if user does not exists
    $_SESSION['google_signup'] = [
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name
    ];

    header("Location: {$base_url}auth/google/select-role.php");
    exit();

} else {
    // user exists
    $user = $result->fetch_assoc();

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name']  = $user['last_name'];
    $_SESSION['email']      = $email;
    $_SESSION['role']       = $user['role'];
    $_SESSION['login_success'] = true;

    // Tutor pending check
    if ($user['role'] === 'tutor' && $user['status'] === 'pending') {
        header("Location: {$base_url}tutor/pending.php");
        exit();
    }

    if ($user['role'] === 'tutor' && $user['status'] === 'approved') {
        header("Location: {$base_url}tutor/dashboard.php");
    }

    // Redirect by role
    switch ($user['role']) {
        case 'student':
            header("Location: {$base_url}student/dashboard.php");
            break;

        case 'tutor':
            header("Location: {$base_url}tutor/dashboard.php");
            break;

        case 'admin':
            header("Location: {$base_url}admin/dashboard.php");
            break;

        default:
            header("Location: {$base_url}");
            break;
    }

    exit();
}
