<?php
session_start();
require_once 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$all_users = [];

try {
    $stmt_users = $pdo->query("SELECT user_id, username, email, phone_number, user_type, account_status, registration_date FROM Users ORDER BY user_id DESC");
    $all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_action'])) {
        $target_user_id = $_POST['target_user_id'] ?? null;
        $action_type = $_POST['action_type'] ?? '';
        $new_status = $_POST['new_status'] ?? '';
        $new_type = $_POST['new_type'] ?? '';

        if ($target_user_id) {
            if ($action_type === 'update_status' && !empty($new_status)) {
                $stmt_update = $pdo->prepare("UPDATE Users SET account_status = ? WHERE user_id = ?");
                $stmt_update->execute([$new_status, $target_user_id]);
                $message = '<div class="alert alert-success">تم تحديث حالة المستخدم بنجاح!</div>';
            } elseif ($action_type === 'update_type' && !empty($new_type)) {
                $stmt_update = $pdo->prepare("UPDATE Users SET user_type = ? WHERE user_id = ?");
                $stmt_update->execute([$new_type, $target_user_id]);
                $message = '<div class="alert alert-success">تم تحديث نوع المستخدم بنجاح!</div>';
            } elseif ($action_type === 'delete_user') {
                $stmt_delete = $pdo->prepare("DELETE FROM Users WHERE user_id = ?");
                $stmt_delete->execute([$target_user_id]);
                $message = '<div class="alert alert-success">تم حذف المستخدم بنجاح!</div>';
            }
            $stmt_users = $pdo->query("SELECT user_id, username, email, phone_number, user_type, account_status, registration_date FROM Users ORDER BY user_id DESC");
            $all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $message = '<div class="alert alert-danger">خطأ: لم يتم تحديد المستخدم المستهدف.</div>';
        }
    }

} catch (PDOException $e) {
    error_log("Manage users error: " . $e->getMessage());
    $message = '<div class="alert alert-danger">حدث خطأ في قاعدة البيانات: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إدارة المستخدمين - لوحة المشرف</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; color: #343a40; }
    .container { margin-top: 50px; }
    .card { padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .admin-header { font-size: 2rem; color: #dc3545; margin-bottom: 30px; }
    .table-responsive { margin-top: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2 class="text-center admin-header">إدارة المستخدمين</h2>

      <?= $message ?>

      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>اسم المستخدم</th>
              <th>البريد الإلكتروني</th>
              <th>نوع الحساب</th>
              <th>حالة الحساب</th>
              <th>رقم الهاتف</th>
              <th>تاريخ التسجيل</th>
              <th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($all_users) > 0): ?>
              <?php foreach ($all_users as $user): ?>
                <tr>
                  <td><?= htmlspecialchars($user['user_id']) ?></td>
                  <td><?= htmlspecialchars($user['username']) ?></td>
                  <td><?= htmlspecialchars($user['email']) ?></td>
                  <td>
                      <form method="POST" style="display:inline;">
                          <input type="hidden" name="target_user_id" value="<?= $user['user_id'] ?>">
                          <input type="hidden" name="action_type" value="update_type">
                          <select name="new_type" class="form-select form-select-sm" onchange="this.form.submit()">
                              <option value="client" <?= ($user['user_type'] == 'client') ? 'selected' : '' ?>>عميل</option>
                              <option value="helper" <?= ($user['user_type'] == 'helper') ? 'selected' : '' ?>>مساعد</option>
                              <option value="driver" <?= ($user['user_type'] == 'driver') ? 'selected' : '' ?>>سائق</option>
                              <option value="admin" <?= ($user['user_type'] == 'admin') ? 'selected' : '' ?>>مشرف</option>
                          </select>
                      </form>
                  </td>
                  <td>
                      <form method="POST" style="display:inline;">
                          <input type="hidden" name="target_user_id" value="<?= $user['user_id'] ?>">
                          <input type="hidden" name="action_type" value="update_status">
                          <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                              <option value="active" <?= ($user['account_status'] == 'active') ? 'selected' : '' ?>>نشط</option>
                              <option value="pending" <?= ($user['account_status'] == 'pending') ? 'selected' : '' ?>>معلق</option>
                              <option value="inactive" <?= ($user['account_status'] == 'inactive') ? 'selected' : '' ?>>غير نشط</option>
                              <option value="rejected" <?= ($user['account_status'] == 'rejected') ? 'selected' : '' ?>>مرفوض</option>
                          </select>
                      </form>
                  </td>
                  <td><?= htmlspecialchars($user['phone_number']) ?></td>
                  <td><?= htmlspecialchars($user['registration_date']) ?></td>
                  <td>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ هذا الإجراء لا يمكن التراجع عنه!');">
                          <input type="hidden" name="target_user_id" value="<?= $user['user_id'] ?>">
                          <input type="hidden" name="action_type" value="delete_user">
                          <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                      </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center">لا يوجد مستخدمون لعرضهم.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="text-center mt-4">
        <a href="completed_requests.php" class="btn btn-secondary">العودة للوحة تحكم المشرف</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>