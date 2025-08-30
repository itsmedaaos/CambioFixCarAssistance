<?php
session_start();
require_once 'db.php'; 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = ''; 

// ***************************************************************
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $message .= "<div class='alert-message alert-info'>تم استلام طلب POST.<br>بيانات النموذج: <pre>" . htmlspecialchars(print_r($_POST, true)) . "</pre></div>";

    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
        $action = $_POST['action']; 

        if ($request_id > 0) {
            try {
                $pdo->beginTransaction();

                switch ($action) {
                    // case 'accept': // قبول طلب قيد التعيين (جعله نشطًا)
                    //     $stmt = $pdo->prepare("UPDATE Requests SET status = 'active' WHERE request_id = ? AND status = 'pending_assignment'");
                    //     $stmt->execute([$request_id]);
                    //     if ($stmt->rowCount() > 0) {
                    //         $message = "<div class='alert-message alert-success'>تم قبول الطلب رقم " . $request_id . " وتغيير حالته إلى 'نشطة'.</div>";
                    //     } else {
                    //         $message = "<div class='alert-message alert-danger'>لا يمكن قبول الطلب رقم " . $request_id . " لأنه ليس في حالة 'قيد التعيين' أو تم قبوله مسبقًا.</div>";
                    //     }
                    //     break;

                    case 'reject': 
                        $stmt = $pdo->prepare("UPDATE Requests SET status = 'cancelled' WHERE request_id = ? AND status = 'pending_assignment'");
                        $stmt->execute([$request_id]);
                        if ($stmt->rowCount() > 0) {
                            $message = "<div class='alert-message alert-danger'>تم رفض (إلغاء) الطلب رقم " . $request_id . ".</div>";
                        } else {
                            $message = "<div class='alert-message alert-danger'>لا يمكن رفض الطلب رقم " . $request_id . " لأنه ليس في حالة 'قيد التعيين' أو تم التعامل معه مسبقًا.</div>";
                        }
                        break;

                    // case 'complete': 
                    //     $stmt = $pdo->prepare("UPDATE Requests SET status = 'completed', completion_time = NOW() WHERE request_id = ? AND status = 'active'");
                    //     $stmt->execute([$request_id]);
                    //     if ($stmt->rowCount() > 0) {
                    //         $message = "<div class='alert-message alert-success'>تم إكمال الطلب رقم " . $request_id . " بنجاح.</div>";
                    //     } else {
                    //         $message = "<div class='alert-message alert-danger'>لا يمكن إكمال الطلب رقم " . $request_id . " لأنه ليس في حالة 'نشطة' أو تم إكماله مسبقًا.</div>";
                    //     }
                    //     break;

                    case 'cancel': 
                        $stmt = $pdo->prepare("UPDATE Requests SET status = 'cancelled' WHERE request_id = ? AND status IN ('pending_assignment', 'active')");
                        $stmt->execute([$request_id]);
                        if ($stmt->rowCount() > 0) {
                            $message = "<div class='alert-message alert-danger'>تم إلغاء الطلب رقم " . $request_id . " بنجاح.</div>";
                        } else {
                            $message = "<div class='alert-message alert-danger'>لا يمكن إلغاء الطلب رقم " . $request_id . " لأنه ليس في حالة تسمح بالإلغاء.</div>";
                        }
                        break;

                    default:
                        $message = "<div class='alert-message alert-danger'>إجراء غير صالح: " . htmlspecialchars($action) . "</div>";
                }
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                // ***************************************************************

                error_log("Request action error: " . $e->getMessage()); 
                $message = "<div class='alert-message alert-danger'>حدث خطأ أثناء معالجة الطلب. الرجاء المحاولة لاحقاً. خطأ فني: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            $message = "<div class='alert-message alert-danger'>معرف طلب غير صالح.</div>";
        }
    } else {
        $message .= "<div class='alert-message alert-warning'>لم يتم تحديد الإجراء أو معرف الطلب في طلب POST.</div>";
    }
}
// ***************************************************************
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الطلبات النشطة</title>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
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
    }
    .table th, .table td {
      vertical-align: middle;
    }
    .no-requests {
      text-align: center;
      color: #6c757d;
      margin-top: 30px;
    }
    .status-badge {
      padding: 0.35em 0.65em;
      border-radius: 0.25rem;
      font-size: 0.75em;
      font-weight: 700;
      line-height: 1;
      text-align: center;
      white-space: nowrap;
      vertical-align: baseline;
    }
    .bg-warning { background-color: #ffc107 !important; color: #212529 !important; } /* قيد التعيين */
    .bg-info { background-color: #0dcaf0 !important; color: #212529 !important; } /* نشطة */
    .bg-success { background-color: #198754 !important; color: #fff !important; } /* مكتملة */
    .bg-danger { background-color: #dc3545 !important; color: #fff !important; } /* ملغاة */

    /* أزرار الإجراءات */
    .btn-action {
      margin: 2px;
      font-size: 0.85rem;
      padding: 0.3rem 0.6rem;
              font-family: 'Cairo', sans-serif;

    }
    .alert-message {
        margin-top: 20px;
        padding: 15px;
        border-radius: 5px;
        word-break: break-all; 
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
    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
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


.bg-dark-blue {
  background-color: #003366;
}

.footer-logo {
  max-height: 60px;
}

.logo img {
  max-height: 50px;
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
  </style>
</head>
<body>
 
<header class="bg-dark-blue py-3">
  <div class="container d-flex justify-content-between align-items-center flex-row">

    <div class="logo">
      <img src="images/cambioFixLogo.png" alt="CambioFix Logo" class="footer-logo">
    </div>

    <nav class="d-flex gap-4">
      <a href="index.html" class="text-white fw-bold text-decoration-none">الرئيسية</a>
      <a href="login.php" class="text-white fw-bold text-decoration-none">تسجيل دخول</a>
      <a href="admin.php" class="text-white fw-bold text-decoration-none">لوحة المشرف</a>
      <a href="completed_requests.php" class="text-white fw-bold text-decoration-none">الطلبات المكتملة</a>
    </nav>

  </div>
</header>

  <div class="container py-4">
    <h2 class="text-center section-header"> الطلبات النشطة</h2>

    <?php if (!empty($message)): ?>
        <?= $message ?>
    <?php endif; ?>

    <div class="card mt-4">
      <div class="card-header">قائمة الطلبات قيد الانتظار والنشطة</div>
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
                                  $status_badge = '<span class="status-badge bg-warning">قيد التعيين</span>';
                                  break;
                              case 'active':
                                  $status_badge = '<span class="status-badge bg-info">نشطة</span>';
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
                              <form method="POST" action="" style="display:inline-block;">
                                <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                <?php if ($request['status'] === 'pending_assignment'): ?>
                                    <!-- <button type="submit" name="action" value="accept" class="btn btn-success btn-sm btn-action">قبول</button> -->
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm btn-action">رفض</button>
                                <?php elseif ($request['status'] === 'active'): ?>
                                    <!-- <button type="submit" name="action" value="complete" class="btn btn-success btn-sm btn-action">إكمال</button> -->
                                    <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm btn-action">إلغاء</button>
                                <?php endif; ?>
                              </form>
                              <!-- <a href="#" class="btn btn-info btn-sm btn-action">تفاصيل</a> -->
                            </td>
                          </tr>
                          <?php
                      }
                  } else {
                      echo '<tr><td colspan="8" class="no-requests">لا توجد طلبات نشطة حالياً.</td></tr>';
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