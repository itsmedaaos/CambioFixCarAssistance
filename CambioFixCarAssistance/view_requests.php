<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if ($role === 'customer') {
    $stmt = $pdo->prepare("SELECT r.request_id, r.location, r.status, pt.type_name, r.request_time
                           FROM assistance_requests r
                           JOIN problem_types pt ON r.problem_type_id = pt.problem_type_id
                           WHERE r.customer_id = ?
                           ORDER BY r.request_time DESC");
    $stmt->execute([$user_id]);
} elseif ($role === 'helper') {
    $stmt = $pdo->prepare("SELECT a.assignment_id, r.location, pt.type_name, a.assigned_time, a.completed_time, r.status
                           FROM request_assignments a
                           JOIN assistance_requests r ON a.request_id = r.request_id
                           JOIN problem_types pt ON r.problem_type_id = pt.problem_type_id
                           WHERE a.helper_id = ?
                           ORDER BY a.assigned_time DESC");
    $stmt->execute([$user_id]);
} elseif ($role === 'admin') {
    $stmt = $pdo->query("SELECT r.request_id, u.full_name AS customer_name, r.location, pt.type_name, r.status, r.request_time
                          FROM assistance_requests r
                          JOIN users u ON r.customer_id = u.user_id
                          JOIN problem_types pt ON r.problem_type_id = pt.problem_type_id
                          ORDER BY r.request_time DESC");
}

echo "<table border='1' cellpadding='5'>";
echo "<tr>";
if ($role === 'customer') {
    echo "<th>Request ID</th><th>Location</th><th>Problem</th><th>Status</th><th>Requested At</th>";
    foreach ($stmt as $row) {
        echo "<tr><td>{$row['request_id']}</td><td>{$row['location']}</td><td>{$row['type_name']}</td><td>{$row['status']}</td><td>{$row['request_time']}</td></tr>";
    }
} elseif ($role === 'helper') {
    echo "<th>Assignment ID</th><th>Location</th><th>Problem</th><th>Status</th><th>Assigned</th><th>Completed</th>";
    foreach ($stmt as $row) {
        echo "<tr><td>{$row['assignment_id']}</td><td>{$row['location']}</td><td>{$row['type_name']}</td><td>{$row['status']}</td><td>{$row['assigned_time']}</td><td>{$row['completed_time']}</td></tr>";
    }
} elseif ($role === 'admin') {
    echo "<th>Request ID</th><th>Customer</th><th>Location</th><th>Problem</th><th>Status</th><th>Requested At</th>";
    foreach ($stmt as $row) {
        echo "<tr><td>{$row['request_id']}</td><td>{$row['customer_name']}</td><td>{$row['location']}</td><td>{$row['type_name']}</td><td>{$row['status']}</td><td>{$row['request_time']}</td></tr>";
    }
}
echo "</tr></table>";
?>
