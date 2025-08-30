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
$request_id = $_GET['request_id'] ?? null;
$message = '';
$can_rate = false;
$helper_user_id = null;
$driver_user_id = null;
$rated_user_id = null; 
$rated_user_type = null;

if (!$request_id) {
    $message = '<div class="alert alert-danger">لم يتم تحديد الطلب الذي تريد تقييمه.</div>';
} else {
    try {
        $stmt_request = $pdo->prepare("SELECT client_user_id, helper_user_id, driver_user_id, status FROM Requests WHERE request_id = ?");
        $stmt_request->execute([$request_id]);
        $request = $stmt_request->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $message = '<div class="alert alert-danger">الطلب غير موجود.</div>';
        } elseif ($request['client_user_id'] != $client_user_id) {
            $message = '<div class="alert alert-danger">ليس لديك صلاحية لتقييم هذا الطلب.</div>';
        } elseif ($request['status'] !== 'completed') {
            $message = '<div class="alert alert-warning">لا يمكن تقييم هذا الطلب إلا بعد اكتماله.</div>';
        } else {
            if ($request['helper_user_id']) {
                $rated_user_id = $request['helper_user_id'];
                $rated_user_type = 'helper';
            } elseif ($request['driver_user_id']) {
                $rated_user_id = $request['driver_user_id'];
                $rated_user_type = 'driver';
            } else {
                $message = '<div class="alert alert-info">لا يوجد مساعد أو سائق معين لهذا الطلب لتقييمه.</div>';
            }

            if ($rated_user_id) {
                $stmt_feedback = $pdo->prepare("SELECT COUNT(*) FROM Feedback WHERE request_id = ? AND user_id = ?");
                $stmt_feedback->execute([$request_id, $client_user_id]);
                if ($stmt_feedback->fetchColumn() > 0) {
                    $message = '<div class="alert alert-info">لقد قمت بتقييم هذا الطلب بالفعل.</div>';
                } else {
                    $can_rate = true;
                }
            }
        }

        if ($can_rate && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_rating'])) {
            $rating_value = $_POST['rating'] ?? 0;
            $comments = trim($_POST['comments'] ?? '');

            if ($rating_value < 1 || $rating_value > 5) {
                $message = '<div class="alert alert-danger">التقييم يجب أن يكون بين 1 و 5 نجوم.</div>';
            } else {
                $pdo->beginTransaction(); 
                $stmt_insert_feedback = $pdo->prepare("INSERT INTO Feedback (request_id, user_id, rating, comments, feedback_date) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP())");
                $stmt_insert_feedback->execute([$request_id, $client_user_id, $rating_value, $comments]);

                if ($rated_user_type === 'helper') {
                    $stmt_update_rating = $pdo->prepare("UPDATE Helpers h SET rating = (SELECT AVG(f.rating) FROM Feedback f JOIN Requests r ON f.request_id = r.request_id WHERE r.helper_user_id = h.user_id) WHERE h.user_id = ?");
                    $stmt_update_rating->execute([$rated_user_id]);
                } elseif ($rated_user_type === 'driver') {
                    
                    $stmt_update_driver_rating = $pdo->prepare("UPDATE Users SET rating = (SELECT AVG(f.rating) FROM Feedback f JOIN Requests r ON f.request_id = r.request_id WHERE r.driver_user_id = ?) WHERE user_id = ?");
                    $stmt_update_driver_rating->execute([$rated_user_id, $rated_user_id]);
                }
                $pdo->commit(); 

                $message = '<div class="alert alert-success">شكراً لك! تم إرسال تقييمك بنجاح.</div>';
                $can_rate = false; 
            }
        }

    } catch (PDOException $e) {
        $pdo->rollBack(); 
        error_log("Rating submission error: " . $e->getMessage());
        $message = '<div class="alert alert-danger">حدث خطأ في قاعدة البيانات أثناء إرسال تقييمك: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تقييم الخدمة للطلب #<?= htmlspecialchars($request_id) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; color: #343a40; }
    .container { margin-top: 50px; }
    .card { padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .rating-header { font-size: 2rem; color: #007bff; margin-bottom: 30px; }
    .rating-stars span { cursor: pointer; font-size: 2em; color: #ddd; }
    .rating-stars span.active { color: #ffc107; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2 class="text-center rating-header">تقييم الخدمة للطلب #<?= htmlspecialchars($request_id) ?></h2>
      <p class="text-center">الرجاء تقييم الخدمة التي تلقيتها.</p>

      <?= $message ?>

      <?php if ($can_rate): ?>
      <form method="POST" action="">
        <div class="mb-3 text-center">
          <label class="form-label d-block mb-2">تقييمك:</label>
          <div class="rating-stars" id="ratingStars">
            <span data-value="1">&#9733;</span><span data-value="2">&#9733;</span><span data-value="3">&#9733;</span><span data-value="4">&#9733;</span><span data-value="5">&#9733;</span>
          </div>
          <input type="hidden" name="rating" id="ratingInput" value="0" required>
        </div>
        <div class="mb-3">
          <label for="comments" class="form-label">تعليقات (اختياري):</label>
          <textarea class="form-control" id="comments" name="comments" rows="3" placeholder="اكتب تعليقاتك هنا..."></textarea>
        </div>
        <div class="text-center mt-4">
          <button type="submit" name="submit_rating" class="btn btn-primary">إرسال التقييم</button>
        </div>
      </form>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="client_dashboard.php" class="btn btn-secondary">العودة للوحة التحكم</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('#ratingStars span');
        const ratingInput = document.getElementById('ratingInput');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                ratingInput.value = value;
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            star.addEventListener('mouseover', function() {
                const value = parseInt(this.dataset.value);
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
            star.addEventListener('mouseout', function() {
                const currentValue = parseInt(ratingInput.value);
                stars.forEach((s, i) => {
                    if (i < currentValue) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                    s.style.color = ''; // Reset to default active/inactive color
                });
            });
        });

        const initialRating = parseInt(ratingInput.value);
        if (initialRating > 0) {
            stars.forEach((s, i) => {
                if (i < initialRating) {
                    s.classList.add('active');
                }
            });
        }
    });
  </script>
</body>
</html>