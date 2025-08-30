<?php
session_start();
require_once 'db.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$register_error = '';
$register_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $user_type_selected = $_POST['user_type'] ?? 'client'; 

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($phone_number)) {
        $register_error = "الرجاء تعبئة جميع الحقول المطلوبة.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "تنسيق البريد الإلكتروني غير صحيح.";
    } elseif ($password !== $confirm_password) {
        $register_error = "كلمتا المرور غير متطابقتين.";
    } elseif (strlen($password) < 6) {
        $register_error = "يجب أن تكون كلمة المرور 6 أحرف على الأقل.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetchColumn() > 0) {
                $register_error = "البريد الإلكتروني أو اسم المستخدم موجود بالفعل. الرجاء اختيار آخر.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

               
                $account_status = ($user_type_selected === 'driver') ? 'pending' : 'active';

                $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash, phone_number, user_type, account_status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash, $phone_number, $user_type_selected, $account_status]);

                if ($user_type_selected === 'driver') {
                    $new_user_id = $pdo->lastInsertId();
                   
                    $license_number = trim($_POST['license_number'] ?? '');
                    $vehicle_details = trim($_POST['vehicle_details'] ?? '');

                    if (empty($license_number)) {
                         $register_error = "الرجاء إدخال رقم الرخصة للسائق.";
                        
                         $pdo->rollBack(); 
                         throw new Exception("رقم الرخصة مطلوب للسائق."); 
                    }


                    $stmt_driver_app = $pdo->prepare("INSERT INTO DriverApplications (user_id, license_number, vehicle_details, status) VALUES (?, ?, ?, 'pending')");
                    $stmt_driver_app->execute([$new_user_id, $license_number, $vehicle_details]);
                }


                $register_success = "تم إنشاء حسابك بنجاح! يمكنك الآن تسجيل الدخول.";
              
            }
        } catch (PDOException $e) {
            // التعامل مع أخطاء قاعدة البيانات
            error_log("Registration error: " . $e->getMessage());
            $register_error = "حدث خطأ أثناء إنشاء الحساب. الرجاء المحاولة لاحقًا. " . htmlspecialchars($e->getMessage()); // لإظهار الخطأ للمطورين فقط
        } catch (Exception $e) {
            $register_error = "حدث خطأ: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>إنشاء حساب جديد</title>
  <link rel="stylesheet" href="login.css" /> <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet" />
  <style>
   
    .driver-fields {
        display: none; 
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>إنشاء حساب جديد</h2>

      <?php if ($register_error): ?>
        <div style="color: red; text-align: center; margin-bottom: 15px;"><?php echo $register_error; ?></div>
      <?php endif; ?>
      <?php if ($register_success): ?>
        <div style="color: green; text-align: center; margin-bottom: 15px;"><?php echo $register_success; ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <label for="username">اسم المستخدم</label>
        <input
          type="text"
          id="username"
          name="username"
          placeholder="أدخل اسم المستخدم"
          required
          value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
        />

        <label for="email">البريد الإلكتروني</label>
        <input
          type="email"
          id="email"
          name="email"
          placeholder="أدخل بريدك الإلكتروني"
          required
          value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
        />

        <label for="phone_number">رقم الهاتف</label>
        <input
          type="text"
          id="phone_number"
          name="phone_number"
          placeholder="أدخل رقم هاتفك"
          required
          value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>"
        />

        <label for="password">كلمة المرور</label>
        <input
          type="password"
          id="password"
          name="password"
          placeholder="أدخل كلمة المرور"
          required
        />

        <label for="confirm_password">تأكيد كلمة المرور</label>
        <input
          type="password"
          id="confirm_password"
          name="confirm_password"
          placeholder="أعد إدخال كلمة المرور"
          required
        />

        <label for="user_type">نوع الحساب</label>
        <select id="user_type" name="user_type" required onchange="toggleDriverFields()">
          <option value="client" <?php echo (($_POST['user_type'] ?? '') == 'client') ? 'selected' : ''; ?>>عميل</option>
          <option value="driver" <?php echo (($_POST['user_type'] ?? '') == 'driver') ? 'selected' : ''; ?>>مساعد </option>
          </select>

        <div id="driverFields" class="driver-fields">
            <label for="license_number">رقم الرخصة</label>
            <input
              type="text"
              id="license_number"
              name="license_number"
              placeholder="أدخل رقم الرخصة"
              value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>"
            />

            <label for="vehicle_details">تفاصيل المركبة</label>
            <textarea
              id="vehicle_details"
              name="vehicle_details"
              placeholder="مثال: نوع السيارة، الموديل، اللون"
            ><?php echo htmlspecialchars($_POST['vehicle_details'] ?? ''); ?></textarea>
        </div>

        <div class="text-center">
          <button type="submit" class="btn btn-primary">إنشاء حساب</button>
        </div>
      </form>

      <div class="card-body text-center">
        لديك حساب بالفعل؟
        <a href="login.php" class="btn btn-outline-primary">تسجيل الدخول</a>
      </div>
    </div>
  </div>

  <div class="bottom-bar"></div>

  <script>
    function toggleDriverFields() {
        var userTypeSelect = document.getElementById('user_type');
        var driverFieldsDiv = document.getElementById('driverFields');
        if (userTypeSelect.value === 'driver') {
            driverFieldsDiv.style.display = 'block';
        } else {
            driverFieldsDiv.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleDriverFields();
    });
  </script>

</body>
</html>