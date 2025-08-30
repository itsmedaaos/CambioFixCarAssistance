<?php
session_start();

require_once 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $login_error = "الرجاء تعبئة جميع الحقول.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, username, password_hash, user_type, account_status FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password_hash'])) {
                    if ($user['account_status'] === 'pending') {
                        $login_error = "حسابك قيد المراجعة. يرجى الانتظار حتى تتم الموافقة عليه.";
                    } elseif ($user['account_status'] === 'inactive') {
                        $login_error = "حسابك غير نشط. يرجى الاتصال بالإدارة.";
                    } elseif ($user['account_status'] === 'rejected') {
                        $login_error = "تم رفض طلب حسابك. يرجى الاتصال بالإدارة للمزيد من المعلومات.";

                        /* اهني يتم توجيه المستخدم على حسب الصلاحية */
                    } elseif ($user['account_status'] === 'active') {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_type'] = $user['user_type'];

                        if ($user['user_type'] === 'admin') {
                            header("Location: admin.php");
                            exit();
                        } elseif ($user['user_type'] === 'client') {
                            header("Location: client_dashboard.php");
                            exit();
                        } elseif ($user['user_type'] === 'helper') {
                            header("Location: helper_dashboard.php");
                            exit();
                        } elseif ($user['user_type'] === 'driver') {
                            header("Location: driver_dashboard.php");
                            exit();
                        }
                    } else {
                        $login_error = "حدث خطأ غير متوقع في حالة الحساب. يرجى الاتصال بالدعم.";
                    }
                } else {
                    $login_error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
                }
            } else {
                $login_error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage()); 
            $login_error = "حدث خطأ أثناء تسجيل الدخول. الرجاء المحاولة لاحقًا.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>تسجيل الدخول</title>
  <link rel="stylesheet" href="login.css" />
  <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet" />
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>تسجيل الدخول</h2>
      <?php if ($login_error): ?>
        <div style="color: red; text-align: center; margin-bottom: 15px;"><?php echo $login_error; ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <label for="email">البريد الإلكتروني</label>
        <input
          type="email"
          id="email"
          name="email"
          placeholder="أدخل بريدك الإلكتروني"
          required
          value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
        />

        <label for="password">كلمة المرور</label>
        <input
          type="password"
          id="password"
          name="password"
          placeholder="أدخل كلمة المرور"
          required
        />

        <div class="text-center">
          <button type="submit" class="btn btn-primary">تسجيل الدخول</button>
        </div>
      </form>

      <div class="card-body text-center">
        ليس لديك حساب؟
        <a href="register.php" class="btn btn-outline-primary">إنشاء حساب</a>
      </div>
      <div class="card-body text-center">
        <a href="forgot_password.php" class="btn btn-link">نسيت كلمة المرور؟</a> </div>
    </div>
  </div>

  <div class="bottom-bar"></div>

</body>
</html>

