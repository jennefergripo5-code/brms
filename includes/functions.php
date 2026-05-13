<?php
// includes/functions.php - Helper functions
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Compute overdue + penalty (₱5 per day)
function compute_status_penalty($due_date, $return_date = null) {
    $today = $return_date ? new DateTime($return_date) : new DateTime();
    $due   = new DateTime($due_date);

    $penalty = 0;

    if ($today > $due) {
        $interval = $due->diff($today);
        $days = $interval->days;

        $penalty = $days * 10; // ₱10 per day
        $status = $return_date ? 'returned' : 'overdue';
    } else {
        $status = $return_date ? 'returned' : 'borrowed';
    }

    return [
        'status' => $status,
        'penalty' => $penalty
    ];
};


// Auto-update overdue status in DB
function refresh_overdue_status($conn) {
    $conn->query("UPDATE borrow_records
                  SET status='overdue'
                  WHERE status='borrowed' AND due_date < CURDATE()");
}
