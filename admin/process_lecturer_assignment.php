<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_applications.php');
    exit();
}

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    $application_id = (int)($_POST['application_id'] ?? 0);
    $lecturer_ids = $_POST['lecturers'] ?? [];
    $message = sanitize($_POST['message'] ?? '');
    
    // Validation
    if (empty($application_id)) {
        $_SESSION['error_message'] = 'Invalid application ID.';
        header('Location: manage_applications.php');
        exit();
    }
    
    if (empty($lecturer_ids) || !is_array($lecturer_ids)) {
        $_SESSION['error_message'] = 'Please select at least one lecturer.';
        header('Location: assign_lecturer.php?id=' . $application_id);
        exit();
    }
    
    // Get application details (optimized query)
    $stmt = $pdo->prepare("
        SELECT a.id, a.student_id, a.status, u.full_name as student_name, c.course_code, c.course_name
        FROM applications a 
        JOIN users u ON a.student_id = u.id 
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND a.status = 'hod_approved'
    ");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        $_SESSION['error_message'] = 'Application not found or not ready for lecturer assignment.';
        header('Location: manage_applications.php');
        exit();
    }
    
    // Validate lecturer IDs (use a more efficient query)
    $placeholders = str_repeat('?,', count($lecturer_ids) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT id, full_name, email 
        FROM users 
        WHERE id IN ($placeholders) AND role = 'lecturer' AND status = 'active'
    ");
    $stmt->execute($lecturer_ids);
    $valid_lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($valid_lecturers) !== count($lecturer_ids)) {
        $_SESSION['error_message'] = 'Some selected lecturers are invalid.';
        header('Location: assign_lecturer.php?id=' . $application_id);
        exit();
    }
    
    // Start transaction with better settings for performance
    $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
    $pdo->beginTransaction();
    
    try {
        // Update application status
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET status = 'completed',
                lecturer_assigned_by = ?,
                lecturer_assigned_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user['id'], $application_id]);
        
        // Insert lecturer notifications
        $notification_message = $message ?: 
            "Medical excuse approved for {$application['student_name']} in {$application['course_code']} - {$application['course_name']}.";
        
        // Prepare statements for better performance
        $lecturer_notification_stmt = $pdo->prepare("
            INSERT INTO lecturer_notifications (application_id, lecturer_id, notified_by) 
            VALUES (?, ?, ?)
        ");
        
        $general_notification_stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, application_id, message, type) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($valid_lecturers as $lecturer) {
            // Insert into lecturer_notifications table
            $lecturer_notification_stmt->execute([$application_id, $lecturer['id'], $user['id']]);
            
            // Add general notification using existing connection
            $general_notification_stmt->execute([$lecturer['id'], $application_id, $notification_message, 'info']);
        }
        
        // Log the action using existing connection
        $lecturer_names = array_column($valid_lecturers, 'full_name');
        $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $log_stmt->execute([
            $user['id'], 
            'Application Assigned to Lecturers',
            "Application ID: $application_id assigned to: " . implode(', ', $lecturer_names),
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Notify student using existing connection
        $general_notification_stmt->execute([
            $application['student_id'], 
            $application_id,
            'Your medical excuse application has been completed and assigned to the relevant lecturer(s).', 
            'success'
        ]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = 
            'Application successfully assigned to ' . count($valid_lecturers) . ' lecturer(s): ' . 
            implode(', ', $lecturer_names) . '.';
        
        header('Location: manage_applications.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in process lecturer assignment: " . $e->getMessage());
    $_SESSION['error_message'] = 'A database error occurred. Please try again.';
    header('Location: assign_lecturer.php?id=' . $application_id);
    exit();
} catch (Exception $e) {
    error_log("General error in process lecturer assignment: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while assigning lecturers.';
    header('Location: assign_lecturer.php?id=' . $application_id);
    exit();
}
?>