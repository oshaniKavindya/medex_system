<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$action = sanitize($_POST['action'] ?? '');

try {
    $pdo = getConnection();
    
    if ($action === 'login') {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error_message'] = 'Please fill in all fields.';
            header('Location: login.php');
            exit();
        }
        
        // Check user credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['year'] = $user['year'];
            
            // Log the login
            logAction($user['id'], 'User Login', 'Successful login from IP: ' . $_SERVER['REMOTE_ADDR']);
            
            $_SESSION['success_message'] = 'Welcome back, ' . $user['full_name'] . '!';
            
            // Redirect based on role
            switch ($user['role']) {
                case 'student':
                    header('Location: ../student/dashboard.php');
                    break;
                case 'admin':
                    header('Location: ../admin/dashboard.php');
                    break;
                case 'hod':
                    header('Location: ../hod/dashboard.php');
                    break;
                case 'lecturer':
                    header('Location: ../lecturer/dashboard.php');
                    break;
                default:
                    header('Location: ../index.php');
            }
            exit();
        } else {
            // Login failed
            logAction(null, 'Failed Login Attempt', 'Username: ' . $username . ', IP: ' . $_SERVER['REMOTE_ADDR']);
            $_SESSION['error_message'] = 'Invalid username or password.';
            header('Location: login.php');
            exit();
        }
        
    } elseif ($action === 'register') {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $role = sanitize($_POST['role'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $year = ($role === 'student') ? (int)($_POST['year'] ?? 0) : null;
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);
        
        // Validation
        $errors = [];
        
        if (empty($full_name)) $errors[] = 'Full name is required.';
        if (empty($username)) $errors[] = 'Username is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        if (empty($role)) $errors[] = 'Role is required.';
        if (empty($department)) $errors[] = 'Department is required.';
        if ($role === 'student' && empty($year)) $errors[] = 'Academic year is required for students.';
        if (empty($password)) $errors[] = 'Password is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';
        if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
        if (!$terms) $errors[] = 'You must agree to the terms and conditions.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = 'Username can only contain letters, numbers, and underscores.';
        if (!in_array($role, ['student', 'lecturer'])) $errors[] = 'Invalid role selected.';
        if (!in_array($department, ['survey_geodesy', 'remote_sensing_gis'])) $errors[] = 'Invalid department selected.';
        
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode('<br>', $errors);
            header('Location: register.php');
            exit();
        }
        
        // Check for existing username or email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = 'Username or email already exists. Please choose different ones.';
            header('Location: register.php');
            exit();
        }
        
        // Create new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, email, full_name, role, department, year, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        if ($stmt->execute([$username, $hashed_password, $email, $full_name, $role, $department, $year])) {
            $user_id = $pdo->lastInsertId();
            
            // Log the registration
            logAction($user_id, 'User Registration', 'New user registered: ' . $username);
            
            // Add welcome notification
            addNotification($user_id, 'Welcome to the Medical Excuse Management System! Your account has been created successfully.', 'success');
            
            $_SESSION['success_message'] = 'Account created successfully! Please login with your credentials.';
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['error_message'] = 'Registration failed. Please try again.';
            header('Location: register.php');
            exit();
        }
    } else {
        header('Location: login.php');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Database error in auth process: " . $e->getMessage());
    $_SESSION['error_message'] = 'A system error occurred. Please try again later.';
    
    if ($action === 'login') {
        header('Location: login.php');
    } else {
        header('Location: register.php');
    }
    exit();
} catch (Exception $e) {
    error_log("General error in auth process: " . $e->getMessage());
    $_SESSION['error_message'] = 'An unexpected error occurred. Please try again.';
    
    if ($action === 'login') {
        header('Location: login.php');
    } else {
        header('Location: register.php');
    }
    exit();
}
?>