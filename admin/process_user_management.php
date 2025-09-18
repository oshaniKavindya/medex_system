<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if action is provided
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit();
}

$action = $_POST['action'];

try {
    $pdo = getConnection();
    
    switch ($action) {
        case 'add_user':
            addUser($pdo);
            break;
            
        case 'edit_user':
            editUser($pdo);
            break;
            
        case 'toggle_status':
            toggleUserStatus($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in process_user_management: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
}

function addUser($pdo) {
    // Validate required fields
    $required = ['full_name', 'username', 'email', 'role', 'department', 'password'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    $fullName = sanitize($_POST['full_name']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $department = sanitize($_POST['department']);
    $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        return;
    }
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role, department, year, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$username, $email, $password, $fullName, $role, $department, $year]);
    
    echo json_encode(['success' => true, 'message' => 'User added successfully']);
}

function editUser($pdo) {
    // Validate required fields
    if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    $required = ['full_name', 'username', 'email', 'role', 'department'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    $userId = (int)$_POST['user_id'];
    $fullName = sanitize($_POST['full_name']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $department = sanitize($_POST['department']);
    $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
    
    // Check if username or email already exists for other users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $userId]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        return;
    }
    
    // Update user
    $stmt = $pdo->prepare("
        UPDATE users 
        SET username = ?, email = ?, full_name = ?, role = ?, department = ?, year = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$username, $email, $fullName, $role, $department, $year, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
}

function toggleUserStatus($pdo) {
    if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    if (empty($_POST['status']) || !in_array($_POST['status'], ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $userId = (int)$_POST['user_id'];
    $status = $_POST['status'];
    
    // Don't allow deactivating yourself
    if ($userId == $_SESSION['user']['id'] && $status === 'inactive') {
        echo json_encode(['success' => false, 'message' => 'You cannot deactivate yourself']);
        return;
    }
    
    // Update user status
    $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $userId]);
    
    $message = $status === 'active' ? 'User activated successfully' : 'User deactivated successfully';
    echo json_encode(['success' => true, 'message' => $message]);
}
?>
