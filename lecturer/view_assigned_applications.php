<?php
$pageTitle = 'My Assigned Applications';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('lecturer');

$user = getCurrentUser();

// Pagination setup
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $pdo = getConnection();
    
    // Get total count of assigned applications
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM lecturer_notifications ln
        JOIN applications a ON ln.application_id = a.id
        WHERE ln.lecturer_id = ?
    ");
    $stmt->execute([$user['id']]);
    $total_applications = $stmt->fetchColumn();
    $total_pages = ceil($total_applications / $limit);
    
    // Get assigned applications with pagination
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code, u.full_name as student_name, 
               u.year as student_year, u.department as student_department,
               ln.notified_at, ln.is_acknowledged, ln.acknowledged_at,
               admin.full_name as assigned_by_name
        FROM lecturer_notifications ln
        JOIN applications a ON ln.application_id = a.id
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON a.student_id = u.id
        JOIN users admin ON ln.notified_by = admin.id
        WHERE ln.lecturer_id = ?
        ORDER BY 
            CASE WHEN ln.is_acknowledged = 0 THEN 0 ELSE 1 END,
            ln.notified_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user['id'], $limit, $offset]);
    $applications = $stmt->fetchAll();
    
    // Get statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN ln.is_acknowledged = 0 THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN ln.is_acknowledged = 1 THEN 1 ELSE 0 END) as acknowledged
        FROM lecturer_notifications ln
        WHERE ln.lecturer_id = ?
    ");
    $stmt->execute([$user['id']]);
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Database error in lecturer view_assigned_applications: " . $e->getMessage());
    $applications = [];
    $total_applications = 0;
    $total_pages = 0;
    $stats = ['total' => 0, 'pending' => 0, 'acknowledged' => 0];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>My Assigned Applications</h2>
        <p class="text-muted mb-0">Applications assigned to you by the medical officer</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Dashboard
        </a>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?php echo $stats['total']; ?></h3>
                <p class="mb-0">Total Assigned</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?php echo $stats['pending']; ?></h3>
                <p class="mb-0">Pending Review</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?php echo $stats['acknowledged']; ?></h3>
                <p class="mb-0">Acknowledged</p>
            </div>
        </div>
    </div>
</div>

<!-- Applications Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Assigned Applications
        </h5>
        <div class="text-muted">
            Showing <?php echo min($offset + 1, $total_applications); ?>-<?php echo min($offset + $limit, $total_applications); ?> 
            of <?php echo $total_applications; ?> applications
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($applications)): ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-check fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Applications Assigned</h4>
                <p class="text-muted">You haven't been assigned any applications yet.</p>
                <p class="text-muted">Applications assigned by the medical officer will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Application #</th>
                            <th>Student Details</th>
                            <th>Course</th>
                            <th>Application Details</th>
                            <th>Assigned By</th>
                            <th>Assignment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr class="<?php echo !$app['is_acknowledged'] ? 'table-warning' : ''; ?>">
                                <td>
                                    <span class="fw-bold text-primary">#<?php echo $app['id']; ?></span>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($app['student_name']); ?></div>
                                        <small class="text-muted">
                                            <?php echo getDepartmentName($app['student_department']); ?> - Year <?php echo $app['student_year']; ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?php echo htmlspecialchars($app['course_code']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['course_name']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-info mb-1">
                                            <?php echo ucfirst(str_replace('_', ' ', $app['application_type'])); ?>
                                        </span>
                                        <div class="small">
                                            <strong>Date:</strong> <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                            <?php if ($app['application_time']): ?>
                                                <br><strong>Time:</strong> <?php echo date('g:i A', strtotime($app['application_time'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?php echo htmlspecialchars($app['assigned_by_name']); ?></div>
                                        <small class="text-muted">Medical Officer</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div><?php echo date('M j, Y', strtotime($app['notified_at'])); ?></div>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($app['notified_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($app['is_acknowledged']): ?>
                                        <div>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Acknowledged
                                            </span>
                                            <div class="small text-muted mt-1">
                                                <?php echo date('M j, Y g:i A', strtotime($app['acknowledged_at'])); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Pending Review
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <a href="view_application.php?id=<?php echo $app['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm mb-1">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                        <?php if (!$app['is_acknowledged']): ?>
                                            <a href="acknowledge_application.php?id=<?php echo $app['id']; ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>Acknowledge
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Applications pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>">Previous</a>
                        </li>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif;
                        endif;
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor;
                        
                        if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>
