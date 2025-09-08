<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit();
}

$course_id = (int)$_GET['id'];

try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as created_by_name
        FROM courses c 
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit();
    }
    
    // Check if user has permission to view this course
    $user = getCurrentUser();
    $canView = false;
    
    if (hasRole('admin')) {
        $canView = true;
    } elseif (hasRole('student')) {
        // Students can only see courses from their department and year
        $canView = ($course['department'] === $user['department'] && $course['year'] == $user['year']);
    } elseif (hasRole('hod')) {
        // HOD can see courses from their department
        $canView = ($course['department'] === $user['department']);
    } elseif (hasRole('lecturer')) {
        // Lecturers can see courses from their department
        $canView = ($course['department'] === $user['department']);
    }
    
    if (!$canView) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'course' => $course
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get course details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in get course details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>