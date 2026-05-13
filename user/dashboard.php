<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_login();
require_once __DIR__.'/../includes/functions.php';

$uid = $_SESSION['user_id'];

// USER INFO
$user = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();

// ACTIVE BORROWINGS
$active = $conn->query("
    SELECT br.*, i.item_name
    FROM borrow_records br
    JOIN items i ON i.item_id = br.item_id
    WHERE br.user_id=$uid AND br.status IN ('borrowed','overdue')
    ORDER BY br.due_date ASC
");

// HISTORY
$history = $conn->query("
    SELECT br.*, i.item_name
    FROM borrow_records br
    JOIN items i ON i.item_id = br.item_id
    WHERE br.user_id=$uid
    ORDER BY br.record_id DESC
    LIMIT 10
");

// STATS
$totalBorrowed = $conn->query("
    SELECT COUNT(*) c FROM borrow_records WHERE user_id=$uid
")->fetch_assoc()['c'];

$activeCount = $conn->query("
    SELECT COUNT(*) c FROM borrow_records
    WHERE user_id=$uid AND status IN('borrowed','overdue')
")->fetch_assoc()['c'];

$returned = $conn->query("
    SELECT COUNT(*) c FROM borrow_records
    WHERE user_id=$uid AND status='returned'
")->fetch_assoc()['c'];

include __DIR__.'/../includes/header.php';
?>

<!-- HEADER -->
<div class="mb-4">
    <h3>👋 Welcome, <?= e($user['full_name']) ?></h3>
    <small class="text-muted">Your borrowing dashboard</small>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card text-bg-primary shadow-sm">
            <div class="card-body">
                <h6>Total Borrowed</h6>
                <h2><?= $totalBorrowed ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-bg-warning shadow-sm">
            <div class="card-body">
                <h6>Active Borrowings</h6>
                <h2><?= $activeCount ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-bg-success shadow-sm">
            <div class="card-body">
                <h6>Returned Items</h6>
                <h2><?= $returned ?></h2>
            </div>
        </div>
    </div>

</div>

<!-- ACTIVE BORROWINGS -->
<h5 class="mb-3">📦 My Active Borrowings</h5>

<div class="row g-3">

<?php while($r = $active->fetch_assoc()): ?>

<?php
$color = $r['status'] === 'overdue' ? 'danger' : 'warning';
?>

<div class="col-md-6">

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <h5><?= e($r['item_name']) ?></h5>

            <p class="mb-1">
                <b>Borrowed:</b> <?= $r['borrow_date'] ?>
            </p>

            <p class="mb-1">
                <b>Due:</b> <?= $r['due_date'] ?>
            </p>

            <span class="badge bg-<?= $color ?>">
                <?= ucfirst($r['status']) ?>
            </span>

        </div>

    </div>

</div>

<?php endwhile; ?>

</div>

<!-- HISTORY -->
<h5 class="mt-5 mb-3">📜 Recent History</h5>

<div class="card shadow-sm">
<div class="card-body">

<table class="table table-hover">

<thead class="table-dark">
<tr>
    <th>Item</th>
    <th>Borrowed</th>
    <th>Due</th>
    <th>Status</th>
</tr>
</thead>

<tbody>

<?php while($h = $history->fetch_assoc()): ?>

<?php
$color = [
    'borrowed' => 'warning',
    'returned' => 'success',
    'overdue'  => 'danger'
][$h['status']] ?? 'secondary';
?>

<tr>
    <td><?= e($h['item_name']) ?></td>
    <td><?= $h['borrow_date'] ?></td>
    <td><?= $h['due_date'] ?></td>
    <td>
        <span class="badge bg-<?= $color ?>">
            <?= ucfirst($h['status']) ?>
        </span>
    </td>
</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>