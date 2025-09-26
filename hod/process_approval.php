<?php
// Start output buffering for better performance
ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

requireRole('hod');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: review_applications.php');
    exit();
}

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    $application_id = (int)($_POST['application_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    
    if (empty($application_id) || $action !== 'approve') {
        $_SESSION['error_message'] = 'Invalid parameters.';
        header('Location: review_applications.php');
        exit();
    }
    
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name as student_name, u.department as student_dept, u.id as student_id,
               c.course_name, c.course_code
        FROM applications a 
        JOIN users u ON a.student_id = u.id 
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND u.department = ?
    ");
    $stmt->execute([$application_id, $user['department']]);
    $application = $stmt->fetch();
    
    if (!$application) {
        $_SESSION['error_message'] = 'Application not found or not in your department.';
        header('Location: review_applications.php');
        exit();
    }
    
    if ($application['status'] !== 'admin_reviewed') {
        $_SESSION['error_message'] = 'Application is not ready for HOD approval.';
        header('Location: review_applications.php?id=' . $application_id);
        exit();
    }
    
    $decision = sanitize($_POST['decision'] ?? '');
    $comments = sanitize($_POST['comments'] ?? '');
    
    if (!in_array($decision, ['approve', 'reject'])) {
        $_SESSION['error_message'] = 'Invalid decision.';
        header('Location: review_applications.php?id=' . $application_id);
        exit();
    }
    
    $pdo->beginTransaction();
    
    try {
        if ($decision === 'approve') {
            // Approve application and send back to admin for lecturer assignment
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET status = 'hod_approved', 
                    hod_comments = ?, 
                    hod_reviewed_by = ?, 
                    approved_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$comments, $user['id'], $application_id]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], 'Application Approved by HOD', "Application ID: $application_id", $_SERVER['REMOTE_ADDR']]);
            
            // Notify student
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, application_id, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$application['student_id'], $application_id,
                'Your medical excuse application has been approved by the HOD. The admin will now assign it to the relevant lecturer.', 
                'success']);
            
            // Notify all admins to assign lecturer
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            // Batch insert notifications for admins
            if (!empty($admins)) {
                $notifyStmt = $pdo->prepare("INSERT INTO notifications (user_id, application_id, message, type) VALUES (?, ?, ?, ?)");
                foreach ($admins as $admin) {
                    $notifyStmt->execute([$admin['id'], $application_id,
                        "Application ID: $application_id has been approved by HOD. Please assign it to the relevant lecturer.", 
                        'info']);
                }
            }
            
            $message = 'Application approved successfully. Admin will now assign it to the relevant lecturer.';
            
        } else {
            // Reject application
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET status = 'hod_rejected', 
                    hod_comments = ?, 
                    hod_reviewed_by = ?, 
                    approved_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$comments, $user['id'], $application_id]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], 'Application Rejected by HOD', "Application ID: $application_id, Reason: $comments", $_SERVER['REMOTE_ADDR']]);
            
            // Notify student
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, application_id, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$application['student_id'], $application_id,
                'Your medical excuse application has been rejected by the HOD. Please check the comments for more information.', 
                'warning']);
            
            // Notify admins
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            // Batch insert notifications for admins
            if (!empty($admins)) {
                $notifyStmt = $pdo->prepare("INSERT INTO notifications (user_id, application_id, message, type) VALUES (?, ?, ?, ?)");
                foreach ($admins as $admin) {
                    $notifyStmt->execute([$admin['id'], $application_id,
                        "Application ID: $application_id has been rejected by HOD.", 
                        'warning']);
                }
            }
            
            $message = 'Application rejected successfully.';
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = $message;
        
        // Clean output buffer and redirect
        ob_end_clean();
        header('Location: review_applications.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in HOD process approval: " . $e->getMessage());
    $_SESSION['error_message'] = 'A database error occurred. Please try again.';
    ob_end_clean();
    header('Location: review_applications.php');
    exit();
} catch (Exception $e) {
    error_log("General error in HOD process approval: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while processing the application.';
    ob_end_clean();
    header('Location: review_applications.php');
    exit();
}
?>