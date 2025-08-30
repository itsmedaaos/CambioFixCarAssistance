<?php

require_once 'db.php';

if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$request_id = $_GET['id'];

$stmt = $pdo->prepare(
    "SELECT r.*, cu.username AS client_name, cu.phone_number AS client_phone,
            hu.username AS helper_name, hu.phone_number AS helper_phone
     FROM requests r
     JOIN users cu ON r.client_user_id = cu.user_id
     LEFT JOIN users hu ON r.helper_user_id = hu.user_id
     WHERE r.request_id = ?"
);
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الطلب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container py-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">تفاصيل الطلب #<?= $request_id ?></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>معلومات العميل</h4>
                        <p><strong>الاسم:</strong> <?= htmlspecialchars($request['client_name']) ?></p>
                        <p><strong>رقم الهاتف:</strong> <?= htmlspecialchars($request['client_phone']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h4>معلومات المساعد</h4>
                        <p><strong>الاسم:</strong> <?= htmlspecialchars($request['helper_name'] ?? 'غير محدد') ?></p>
                        <p><strong>رقم الهاتف:</strong> <?= htmlspecialchars($request['helper_phone'] ?? 'غير محدد') ?></p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h4>تفاصيل الخدمة</h4>
                        <p><strong>نوع الخدمة:</strong> <?= htmlspecialchars($request['service_requested']) ?></p>
                        <p><strong>موقع الالتقاط:</strong> <?= htmlspecialchars($request['pickup_location']) ?></p>
                        <p><strong>الوجهة:</strong> <?= htmlspecialchars($request['destination_location'] ?? 'غير محدد') ?></p>
                        <p><strong>وقت الطلب:</strong> <?= htmlspecialchars($request['request_time']) ?></p>
                        <p><strong>الحالة:</strong> 
                            <?php
                            switch($request['status']) {
                                case 'pending_assignment':
                                    echo 'قيد الانتظار';
                                    break;
                                case 'active':
                                    echo 'نشط';
                                    break;
                                case 'completed':
                                    echo 'مكتمل';
                                    break;
                                case 'cancelled':
                                    echo 'ملغي';
                                    break;
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="admin.php" class="btn btn-secondary">العودة للوحة التحكم</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>