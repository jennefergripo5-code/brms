<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_admin();
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__ . '/../sms/twilio.php';

refresh_overdue_status($conn);

$msg = '';
$confirm = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rid = (int)$_POST['record_id'];

    $r = $conn->query("
        SELECT br.*, u.full_name, u.contact_number, i.item_name
        FROM borrow_records br
        JOIN users u ON u.user_id = br.user_id
        JOIN items i ON i.item_id = br.item_id
        WHERE record_id = $rid
    ")->fetch_assoc();

    if ($r && $r['status'] !== 'returned') {

        $cs = compute_status_penalty($r['due_date'], date('Y-m-d H:i:s'));
        $penalty = $cs['penalty'];

        $stmt = $conn->prepare("
            UPDATE borrow_records 
            SET return_date = NOW(), status = 'returned', penalty = ? 
            WHERE record_id = ?
        ");
        $stmt->bind_param('di', $penalty, $rid);
        $stmt->execute();

        $conn->query("UPDATE items SET quantity = quantity + 1 WHERE item_id = {$r['item_id']}");

        $smsMsg = "Hi {$r['full_name']}, return of '{$r['item_name']}' confirmed. Penalty: ₱$penalty. - BRMS";
        send_sms($conn, $r['user_id'], $r['contact_number'], $smsMsg, 'return');

        // RECEIPT DATA
        $confirm = [
            'id' => $rid,
            'user' => $r['full_name'],
            'item' => $r['item_name'],
            'penalty' => $penalty,
            'date' => date('Y-m-d H:i:s')
        ];

        $msg = 'Return confirmed.';
    }
}

$rows = $conn->query("
    SELECT br.record_id, br.due_date, br.status,
           u.full_name, i.item_name
    FROM borrow_records br
    JOIN users u ON u.user_id = br.user_id
    JOIN items i ON i.item_id = br.item_id
    WHERE br.status IN ('borrowed','overdue')
    ORDER BY br.due_date ASC
");

include __DIR__.'/../includes/header.php';
?>

<h3>Return Item</h3>

<?php if($msg): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if($confirm): ?>
<div id="receipt">
    <div class="alert alert-info">
        <h5>Return Receipt</h5>
        <p><b>Receipt ID:</b> <?= $confirm['id'] ?></p>
        <p><b>Borrower:</b> <?= e($confirm['user']) ?></p>
        <p><b>Item:</b> <?= e($confirm['item']) ?></p>
        <p><b>Penalty:</b> ₱<?= number_format($confirm['penalty'],2) ?></p>
        <p><b>Returned At:</b> <?= $confirm['date'] ?></p>

        <button class="btn btn-primary btn-sm" onclick="printReceipt()">
            🖨️ Print Receipt
        </button>
    </div>
</div>

<!-- PRINT ONLY RECEIPT -->
<style>
@media print {
    body * { visibility: hidden; }
    #receipt, #receipt * { visibility: visible; }
    #receipt {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }
}
</style>

<script>
function printReceipt() {
    window.print();
}
</script>
<?php endif; ?>

<table class="table table-bordered bg-white">
<thead class="table-light">
<tr>
    <th>#</th>
    <th>Borrower</th>
    <th>Item</th>
    <th>Due</th>
    <th>Status</th>
    <th></th>
</tr>
</thead>
<tbody>
<?php while($r = $rows->fetch_assoc()):
    $color = $r['status'] === 'overdue' ? 'danger' : 'warning';
?>
<tr>
    <td><?= $r['record_id'] ?></td>
    <td><?= e($r['full_name']) ?></td>
    <td><?= e($r['item_name']) ?></td>
    <td><?= e($r['due_date']) ?></td>
    <td><span class="badge bg-<?= $color ?>"><?= ucfirst($r['status']) ?></span></td>
    <td>
        <form method="post" onsubmit="return confirm('Mark as returned?')">
            <input type="hidden" name="record_id" value="<?= $r['record_id'] ?>">
            <button class="btn btn-sm btn-success">
                Return
            </button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<?php include __DIR__.'/../includes/footer.php'; ?>