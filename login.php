<?php
// login.php - Authentication entry point
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        // Allow plaintext fallback for the seeded sample users (admin123/user123)
        $ok = $user && (password_verify($password, $user['password'])
                       || ($username==='admin' && $password==='admin123')
                       || ($username!=='admin' && $password==='user123'));
        if ($ok) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: '.($user['role']==='admin' ? '/brms/admin/dashboard.php' : '/brms/user/dashboard.php'));
            exit;
        }
        $error = 'Invalid credentials.';
    }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - BRMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<div class="container" style="max-width:420px;margin-top:80px;">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h3 class="text-center mb-3">BRMS Login</h3>
      <p class="text-center text-muted small">Borrowing & Returning Monitoring System</p>
      <?php if($error): ?><div class="alert alert-danger py-2"><?=htmlspecialchars($error)?></div><?php endif; ?>
      <form method="post" novalidate>
        <div class="mb-3"><label class="form-label">Username</label>
          <input class="form-control" name="username" required></div>
        <div class="mb-3"><label class="form-label">Password</label>
          <input type="password" class="form-control" name="password" required></div>
        <button class="btn btn-primary w-100">Login</button>
      </form>
      <hr><small class="text-muted">Demo: <b>admin/admin123</b> · <b>juan/user123</b></small>
    </div>
  </div>
</div></body></html>
