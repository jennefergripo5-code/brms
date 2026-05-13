<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php'; require_admin();
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/mailer.php';
require_once __DIR__ . '/../sms/twilio.php';

$msg = '';
$receipt = null;

// PROCESS BORROW
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $uid = (int)$_POST['user_id'];
    $iid = (int)$_POST['item_id'];
    $due = $_POST['due_date'];

    // GET USER + ITEM
    $u = $conn->query("SELECT * FROM users WHERE user_id=$uid")->fetch_assoc();
    $it = $conn->query("SELECT * FROM items WHERE item_id=$iid")->fetch_assoc();

    // VALIDATION
    if (!$it || $it['quantity'] <= 0) {
        $msg = '❌ Item is out of stock.';
    } else {

        // CHECK DUPLICATE BORROW
        $dup = $conn->query("
            SELECT 1 FROM borrow_records
            WHERE user_id=$uid AND item_id=$iid
            AND status IN('borrowed','overdue')
        ")->num_rows;

        if ($dup) {
            $msg = '⚠ User already borrowed this item.';
        } else {

            // INSERT BORROW
            $stmt = $conn->prepare("
                INSERT INTO borrow_records(user_id,item_id,due_date,status)
                VALUES(?,?,?,'borrowed')
            ");
            $stmt->bind_param('iis', $uid, $iid, $due);
            $stmt->execute();

            $rid = $stmt->insert_id;

            // UPDATE STOCK
            $conn->query("UPDATE items SET quantity = quantity - 1 WHERE item_id=$iid");

            // RECEIPT
            $receipt = [
                'id' => $rid,
                'user' => $u['full_name'],
                'item' => $it['item_name'],
                'due' => $due,
                'date' => date('Y-m-d H:i')
            ];

            $msg = '✅ Borrow recorded successfully.';

            // ======================
            // 📱 SMS NOTIFICATION
            // ======================
            $smsMsg = "Hi {$u['full_name']}, you borrowed '{$it['item_name']}'. Due date: $due. - BRMS";
            send_sms($conn, $uid, $u['contact_number'], $smsMsg, 'borrow');

            // ======================
            // 📧 EMAIL NOTIFICATION
            // ======================
            if (!empty($u['email'])) {

                $subject = "BRMS Borrow Confirmation";

                $message = "
Hello {$u['full_name']},

You successfully borrowed: {$it['item_name']}
Due Date: {$due}

Please return it on time to avoid penalties.

- BRMS System
";

                send_email($u['email'], $subject, $message);
            }
        }
    }
}

// DATA
$users = $conn->query("
    SELECT user_id, full_name
    FROM users
    WHERE role='borrower'
    ORDER BY full_name
");

$items = $conn->query("
    SELECT item_id, item_name, quantity
    FROM items
    WHERE quantity > 0
    ORDER BY item_name
");

include __DIR__.'/../includes/header.php';
?>

<h3 class="mb-3">📤 Borrow Item</h3>

<?php if($msg): ?>
<div class="alert alert-info shadow-sm">
  <?= $msg ?>
</div>
<?php endif; ?>

<?php if($receipt): ?>
<div class="card shadow-sm mb-3">
  <div class="card-body">

    <h5>📄 Borrow Receipt #<?= $receipt['id'] ?></h5>

    <p><b>Borrower:</b> <?= e($receipt['user']) ?></p>
    <p><b>Item:</b> <?= e($receipt['item']) ?></p>
    <p><b>Borrow Date:</b> <?= e($receipt['date']) ?></p>
    <p><b>Due Date:</b> <?= e($receipt['due']) ?></p>

    <button class="btn btn-secondary btn-sm" onclick="window.print()">
      🖨 Print Receipt
    </button>

  </div>
</div>
<?php endif; ?>

<!-- FORM -->
<form method="post" class="card p-3 shadow-sm" style="max-width:520px">

  <div class="mb-2">
    <label>Borrower</label>
    <select name="user_id" class="form-select" required>
      <option value="">-- select borrower --</option>
      <?php while($u=$users->fetch_assoc()): ?>
        <option value="<?=$u['user_id']?>">
          <?= e($u['full_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>
  </div>

  <div class="mb-2">
    <label>Item</label>
    <select name="item_id" class="form-select" required>
      <option value="">-- select item --</option>
      <?php while($i=$items->fetch_assoc()): ?>
        <option value="<?=$i['item_id']?>">
          <?= e($i['item_name']) ?> (<?= $i['quantity'] ?> left)
        </option>
      <?php endwhile; ?>
    </select>
  </div>

  <div class="mb-3">
    <label>Due Date</label>
    <input type="date"
           name="due_date"
           class="form-control"
           min="<?= date('Y-m-d') ?>"
           value="<?= date('Y-m-d', strtotime('+7 days')) ?>"
           required>
  </div>

  <button class="btn btn-primary w-100">
    ➕ Confirm Borrow
  </button>

</form>

<?php include __DIR__.'/../includes/footer.php'; ?>