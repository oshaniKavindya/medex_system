<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Medical Excuse Management System</title>
    
    <!-- jQuery (must be loaded first) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/medex_system/assets/css/style.css" rel="stylesheet">
    
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/medex_system/">
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
                                <a class="nav-link" href="/medex_system/student/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/student/submit_application.php">
                                    <i class="fas fa-plus-circle me-1"></i>Submit Application
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/student/view_applications.php">
                                    <i class="fas fa-list me-1"></i>My Applications
                                </a>
                            </li>
                        <?php elseif (hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/admin/manage_applications.php">
                                    <i class="fas fa-clipboard-list me-1"></i>Applications
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/admin/manage_users.php">
                                    <i class="fas fa-users me-1"></i>Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/admin/manage_courses.php">
                                    <i class="fas fa-book me-1"></i>Courses
                                </a>
                            </li>
                        <?php elseif (hasRole('hod')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/hod/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/hod/review_applications.php">
                                    <i class="fas fa-check-circle me-1"></i>Review Applications
                                </a>
                            </li>
                        <?php elseif (hasRole('lecturer')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/lecturer/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/medex_system/lecturer/view_approved.php">
                                    <i class="fas fa-eye me-1"></i>Approved Applications
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" title="Notifications">
                                <i class="fas fa-bell"></i>
                                <?php
                                // Get notifications directly in header
                                try {
                                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    
                                    // Get recent notifications
                                    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                                    $stmt->execute([$currentUser['id']]);
                                    $headerNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Get unread count
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                                    $stmt->execute([$currentUser['id']]);
                                    $headerUnreadCount = $stmt->fetchColumn();
                                    
                                } catch (Exception $e) {
                                    $headerNotifications = [];
                                    $headerUnreadCount = 0;
                                }
                                
                                if ($headerUnreadCount > 0):
                                ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $headerUnreadCount; ?>
                                        <span class="visually-hidden">unread notifications</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="min-width: 350px; max-height: 400px; overflow-y: auto;">
                                <li>
                                    <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                        <span>Notifications</span>
                                        <?php if ($headerUnreadCount > 0): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()" title="Mark all as read">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                        <?php endif; ?>
                                    </h6>
                                </li>
                                
                                <?php if (empty($headerNotifications)): ?>
                                    <li><span class="dropdown-item text-muted text-center py-3">No notifications</span></li>
                                <?php else: ?>
                                    <?php foreach ($headerNotifications as $notification): ?>
                                        <li>
                                            <div class="dropdown-item notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>" 
                                                 data-notification-id="<?php echo $notification['id']; ?>"
                                                 style="cursor: pointer; border-left: <?php echo $notification['is_read'] == 0 ? '4px solid #0d6efd' : '4px solid transparent'; ?>;">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-1">
                                                            <?php 
                                                            $iconClass = 'fas fa-info-circle text-primary';
                                                            switch($notification['type']) {
                                                                case 'success':
                                                                    $iconClass = 'fas fa-check-circle text-success';
                                                                    break;
                                                                case 'warning':
                                                                    $iconClass = 'fas fa-exclamation-triangle text-warning';
                                                                    break;
                                                                case 'error':
                                                                    $iconClass = 'fas fa-times-circle text-danger';
                                                                    break;
                                                            }
                                                            ?>
                                                            <i class="<?php echo $iconClass; ?> me-2"></i>
                                                            <small class="text-muted">
                                                                <?php 
                                                                $time = time() - strtotime($notification['created_at']);
                                                                if ($time < 60) echo 'just now';
                                                                elseif ($time < 3600) echo floor($time / 60) . 'm ago';
                                                                elseif ($time < 86400) echo floor($time / 3600) . 'h ago';
                                                                elseif ($time < 2592000) echo floor($time / 86400) . 'd ago';
                                                                else echo date('M j', strtotime($notification['created_at']));
                                                                ?>
                                                            </small>
                                                            <?php if ($notification['is_read'] == 0): ?>
                                                                <span class="badge bg-primary ms-2" style="font-size: 0.65em;">New</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="small">
                                                            <?php echo htmlspecialchars(substr($notification['message'], 0, 80)) . (strlen($notification['message']) > 80 ? '...' : ''); ?>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex">
                                                        <?php if ($notification['is_read'] == 0): ?>
                                                            <button class="btn btn-sm btn-outline-secondary" 
                                                                    onclick="markAsRead(<?php echo $notification['id']; ?>)"
                                                                    title="Mark as read">
                                                                <i class="fas fa-check" style="font-size: 0.7em;"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($headerNotifications)): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-center fw-bold text-primary" href="/medex_system/notifications.php">
                                            <i class="fas fa-eye me-1"></i>View All Notifications
                                        </a>
                                    </li>
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
                                <li><a class="dropdown-item" href="/medex_system/profile.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                                <li><a class="dropdown-item" href="/medex_system/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/medex_system/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/medex_system/auth/register.php">
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

    <script>
    $(document).ready(function() {
        // Handle notification clicks in dropdown
        $('.notification-item').click(function(e) {
            e.preventDefault();
            const notificationId = $(this).data('notification-id');
            
            if (notificationId && $(this).hasClass('unread')) {
                // Mark as read
                $.post('/medex_system/ajax/mark_notification_read.php', {
                    notification_id: notificationId
                }, function(response) {
                    if (response.success) {
                        $(`[data-notification-id="${notificationId}"]`).removeClass('unread');
                        updateNotificationBadge();
                    }
                });
            }
        });
        
        // Update notification badge count
        function updateNotificationBadge() {
            const unreadCount = $('.notification-item.unread').length;
            const $badge = $('.position-absolute.badge');
            
            if (unreadCount > 0) {
                $badge.text(unreadCount).show();
            } else {
                $badge.hide();
            }
        }
    });
    
    // Mark single notification as read
    function markAsRead(notificationId) {
        event.stopPropagation();
        
        $.post('/medex_system/ajax/mark_notification_read.php', {
            notification_id: notificationId
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        }).fail(function() {
            alert('Failed to mark notification as read');
        });
    }
    
    // Mark all notifications as read
    function markAllAsRead() {
        event.stopPropagation();
        
        if (!confirm('Mark all notifications as read?')) {
            return;
        }
        
        $.post('/medex_system/ajax/mark_all_notifications_read.php', {}, function(response) {
            if (response.success) {
                location.reload();
            }
        }).fail(function() {
            alert('Failed to mark all notifications as read');
        });
    }
    </script>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container"><?php?>