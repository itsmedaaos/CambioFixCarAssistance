<?php
session_start();
require_once 'db.php';

// ***************************************************************
// تمكين عرض الأخطاء لغرض التصحيح فقط (أزل هذا في بيئة الإنتاج)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ***************************************************************

// التحقق مما إذا كان المستخدم مسجل الدخول ونوعه "client"
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit();
}

$client_user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // جلب اسم المستخدم من الجلسة

// رسالة نجاح أو خطأ
$message = '';
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}

// ***************************************************************
// معالجة طلب الخدمة الجديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $service_requested = $_POST['service_requested'];
    $location = $_POST['location'];
    $request_time = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("INSERT INTO Requests (client_user_id, service_requested, pickup_location, status, request_time) VALUES (?, ?, ?, 'pending_assignment', ?)");
        $stmt->execute([$client_user_id, $service_requested, $location, $request_time]);
        $message = "تم إرسال طلبك بنجاح. سيتم التواصل معك قريباً.";
    } catch (PDOException $e) {
        $message = "حدث خطأ: " . $e->getMessage();
    }
}
// ***************************************************************

// ***************************************************************
// دالة لترجمة حالة الطلب إلى العربية
function translateStatus($status) {
    switch ($status) {
        case 'pending_assignment':
            return 'قيد التعيين';
        case 'active':
            return 'نشط';
        case 'completed':
            return 'مكتمل';
        case 'cancelled':
            return 'ملغي';
        default:
            return $status;
    }
}
// ***************************************************************

// ***************************************************************
// استعلام جلب الطلبات الحالية للعميل مع اسم المساعد ومعلومات التقييم والسعر
$stmt_current_requests = $pdo->prepare("
    SELECT
        r.*,
        u.username AS helper_username,
        f.rating,
        f.comment,
        f.feedback_id,
        sp.price
    FROM Requests r
   LEFT JOIN Users u ON r.helper_user_id = u.user_id
    LEFT JOIN Feedback f ON r.request_id = f.request_id
    LEFT JOIN service_prices sp ON r.service_requested = sp.service_type
    WHERE r.client_user_id = ?
    ORDER BY r.request_time DESC
");
$stmt_current_requests->execute([$client_user_id]);
$current_requests = $stmt_current_requests->fetchAll(PDO::FETCH_ASSOC);

// ***************************************************************
// جلب قائمة الخدمات وأسعارها من قاعدة البيانات
$stmt_services = $pdo->query("SELECT * FROM service_prices");
$services_list = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

// ***************************************************************
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم العميل</title>
           <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .request-status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        .status-pending-assignment { background-color: #ffc107; color: #000; }
        .status-active { background-color: #0d6efd; color: #fff; }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        .rating-stars { color: #ffc107; }
        .feedback-form-container {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
        }
        #price-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
            margin-top: 10px;

        }body {
  font-family: 'Cairo', sans-serif;
  background-color: #f4f6f9;
}

header .navbar {
  background-color: #002b67;
  padding: 10px 0;
}

.navbar-brand {
  font-weight: bold;
  font-size: 1.3rem;
  color: #f7941d !important;
}

.navbar-text strong {
  color: #f7941d;
}

.btn-outline-danger {
  color: #fff;
  border-color: #f7941d;
  background-color: #f7941d;
}

.btn-outline-danger:hover {
  background-color: #e67a12;
  border-color: #e67a12;
}

h2 {
  color: #002b67;
}

.card {
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.08);
}

.card-header {
  font-weight: bold;
  font-size: 1.1rem;
}

.card-header.bg-primary {
  background-color: #f7941d !important;
  color: white;
}

.card-header.bg-success {
  background-color: #002b67 !important;
  color: white;
}

.btn-primary {
  background-color: #002b67;
  border-color: #002b67;
}

.btn-primary:hover {
  background-color: #001f4d;
  border-color: #001f4d;
}

.table th {
  background-color: #f0f0f0;
}

.table td, .table th {
  vertical-align: middle !important;
}

.badge {
  border-radius: 50px;
  font-size: 0.8rem;
}

.rating-stars i {
  cursor: pointer;
  font-size: 1.2rem;
}
.navbar-brand span {
    font-size: 1.3rem;
    font-weight: bold;
    color: #f7941d;
}

.navbar-brand img {
    max-height: 40px;
}

.navbar .btn-outline-danger {
    color: #fff;
    border-color: #f7941d;
    background-color: #f7941d;
}

.navbar .btn-outline-danger:hover {
    background-color: #e67a12;
    border-color: #e67a12;
}
.custom-link {
  color: white !important;
  font-weight: 500;
  position: relative;
  transition: color 0.3s ease;
  text-decoration: none;
  padding-bottom: 2px;
}

.custom-link:hover {
  color: #f7941d !important;
}

.custom-link::after {
  content: "";
  position: absolute;
  bottom: 0;
  right: 0;
  height: 2px;
  width: 0%;
  background-color: #f7941d;
  transition: width 0.3s ease-in-out;
}

.custom-link:hover::after {
  width: 100%;
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


    </style>
</head>
<body class="bg-light">

<header>
  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #002b67;">
    <div class="container d-flex justify-content-between align-items-center">

      <!-- الشعار -->
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="images/cambioFixLogo.png" alt="CambioFix Logo" height="40" class="ms-2">
      </a>

      <!-- اسم المستخدم -->
      <span class="navbar-text text-white fw-bold">
        أهلاً بك، <strong style="color: #f7941d;"><?= htmlspecialchars($username) ?></strong>
      </span>

      <!-- الروابط -->
      <div class="d-flex align-items-center gap-4">
        <a class="nav-link custom-link" href="index.html">الرئيسية</a>
        <a class="nav-link custom-link" href="profile.php">الملف الشخصي</a>
        <a class="nav-link custom-link" href="login.php">تسجيل دخول</a>
      </div>

    </div>
  </nav>
</header>



    <div class="container py-4">
        <h2 class="text-center mb-4">طلباتك</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">إرسال طلب خدمة جديد</div>
            <div class="card-body">
                <form action="client_dashboard.php" method="POST">
                    <div class="mb-3">
                        <label for="service_requested" class="form-label">نوع الخدمة:</label>
                        <select class="form-select" id="service_requested" name="service_requested" required onchange="displayServicePrice()">
                            <option value="">اختر خدمة...</option>
                            <?php foreach ($services_list as $service): ?>
                                <option value="<?= htmlspecialchars($service['service_type']) ?>" data-price="<?= htmlspecialchars($service['price']) ?>">
                                    <?= htmlspecialchars($service['service_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="price-display" class="mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">الموقع:</label>
                        <input type="text" class="form-control" id="location" name="location" required placeholder="أدخل موقعك الحالي">
                    </div>
                    <button type="submit" name="submit_request" class="btn btn-primary">إرسال الطلب</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-success text-white">الطلبات الحالية</div>
            <div class="card-body">
                <?php if ($current_requests): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>الخدمة المطلوبة</th>
                                    <th>الموقع</th>
                                    <th>التاريخ</th>
                                    <th>الحالة</th>
                                    <th>المساعد</th>
                                    <th>السعر</th>
                                    <th>التقييم</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($current_requests as $request): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request['request_id']) ?></td>
                                        <td><?= htmlspecialchars($request['service_requested']) ?></td>
                                        <td><?= htmlspecialchars($request['pickup_location']) ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($request['request_time']))) ?></td>
                                        <td><span class="badge request-status-badge status-<?= str_replace('_', '-', $request['status']) ?>"><?= translateStatus($request['status']) ?></span></td>
                                        <td><?= $request['helper_username'] ? htmlspecialchars($request['helper_username']) : 'لم يتم التعيين بعد' ?></td>
                                        <td><?= $request['price'] ? htmlspecialchars(number_format($request['price'], 2)) . ' LYD' : 'غير متوفر' ?></td>
                                        <td>
                                            <?php if ($request['rating']): ?>
                                                <div class="rating-stars">
                                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                                        <i class="fa<?= $i < $request['rating'] ? 's' : 'r' ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php else: ?>
                                                <span>لا يوجد تقييم</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'completed' && !$request['feedback_id']): ?>
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#feedbackModal-<?= $request['request_id'] ?>">
                                                    تقييم
                                                </button>
                                                <div class="modal fade" id="feedbackModal-<?= $request['request_id'] ?>" tabindex="-1" aria-labelledby="feedbackModalLabel-<?= $request['request_id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="submit_feedback.php" method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="feedbackModalLabel-<?= $request['request_id'] ?>">تقييم الخدمة</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">التقييم:</label>
                                                                        <div class="rating-stars">
                                                                            <i class="far fa-star" data-rating="1"></i>
                                                                            <i class="far fa-star" data-rating="2"></i>
                                                                            <i class="far fa-star" data-rating="3"></i>
                                                                            <i class="far fa-star" data-rating="4"></i>
                                                                            <i class="far fa-star" data-rating="5"></i>
                                                                            <input type="hidden" name="rating" id="rating-<?= $request['request_id'] ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="comment-<?= $request['request_id'] ?>" class="form-label">تعليق (اختياري):</label>
                                                                        <textarea class="form-control" id="comment-<?= $request['request_id'] ?>" name="comment" rows="3"></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                                                    <button type="submit" class="btn btn-primary">إرسال التقييم</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php elseif ($request['status'] === 'active'): ?>
                                                <span>في الطريق إليك</span>
                                            <?php else: ?>
                                                <span>-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">لا توجد لديك طلبات سابقة.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Price display logic
            const serviceSelect = document.getElementById('service_requested');
            const priceDisplay = document.getElementById('price-display');
            serviceSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                if (price) {
                    priceDisplay.innerHTML = `السعر التقديري: <strong>${parseFloat(price).toFixed(2)} LYD</strong>`;
                } else {
                    priceDisplay.innerHTML = '';
                }
            });

            // Rating stars logic for modals
            document.querySelectorAll('.modal').forEach(modal => {
                const ratingStars = modal.querySelectorAll('.rating-stars .fa-star');
                ratingStars.forEach(star => {
                    star.addEventListener('click', function() {
                        const ratingValue = this.getAttribute('data-rating');
                        const input = modal.querySelector('input[name="rating"]');
                        input.value = ratingValue;
                        
                        ratingStars.forEach(s => {
                            if (s.getAttribute('data-rating') <= ratingValue) {
                                s.classList.remove('far');
                                s.classList.add('fas');
                            } else {
                                s.classList.remove('fas');
                                s.classList.add('far');
                            }
                        });
                    });
                });
            });
        });
    </script>
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

</body>
</html>