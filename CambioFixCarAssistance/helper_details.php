<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تفاصيل المساعد</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Cairo', sans-serif; /* استخدام خط القاهرة إذا كان متوفرًا */
    }
    .details-box {
      border: 2px solid #ccc;
      border-radius: 15px;
      background-color: #fff;
      padding: 25px;
      max-width: 700px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    .details-box h2 {
      color: #003366;
      font-weight: bold;
      border-bottom: 2px solid #f7941d;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }
    .details-box .info {
      margin-bottom: 10px;
      font-size: 18px;
    }
    .label {
      color: #0d6efd;
      font-weight: bold;
    }
    /* تنسيقات إضافية للأزرار من admin_style.css أو أضفها في ملف منفصل */
    .btn-secondary {
      background-color: #6c757d;
      border-color: #6c757d;
      color: white;
    }
    .btn-secondary:hover {
      background-color: #5a6268;
      border-color: #545b62;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="details-box">
      <h2>تفاصيل المساعد</h2>

      <?php
      require_once 'db.php'; // تأكد من وجود ملف الاتصال بقاعدة البيانات
        
      // تمكين عرض الأخطاء لغرض التصحيح فقط (أزل هذا في بيئة الإنتاج)
      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);

      $helper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // الحصول على ID المساعد من URL
      if ($helper_id > 0) {
          try {
              // جلب تفاصيل المساعد من جدول Users و Helpers
              $stmt = $pdo->prepare("
                  SELECT
                      u.username,
                      u.phone_number,
                      u.registration_date,
                      h.current_location,
                      h.total_completed_requests
                  FROM
                      Users u
                  JOIN
                      Helpers h ON u.user_id = h.user_id
                  WHERE
                      u.user_id = ? AND u.user_type = 'helper'
              ");
              $stmt->execute([$helper_id]);
              $helper = $stmt->fetch(PDO::FETCH_ASSOC);

              if ($helper) {
                  echo '<div class="info"><span class="label">الاسم:</span> ' . htmlspecialchars($helper['username']) . '</div>';
                  echo '<div class="info"><span class="label">رقم الهاتف:</span> ' . htmlspecialchars($helper['phone_number']) . '</div>';
                  echo '<div class="info"><span class="label">الموقع:</span> ' . htmlspecialchars($helper['current_location'] ?? 'غير محدد') . '</div>';
                  echo '<div class="info"><span class="label">عدد الطلبات المكتملة:</span> ' . htmlspecialchars($helper['total_completed_requests']) . '</div>';
                  echo '<div class="info"><span class="label">تاريخ الانضمام:</span> ' . htmlspecialchars(date('Y-m-d', strtotime($helper['registration_date']))) . '</div>';
              } else {
                  echo '<div class="info" style="color: red;">لم يتم العثور على تفاصيل المساعد أو أن معرف المساعد غير صحيح.</div>';
              }
          } catch (PDOException $e) {
              error_log("Error fetching helper details: " . $e->getMessage());
              echo '<div class="info" style="color: red;">حدث خطأ أثناء تحميل التفاصيل. الرجاء المحاولة لاحقاً.</div>';
          }
      } else {
          echo '<div class="info" style="color: red;">معرف المساعد غير موجود أو غير صالح في الرابط.</div>';
      }
      ?>

      <div class="text-center mt-4">
        <a href="javascript:history.back()" class="btn btn-secondary">رجوع</a>
      </div>
    </div>
  </div>
</body>
</html>