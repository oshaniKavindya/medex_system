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
    
    $action = sanitize($_POST['action'] ?? '');
    
    if ($action === 'add') {
        // Add new course
        $course_code = sanitize($_POST['course_code'] ?? '');
        $course_name = sanitize($_POST['course_name'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $submission_end_date = sanitize($_POST['submission_end_date'] ?? '');
        $confirm = isset($_POST['confirm']);
        
        // Validation
        $errors = [];
        
        if (empty($course_code)) $errors[] = 'Course code is required.';
        if (empty($course_name)) $errors[] = 'Course name is required.';
        if (empty($department)) $errors[] = 'Department is required.';
        if (empty($year) || $year < 1 || $year > 4) $errors[] = 'Valid academic year is required.';
        if (!$confirm) $errors[] = 'You must confirm the course information is accurate.';
        
        // Validate submission end date if provided
        if (!empty($submission_end_date)) {
            $date = DateTime::createFromFormat('Y-m-d', $submission_end_date);
            if (!$date || $date->format('Y-m-d') !== $submission_end_date) {
                $errors[] = 'Invalid submission end date format.';
            } elseif ($date < new DateTime()) {
                $errors[] = 'Submission end date cannot be in the past.';
            }
        }
        
        // Validate course code format
        if (!empty($course_code) && !preg_match('/^[A-Z]{2,4}[0-9]{3}$/', $course_code)) {
            $errors[] = 'Course code must be 2-4 letters followed by 3 numbers (e.g., SG101).';
        }
        
        // Validate department
        if (!in_array($department, ['survey_geodesy', 'remote_sensing_gis'])) {
            $errors[] = 'Invalid department selected.';
        }
        
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode('<br>', $errors);
            header('Location: add_course.php');
            exit();
        }
        
        // Check if course code already exists
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE course_code = ?");
        $stmt->execute([$course_code]);
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = 'Course code already exists. Please choose a different code.';
            header('Location: add_course.php');
            exit();
        }
        
        // Insert new course
        $stmt = $pdo->prepare("
            INSERT INTO courses (course_code, course_name, department, year, description, submission_end_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $submission_end_date_value = !empty($submission_end_date) ? $submission_end_date : null;
        
        if ($stmt->execute([$course_code, $course_name, $department, $year, $description, $submission_end_date_value, $user['id']])) {
            $course_id = $pdo->lastInsertId();
            
            // Log the action
            logAction($user['id'], 'Course Added', "Course ID: $course_id, Code: $course_code");
            
            $_SESSION['success_message'] = "Course '{$course_code} - {$course_name}' has been added successfully.";
            header('Location: manage_courses.php');
            exit();
        } else {
            $_SESSION['error_message'] = 'Failed to add course. Please try again.';
            header('Location: add_course.php');
            exit();
        }
        
    } elseif ($action === 'edit') {
        // Edit existing course
        $course_id = (int)($_POST['course_id'] ?? 0);
        $course_code = sanitize($_POST['course_code'] ?? '');
        $course_name = sanitize($_POST['course_name'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $submission_end_date = sanitize($_POST['submission_end_date'] ?? '');
        
        // Validation
        if (empty($course_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
            exit();
        }
        
        if (empty($course_code) || empty($course_name) || empty($department) || empty($year)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit();
        }
        
        // Validate submission end date if provided
        if (!empty($submission_end_date)) {
            $date = DateTime::createFromFormat('Y-m-d', $submission_end_date);
            if (!$date || $date->format('Y-m-d') !== $submission_end_date) {
                echo json_encode(['success' => false, 'message' => 'Invalid submission end date format']);
                exit();
            } elseif ($date < new DateTime()) {
                echo json_encode(['success' => false, 'message' => 'Submission end date cannot be in the past']);
                exit();
            }
        }
        
        // Validate course code format
        if (!preg_match('/^[A-Z]{2,4}[0-9]{3}$/', $course_code)) {
            echo json_encode(['success' => false, 'message' => 'Invalid course code format']);
            exit();
        }
        
        // Check if course exists
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $existingCourse = $stmt->fetch();
        
        if (!$existingCourse) {
            echo json_encode(['success' => false, 'message' => 'Course not found']);
            exit();
        }
        
        // Check if course code already exists for different course
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ?");
        $stmt->execute([$course_code, $course_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Course code already exists']);
            exit();
        }
        
        // Update course
        $stmt = $pdo->prepare("
            UPDATE courses 
            SET course_code = ?, course_name = ?, department = ?, year = ?, description = ?, submission_end_date = ?
            WHERE id = ?
        ");
        
        $submission_end_date_value = !empty($submission_end_date) ? $submission_end_date : null;
        
        if ($stmt->execute([$course_code, $course_name, $department, $year, $description, $submission_end_date_value, $course_id])) {
            // Log the action
            logAction($user['id'], 'Course Updated', "Course ID: $course_id, Code: $course_code");
            
            echo json_encode(['success' => true, 'message' => 'Course updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update course']);
        }
        exit();
        
    } elseif ($action === 'delete') {
        // Delete course
        $course_id = (int)($_POST['course_id'] ?? 0);
        
        if (empty($course_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
            exit();
        }
        
        // Check if course exists
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();
        
        if (!$course) {
            echo json_encode(['success' => false, 'message' => 'Course not found']);
            exit();
        }
        
        // Check if course is being used in applications
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $applicationCount = $stmt->fetchColumn();
        
        if ($applicationCount > 0) {
            echo json_encode([
                'success' => false, 
                'message' => "Cannot delete course. It is referenced in {$applicationCount} application(s)."
            ]);
            exit();
        }
        
        // Delete the course
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        
        if ($stmt->execute([$course_id])) {
            // Log the action
            logAction($user['id'], 'Course Deleted', "Course: {$course['course_code']} - {$course['course_name']}");
            
            echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete course']);
        }
        exit();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Database error in process course: " . $e->getMessage());
    
    if (in_array($action, ['edit', 'delete'])) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } else {
        $_SESSION['error_message'] = 'A database error occurred. Please try again.';
        header('Location: ' . ($action === 'add' ? 'add_course.php' : 'manage_courses.php'));
    }
    exit();
} catch (Exception $e) {
    error_log("General error in process course: " . $e->getMessage());
    
    if (in_array($action, ['edit', 'delete'])) {
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    } else {
        $_SESSION['error_message'] = 'An unexpected error occurred. Please try again.';
        header('Location: ' . ($action === 'add' ? 'add_course.php' : 'manage_courses.php'));
    }
    exit();
}
?>