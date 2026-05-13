<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_admin();
require_once __DIR__.'/../includes/functions.php';

refresh_overdue_status($conn);

// SEARCH + FILTER
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

// STATS
$total = $conn->query("SELECT COUNT(*) c FROM borrow_records")->fetch_assoc()['c'];
$borrowed = $conn->query("SELECT COUNT(*) c FROM borrow_records WHERE status='borrowed'")->fetch_assoc()['c'];
$returned = $conn->query("SELECT COUNT(*) c FROM borrow_records WHERE status='returned'")->fetch_assoc()['c'];
$overdue = $conn->query("SELECT COUNT(*) c FROM borrow_records WHERE status='overdue'")->fetch_assoc()['c'];

// QUERY
$sql = "
SELECT br.*, u.full_name, i.item_name
FROM borrow_records br
JOIN users u ON u.user_id = br.user_id
JOIN items i ON i.item_id = br.item_id
WHERE (u.full_name LIKE ? OR i.item_name LIKE ?)
";

$params = ["%$q%", "%$q%"];
$types = "ss";

if (in_array($status, ['borrowed','returned','overdue'])) {
    $sql .= " AND br.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY br.record_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result();

include __DIR__.'/../includes/header.php';
?>

<h3 class="mb-3">📚 Borrow Records Dashboard</h3>

<!-- STATS CARDS -->
<div class="row g-3 mb-3">

  <div class="col-md-3">
    <div class="card text-bg-primary shadow-sm">
      <div class="card-body">
        <h6>Total Records</h6>
        <h2><?= $total ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card text-bg-warning shadow-sm">
      <div class="card-body">
        <h6>Borrowed</h6>
        <h2><?= $borrowed ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card text-bg-success shadow-sm">
      <div class="card-body">
        <h6>Returned</h6>
        <h2><?= $returned ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card text-bg-danger shadow-sm">
      <div class="card-body">
        <h6>Overdue</h6>
        <h2><?= $overdue ?></h2>
      </div>
    </div>
  </div>

</div>

<!-- FILTER -->
<form class="row g-2 mb-3">

  <div class="col-md-6">
    <input class="form-control shadow-sm"
           name="q"
           value="<?= e($q) ?>"
           placeholder="🔍 Search borrower or item">
  </div>

  <div class="col-md-3">
    <select name="status" class="form-select shadow-sm">
      <option value="">All Status</option>
      <option value="borrowed" <?= $status=='borrowed'?'selected':'' ?>>Borrowed</option>
      <option value="returned" <?= $status=='returned'?'selected':'' ?>>Returned</option>
      <option value="overdue" <?= $status=='overdue'?'selected':'' ?>>Overdue</option>
    </select>
  </div>

  <div class="col-md-3">
    <button class="btn btn-dark w-100">Filter</button>
  </div>

</form>

<!-- TABLE -->
<table class="table table-hover table-bordered bg-white shadow-sm">

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
    <span class="badge rounded-pill bg-<?= $color ?>">
      <?= ucfirst($r['status']) ?>
    </span>
  </td>

  <td>₱<?= number_format($r['penalty'], 2) ?></td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<?php include __DIR__.'/../includes/footer.php'; ?>