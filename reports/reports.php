<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_admin();
require_once __DIR__.'/../includes/functions.php';

refresh_overdue_status($conn);

// DATE RANGE
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// QUERY
$stmt = $conn->prepare("
    SELECT br.*, u.full_name, i.item_name
    FROM borrow_records br
    JOIN users u ON u.user_id=br.user_id
    JOIN items i ON i.item_id=br.item_id
    WHERE DATE(br.borrow_date) BETWEEN ? AND ?
    ORDER BY br.borrow_date DESC
");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$rows = $stmt->get_result();

// STATS
$totalRecords = $conn->query("
    SELECT COUNT(*) c FROM borrow_records
    WHERE DATE(borrow_date) BETWEEN '$from' AND '$to'
")->fetch_assoc()['c'];

$totalPenalty = $conn->query("
    SELECT COALESCE(SUM(penalty),0) c FROM borrow_records
    WHERE DATE(borrow_date) BETWEEN '$from' AND '$to'
")->fetch_assoc()['c'];

$totalReturned = $conn->query("
    SELECT COUNT(*) c FROM borrow_records
    WHERE status='returned'
    AND DATE(borrow_date) BETWEEN '$from' AND '$to'
")->fetch_assoc()['c'];

$totalOverdue = $conn->query("
    SELECT COUNT(*) c FROM borrow_records
    WHERE status='overdue'
    AND DATE(borrow_date) BETWEEN '$from' AND '$to'
")->fetch_assoc()['c'];

include __DIR__.'/../includes/header.php';
?>

<h3 class="mb-3">📊 Reports Dashboard</h3>

<!-- STATS CARDS -->
<div class="row g-3 mb-3">

  <div class="col-md-3">
    <div class="card text-bg-primary shadow-sm">
      <div class="card-body">
        <h6>Total Records</h6>
        <h2><?= $totalRecords ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card text-bg-success shadow-sm">
      <div class="card-body">
        <h6>Returned</h6>
        <h2><?= $totalReturned ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card text-bg-danger shadow-sm">
      <div class="card-body">
        <h6>Overdue</h6>
        <h2><?= $totalOverdue ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card text-bg-warning shadow-sm">
      <div class="card-body">
        <h6>Total Penalty</h6>
        <h2>₱<?= number_format($totalPenalty,2) ?></h2>
      </div>
    </div>
  </div>

</div>

<!-- HEADER ACTION -->
<div class="d-flex justify-content-between align-items-center mb-3">

  <h5>Borrowing Report (<?= e($from) ?> → <?= e($to) ?>)</h5>

  <button class="btn btn-dark" onclick="window.print()">
    🖨 Print Report
  </button>

</div>

<!-- FILTER -->
<form class="row g-2 mb-3 d-print-none">

  <div class="col-md-4">
    <label>From</label>
    <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
  </div>

  <div class="col-md-4">
    <label>To</label>
    <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
  </div>

  <div class="col-md-4 d-flex align-items-end">
    <button class="btn btn-primary w-100">Generate</button>
  </div>

</form>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="card-body">

<table class="table table-hover table-bordered">

<thead class="table-dark">
<tr>
  <th>#</th>
  <th>Borrower</th>
  <th>Item</th>
  <th>Borrow Date</th>
  <th>Due Date</th>
  <th>Returned</th>
  <th>Status</th>
  <th>Penalty</th>
</tr>
</thead>

<tbody>

<?php while($r=$rows->fetch_assoc()):

  $color = [
    'borrowed' => 'warning',
    'returned' => 'success',
    'overdue'  => 'danger'
  ][$r['status']] ?? 'secondary';

?>

<tr>

  <td><?= $r['record_id'] ?></td>
  <td><?= e($r['full_name']) ?></td>
  <td><?= e($r['item_name']) ?></td>
  <td><?= e($r['borrow_date']) ?></td>
  <td><?= e($r['due_date']) ?></td>
  <td><?= e($r['return_date'] ?? '-') ?></td>

  <td>
    <span class="badge bg-<?= $color ?>">
      <?= ucfirst($r['status']) ?>
    </span>
  </td>

  <td>₱<?= number_format($r['penalty'],2) ?></td>

</tr>

<?php endwhile; ?>

</tbody>

<tfoot>
<tr>
  <th colspan="7" class="text-end">Total Penalty</th>
  <th>₱<?= number_format($totalPenalty,2) ?></th>
</tr>
</tfoot>

</table>

</div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>