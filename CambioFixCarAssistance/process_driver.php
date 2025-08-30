<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = $_POST['driver_app_id'];
    $admin_id = $_SESSION['user_id']; // Assuming admin is logged in
    
    if (isset($_POST['accept_driver'])) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Get user_id from application
            $stmt = $pdo->prepare("SELECT user_id FROM driverapplications WHERE application_id = ?");
            $stmt->execute([$application_id]);
            $user_id = $stmt->fetchColumn();
            
            // Update application status
            $stmt = $pdo->prepare("UPDATE driverapplications SET status = 'approved', reviewed_by = ? WHERE application_id = ?");
            $stmt->execute([$admin_id, $application_id]);
            
            // Create helper record
            $stmt = $pdo->prepare("INSERT INTO helpers (user_id, name) SELECT user_id, username FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Update user type
            $stmt = $pdo->prepare("UPDATE users SET user_type = 'helper' WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            header("Location: admin.php?success=driver_approved");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: admin.php?error=approval_failed");
        }
    }
    
    if (isset($_POST['reject_driver'])) {
        try {
            $stmt = $pdo->prepare("UPDATE driverapplications SET status = 'rejected', reviewed_by = ? WHERE application_id = ?");
            $stmt->execute([$admin_id, $application_id]);
            header("Location: admin.php?success=driver_rejected");
        } catch (Exception $e) {
            header("Location: admin.php?error=rejection_failed");
        }
    }
}