<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer + .env (reuse database loader if possible)
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function sendOTP($toEmail, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '80b528002@smtp-brevo.com';
        $mail->Password   = $_ENV['BREVO_API_KEY']; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@studybuddy.com', 'StudyBuddy');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your StudyBuddy Verification Code';
        $mail->Body = "
            <div style='font-family: Arial;'>
                <h2>Your OTP Code</h2>
                <p>Use the code below to verify your email:</p>
                <h1 style='letter-spacing:5px;'>$otp</h1>
                <p>This code expires in 10 minutes.</p>
            </div>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}
