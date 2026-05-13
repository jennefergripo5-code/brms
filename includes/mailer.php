<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function send_email($to, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {

        // Gmail SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // 🔴 CHANGE THESE
        $mail->Username = 'celisjoehannimae@gmail.com';
        $mail->Password = 'tlnv bzjk pijz ffmz';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('celisjoehannimae@gmail.com', 'BRMS System');
        $mail->addAddress($to);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}