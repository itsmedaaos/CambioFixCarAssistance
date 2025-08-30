<?php
session_start();
require_once 'db.php';

// ***************************************************************
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ***************************************************************

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit();
}

$client_user_id = $_SESSION['user_id']; // جلب user_id من الجلسة

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: client_dashboard.php?msg=" . urlencode("طلب غير صالح."));
    exit();
}

if (!isset($_POST['request_id']) || !isset($_POST['rating'])) {
    header("Location: client_dashboard.php?msg=" . urlencode("الرجاء إدخال التقييم."));
    exit();
}

$request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
$rating = filter_var($_POST['rating'], FILTER_SANITIZE_NUMBER_INT);
$comment = isset($_POST['comment']) ? filter_var($_POST['comment'], FILTER_SANITIZE_STRING) : null;

if ($rating < 1 || $rating > 5) {
    header("Location: client_dashboard.php?msg=" . urlencode("التقييم غير صالح."));
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt_check_request = $pdo->prepare("SELECT COUNT(*) FROM Requests WHERE request_id = ? AND client_user_id = ? AND status = 'completed'");
    $stmt_check_request->execute([$request_id, $client_user_id]);
    if ($stmt_check_request->fetchColumn() == 0) {
        $pdo->rollBack();
        header("Location: client_dashboard.php?msg=" . urlencode("الطلب غير موجود أو لا يمكن تقييمه."));
        exit();
    }

    $stmt_check_feedback = $pdo->prepare("SELECT COUNT(*) FROM Feedback WHERE request_id = ?");
    $stmt_check_feedback->execute([$request_id]);
    if ($stmt_check_feedback->fetchColumn() > 0) {
        $pdo->rollBack();
        header("Location: client_dashboard.php?msg=" . urlencode("تم إرسال تقييم لهذا الطلب مسبقاً."));
        exit();
    }

    $stmt_insert_feedback = $pdo->prepare("INSERT INTO Feedback (request_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt_insert_feedback->execute([$request_id, $client_user_id, $rating, $comment]);

    $pdo->commit();
    header("Location: client_dashboard.php?msg=" . urlencode("تم إرسال تقييمك بنجاح."));
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: client_dashboard.php?msg=" . urlencode("حدث خطأ في قاعدة البيانات: " . $e->getMessage()));
    exit();
}
?>