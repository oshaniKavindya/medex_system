<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/medical_excuse_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medical_excuse_system/includes/functions.php';

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Medical Excuse Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/medical_excuse_system/assets/css/style.css" rel="stylesheet">
    
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/medical_excuse_system/">
                <i class="fas fa-stethoscope me-2"></i>
                Medical Excuse System
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                    <ul class="navbar-nav me-auto">
                        <?php if (hasRole('student')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/student/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/student/submit_application.php">
                                    <i class="fas fa-plus-circle me-1"></i>Submit Application
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/student/view_applications.php">
                                    <i class="fas fa-list me-1"></i>My Applications
                                </a>
                            </li>
                        <?php elseif (hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/admin/manage_applications.php">
                                    <i class="fas fa-clipboard-list me-1"></i>Applications
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/admin/manage_users.php">
                                    <i class="fas fa-users me-1"></i>Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/admin/manage_courses.php">
                                    <i class="fas fa-book me-1"></i>Courses
                                </a>
                            </li>
                        <?php elseif (hasRole('hod')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/hod/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/hod/review_applications.php">
                                    <i class="fas fa-check-circle me-1"></i>Review Applications
                                </a>
                            </li>
                        <?php elseif (hasRole('lecturer')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/lecturer/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medical_excuse_system/lecturer/view_approved.php">
                                    <i class="fas fa-eye me-1"></i>Approved Applications
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php
                                $notifications = getUserNotifications($currentUser['id'], 5);
                                $unreadCount = array_reduce($notifications, function($count, $notif) {
                                    return $count + ($notif['is_read'] == 0 ? 1 : 0);
                                }, 0);
                                if ($unreadCount > 0):
                                ?>
                                    <span class="notification-badge"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <?php if (empty($notifications)): ?>
                                    <li><span class="dropdown-item text-muted">No notifications</span></li>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <li>
                                            <a class="dropdown-item notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>" 
                                               href="#" 
                                               data-notification-id="<?php echo $notification['id']; ?>">
                                                <small class="text-muted"><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></small><br>
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </li>
                        
                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($currentUser['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">
                                    <?php echo getRoleName($currentUser['role']); ?><br>
                                    <small class="text-muted"><?php echo getDepartmentName($currentUser['department']); ?></small>
                                </h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/medical_excuse_system/profile.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                                <li><a class="dropdown-item" href="/medical_excuse_system/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/medical_excuse_system/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/medical_excuse_system/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Alert Container -->
    <div id="alertContainer" class="container mt-3">
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success alert-dismissible fade show">
                    ' . htmlspecialchars($_SESSION['success_message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['success_message']);
        }
        
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show">
                    ' . htmlspecialchars($_SESSION['error_message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['error_message']);
        }
        
        if (isset($_SESSION['warning_message'])) {
            echo '<div class="alert alert-warning alert-dismissible fade show">
                    ' . htmlspecialchars($_SESSION['warning_message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['warning_message']);
        }
        ?>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container"><?php // Main content will be inserted here by individual pages ?>