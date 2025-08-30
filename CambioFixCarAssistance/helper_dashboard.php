<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verify helper login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'helper') {
    header("Location: login.php");
    exit();
}

$helper_user_id = $_SESSION['user_id'];
$message = '';

// Get current helper_id from helpers table (للاستخدام في العمليات)
// $stmt = $pdo->prepare("SELECT helper_id FROM helpers WHERE user_id = ?");
// $stmt->execute([$helper_user_id]);
// $helper_record = $stmt->fetch();
// $helper_id = $helper_record ? $helper_record['helper_id'] : null;
$helper_id = $helper_user_id; // نستخدم user_id مباشرة

// Handle POST requests for request actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
        $action = $_POST['action'];

        try {
            $pdo->beginTransaction();

            switch ($action) {
case 'accept':
    $helper_id = $_SESSION['user_id'] ?? null; 

    if ($helper_id) {
        $stmt = $pdo->prepare("UPDATE Requests SET status = 'active', helper_user_id = ? WHERE request_id = ? AND status = 'pending_assignment'");
        $stmt->execute([$helper_id, $request_id]);

        if ($stmt->rowCount() > 0) {
            $message = "<div class='alert-message alert-success'>تم قبول الطلب بنجاح.</div>";
        } else {
            $message = "<div class='alert-message alert-danger'>لا يمكن قبول الطلب. قد يكون تم قبوله من قبل مساعد آخر.</div>";
        }
    } else {
        $message = "<div class='alert-message alert-danger'>حدث خطأ: لم يتم التعرف على هوية المساعد.</div>";
    }
    break;



                case 'reject':
                    // Update the request: set status to cancelled, assign helper_user_id, and set completion_time
                    $stmt = $pdo->prepare("UPDATE Requests SET status = 'cancelled', helper_user_id = ?, completion_time = NOW() WHERE request_id = ? AND status = 'pending_assignment'");
                    $stmt->execute([$helper_id, $request_id]);
                    if ($stmt->rowCount() > 0) {
                        $message = "<div class='alert-message alert-danger'>تم رفض الطلب بنجاح.</div>";
                    } else {
                        $message = "<div class='alert-message alert-danger'>لا يمكن رفض الطلب. قد يكون تم قبوله من قبل مساعد آخر.</div>";
                    }
                    break;

                case 'complete':
                    $stmt = $pdo->prepare("UPDATE Requests SET status = 'completed', completion_time = NOW() WHERE request_id = ? AND status = 'active' AND helper_user_id = ?");
                    $stmt->execute([$request_id, $helper_id]);
                    if ($stmt->rowCount() > 0) {
                        $message = "<div class='alert-message alert-success'>تم إكمال الطلب بنجاح.</div>";
                    }
                    break;

                case 'cancel':
                    $stmt = $pdo->prepare("UPDATE Requests SET status = 'cancelled', completion_time = NOW() WHERE request_id = ? AND helper_user_id = ?");
                    $stmt->execute([$request_id, $helper_id]);
                    if ($stmt->rowCount() > 0) {
                        $message = "<div class='alert-message alert-danger'>تم إلغاء الطلب.</div>";
                    }
                    break;
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "<div class='alert-message alert-danger'>حدث خطأ: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المساعد</title>
       <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .container {
            max-width: 1200px;
              font-family: 'Cairo', sans-serif;

        }
        .section-header {
            background-color: #003366;
            color: white;
            padding: 15px 20px 15px 60px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 1.8rem;
            text-align: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
              font-family: 'Cairo', sans-serif;

        }
        .logout-btn-header {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 8px 20px 8px 16px;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 7px;
              font-family: 'Cairo', sans-serif;

        }
        .logout-btn-header:hover {
            background: #c82333;
            color: #fff;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #f7941d;
            color: white;
            font-weight: bold;
            border-top-left-radius: 9px;
            border-top-right-radius: 9px;
            padding: 12px 15px;
        }
        .status-badge {
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
            font-size: 0.75em;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }
        .bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
        .bg-info { background-color: #0dcaf0 !important; color: #212529 !important; }
        .bg-success { background-color: #198754 !important; color: #fff !important; }
        .bg-danger { background-color: #dc3545 !important; color: #fff !important; }
        .alert-message {
            margin: 20px 0;
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
        .btn-action {
            margin: 2px;
            font-size: 0.85rem;
            padding: 0.3rem 0.6rem;
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
.nav-link:hover {
  text-decoration: underline;
}

.main-header {
  background-color: #002b67;
  color: white;
}

.main-header a {
  color: white;
  text-decoration: none;
  font-weight: bold;
  transition: color 0.2s;
}

.main-header a:hover {
  color: #f7941d;
}

.header-logo {
  max-height: 50px;
}


    </style>
</head>
<body>
    
<header class="main-header py-3">
  <div class="container d-flex justify-content-between align-items-center">
    <div class="logo d-flex align-items-center gap-2">
      <img src="images/cambioFixLogo.png" alt="CambioFix Logo" class="header-logo">
    </div>
    <nav class="d-flex flex-wrap gap-4">
      <a href="index.html">الرئيسية</a>
    <a  href="profile.php">الملف الشخصي</a>
      <a href="login.php">تسجيل دخول</a>
  
    </nav>
  </div>
</header>
    <div class="container py-4">
        <?php if (!empty($message)) echo $message; ?>

        <!-- الطلبات المعلقة -->
        <div class="card mt-4">
            <div class="card-header">الطلبات المعلقة</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>نوع الخدمة</th>
                                <th>الموقع</th>
                                <th>وقت الطلب</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Get all pending requests (متاحة لكل المساعدين)
                                $stmt = $pdo->prepare("
                                    SELECT r.*, u.username as client_name
                                    FROM Requests r
                                    JOIN Users u ON r.client_user_id = u.user_id
                                    WHERE r.status = 'pending_assignment'
                                    ORDER BY r.request_time DESC
                                ");
                                $stmt->execute();
                                $pending_requests = $stmt->fetchAll();

                                if ($pending_requests) {
                                    foreach ($pending_requests as $request) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($request['request_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['client_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['service_requested']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['pickup_location']) . "</td>";
                                        echo "<td>" . htmlspecialchars(date('Y-m-d H:i', strtotime($request['request_time']))) . "</td>";
                                        echo "<td><span class='status-badge bg-warning'>قيد التعيين</span></td>";
                                        echo "<td>
                                            <form method='POST' style='display:inline;'>
                                                <input type='hidden' name='request_id' value='" . $request['request_id'] . "'>
                                                <button type='submit' name='action' value='accept' class='btn btn-success btn-sm btn-action'>قبول</button>
                                                <button type='submit' name='action' value='reject' class='btn btn-danger btn-sm btn-action'>رفض</button>
                                            </form>
                                          </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>لا توجد طلبات معلقة حالياً</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='7' class='text-danger'>حدث خطأ في عرض الطلبات المعلقة</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- الطلبات النشطة للمساعد الحالي فقط -->
        <div class="card mt-4">
            <div class="card-header">الطلبات النشطة</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>نوع الخدمة</th>
                                <th>الموقع</th>
                                <th>وقت الطلب</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Get active requests for this helper
                                $stmt = $pdo->prepare("
                                    SELECT r.*, u.username as client_name
                                    FROM Requests r
                                    JOIN Users u ON r.client_user_id = u.user_id
                                    WHERE r.helper_user_id = ?
                                    AND r.status = 'active'
                                    ORDER BY r.request_time DESC
                                ");
                                $stmt->execute([$helper_id]);
                                $active_requests = $stmt->fetchAll();

                                if ($active_requests) {
                                    foreach ($active_requests as $request) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($request['request_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['client_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['service_requested']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['pickup_location']) . "</td>";
                                        echo "<td>" . htmlspecialchars(date('Y-m-d H:i', strtotime($request['request_time']))) . "</td>";
                                        echo "<td><span class='status-badge bg-info'>نشط</span></td>";
                                        echo "<td>
                                            <form method='POST' style='display:inline;'>
                                                <input type='hidden' name='request_id' value='" . $request['request_id'] . "'>
                                                <button type='submit' name='action' value='complete' class='btn btn-success btn-sm btn-action'>إكمال</button>
                                                <button type='submit' name='action' value='cancel' class='btn btn-danger btn-sm btn-action'>إلغاء</button>
                                            </form>
                                          </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>لا توجد طلبات نشطة حالياً</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='7' class='text-danger'>حدث خطأ في عرض الطلبات النشطة</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- الطلبات المنجزة والملغاة: تظهر للجميع وليس فقط لهذا المساعد -->
        <div class="card mt-4">
            <div class="card-header">الطلبات المنجزة والملغاة</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>نوع الخدمة</th>
                                <th>الموقع</th>
                                <th>وقت الطلب</th>
                                <th>وقت الإنجاز/الإلغاء</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Show all completed & cancelled requests (not only for this helper)
                                $stmt = $pdo->prepare("
                                    SELECT r.*, u.username as client_name
                                    FROM Requests r
                                    JOIN Users u ON r.client_user_id = u.user_id
                                    WHERE r.status IN ('completed', 'cancelled')
                                    ORDER BY r.completion_time DESC
                                    LIMIT 20
                                ");
                                $stmt->execute();
                                $completed_requests = $stmt->fetchAll();

                                if ($completed_requests) {
                                    foreach ($completed_requests as $request) {
                                        $status_class = $request['status'] == 'completed' ? 'bg-success' : 'bg-danger';
                                        $status_text = $request['status'] == 'completed' ? 'مكتمل' : 'ملغي';

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($request['request_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['client_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['service_requested']) . "</td>";
                                        echo "<td>" . htmlspecialchars($request['pickup_location']) . "</td>";
                                        echo "<td>" . htmlspecialchars(date('Y-m-d H:i', strtotime($request['request_time']))) . "</td>";
                                        echo "<td>" . ($request['completion_time'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($request['completion_time']))) : '-') . "</td>";
                                        echo "<td><span class='status-badge {$status_class}'>{$status_text}</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>لا توجد طلبات مكتملة أو ملغاة</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='7' class='text-danger'>حدث خطأ في عرض الطلبات المنجزة</td></tr>";
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