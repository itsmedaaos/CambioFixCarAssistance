<?php
session_start();
require_once 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'];
$user_data = [];
$message = '';

try {
    $stmt = $pdo->prepare("SELECT username, email, phone_number, user_type, account_status FROM Users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_type === 'helper') {
        $stmt_helper = $pdo->prepare("SELECT current_availability, total_completed_requests, rating FROM Helpers WHERE user_id = ?");
        $stmt_helper->execute([$user_id]);
        $helper_data = $stmt_helper->fetch(PDO::FETCH_ASSOC);
        if ($helper_data) {
            $user_data = array_merge($user_data, $helper_data);
        }
    } elseif ($user_type === 'driver') {
        $stmt_driver_app = $pdo->prepare("SELECT license_number, vehicle_details, status FROM DriverApplications WHERE user_id = ? ORDER BY application_date DESC LIMIT 1");
        $stmt_driver_app->execute([$user_id]);
        $driver_app_data = $stmt_driver_app->fetch(PDO::FETCH_ASSOC);
        if ($driver_app_data) {
            $user_data = array_merge($user_data, $driver_app_data);
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username_field'] ?? '');
        $new_email = trim($_POST['email_field'] ?? '');
        $new_phone_number = trim($_POST['phone_number_field'] ?? '');

        if (empty($new_username) || empty($new_email) || empty($new_phone_number)) {
            $message = '<div class="alert alert-danger">الرجاء تعبئة جميع الحقول المطلوبة.</div>';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div class="alert alert-danger">تنسيق البريد الإلكتروني غير صحيح.</div>';
        } else {
            $stmt_check_duplicate = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE (email = ? OR username = ?) AND user_id != ?");
            $stmt_check_duplicate->execute([$new_email, $new_username, $user_id]);
            if ($stmt_check_duplicate->fetchColumn() > 0) {
                $message = '<div class="alert alert-danger">البريد الإلكتروني أو اسم المستخدم موجود بالفعل.</div>';
            } else {
                $stmt_update = $pdo->prepare("UPDATE Users SET username = ?, email = ?, phone_number = ? WHERE user_id = ?");
                $stmt_update->execute([$new_username, $new_email, $new_phone_number, $user_id]);

                $_SESSION['username'] = $new_username;

                if ($user_type === 'helper' && isset($_POST['current_availability'])) {
                    $new_availability = $_POST['current_availability'];
                    $stmt_update_helper = $pdo->prepare("UPDATE Helpers SET current_availability = ? WHERE user_id = ?");
                    $stmt_update_helper->execute([$new_availability, $user_id]);
                }

                $message = '<div class="alert alert-success">تم تحديث الملف الشخصي بنجاح!</div>';
                $stmt = $pdo->prepare("SELECT username, email, phone_number, user_type, account_status FROM Users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }

} catch (PDOException $e) {
    error_log("Profile error: " . $e->getMessage());
    $message = '<div class="alert alert-danger">حدث خطأ في قاعدة البيانات: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ملفي الشخصي - <?= htmlspecialchars($username) ?></title>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

  <style>
    body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; color: #343a40; }
    .container { margin-top: 50px; }
    .card { padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .profile-header { font-size: 2rem; color: #007bff; margin-bottom: 30px; }
    .form-label { font-weight: bold; }
    
.nav-link:hover {
  text-decoration: underline;
}
.main-header * {
  margin: 0;
  padding: 0;
}

.main-header {
  background-color: #002b67;
  padding: 8px 0;
}

.header-logo {
  max-height: 40px;
  margin-right: 80px;
  margin-top: 20px;
  margin-bottom: 10px;
}

.main-header .nav-link {
  color: white;
  font-weight: bold;
  text-decoration: none;
  transition: color 0.2s;
}

.main-header .nav-link:hover {
  color: #f7941d;
}

 .footer-custom {
  background: linear-gradient(to bottom, #3c4e82 0%, #071a42 100%);
  direction: rtl;
  font-family: 'Cairo', sans-serif;
}

.footer-logo {
  max-width: 150px;
  height: auto;
}

.social-icon {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid #ffffff90;
  color: #fff;
  font-size: 16px;
  transition: all 0.3s ease;
}

.social-icon:hover {
  background-color: #ffffff22;
  transform: scale(1.1);
}

.footer-separator {
  border-top: 1px solid #ffffff30;
}
.custom-logout {
  background-color: #f7941d; /* برتقالي أو أي لون تريده */
  color: white;
  border: none;
}

.custom-logout:hover {
  background-color: #e68310; /* لون عند التحويم */
  color: white;
}

  </style>
</head>
<body>
  <header class="main-header">
  <div class="container d-flex justify-content-between align-items-center">
    <!-- الشعار -->
    <div class="logo">
      <img src="images/cambioFixLogo.png" alt="CambioFix Logo" class="header-logo">
    </div>

    <!-- روابط التنقل -->
    <nav class="d-flex gap-4 align-items-center">
      <a href="index.html" class="nav-link">الرئيسية</a>
      <a href="login.php" class="nav-link">تسجيل خروج</a>
    
    </nav>
  </div>
</header>



  <div class="container">
    <div class="card">
      <h2 class="text-center profile-header">ملفي الشخصي</h2>
      <p class="text-center">نوع حسابك: <span class="badge bg-primary"><?= htmlspecialchars($user_type) ?></span></p>
      <p class="text-center">حالة حسابك: <span class="badge bg-info"><?= htmlspecialchars($user_data['account_status'] ?? 'غير معروف') ?></span></p>

      <?= $message ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label for="username_field" class="form-label">اسم المستخدم:</label>
          <input type="text" class="form-control" id="username_field" name="username_field" value="<?= htmlspecialchars($user_data['username'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
          <label for="email_field" class="form-label">البريد الإلكتروني:</label>
          <input type="email" class="form-control" id="email_field" name="email_field" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
          <label for="phone_number_field" class="form-label">رقم الهاتف:</label>
          <input type="text" class="form-control" id="phone_number_field" name="phone_number_field" value="<?= htmlspecialchars($user_data['phone_number'] ?? '') ?>" required>
        </div>

        <?php if ($user_type === 'helper'): ?>
        <div class="mb-3">
            <label for="current_availability" class="form-label">حالة التوفر:</label>
            <select class="form-select" id="current_availability" name="current_availability">
                <option value="available" <?= (($user_data['current_availability'] ?? '') == 'available') ? 'selected' : '' ?>>متاح</option>
                <option value="on_duty" <?= (($user_data['current_availability'] ?? '') == 'on_duty') ? 'selected' : '' ?>>في مهمة</option>
                <option value="offline" <?= (($user_data['current_availability'] ?? '') == 'offline') ? 'selected' : '' ?>>غير متصل</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">إجمالي الطلبات المكتملة:</label>
            <p><?= htmlspecialchars($user_data['total_completed_requests'] ?? 0) ?></p>
        </div>
        <div class="mb-3">
            <label class="form-label">التقييم:</label>
            <p><?= htmlspecialchars($user_data['rating'] ?? 0.0) ?> ⭐</p>
        </div>
        <?php elseif ($user_type === 'driver'): ?>
        <div class="mb-3">
            <label class="form-label">رقم الرخصة:</label>
            <p><?= htmlspecialchars($user_data['license_number'] ?? 'غير متوفر') ?></p>
        </div>
        <div class="mb-3">
            <label class="form-label">تفاصيل المركبة:</label>
            <p><?= htmlspecialchars($user_data['vehicle_details'] ?? 'غير متوفر') ?></p>
        </div>
        <div class="mb-3">
            <label class="form-label">حالة طلب السائق:</label>
            <p><span class="badge bg-secondary"><?= htmlspecialchars($user_data['status'] ?? 'غير متوفر') ?></span></p>
        </div>
        <?php endif; ?>

        <div class="text-center mt-4">
          <button type="submit" name="update_profile" class="btn btn-primary">تحديث الملف الشخصي</button>
        <a href="logout.php" class="btn custom-logout me-2">تسجيل الخروج</a>
        </div>
      </form>
    </div>
  </div>
     <footer class="footer-custom text-white pt-5 pb-4 mt-5">
  <div class="container">
    <div class="row text-center text-md-start align-items-start gy-4 px-3 px-md-5">

      <!-- الشعار والمعلومات -->
      <div class="col-md-4 text-md-start order-1 order-md-1">
        <img src="images/cambioFixLogo.png" alt="الشعار" class="footer-logo mb-2">
        
      </div>

      <!-- التواصل الاجتماعي -->
      <div class="col-md-4 d-flex flex-column align-items-center order-2">
        <h5 class="fw-bold mb-2">تابعنا على</h5>
        <div class="d-flex justify-content-center gap-3 mt-2">
          <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>

      <!-- البريد الإلكتروني -->
      <div class="col-md-4 text-md-end order-3 order-md-3">
        <h5 class="fw-bold mb-2">📧 بريد إلكتروني</h5>
        <p class="mb-0">roadassistance@gmail.com</p>
      </div>

    </div>

    <hr class="footer-separator my-4">

    <div class="text-center small">
      <p class="mb-1">إشعار قانوني بشأن العلامات التجارية: اسم المنتج المعروف بـ <strong>Road Assistance</strong> والعلامات التجارية والعلامات التجارية المسجلة هي ملك لأصحابها المعنيين.</p>
      <p class="mb-0">© جميع الحقوق محفوظة</p>
    </div>
  </div>
</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>