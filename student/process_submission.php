<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: submit_application.php');
    exit();
}

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Validate input data
    $course_id = (int)($_POST['course_id'] ?? 0);
    $application_type = sanitize($_POST['application_type'] ?? '');
    $application_date = sanitize($_POST['application_date'] ?? '');
    $application_time = sanitize($_POST['application_time'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    $certificate_type = sanitize($_POST['certificate_type'] ?? '');
    $declaration = isset($_POST['declaration']);
    
    // Validation
    $errors = [];
    
    if (empty($course_id)) $errors[] = 'Please select a course.';
    if (empty($application_type)) $errors[] = 'Please select the absence type.';
    if (empty($application_date)) $errors[] = 'Please enter the date of absence.';
    if (empty($reason)) $errors[] = 'Please provide a reason for absence.';
    if (empty($certificate_type)) $errors[] = 'Please select the medical certificate type.';
    if (!$declaration) $errors[] = 'You must agree to the declaration.';
    
    // Validate date (within 14 days)
    if (!empty($application_date)) {
        $absenceDate = new DateTime($application_date);
        $today = new DateTime();
        $interval = $today->diff($absenceDate);
        if ($interval->days > 14) {
            $errors[] = 'Applications must be submitted within 14 days of absence.';
        }
    }
    
    // Validate course belongs to student's department and year
    if (!empty($course_id)) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND department = ? AND year = ?");
        $stmt->execute([$course_id, $user['department'], $user['year']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Invalid course selection.';
        }
    }
    
    // Validate file uploads
    if (!isset($_FILES['letter_file']) || $_FILES['letter_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload the letter document.';
    }
    if (!isset($_FILES['medical_application_file']) || $_FILES['medical_application_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload the medical application document.';
    }
    if (!isset($_FILES['medical_certificate_file']) || $_FILES['medical_certificate_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload the medical certificate.';
    }
    
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode('<br>', $errors);
        header('Location: submit_application.php');
        exit();
    }
    
    // Create upload directories
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/medical_excuse_system/assets/uploads/';
    $letterDir = $uploadDir . 'letters/';
    $applicationDir = $uploadDir . 'applications/';
    $certificateDir = $uploadDir . 'certificates/';
    
    if (!file_exists($letterDir)) mkdir($letterDir, 0777, true);
    if (!file_exists($applicationDir)) mkdir($applicationDir, 0777, true);
    if (!file_exists($certificateDir)) mkdir($certificateDir, 0777, true);
    
    // Upload files
    $letterUpload = uploadFile($_FILES['letter_file'], $letterDir, ['pdf', 'jpg', 'jpeg', 'png']);
    $applicationUpload = uploadFile($_FILES['medical_application_file'], $applicationDir, ['pdf', 'jpg', 'jpeg', 'png']);
    $certificateUpload = uploadFile($_FILES['medical_certificate_file'], $certificateDir, ['pdf', 'jpg', 'jpeg', 'png']);
    
    if (!$letterUpload['success'] || !$applicationUpload['success'] || !$certificateUpload['success']) {
        $errors = [];
        if (!$letterUpload['success']) $errors[] = 'Letter upload failed: ' . $letterUpload['message'];
        if (!$applicationUpload['success']) $errors[] = 'Medical application upload failed: ' . $applicationUpload['message'];
        if (!$certificateUpload['success']) $errors[] = 'Medical certificate upload failed: ' . $certificateUpload['message'];
        
        $_SESSION['error_message'] = implode('<br>', $errors);
        header('Location: submit_application.php');
        exit();
    }
    
    // Insert application into database
    $stmt = $pdo->prepare("
        INSERT INTO applications (
            student_id, course_id, application_type, application_date, application_time,
            reason, letter_file, medical_application_file, medical_certificate_file,
            certificate_type, status, submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $success = $stmt->execute([
        $user['id'],
        $course_id,
        $application_type,
        $application_date,
        $application_time ?: null,
        $reason,
        $letterUpload['filename'],
        $applicationUpload['filename'],
        $certificateUpload['filename'],
        $certificate_type
    ]);
    
    if ($success) {
        $applicationId = $pdo->lastInsertId();
        
        // Log the action
        logAction($user['id'], 'Application Submitted', "Application ID: $applicationId");
        
        // Add notification for student
        addNotification($user['id'], 'Your medical excuse application has been submitted successfully and is now pending review.', 'success', $applicationId);
        
        // Notify all admin users
        $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll();
        
        foreach ($admins as $admin) {
            addNotification($admin['id'], "New medical excuse application submitted by {$user['full_name']} (ID: $applicationId)", 'info', $applicationId);
        }
        
        $_SESSION['success_message'] = 'Your medical excuse application has been submitted successfully! You will be notified when it is reviewed.';
        header('Location: view_applications.php?id=' . $applicationId);
        exit();
    } else {
        // Clean up uploaded files on database error
        if (file_exists($letterDir . $letterUpload['filename'])) {
            unlink($letterDir . $letterUpload['filename']);
        }
        if (file_exists($applicationDir . $applicationUpload['filename'])) {
            unlink($applicationDir . $applicationUpload['filename']);
        }
        if (file_exists($certificateDir . $certificateUpload['filename'])) {
            unlink($certificateDir . $certificateUpload['filename']);
        }
        
        $_SESSION['error_message'] = 'Failed to submit application. Please try again.';
        header('Location: submit_application.php');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Database error in process submission: " . $e->getMessage());
    $_SESSION['error_message'] = 'A system error occurred. Please try again later.';
    header('Location: submit_application.php');
    exit();
} catch (Exception $e) {
    error_log("General error in process submission: " . $e->getMessage());
    $_SESSION['error_message'] = 'An unexpected error occurred. Please try again.';
    header('Location: submit_application.php');
    exit();
}
?>