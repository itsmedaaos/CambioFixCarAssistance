<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة تحكم المشرف</title>
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
    /* يمكنك إضافة أنماط مخصصة هنا إذا لم تكن في admin_style.css */
    .section-header {
      background-color: #003366;
      color: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 25px;
      font-size: 1.8rem;
      text-align: center;
        font-family: 'Cairo', sans-serif;

    }
    .card {
      border: 1px solid #e0e0e0;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      margin-bottom: 20px;
        font-family: 'Cairo', sans-serif;

    }
    .card-header {
      background-color: #f7941d;
      color: white;
      font-weight: bold;
      border-top-left-radius: 9px;
      border-top-right-radius: 9px;
      padding: 12px 15px;
        font-family: 'Cairo', sans-serif;

    }
    .table-responsive {
      margin-top: 20px;
        font-family: 'Cairo', sans-serif;

    }
    .table th, .table td {
      vertical-align: middle;
    }
    .btn-approve {
      background-color: #28a745;
      border-color: #28a745;
      color: white;
    }
    .btn-reject {
      background-color: #dc3545;
      border-color: #dc3545;
      color: white;
    }
    .btn-approve:hover, .btn-reject:hover {
        opacity: 0.9;
    }
    .alert-message {
        margin-top: 20px;
        padding: 15px;
        border-radius: 5px;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
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
a.text-white:hover {
  color: #f7941d;
  text-decoration: underline;
}
.main-header a:hover {
  color: #f7941d;
  text-decoration: underline;
}
.bg-dark-blue {
  background-color: #003366;
}

.footer-logo {
  max-height: 60px;
}


.logo img {
  max-height: 60px;
}

nav a {
  font-weight: bold;
  color: white;
  text-decoration: none;
    font-family: 'Cairo', sans-serif;

}

nav a:hover {
  color: #f16800;
  text-decoration: underline;
}


  </style>
</head>
<body>
<header class="bg-dark-blue py-3">
  <div class="container d-flex justify-content-between align-items-center flex-row">

    <!-- الشعار على اليمين -->
    <div class="logo">
      <img src="images/cambioFixLogo.png" alt="CambioFix Logo" class="footer-logo">
    </div>

    <!-- روابط التنقل على اليسار -->
    <nav class="d-flex gap-4">
      <a href="index.html" class="text-white fw-bold text-decoration-none">الرئيسية</a>
      <a href="login.php" class="text-white fw-bold text-decoration-none">تسجيل دخول</a>
      <a href="admin.php" class="text-white fw-bold text-decoration-none">لوحة المشرف</a>
      <a href="completed_requests.php" class="text-white fw-bold text-decoration-none">الطلبات المكتملة</a>
    </nav>

  </div>
</header>






  <div class="container py-4">
    <h2 class="text-center section-header"> الطلبات المكتملة</h2>

    <?php
    require_once 'db.php'; // تأكد من وجود ملف الاتصال بقاعدة البيانات

    // تمكين عرض الأخطاء لغرض التصحيح فقط (أزل هذا في بيئة الإنتاج)
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $message = ''; // لرسائل النجاح أو الخطأ

    // معالجة طلبات القبول والرفض
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        $application_id = filter_var($_POST['application_id'], FILTER_SANITIZE_NUMBER_INT);
        $action = $_POST['action']; // 'approve' or 'reject'
        $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);

        if ($application_id && $user_id) {
            try {
                $pdo->beginTransaction();

                if ($action === 'approve') {
                    // 1. تحديث حالة الطلب في DriverApplications
                    $stmt_app = $pdo->prepare("UPDATE DriverApplications SET status = 'approved', reviewed_by = ?, review_notes = 'تمت الموافقة من المشرف' WHERE application_id = ?");
                    // افترض user_id للمشرف الحالي هو 1 (يمكنك تغيير هذا ليعكس المشرف الذي سجل الدخول)
                    $stmt_app->execute([1, $application_id]);

                    // 2. تحديث حالة حساب المستخدم في Users إلى 'active'
                    $stmt_user = $pdo->prepare("UPDATE Users SET account_status = 'active', user_type = 'helper' WHERE user_id = ?");
                    $stmt_user->execute([$user_id]);

                    // 3. (اختياري) تحديث حالة المساعد في Helpers إلى 'available'
                    $stmt_helper = $pdo->prepare("UPDATE Helpers SET current_availability = 'available' WHERE user_id = ?");
                    $stmt_helper->execute([$user_id]);

                    $message = "<div class='alert-message alert-success'>تمت الموافقة على طلب السائق بنجاح.</div>";

                } elseif ($action === 'reject') {
                    // 1. تحديث حالة الطلب في DriverApplications
                    $stmt_app = $pdo->prepare("UPDATE DriverApplications SET status = 'rejected', reviewed_by = ?, review_notes = 'تم الرفض من المشرف' WHERE application_id = ?");
                    // افترض user_id للمشرف الحالي هو 1
                    $stmt_app->execute([1, $application_id]);

                    // 2. تحديث حالة حساب المستخدم في Users إلى 'inactive' أو 'suspended'
                    $stmt_user = $pdo->prepare("UPDATE Users SET account_status = 'inactive' WHERE user_id = ?");
                    $stmt_user->execute([$user_id]);

                    $message = "<div class='alert-message alert-danger'>تم رفض طلب السائق بنجاح.</div>";
                }
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Admin action error: " . $e->getMessage());
                $message = "<div class='alert-message alert-danger'>حدث خطأ أثناء معالجة الطلب. الرجاء المحاولة لاحقاً.</div>";
            }
        } else {
            $message = "<div class='alert-message alert-danger'>بيانات غير صالحة.</div>";
        }
    }

    // عرض رسائل النجاح/الخطأ
    if (!empty($message)) {
        echo $message;
    }
    ?>

    <div class="card">
      <div class="card-header">طلبات السائقين الجدد (قيد الانتظار)</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center">
            <thead>
              <tr>
                <th>المعرف</th>
                <th>الاسم</th>
                <th>رقم الهاتف</th>
                <th>تاريخ الطلب</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                  $stmt = $pdo->prepare("
                      SELECT da.application_id, da.user_id, u.username, u.phone_number, da.application_date, da.status
                      FROM DriverApplications da
                      JOIN Users u ON da.user_id = u.user_id
                      WHERE da.status = 'pending'
                      ORDER BY da.application_date DESC
                  ");
                  $stmt->execute();
                  $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                  if ($applications) {
                      foreach ($applications as $app) {
                          ?>
                          <tr>
                            <td><?= htmlspecialchars($app['application_id']) ?></td>
                            <td><?= htmlspecialchars($app['username']) ?></td>
                            <td><?= htmlspecialchars($app['phone_number']) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($app['application_date']))) ?></td>
                            <td><span class="badge bg-warning"><?= htmlspecialchars($app['status']) ?></span></td>
                            <td>
                              <form method="POST" action="" style="display:inline-block;">
                                <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $app['user_id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve btn-sm">قبول</button>
                                <button type="submit" name="action" value="reject" class="btn btn-reject btn-sm">رفض</button>
                              </form>
                            </td>
                          </tr>
                          <?php
                      }
                  } else {
                      echo '<tr><td colspan="6">لا توجد طلبات سائقين جديدة حالياً.</td></tr>';
                  }
              } catch (PDOException $e) {
                  error_log("Error fetching driver applications: " . $e->getMessage());
                  echo '<tr><td colspan="6" class="text-danger">حدث خطأ أثناء تحميل طلبات السائقين: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header">جميع المساعدين</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center">
            <thead>
              <tr>
                <th>المعرف</th>
                <th>الاسم</th>
                <!-- <th>الموقع الحالي</th> -->
                <!-- <th>التقييم</th> -->
                <th>الحالة</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                  $stmt = $pdo->prepare("
                      SELECT u.user_id, u.username, h.current_location, h.rating, h.current_availability
                      FROM Users u
                      JOIN Helpers h ON u.user_id = h.user_id
                      WHERE u.user_type = 'helper'
                      ORDER BY u.username ASC
                  ");
                  $stmt->execute();
                  $helpers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                  if ($helpers) {
                      foreach ($helpers as $helper) {
                          ?>
                          <tr>
                            <td><?= htmlspecialchars($helper['user_id']) ?></td>
                            <td><?= htmlspecialchars($helper['username']) ?></td>
                            <!-- <td><?= htmlspecialchars($helper['current_location'] ?? 'غير محدد') ?></td> -->
                            <!-- <td><?= htmlspecialchars($helper['rating']) ?></td> -->
                            <td><span class="badge bg-info"><?= htmlspecialchars($helper['current_availability']) ?></span></td>
                            <td>
                              <a href="helper_details.php?id=<?= $helper['user_id'] ?>" class="btn btn-primary btn-sm">عرض التفاصيل</a>
                            </td>
                          </tr>
                          <?php
                      }
                  } else {
                      echo '<tr><td colspan="6">لا يوجد مساعدون مسجلون حالياً.</td></tr>';
                  }
              } catch (PDOException $e) {
                  error_log("Error fetching helpers: " . $e->getMessage());
                  echo '<tr><td colspan="6" class="text-danger">حدث خطأ أثناء تحميل المساعدين: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header">جميع العملاء</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center">
            <thead>
              <tr>
                <th>المعرف</th>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>رقم الهاتف</th>
                <!-- <th>الإجراءات</th> -->
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                  $stmt = $pdo->prepare("
                      SELECT user_id, username, email, phone_number
                      FROM Users
                      WHERE user_type = 'client'
                      ORDER BY username ASC
                  ");
                  $stmt->execute();
                  $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                  if ($clients) {
                      foreach ($clients as $client) {
                          ?>
                          <tr>
                            <td><?= htmlspecialchars($client['user_id']) ?></td>
                            <td><?= htmlspecialchars($client['username']) ?></td>
                            <td><?= htmlspecialchars($client['email']) ?></td>
                            <td><?= htmlspecialchars($client['phone_number']) ?></td>
                            <!-- <td>
                              <a href="#" class="btn btn-info btn-sm">تفاصيل</a>
                            </td> -->
                          </tr>
                          <?php
                      }
                  } else {
                      echo '<tr><td colspan="5">لا يوجد عملاء مسجلون حالياً.</td></tr>';
                  }
              } catch (PDOException $e) {
                  error_log("Error fetching clients: " . $e->getMessage());
                  echo '<tr><td colspan="5" class="text-danger">حدث خطأ أثناء تحميل العملاء: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header">جميع الطلبات (قيد الانتظار والنشطة)</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center">
            <thead>
              <tr>
                <th>معرف الطلب</th>
                <th>العميل</th>
                <th>نوع الخدمة</th>
                <th>الموقع</th>
                <th>الحالة</th>
                <th>وقت الطلب</th>
                <th>المساعد المعين</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                  $stmt = $pdo->prepare("
                      SELECT
                          r.request_id,
                          cu.username AS client_name,
                          r.service_requested,
                          r.pickup_location,
                          r.status,
                          r.request_time,
                          hu.username AS helper_name
                      FROM
                          Requests r
                      JOIN
                          Users cu ON r.client_user_id = cu.user_id
                      LEFT JOIN
                          Users hu ON r.helper_user_id = hu.user_id
                      WHERE
                          r.status IN ('pending_assignment', 'active')
                      ORDER BY
                          r.request_time DESC
                  ");
                  $stmt->execute();
                  $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

                  if ($requests) {
                      foreach ($requests as $request) {
                          $status_badge = '';
                          switch ($request['status']) {
                              case 'pending_assignment':
                                  $status_badge = '<span class="badge bg-warning">قيد التعيين</span>';
                                  break;
                              case 'active':
                                  $status_badge = '<span class="badge bg-info">نشطة</span>';
                                  break;
                              case 'completed':
                                  $status_badge = '<span class="badge bg-success">مكتملة</span>';
                                  break;
                              case 'cancelled':
                                  $status_badge = '<span class="badge bg-danger">ملغاة</span>';
                                  break;
                          }
                          ?>
                          <tr>
                            <td><?= htmlspecialchars($request['request_id']) ?></td>
                            <td><?= htmlspecialchars($request['client_name']) ?></td>
                            <td><?= htmlspecialchars($request['service_requested']) ?></td>
                            <td><?= htmlspecialchars($request['pickup_location']) ?></td>
                            <td><?= $status_badge ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($request['request_time']))) ?></td>
                            <td><?= htmlspecialchars($request['helper_name'] ?? 'لم يعين بعد') ?></td>
                            <td>
                              <a href="manage_users.php" class="btn btn-primary btn-sm">إدارة</a>
                            </td>
                          </tr>
                          <?php
                      }
                  } else {
                      echo '<tr><td colspan="8">لا توجد طلبات قيد الانتظار أو نشطة حالياً.</td></tr>';
                  }
              } catch (PDOException $e) {
                  error_log("Error fetching active requests: " . $e->getMessage());
                  echo '<tr><td colspan="8" class="text-danger">حدث خطأ أثناء تحميل الطلبات: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

<footer class="footer-custom text-white pt-5 pb-4 mt-5">
  <div class="container">
    <div class="row text-center text-md-start align-items-start gy-4 px-3 px-md-5">

      <div class="col-md-4 text-md-start order-1 order-md-1">
        <img src="images/cambioFixLogo.png" alt="الشعار" class="footer-logo mb-2">
        
      </div>

      <div class="col-md-4 d-flex flex-column align-items-center order-2">
        <h5 class="fw-bold mb-2">تابعنا على</h5>
        <div class="d-flex justify-content-center gap-3 mt-2">
          <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>

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
<!-- 
client_dashboard.php
helper_dashboard.php
driver_dashboard.php
completed_requests.php
active_requests.php
login.php
admin.php
register.php 
-->