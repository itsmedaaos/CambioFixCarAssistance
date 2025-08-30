<?php
session_start();
require_once 'db.php'; 

// ***************************************************************
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ***************************************************************

$message = ''; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php"); 
    exit();
}

$admin_user_id = $_SESSION['user_id']; 

// ***************************************************************

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<div style='background-color:#ffeeba; color:#856404; border:1px solid #ffdf7e; padding:10px; margin-bottom:15px;'>";
    echo "<h3>بيانات POST المستلمة:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";

    // ***************************************************************

    if (isset($_POST['action']) && isset($_POST['application_id']) && isset($_POST['user_id'])) {
        $application_id = filter_var($_POST['application_id'], FILTER_SANITIZE_NUMBER_INT);
        $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $action = $_POST['action']; 

        if ($application_id > 0 && $user_id > 0) {
            try {
                $pdo->beginTransaction();

                if ($action === 'accept_driver') {
                    $stmt_app = $pdo->prepare("UPDATE DriverApplications SET status = 'accepted', reviewed_by = ?, review_notes = 'تمت الموافقة من المشرف' WHERE application_id = ? AND status = 'pending'");
                    $stmt_app->execute([$admin_user_id, $application_id]);

                    $stmt_user = $pdo->prepare("UPDATE Users SET account_status = 'active', user_type = 'helper' WHERE user_id = ? AND user_type = 'driver'");
                    $stmt_user->execute([$user_id]);

                    $stmt_check_helper = $pdo->prepare("SELECT COUNT(*) FROM Helpers WHERE user_id = ?");
                    $stmt_check_helper->execute([$user_id]);
                    if ($stmt_check_helper->fetchColumn() == 0) {
                        $stmt_insert_helper = $pdo->prepare("INSERT INTO Helpers (user_id, current_availability, total_completed_requests, rating) VALUES (?, 'available', 0, 0.0)");
                        $stmt_insert_helper->execute([$user_id]);
                    }


                    if ($stmt_app->rowCount() > 0 && $stmt_user->rowCount() > 0) {
                        $message = "<div class='alert-message alert-success'>تم قبول السائق بنجاح وتفعيل حسابه.</div>";
                    } else {
                        $message = "<div class='alert-message alert-warning'>لم يتم العثور على طلب السائق المعلق أو المستخدم لـ " . htmlspecialchars($action) . " (ربما تم التعامل معه بالفعل).</div>";
                    }

                } elseif ($action === 'reject_driver') {
                    $stmt_app = $pdo->prepare("UPDATE DriverApplications SET status = 'rejected', reviewed_by = ?, review_notes = 'تم الرفض من المشرف' WHERE application_id = ? AND status = 'pending'");
                    $stmt_app->execute([$admin_user_id, $application_id]); 

                    $stmt_user = $pdo->prepare("UPDATE Users SET account_status = 'rejected' WHERE user_id = ? AND user_type = 'driver'");
                    $stmt_user->execute([$user_id]);

                    if ($stmt_app->rowCount() > 0 && $stmt_user->rowCount() > 0) {
                        $message = "<div class='alert-message alert-info'>تم رفض طلب السائق بنجاح.</div>";
                    } else {
                        $message = "<div class='alert-message alert-warning'>لم يتم العثور على طلب السائق المعلق أو المستخدم لـ " . htmlspecialchars($action) . " (ربما تم التعامل معه بالفعل).</div>";
                    }
                } else {
                    $message = "<div class='alert-message alert-danger'>إجراء غير صالح: " . htmlspecialchars($action) . "</div>";
                }

                $pdo->commit();

                // ***************************************************************

                header("Location: admin.php?msg=" . urlencode($message)); 
                exit();

                // ***************************************************************

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Driver application action error: " . $e->getMessage());

                // ***************************************************************

                $message = "<div class='alert-message alert-danger'>حدث خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</div>";

                // ***************************************************************
            }
        } else {
            $message = "<div class='alert-message alert-danger'>معرف تطبيق أو مستخدم غير صالح.</div>";
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة المشرف</title>
 <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>

    .alert-message {
        margin-top: 20px;
        padding: 15px;
        border-radius: 5px;
        word-break: break-all;
    }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }


    .admin-section {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        font-family: 'Cairo', sans-serif;

    }


    .section-header {
        background-color: #003366;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-size: 1.5rem;
        text-align: center;
        font-family: 'Cairo', sans-serif;

    }
    .pending-driver .card-title {
        color: #007bff;
    }
    .pending-driver .badge {
        font-size: 0.9em;
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
  background-color: #f16800;
;
  transform: scale(1.1);
}

.footer-separator {
  border-top: 1px solid #ffffff30;
}

nav a {
    color: #000;
    text-decoration: none;
    transition: color 0.3s ease;
    font-family: 'Cairo', sans-serif;

  }

  nav a:hover {
  color: #f16800;
  }

  .logo {
  transition: transform 0.3s ease;
  display: inline-block;
}

.logo:hover {
  transform: scale(1.1); 
}

.admin-title {
  font-family: 'Cairo', sans-serif;
  color: #f16800;
  font-weight: bold;
}



  </style>
</head>
<header>
  <div class="logo"> <img src="images/cambioFixLogo.png" alt="الشعار" class="footer-logo mb-2">
</div>
  <nav>
    <a href="index.html">الرئيسية</a>
    <a href="login.php">تسجيل دخول</a>
    <a href="profile.php">الملف الشخصي</a>
    <a href="completed_requests.php">الطلبات المكتملة</a>
    <a href="active_requests.php">الطلبات النشطة</a>

  </nav>
</header>

<body class="bg-light">
  <div class="container py-4">
    <h2 class="text-center mb-4 admin-title"> ----- لوحـة تحكـم المُشـرف ----- </h2>

   

    <?php if (!empty($message)): ?>
        <div class="mb-4"><?= $message ?></div>
    <?php endif; ?>

    <div class="admin-section">
      <h4 class="section-header"> طلبات السائقين الجدد</h4>
      <?php
      try {
          $stmt = $pdo->prepare("
              SELECT da.application_id, u.user_id, u.username, u.email, u.phone_number, da.application_date, da.status
              FROM DriverApplications da
              JOIN Users u ON da.user_id = u.user_id
              WHERE da.status = 'pending'
              ORDER BY da.application_date DESC
          ");
          $stmt->execute();
          $pending_drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if ($pending_drivers) {
              foreach ($pending_drivers as $driver) {
                  ?>
                  <div class="card mb-3 pending-driver">
                    <div class="row g-0 align-items-center">
                      <div class="col-md-2 text-center p-3">
                        <img src="images/user.png" alt="سائق" class="img-fluid" style="max-height: 80px;">
                      </div>
                      <div class="col-md-7">
                        <div class="card-body">
                          <h5 class="card-title">الاسم: <?= htmlspecialchars($driver['username']) ?></h5>
                          <p class="card-text mb-1"><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($driver['email']) ?></p>
                          <p class="card-text mb-1"><strong>رقم الهاتف:</strong> <?= htmlspecialchars($driver['phone_number']) ?></p>
                          <p class="card-text mb-1"><strong>تاريخ الطلب:</strong> <?= htmlspecialchars(date('Y-m-d', strtotime($driver['application_date']))) ?></p>
                          <p class="card-text"><strong>الحالة:</strong> <span class="badge bg-warning"><?= htmlspecialchars($driver['status']) ?></span></p>
                        </div>
                      </div>
                      <div class="col-md-3 d-flex flex-column justify-content-center align-items-center">
                        <form method="POST" action="admin.php" style="margin-bottom: 5px;">
                          <input type="hidden" name="application_id" value="<?= $driver['application_id'] ?>">
                          <input type="hidden" name="user_id" value="<?= $driver['user_id'] ?>">
                          <button type="submit" name="action" value="accept_driver" class="btn btn-success btn-sm w-100 mb-2">قبول</button>
                        </form>
                        <form method="POST" action="admin.php">
                          <input type="hidden" name="application_id" value="<?= $driver['application_id'] ?>">
                          <input type="hidden" name="user_id" value="<?= $driver['user_id'] ?>">
                          <button type="submit" name="action" value="reject_driver" class="btn btn-danger btn-sm w-100">رفض</button>
                        </form>
                      </div>
                    </div>
                  </div>
                  <?php
              } 
          } else { 
              echo '<p class="text-center text-muted" id="no-pending">لا توجد طلبات سائقين جديدة حالياً.</p>';
          }
      } catch (PDOException $e) {
          error_log("Error fetching pending drivers: " . $e->getMessage());
          echo '<div class="alert-message alert-danger">حدث خطأ أثناء تحميل طلبات السائقين: ' . htmlspecialchars($e->getMessage()) . '</div>';
      }
      ?>
    </div>

    <div class="admin-section">
      <h4 class="section-header"> الطلبات النشطة</h4>
      <a href="active_requests.php" class="btn btn-primary mb-3">عرض الطلبات النشطة</a>
    </div>

    <div class="admin-section">
      <h4 class="section-header"> الطلبات المكتملة</h4>
      <a href="completed_requests.php" class="btn btn-primary mb-3">عرض الطلبات المكتملة</a>
    </div>

    <div class="admin-section">
      <h4 class="section-header"> جميع المساعدين</h4>
      <?php
      try {
          $stmt = $pdo->prepare("
              SELECT h.helper_id, u.user_id, u.username, u.phone_number, h.service_type, h.current_location, h.rating, h.current_availability
              FROM Helpers h
              JOIN Users u ON h.user_id = u.user_id
              WHERE u.user_type = 'helper' AND u.account_status = 'active'
              ORDER BY u.username ASC
          ");
          $stmt->execute();
          $helpers = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if ($helpers) {
              foreach ($helpers as $helper):
          ?>
          <div class="card mb-3">
            <div class="row g-0">
              <div class="col-md-2 d-flex align-items-center justify-content-center p-3">
              <img src="images/user.png" alt="مساعد" class="img-fluid" style="max-height:90px;">
              </div>
              <div class="col-md-7 d-flex align-items-center">
                <div class="card-body">
                  <h5 class="card-title"><strong>الاسم :</strong> <?=htmlspecialchars($helper['username'])?></h5>
                  <p class="card-text mb-1"><strong>رقم الهاتف:</strong> <?=htmlspecialchars($helper['phone_number'])?></p>
                  <!-- <p class="card-text mb-1"><strong>الخدمة:</strong> <?=htmlspecialchars($helper['service_type'] )?></p> -->
                  <p class="card-text"><strong>الحالة:</strong>
                    <?php
                      if($helper['current_availability'] == 'available') echo 'متاح';
                      elseif($helper['current_availability'] == 'on_duty') echo 'في مهمة';
                      else echo 'غير متصل';
                    ?>
                  </p>
                </div>
              </div>
              <div class="col-md-3 d-flex align-items-center justify-content-center">
                    <a href="helper_details.php?id=<?=$helper['user_id']?>" class="btn btn-primary">عرض التفاصيل</a>
              </div>
            </div>
          </div>
          <?php
              endforeach;
            } else { 
          ?>
          <p class="text-center text-muted">لا يوجد مساعدين حاليا.</p>
          <?php } 
          } catch (PDOException $e) {
              error_log("Error fetching helpers: " . $e->getMessage());
              echo '<div class="alert-message alert-danger">حدث خطأ أثناء تحميل المساعدين: ' . htmlspecialchars($e->getMessage()) . '</div>';
          }
          ?>
        </div>

  </div><footer class="footer-custom text-white pt-5 pb-4 mt-5">
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