<?php
session_start();
require_once 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق مما إذا كان المستخدم مسجل الدخول ونوعه "client"
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
//     header("Location: login.php");
//     exit();
// }

$client_user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_type = trim($_POST['request_type'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($request_type) || empty($location) || empty($description)) {
        $message = '<div class="alert alert-danger">الرجاء تعبئة جميع الحقول المطلوبة لتقديم الطلب.</div>';
    } else {
        try {
            // إدراج الطلب الجديد في جدول Requests
            $stmt = $pdo->prepare("INSERT INTO Requests (client_user_id, request_type, location, description, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$client_user_id, $request_type, $location, $description]);
            $message = '<div class="alert alert-success">تم إرسال طلبك بنجاح! سيتم مراجعته قريباً.</div>';
        } catch (PDOException $e) {
            error_log("Request submission error: " . $e->getMessage());
            $message = '<div class="alert alert-danger">حدث خطأ أثناء إرسال الطلب: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>طلب مساعدة جديدة</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; color: #343a40; }
    .container { margin-top: 50px; }
    .card { padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .form-header { font-size: 2rem; color: #007bff; margin-bottom: 30px; }
    .form-label { font-weight: bold; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2 class="text-center form-header">تقديم طلب مساعدة جديد</h2>
      <p class="text-center">الرجاء ملء التفاصيل لطلب المساعدة.</p>

      <?= $message ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label for="request_type" class="form-label">نوع الطلب:</label>
          <select class="form-select" id="request_type" name="request_type" required>
            <option value="">اختر نوع الطلب</option>
            <option value="tire_change" <?= (($_POST['request_type'] ?? '') == 'tire_change') ? 'selected' : '' ?>>تغيير إطار</option>
            <option value="battery_boost" <?= (($_POST['request_type'] ?? '') == 'battery_boost') ? 'selected' : '' ?>>شحن بطارية</option>
            <option value="fuel_delivery" <?= (($_POST['request_type'] ?? '') == 'fuel_delivery') ? 'selected' : '' ?>>توصيل وقود</option>
            <option value="towing" <?= (($_POST['request_type'] ?? '') == 'towing') ? 'selected' : '' ?>>خدمة سحب</option>
            <option value="lockout" <?= (($_POST['request_type'] ?? '') == 'lockout') ? 'selected' : '' ?>>فتح سيارة مغلقة</option>
            <option value="other" <?= (($_POST['request_type'] ?? '') == 'other') ? 'selected' : '' ?>>أخرى</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="location" class="form-label">الموقع:</label>
          <input type="text" class="form-control" id="location" name="location" placeholder="أدخل موقعك الحالي" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">الوصف التفصيلي للمشكلة:</label>
          <textarea class="form-control" id="description" name="description" rows="4" placeholder="قدم وصفًا دقيقًا للمشكلة التي تواجهها" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="text-center mt-4">
          <button type="submit" class="btn btn-primary">إرسال الطلب</button>
          <a href="client_dashboard.php" class="btn btn-secondary me-2">العودة للوحة التحكم</a>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>