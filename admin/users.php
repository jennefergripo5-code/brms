<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_admin();
require_once __DIR__.'/../includes/functions.php';

// CREATE
if ($_POST['action'] ?? '' === 'create') {

    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users(full_name,username,password,contact_number,email,role)
        VALUES(?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "ssssss",
        $_POST['full_name'],
        $_POST['username'],
        $hash,
        $_POST['contact_number'],
        $_POST['email'],
        $_POST['role']
    );

    $stmt->execute();
}

// DELETE
if ($_POST['action'] ?? '' === 'delete') {
    $conn->query("DELETE FROM users WHERE user_id=".(int)$_POST['user_id']);
}

// SEARCH
$q = trim($_GET['q'] ?? '');

$stmt = $conn->prepare("
    SELECT * FROM users
    WHERE full_name LIKE ? OR username LIKE ?
    ORDER BY user_id DESC
");

$like = "%$q%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$users = $stmt->get_result();

// STATS
$total_users = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_admin = $conn->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$total_borrower = $conn->query("SELECT COUNT(*) c FROM users WHERE role='borrower'")->fetch_assoc()['c'];

include __DIR__.'/../includes/header.php';
?>

<h3 class="mb-3">👥 Users Dashboard</h3>

<!-- STATS CARDS -->
<div class="row g-3 mb-3">

  <div class="col-md-4">
    <div class="card text-bg-primary shadow-sm">
      <div class="card-body">
        <h5>Total Users</h5>
        <h2><?= $total_users ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card text-bg-dark shadow-sm">
      <div class="card-body">
        <h5>Admins</h5>
        <h2><?= $total_admin ?></h2>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card text-bg-success shadow-sm">
      <div class="card-body">
        <h5>Borrowers</h5>
        <h2><?= $total_borrower ?></h2>
      </div>
    </div>
  </div>

</div>

<!-- SEARCH -->
<form class="mb-3">
  <input class="form-control shadow-sm"
         name="q"
         value="<?=e($q)?>"
         placeholder="🔍 Search users by name or username...">
</form>

<!-- USERS TABLE -->
<table class="table table-hover table-bordered bg-white shadow-sm">

<thead class="table-dark">
<tr>
  <th>ID</th>
  <th>Name</th>
  <th>Username</th>
  <th>Contact</th>
  <th>Email</th>
  <th>Role</th>
  <th>Action</th>
</tr>
</thead>

<tbody>

<?php while($r=$users->fetch_assoc()): ?>

<tr>
  <td><?= $r['user_id'] ?></td>
  <td><?= e($r['full_name']) ?></td>
  <td><?= e($r['username']) ?></td>
  <td><?= e($r['contact_number']) ?></td>
  <td><?= e($r['email']) ?></td>

  <td>
    <?php if($r['role']=='admin'): ?>
      <span class="badge bg-dark">Admin</span>
    <?php else: ?>
      <span class="badge bg-success">Borrower</span>
    <?php endif; ?>
  </td>

  <td>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete user?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="user_id" value="<?=$r['user_id']?>">
      <button class="btn btn-sm btn-danger">🗑</button>
    </form>
  </td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<!-- ADD USER MODAL -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
  ➕ Add User
</button>

<div class="modal fade" id="addModal">
<div class="modal-dialog">
<div class="modal-content">

<form method="post">

<div class="modal-header">
  <h5>Add User</h5>
  <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

  <input type="hidden" name="action" value="create">

  <div class="mb-2">
    <label>Full Name</label>
    <input class="form-control" name="full_name" required>
  </div>

  <div class="mb-2">
    <label>Username</label>
    <input class="form-control" name="username" required>
  </div>

  <div class="mb-2">
    <label>Password</label>
    <input type="password" class="form-control" name="password" required>
  </div>

  <div class="mb-2">
    <label>Contact No.</label>
    <input class="form-control" name="contact_number" required pattern="^09\d{9}$">
  </div>

  <div class="mb-2">
    <label>Email</label>
    <input type="email" name="email" class="form-control">
  </div>

  <div class="mb-2">
    <label>Role</label>
    <select name="role" class="form-select">
      <option value="borrower">Borrower</option>
      <option value="admin">Admin</option>
    </select>
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