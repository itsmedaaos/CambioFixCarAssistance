<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_SESSION['user_id'];
    $location = $_POST['location'];
    $problem_type_id = $_POST['problem_type_id'];

    $stmt = $pdo->prepare("INSERT INTO assistance_requests (customer_id, location, problem_type_id) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$customer_id, $location, $problem_type_id]);
        echo "Request submitted successfully.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>