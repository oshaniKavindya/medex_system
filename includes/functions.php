<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Generate unique filename
function generateFileName($originalName, $prefix = '') {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = $prefix . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
    return $filename;
}

// Upload file
function uploadFile($file, $uploadDir, $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
    }
    
    // Check file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.'];
    }
    
    $fileName = generateFileName($file['name']);
    $uploadPath = $uploadDir . $fileName;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

// Get courses by department and year
function getCoursesByDepartmentYear($department, $year) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM courses 
            WHERE department = ? AND year = ? 
            AND (submission_end_date IS NULL OR submission_end_date >= CURDATE())
            ORDER BY course_name
        ");
        $stmt->execute([$department, $year]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Add notification
function addNotification($userId, $message, $type = 'info', $applicationId = null) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, application_id, message, type) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $applicationId, $message, $type]);
    } catch(PDOException $e) {
        return false;
    }
}

// Get user notifications
function getUserNotifications($userId, $limit = 10) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT n.*, a.id as app_id 
            FROM notifications n 
            LEFT JOIN applications a ON n.application_id = a.id 
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Mark notification as read
function markNotificationRead($notificationId) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$notificationId]);
    } catch(PDOException $e) {
        return false;
    }
}

// Get application status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending':
            return 'badge-warning';
        case 'admin_reviewed':
            return 'badge-info';
        case 'admin_rejected':
            return 'badge-danger';
        case 'hod_approved':
            return 'badge-success';
        case 'hod_rejected':
            return 'badge-danger';
        case 'completed':
            return 'badge-primary';
        default:
            return 'badge-secondary';
    }
}

// Format status text
function formatStatus($status) {
    switch($status) {
        case 'pending':
            return 'Pending Admin Review';
        case 'admin_reviewed':
            return 'Sent to HOD';
        case 'admin_rejected':
            return 'Rejected by Admin';
        case 'hod_approved':
            return 'Approved by HOD';
        case 'hod_rejected':
            return 'Rejected by HOD';
        case 'completed':
            return 'Completed';
        default:
            return ucfirst(str_replace('_', ' ', $status));
    }
}

// Log system action
function logAction($userId, $action, $details = null) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR']]);
    } catch(PDOException $e) {
        return false;
    }
}

// Get department name
function getDepartmentName($department) {
    switch($department) {
        case 'survey_geodesy':
            return 'Survey & Geodesy';
        case 'remote_sensing_gis':
            return 'Remote Sensing & GIS';
        default:
            return ucfirst(str_replace('_', ' ', $department));
    }
}

// Get role name
function getRoleName($role) {
    switch($role) {
        case 'hod':
            return 'Head of Department';
        default:
            return ucfirst($role);
    }
}

// Check if user can access application
function canAccessApplication($userId, $userRole, $applicationId) {
    try {
        $pdo = getConnection();
        
        // Admin and HOD can access all applications
        if (in_array($userRole, ['admin', 'hod'])) {
            return true;
        }
        
        // Students can only access their own applications
        if ($userRole === 'student') {
            $stmt = $pdo->prepare("SELECT student_id FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $app = $stmt->fetch();
            return $app && $app['student_id'] == $userId;
        }
        
        // Lecturers can access approved applications
        if ($userRole === 'lecturer') {
            $stmt = $pdo->prepare("SELECT status FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $app = $stmt->fetch();
            return $app && in_array($app['status'], ['hod_approved', 'completed']);
        }
        
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Generate pagination
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $currentPage) ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">Next</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}
?>