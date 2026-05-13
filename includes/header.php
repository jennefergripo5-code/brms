<?php require_once __DIR__.'/auth.php'; require_login(); $u = current_user(); ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>BRMS - Borrowing & Returning Monitoring System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="/brms/assets/css/style.css" rel="stylesheet">
</head><body>
<nav class="navbar navbar-dark bg-primary px-3">
  <span class="navbar-brand mb-0 h1"><i class="bi bi-journal-bookmark"></i> BRMS</span>
  <div class="text-white">
    <i class="bi bi-person-circle"></i> <?=e($u['name'])?> (<?=e($u['role'])?>)
    &nbsp;|&nbsp; <a class="text-white" href="/brms/logout.php">Logout</a>
  </div>
</nav>
<div class="d-flex">
<aside class="sidebar bg-light p-3">
<?php if($u['role']==='admin'): ?>
  <a href="/brms/admin/dashboard.php" class="d-block py-2"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/brms/admin/users.php" class="d-block py-2"><i class="bi bi-people"></i> Users</a>
  <a href="/brms/admin/items.php" class="d-block py-2"><i class="bi bi-box-seam"></i> Items</a>
  <a href="/brms/admin/borrow.php" class="d-block py-2"><i class="bi bi-arrow-down-circle"></i> Borrow</a>
  <a href="/brms/admin/return.php" class="d-block py-2"><i class="bi bi-arrow-up-circle"></i> Return</a>
  <a href="/brms/admin/records.php" class="d-block py-2"><i class="bi bi-list-check"></i> Records</a>
  <a href="/brms/reports/reports.php" class="d-block py-2"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
<?php else: ?>
  <a href="/brms/user/dashboard.php" class="d-block py-2"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/brms/user/history.php" class="d-block py-2"><i class="bi bi-clock-history"></i> My History</a>
<?php endif; ?>
</aside>
<main class="flex-grow-1 p-4">
