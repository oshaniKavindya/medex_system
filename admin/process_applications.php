<?php
// Start output buffering for better performance
ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method.';
    ob_end_clean();
    header('Location: manage_applications.php');
    exit();
}

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    $application_id = (int)($_POST['application_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    
    if (empty($application_id) || $action !== 'review') {
        $_SESSION['error_message'] = 'Invalid parameters.';
        header('Location: manage_applications.php');
        exit();
    }
    
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name as student_name, u.department as student_dept
        FROM applications a 
        JOIN users u ON a.student_id = u.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();
    
    if (!$application) {
        $_SESSION['error_message'] = 'Application not found.';
        header('Location: manage_applications.php');
        exit();
    }
    
    if ($application['status'] !== 'pending') {
        $_SESSION['error_message'] = 'Application has already been reviewed.';
        header('Location: manage_applications.php?id=' . $application_id);
        exit();
    }
    
    $decision = sanitize($_POST['decision'] ?? '');
    $comments = sanitize($_POST['comments'] ?? '');
    
    if (!in_array($decision, ['approve', 'reject'])) {
        $_SESSION['error_message'] = 'Invalid decision.';
        header('Location: manage_applications.php?id=' . $application_id);
        exit();
    }
    
    $pdo->beginTransaction();
    
    try {
        if ($decision === 'approve') {
            // Approve and forward to HOD
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET status = 'admin_reviewed', 
                    admin_comments = ?, 
                    admin_reviewed_by = ?, 
                    reviewed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$comments, $user['id'], $application_id]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], 'Application Approved by Admin', "Application ID: $application_id", $_SERVER['REMOTE_ADDR']]);
            
            // Notify student
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, application_id, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$application['student_id'], $application_id, 
                'Your medical excuse application has been reviewed and forwarded to HOD for approval.', 
                'info']);
            
            // Notify HODs of the same department
            $stmt = $pdo->prepare("
                SELECT id FROM users 
                WHERE role = 'hod' AND department = ? AND status = 'active'
            ");
            $stmt->execute([$application['student_dept']]);
            $hods = $stmt->fetchAll();
            
            // Batch insert notifications for HODs
            if (!empty($hods)) {
                $notifyStmt = $pdo->prepare("INSERT INTO notifications (user_id, application_id, message, type) VALUES (?, ?, ?, ?)");
                foreach ($hods as $hod) {
                    $notifyStmt->execute([$hod['id'], $application_id,
                        "New medical excuse application ready for review from {$application['student_name']} (ID: $application_id)", 
                        'info']);
                }
            }
            
            $message = 'Application approved and forwarded to HOD successfully.';
            
        } else {
            // Reject application
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET status = 'admin_rejected', 
                    admin_comments = ?, 
                    admin_reviewed_by = ?, 
                    reviewed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$comments, $user['id'], $application_id]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], 'Application Rejected by Admin', "Application ID: $application_id, Reason: $comments", $_SERVER['REMOTE_ADDR']]);
            
            // Notify student
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, application_id, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$application['student_id'], $application_id,
                'Your medical excuse application has been rejected by the medical officer. Please check the comments and resubmit if necessary.', 
                'warning']);
            
            $message = 'Application rejected successfully.';
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = $message;
        
        // Clean output buffer and redirect
        ob_end_clean();
        header('Location: manage_applications.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in process applications: " . $e->getMessage());
    $_SESSION['error_message'] = 'A database error occurred. Please try again.';
    ob_end_clean();
    header('Location: manage_applications.php');
    exit();
} catch (Exception $e) {
    error_log("General error in process applications: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while processing the application.';
    ob_end_clean();
    header('Location: manage_applications.php');
    exit();
}
?>