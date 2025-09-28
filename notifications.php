<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$currentUser = [
    'id' => $_SESSION['user_id'],
    'role' => $_SESSION['role'] ?? 'student',
    'username' => $_SESSION['username'] ?? 'User'
];

$pageTitle = 'Notifications';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        switch ($_POST['action']) {
            case 'mark_read':
                $notificationId = (int)($_POST['notification_id'] ?? 0);
                if ($notificationId > 0) {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                    $success = $stmt->execute([$notificationId, $currentUser['id']]);
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read'
                    ];
                }
                break;
                
            case 'mark_all_read':
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
                $success = $stmt->execute([$currentUser['id']]);
                $response = [
                    'success' => $success,
                    'message' => $success ? 'All notifications marked as read' : 'Failed to mark all notifications as read'
                ];
                break;
                
            case 'delete_notification':
                $notificationId = (int)($_POST['notification_id'] ?? 0);
                if ($notificationId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
                    $success = $stmt->execute([$notificationId, $currentUser['id']]);
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Notification deleted' : 'Failed to delete notification'
                    ];
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Get notifications - FORCE WORKING VERSION
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$currentUser['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$totalCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$currentUser['id']]);
$unreadCount = $stmt->fetchColumn();

// Include functions.php for timeAgo function
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="fas fa-bell me-2"></i>Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-primary ms-2"><?php echo $unreadCount; ?> unread</span>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="btn-group">
                        <?php if ($unreadCount > 0): ?>
                            <button class="btn btn-outline-primary btn-sm" id="markAllRead" title="Mark all notifications as read">
                                <i class="fas fa-check-double me-1"></i>Mark All Read
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()" title="Refresh notifications">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-bell-slash fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Notifications</h5>
                            <p class="text-muted">You don't have any notifications yet.</p>
                            

                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush" id="notificationsList">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>" 
                                     data-notification-id="<?php echo $notification['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="notification-icon me-3">
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
                                                        case 'info':
                                                        default:
                                                            $iconClass = 'fas fa-info-circle text-primary';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="<?php echo $iconClass; ?> fa-lg"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?php echo htmlspecialchars($notification['message']); ?>
                                                        <?php if ($notification['is_read'] == 0): ?>
                                                            <span class="badge bg-primary badge-sm ms-2">New</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo timeAgo($notification['created_at']); ?>
                                                        <?php if ($notification['application_id']): ?>
                                                            | <i class="fas fa-file-medical me-1"></i>Application #<?php echo $notification['application_id']; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown" 
                                                    title="Notification actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if ($notification['is_read'] == 0): ?>
                                                    <li>
                                                        <a class="dropdown-item mark-read-btn" href="#" data-id="<?php echo $notification['id']; ?>">
                                                            <i class="fas fa-check me-2"></i>Mark as Read
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                              
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger delete-btn" href="#" data-id="<?php echo $notification['id']; ?>">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item {
    border: none;
    border-bottom: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f8f9ff;
    border-left: 4px solid #0d6efd;
}

.notification-icon {
    min-width: 40px;
    text-align: center;
}

.badge-sm {
    font-size: 0.7em;
}

.list-group-item:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .notification-item .btn-group {
        flex-direction: column;
    }
    
    .notification-item .d-flex {
        flex-direction: column;
        align-items: stretch;
    }
    
    .notification-item .dropdown {
        align-self: flex-end;
        margin-top: 10px;
    }
}
</style>

<script>
$(document).ready(function() {
    // Mark single notification as read
    $('.mark-read-btn').click(function(e) {
        e.preventDefault();
        const notificationId = $(this).data('id');
        
        $.post('notifications.php', {
            action: 'mark_read',
            notification_id: notificationId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to mark notification as read');
            }
        });
    });

    // Mark all notifications as read
    $('#markAllRead').click(function(e) {
        e.preventDefault();
        
        if (!confirm('Mark all notifications as read?')) {
            return;
        }
        
        $.post('notifications.php', {
            action: 'mark_all_read'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to mark all notifications as read');
            }
        });
    });

    // Delete notification
    $('.delete-btn').click(function(e) {
        e.preventDefault();
        const notificationId = $(this).data('id');
        
        if (!confirm('Delete this notification? This action cannot be undone.')) {
            return;
        }
        
        $.post('notifications.php', {
            action: 'delete_notification',
            notification_id: notificationId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to delete notification');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>