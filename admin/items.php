<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_admin();
require_once __DIR__.'/../includes/functions.php';

// CREATE
if (($_POST['action'] ?? '') === 'create') {

    $stmt = $conn->prepare("
        INSERT INTO items(item_code,item_name,category,quantity,description)
        VALUES(?,?,?,?,?)
    ");

    $stmt->bind_param(
        'sssis',
        $_POST['item_code'],
        $_POST['item_name'],
        $_POST['category'],
        $_POST['quantity'],
        $_POST['description']
    );

    $stmt->execute();
}

// UPDATE (optional future use)
if (($_POST['action'] ?? '') === 'update') {

    $stmt = $conn->prepare("
        UPDATE items
        SET item_name=?, category=?, quantity=?, description=?
        WHERE item_id=?
    ");

    $stmt->bind_param(
        'ssisi',
        $_POST['item_name'],
        $_POST['category'],
        $_POST['quantity'],
        $_POST['description'],
        $_POST['item_id']
    );

    $stmt->execute();
}

// DELETE
if (($_POST['action'] ?? '') === 'delete') {
    $conn->query("DELETE FROM items WHERE item_id=".(int)$_POST['item_id']);
}

// SEARCH + FILTER
$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');

$sql = "SELECT * FROM items WHERE (item_name LIKE ? OR item_code LIKE ?)";
$params = ["%$q%", "%$q%"];
$types = "ss";

if ($cat !== '') {
    $sql .= " AND category = ?";
    $params[] = $cat;
    $types .= "s";
}

$sql .= " ORDER BY item_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result();

// CATEGORY LIST
$cats = $conn->query("SELECT DISTINCT category FROM items WHERE category<>''");

// STATS
$total_items = $conn->query("SELECT COUNT(*) c FROM items")->fetch_assoc()['c'];
$total_stock = $conn->query("SELECT COALESCE(SUM(quantity),0) c FROM items")->fetch_assoc()['c'];
$out_of_stock = $conn->query("SELECT COUNT(*) c FROM items WHERE quantity=0")->fetch_assoc()['c'];

include __DIR__.'/../includes/header.php';
?>

<h3 class="mb-3">📦 Items Dashboard</h3>

<!-- STATS CARDS -->
<div class="row g-3 mb-3">

  <div class="col-md-4">
    <div class="card text-bg-primary shadow-sm">
      <div class="card-body">
        <h5>Total Items</h5>
        <h2><?= $total_items ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card text-bg-success shadow-sm">
      <div class="card-body">
        <h5>Total Stock</h5>
        <h2><?= $total_stock ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card text-bg-danger shadow-sm">
      <div class="card-body">
        <h5>Out of Stock</h5>
        <h2><?= $out_of_stock ?></h2>
      </div>
    </div>
  </div>

</div>

<!-- SEARCH + FILTER -->
<form class="row g-2 mb-3">

  <div class="col-md-6">
    <input class="form-control shadow-sm"
           name="q"
           value="<?=e($q)?>"
           placeholder="🔍 Search item name or code...">
  </div>

  <div class="col-md-4">
    <select class="form-select shadow-sm" name="cat">
      <option value="">All categories</option>
      <?php while($c=$cats->fetch_assoc()): ?>
        <option value="<?=e($c['category'])?>" <?= $cat==$c['category']?'selected':'' ?>>
          <?=e($c['category'])?>
        </option>
      <?php endwhile; ?>
    </select>
  </div>

  <div class="col-md-2">
    <button class="btn btn-dark w-100">Filter</button>
  </div>

</form>

<!-- ITEMS TABLE -->
<table class="table table-hover table-bordered bg-white shadow-sm">

<thead class="table-dark">
<tr>
  <th>Code</th>
  <th>Name</th>
  <th>Category</th>
  <th>Qty</th>
  <th>Description</th>
  <th>Action</th>
</tr>
</thead>

<tbody>

<?php while($r=$items->fetch_assoc()): ?>

<tr>

  <td><?= e($r['item_code']) ?></td>
  <td><?= e($r['item_name']) ?></td>
  <td><?= e($r['category']) ?></td>

  <td>
    <span class="badge bg-<?= $r['quantity']>0?'success':'danger' ?>">
      <?= $r['quantity'] ?>
    </span>
  </td>

  <td><?= e($r['description']) ?></td>

  <td>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete item?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="item_id" value="<?=$r['item_id']?>">
      <button class="btn btn-sm btn-danger">🗑</button>
    </form>
  </td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<!-- ADD ITEM BUTTON -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItem">
  ➕ Add Item
</button>

<!-- ADD MODAL -->
<div class="modal fade" id="addItem">
<div class="modal-dialog">
<div class="modal-content">

<form method="post">

<div class="modal-header">
  <h5>Add Item</h5>
  <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

  <input type="hidden" name="action" value="create">

  <div class="mb-2">
    <label>Item Code</label>
    <input class="form-control" name="item_code" required>
  </div>

  <div class="mb-2">
    <label>Item Name</label>
    <input class="form-control" name="item_name" required>
  </div>

  <div class="mb-2">
    <label>Category</label>
    <input class="form-control" name="category">
  </div>

  <div class="mb-2">
    <label>Quantity</label>
    <input type="number" min="0" class="form-control" name="quantity" value="1">
  </div>

  <div class="mb-2">
    <label>Description</label>
    <textarea class="form-control" name="description"></textarea>
  </div>

</div>

<div class="modal-footer">
  <button class="btn btn-primary">Save</button>
</div>

</form>

</div>
</div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>