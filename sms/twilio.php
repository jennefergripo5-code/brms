<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Twilio\Rest\Client;

function send_sms($conn, $user_id, $contact_number, $message, $sms_type = 'borrow') {

$account_sid = "YOUR_TWILIO_SID";
$auth_token  = "YOUR_TWILIO_TOKEN";
$from        = "YOUR_TWILIO_NUMBER";

    $status = 'pending';

    try {

        // If no credentials = demo mode
        if (!$account_sid || !$auth_token || !$from) {

            $status = 'demo';

        } else {

            $client = new Client($account_sid, $auth_token);

            $client->messages->create(
                $contact_number,
                [
                    'from' => $from,
                    'body' => $message
                ]
            );

            $status = 'sent';
        }

    } catch (Exception $e) {

        $status = 'failed';
        error_log("Twilio SMS Error: " . $e->getMessage());
    }

    // Save SMS log
    $stmt = $conn->prepare("
        INSERT INTO sms_logs
        (user_id, contact_number, message, sms_type, status)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'issss',
        $user_id,
        $contact_number,
        $message,
        $sms_type,
        $status
    );

    $stmt->execute();

    return $status;
}