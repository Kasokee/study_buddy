<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);

if (isset($_ENV['code'])) {
    header('Location: login.php');
    exit();
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
$client->setAccessToken($token);

$oauth = new Google_Service_Oauth2($client);
$userInfo = $oauth->userinfo->get();


$email = $userInfo->email;
$first_name = $userInfo->givenName;
$last_name = $userInfo->familyName;

$stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists, if not create new user with random password
if ($result->num_rows > 0) {
    $role = 'student';
    $status = 'active';
    $password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);

    $insert = $conn->prepare(
        'INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insert->bind_param('ssssss', $first_name, $last_name, $email, $password, $role, $status);
    $insert->execute();
}

// Login user
$stmt = $conn->prepare('SELECT id, role FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $email;
$_SESSION['role'] = $user['role'];
$_SESSION['login_success'] = true;

$base_url = "http://localhost/studybuddy/";

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
}
exit();

?>