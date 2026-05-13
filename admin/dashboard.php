<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_admin();
require_once __DIR__.'/../includes/functions.php';

refresh_overdue_status($conn);

// STATS
$stats = [
  'users'    => $conn->query("SELECT COUNT(*) c FROM users WHERE role='borrower'")->fetch_assoc()['c'],
  'items'    => $conn->query("SELECT COALESCE(SUM(quantity),0) c FROM items")->fetch_assoc()['c'],
  'borrowed' => $conn->query("SELECT COUNT(*) c FROM borrow_records WHERE status='borrowed'")->fetch_assoc()['c'],
  'overdue'  => $conn->query("SELECT COUNT(*) c FROM borrow_records WHERE status='overdue'")->fetch_assoc()['c'],
];

// EXTRA INSIGHTS
$due_today = $conn->query("
    SELECT COUNT(*) c 
    FROM borrow_records 
    WHERE due_date = CURDATE() AND status='borrowed'
")->fetch_assoc()['c'];

include __DIR__.'/../includes/header.php';
?>

<h3 class="mb-3">📊 Dashboard Overview</h3>

<!-- INSIGHT BANNER -->
<div class="alert alert-dark shadow-sm">
  <b>System Insight:</b>
  You currently have <b><?= $stats['overdue'] ?></b> overdue items.
  <b><?= $due_today ?></b> items are due today.
</div>

<!-- STATS CARDS -->
<div class="row g-3">

<?php
$cards = [
  ['Borrowers', $stats['users'], 'people-fill', 'primary'],
  ['Items Stock', $stats['items'], 'box-seam', 'success'],
  ['Borrowed', $stats['borrowed'], 'arrow-down-circle', 'warning'],
  ['Overdue', $stats['overdue'], 'exclamation-triangle', 'danger'],
  ['Due Today', $due_today, 'calendar-event', 'info'],
];

foreach ($cards as $c): ?>
  <div class="col-md-3">
    <div class="card text-bg-<?=$c[3]?> shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="small"><?= $c[0] ?></div>
            <h2 class="mb-0"><?= $c[1] ?></h2>
          </div>
          <i class="bi bi-<?= $c[2] ?>" style="font-size:2.2rem;"></i>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

</div>

<!-- RECENT ACTIVITY -->
<div class="d-flex justify-content-between align-items-center mt-4">
  <h5>📌 Recent Activity</h5>
  <span class="text-muted small">Last 10 transactions</span>
</div>

<table class="table table-bordered table-sm bg-white mt-2">
<thead class="table-light">
<tr>
  <th>Borrower</th>
  <th>Item</th>
  <th>Borrow Date</th>
  <th>Due Date</th>
  <th>Status</th>
</tr>
</thead>

<tbody>
<?php
$res = $conn->query("
    SELECT u.full_name, i.item_name, br.borrow_date, br.due_date, br.status
    FROM borrow_records br
    JOIN users u ON u.user_id = br.user_id
    JOIN items i ON i.item_id = br.item_id
    ORDER BY br.record_id DESC
    LIMIT 10
");

while($r=$res->fetch_assoc()):

  $color = [
    'borrowed' => 'warning',
    'returned' => 'success',
    'overdue'  => 'danger'
  ][$r['status']] ?? 'secondary';
?>
<tr>
  <td><?=e($r['full_name'])?></td>
  <td><?=e($r['item_name'])?></td>
  <td><?=e($r['borrow_date'])?></td>
  <td><?=e($r['due_date'])?></td>
  <td>
    <span class="badge rounded-pill bg-<?=$color?>">
      <?=ucfirst($r['status'])?>
    </span>
  </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<?php include __DIR__.'/../includes/footer.php'; ?>