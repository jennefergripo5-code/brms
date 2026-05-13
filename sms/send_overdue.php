<?php
// sms/send_overdue.php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/twilio.php';

refresh_overdue_status($conn);

// GET OVERDUE ITEMS
$sql = "SELECT br.record_id, br.borrow_date, br.due_date,
               u.user_id, u.full_name, u.contact_number,
               i.item_name
        FROM borrow_records br
        JOIN users u ON br.user_id = u.user_id
        JOIN items i ON br.item_id = i.item_id
        WHERE br.status = 'overdue'";

$res = $conn->query($sql);

while ($r = $res->fetch_assoc()) {

    // 📌 CALCULATE PENALTY
    $today = date('Y-m-d H:i:s');

    // you already have this function in your system
    $penaltyData = compute_status_penalty($r['due_date'], $today);
    $penalty = $penaltyData['penalty'] ?? 0;

    // 📩 SMS MESSAGE WITH PENALTY
    $msg = "Hi {$r['full_name']}, your item '{$r['item_name']}' 
was due on {$r['due_date']}. 

Current penalty: PHP {$penalty}. 
Please return it immediately to avoid additional charges.

- BRMS";

    send_sms(
        $conn,
        $r['user_id'],
        $r['contact_number'],
        $msg,
        'overdue'
    );

    echo "Reminder sent to {$r['full_name']} (Penalty: PHP $penalty)\n";
}