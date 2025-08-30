<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $helper_id = $_POST['helper_id'];
    $assigned_time = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO request_assignments (request_id, helper_id, assigned_time) VALUES (?, ?, ?)");
    $update = $pdo->prepare("UPDATE assistance_requests SET status = 'assigned' WHERE request_id = ?");

    try {
        $stmt->execute([$request_id, $helper_id, $assigned_time]);
        $update->execute([$request_id]);
        echo "Helper assigned successfully.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>