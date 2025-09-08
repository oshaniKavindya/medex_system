<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

requireRole('admin');

// Return JSON for AJAX requests
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    $application_id = (int)($_POST['application_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    
    if (empty($application_id) || $action !== 'review') {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
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
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit();
    }
    
    if ($application['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Application has already been reviewed']);
        exit();
    }
    
    $decision = sanitize($_POST['decision'] ?? '');
    $comments = sanitize($_POST['comments'] ?? '');
    
    if (!in_array($decision, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid decision']);
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
            logAction($user['id'], 'Application Approved by Admin', "Application ID: $application_id");
            
            // Notify student
            addNotification($application['student_id'], 
                'Your medical excuse application has been reviewed and forwarded to HOD for approval.', 
                'info', $application_id);
            
            // Notify HODs of the same department
            $stmt = $pdo->prepare("
                SELECT id FROM users 
                WHERE role = 'hod' AND department = ? AND status = 'active'
            ");
            $stmt->execute([$application['student_dept']]);
            $hods = $stmt->fetchAll();
            
            foreach ($hods as $hod) {
                addNotification($hod['id'], 
                    "New medical excuse application ready for review from {$application['student_name']} (ID: $application_id)", 
                    'info', $application_id);
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
            logAction($user['id'], 'Application Rejected by Admin', "Application ID: $application_id, Reason: $comments");
            
            // Notify student
            addNotification($application['student_id'], 
                'Your medical excuse application has been rejected by the medical officer. Please check the comments and resubmit if necessary.', 
                'warning', $application_id);
            
            $message = 'Application rejected successfully.';
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in process applications: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in process applications: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing the application']);
}
?>