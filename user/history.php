<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_login();
require_once __DIR__.'/../includes/functions.php';

$uid = (int)$_SESSION['user_id'];

// HISTORY DATA
$rows = $conn->query("
    SELECT br.*, i.item_name
    FROM borrow_records br
    JOIN items i ON i.item_id = br.item_id
    WHERE br.user_id = $uid
    ORDER BY br.record_id DESC
");

// STATS
$total = $conn->query("SELECT COUNT(*) c FROM borrow_records WHERE user_id=$uid")->fetch_assoc()['c'];
$active = $conn->query("SELECT COUNT(*) c FROM borrow_records WHERE user_id=$uid AND status='borrowed'")->fetch_assoc()['c'];
$returned = $conn->query("SELECT COUNT(*) c FROM borrow_records WHERE user_id=$uid AND status='returned'")->fetch_assoc()['c'];

include __DIR__.'/../includes/header.php';
?>

<!-- HEADER -->
<div class="mb-4">
    <h3>📜 My Borrow History</h3>
    <small class="text-muted">Track all your borrowing activity</small>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card text-bg-primary shadow-sm">
            <div class="card-body">
                <h6>Total Records</h6>
                <h2><?= $total ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-bg-warning shadow-sm">
            <div class="card-body">
                <h6>Active</h6>
                <h2><?= $active ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-bg-success shadow-sm">
            <div class="card-body">
                <h6>Returned</h6>
                <h2><?= $returned ?></h2>
            </div>
        </div>
    </div>

</div>

<!-- HISTORY CARDS (MODERN UI) -->
<div class="row g-3">

<?php while($r = $rows->fetch_assoc()): ?>

<?php
$color = [
    'borrowed' => 'warning',
    'returned' => 'success',
    'overdue'  => 'danger'
][$r['status']] ?? 'secondary';
?>

<div class="col-md-6">

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <h5>📦 <?= e($r['item_name']) ?></h5>

            <p class="mb-1">
                <b>Borrowed:</b> <?= $r['borrow_date'] ?>
            </p>

            <p class="mb-1">
                <b>Due Date:</b> <?= $r['due_date'] ?>
            </p>

            <p class="mb-1">
                <b>Returned:</b> <?= $r['return_date'] ?? '-' ?>
            </p>

            <p class="mb-2">
                <b>Penalty:</b> ₱<?= number_format($r['penalty'],2) ?>
            </p>

            <span class="badge bg-<?= $color ?>">
                <?= ucfirst($r['status']) ?>
            </span>

        </div>

    </div>

</div>

<?php endwhile; ?>

</div>

<?php include __DIR__.'/../includes/footer.php'; ?>