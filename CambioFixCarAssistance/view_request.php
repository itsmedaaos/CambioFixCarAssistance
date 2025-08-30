<?php
session_start();
require_once 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'helper' && $_SESSION['user_type'] !== 'driver')) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$request_id = $_GET['id'] ?? null; 

$request_details = [];
$message = '';

if (!$request_id) {
    $message = '<div class="alert alert-danger">لم يتم تحديد معرف الطلب.</div>';
} else {
    try {
      
        $stmt = $pdo->prepare("SELECT r.*, u.username as client_username, u.phone_number as client_phone
                               FROM Requests r
                               JOIN Users u ON r.client_user_id = u.user_id
                               WHERE r.request_id = ?");
        $stmt->execute([$request_id]);
        $request_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request_details) {
            $message = '<div class="alert alert-danger">الطلب غير موجود.</div>';
        } elseif ($request_details['status'] !== 'pending' && $request_details['helper_user_id'] !== $user_id && $request_details['driver_user_id'] !== $user_id) {
            $message = '<div class="alert alert-warning">لا يمكنك التعامل مع هذا الطلب.</div>';
        } else {
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
                $action = $_POST['action'];

                if ($action === 'accept' && $request_details['status'] === 'pending') {
                    $stmt_update_request = $pdo->prepare("UPDATE Requests SET status = 'in_progress', helper_user_id = ?, driver_user_id = ?, response_time = CURRENT_TIMESTAMP() WHERE request_id = ?");
                    $assigned_id = ($user_type === 'helper') ? $user_id : null; 
                    if ($user_type === 'driver') {
                        $assigned_id = $user_id; 
                        $stmt_update_request = $pdo->prepare("UPDATE Requests SET status = 'in_progress', driver_user_id = ?, response_time = CURRENT_TIMESTAMP() WHERE request_id = ?");
                        $stmt_update_request->execute([$assigned_id, $request_id]);
                    } else { 
                        $stmt_update_request->execute([$assigned_id, null, $request_id]); 
                    }


                    $message = '<div class="alert alert-success">تم قبول الطلب بنجاح!</div>';
                    $stmt->execute([$request_id]);
                    $request_details = $stmt->fetch(PDO::FETCH_ASSOC);
                } elseif ($action === 'reject' && $request_details['status'] === 'pending') {
                    $stmt_update_request = $pdo->prepare("UPDATE Requests SET status = 'rejected' WHERE request_id = ?");
                    $stmt_update_request->execute([$request_id]);
                    $message = '<div class="alert alert-warning">تم رفض الطلب.</div>';
                    header("Location: " . $user_type . "_dashboard.php");
                    exit();
                } elseif ($action === 'complete' && $request_details['status'] === 'in_progress') {
                    $stmt_update_request = $pdo->prepare("UPDATE Requests SET status = 'completed', completion_time = CURRENT_TIMESTAMP() WHERE request_id = ? AND (helper_user_id = ? OR driver_user_id = ?)");
                    $stmt_update_request->execute([$request_id, $user_id, $user_id]);

                    if ($user_type === 'helper') {
                         $stmt_update_completed_requests = $pdo->prepare("UPDATE Helpers SET total_completed_requests = total_completed_requests + 1 WHERE user_id = ?");
                         $stmt_update_completed_requests->execute([$user_id]);
                    }

                    $message = '<div class="alert alert-success">تم إكمال الطلب بنجاح!</div>';
                    $stmt->execute([$request_id]);
                    $request_details = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        }

    } catch (PDOException $e) {
        error_log("View request error: " . $e->getMessage());
        $message = '<div class="alert alert-danger">حدث خطأ في قاعدة البيانات: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تفاصيل الطلب #<?= htmlspecialchars($request_id) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; color: #343a40; }
    .container { margin-top: 50px; }
    .card { padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .request-header { font-size: 2rem; color: #007bff; margin-bottom: 30px; }
    .detail-item { margin-bottom: 10px; }
    .detail-item strong { min-width: 120px; display: inline-block; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2 class="text-center request-header">تفاصيل الطلب #<?= htmlspecialchars($request_id) ?></h2>

      <?= $message ?>

      <?php if ($request_details): ?>
        <div class="mb-4">
          <div class="detail-item"><strong>نوع الطلب:</strong> <?= htmlspecialchars($request_details['request_type']) ?></div>
          <div class="detail-item"><strong>الموقع:</strong> <?= htmlspecialchars($request_details['location']) ?></div>
          <div class="detail-item"><strong>الوصف:</strong> <?= nl2br(htmlspecialchars($request_details['description'])) ?></div>
          <div class="detail-item"><strong>الحالة:</strong> <span class="badge bg-<?= ($request_details['status'] === 'pending') ? 'warning' : (($request_details['status'] === 'in_progress') ? 'info' : 'success') ?>"><?= htmlspecialchars($request_details['status']) ?></span></div>
          <div class="detail-item"><strong>تاريخ الطلب:</strong> <?= htmlspecialchars($request_details['request_date']) ?></div>
          <div class="detail-item"><strong>العميل:</strong> <?= htmlspecialchars($request_details['client_username']) ?></div>
          <div class="detail-item"><strong>هاتف العميل:</strong> <?= htmlspecialchars($request_details['client_phone']) ?></div>
          <?php if (!empty($request_details['response_time'])): ?>
            <div class="detail-item"><strong>وقت الاستجابة:</strong> <?= htmlspecialchars($request_details['response_time']) ?></div>
          <?php endif; ?>
          <?php if (!empty($request_details['completion_time'])): ?>
            <div class="detail-item"><strong>وقت الإكمال:</strong> <?= htmlspecialchars($request_details['completion_time']) ?></div>
          <?php endif; ?>
        </div>

        <?php if ($user_type === 'helper' || $user_type === 'driver'): ?>
            <div class="text-center mt-4">
                <?php if ($request_details['status'] === 'pending'): ?>
                    <form method="POST" action="" style="display:inline-block;">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="btn btn-success me-2">قبول الطلب</button>
                    </form>
                    <form method="POST" action="" style="display:inline-block;">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-warning">رفض الطلب</button>
                    </form>
                <?php elseif ($request_details['status'] === 'in_progress' && ($request_details['helper_user_id'] == $user_id || $request_details['driver_user_id'] == $user_id)): ?>
                    <form method="POST" action="" style="display:inline-block;">
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" class="btn btn-primary">إكمال الطلب</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
          <a href="<?= htmlspecialchars($user_type) ?>_dashboard.php" class="btn btn-secondary">العودة للوحة التحكم</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>