<?php
$pageTitle = 'HOD Dashboard';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('hod');

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Get application statistics for this HOD's department
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_applications,
            SUM(CASE WHEN a.status = 'admin_reviewed' THEN 1 ELSE 0 END) as pending_approval,
            SUM(CASE WHEN a.status = 'hod_approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN a.status = 'hod_rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN DATE(a.submitted_at) = CURDATE() THEN 1 ELSE 0 END) as today_submissions
        FROM applications a
        JOIN users u ON a.student_id = u.id
        WHERE u.department = ?
    ");
    $stmt->execute([$user['department']]);
    $stats = $stmt->fetch();
    
    // Get applications pending HOD approval
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code, u.full_name as student_name, u.year as student_year,
               admin.full_name as admin_name
        FROM applications a 
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON a.student_id = u.id
        LEFT JOIN users admin ON a.admin_reviewed_by = admin.id
        WHERE a.status = 'admin_reviewed' AND u.department = ?
        ORDER BY a.submitted_at ASC 
        LIMIT 8
    ");
    $stmt->execute([$user['department']]);
    $pending_applications = $stmt->fetchAll();
    
    // Get recent decisions made by this HOD
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code, u.full_name as student_name, u.year as student_year
        FROM applications a 
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON a.student_id = u.id
        WHERE a.hod_reviewed_by = ? AND a.status IN ('hod_approved', 'hod_rejected')
        ORDER BY a.approved_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $recent_decisions = $stmt->fetchAll();
    
    // Get monthly statistics
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(a.submitted_at) as month,
            YEAR(a.submitted_at) as year,
            COUNT(*) as count,
            SUM(CASE WHEN a.status = 'hod_approved' THEN 1 ELSE 0 END) as approved_count
        FROM applications a
        JOIN users u ON a.student_id = u.id
        WHERE u.department = ? AND a.submitted_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY YEAR(a.submitted_at), MONTH(a.submitted_at)
        ORDER BY year DESC, month DESC
        LIMIT 6
    ");
    $stmt->execute([$user['department']]);
    $monthly_stats = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in HOD dashboard: " . $e->getMessage());
    $stats = ['total_applications' => 0, 'pending_approval' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0, 'today_submissions' => 0];
    $pending_applications = [];
    $recent_decisions = [];
    $monthly_stats = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>HOD Dashboard</h2>
        <p class="text-muted mb-0">
            <?php echo getDepartmentName($user['department']); ?> - Head of Department
        </p>
    </div>
    <div>
        <a href="review_applications.php" class="btn btn-primary">
            <i class="fas fa-clipboard-check me-2"></i>
            Review Applications (<?php echo $stats['pending_approval']; ?>)
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-5">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stats-card stats-primary">
            <div class="stats-number"><?php echo $stats['total_applications']; ?></div>
            <div class="stats-label">Total Applications</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stats-card stats-warning">
            <div class="stats-number"><?php echo $stats['pending_approval']; ?></div>
            <div class="stats-label">Pending Approval</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stats-card stats-success">
            <div class="stats-number"><?php echo $stats['approved']; ?></div>
            <div class="stats-label">Approved</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stats-card stats-info">
            <div class="stats-number"><?php echo $stats['today_submissions']; ?></div>
            <div class="stats-label">Today's Submissions</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending Applications -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Applications Pending Your Approval
                </h5>
                <a href="review_applications.php" class="btn btn-outline-primary btn-sm">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($pending_applications)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">All applications have been reviewed!</p>
                        <small class="text-muted">New applications will appear here when reviewed by the medical officer.</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Reviewed By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_applications as $app): ?>
                                    <tr>
                                        <td>#<?php echo $app['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($app['student_name']); ?></strong><br>
                                            <small class="text-muted">Year <?php echo $app['student_year']; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($app['course_code']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($app['course_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <?php echo ucfirst($app['application_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($app['admin_name'] ?: 'Medical Officer'); ?><br>
                                                <?php echo date('M j, g:i A', strtotime($app['reviewed_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="review_applications.php?id=<?php echo $app['id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Recent Decisions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Recent Decisions
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_decisions)): ?>
                    <p class="text-muted">No recent decisions.</p>
                <?php else: ?>
                    <?php foreach ($recent_decisions as $decision): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong><?php echo htmlspecialchars($decision['student_name']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($decision['course_code']); ?> - 
                                    <?php echo ucfirst($decision['application_type']); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge <?php echo getStatusBadgeClass($decision['status']); ?>">
                                    <?php echo $decision['status'] === 'hod_approved' ? 'Approved' : 'Rejected'; ?>
                                </span><br>
                                <small class="text-muted">
                                    <?php echo date('M j', strtotime($decision['approved_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Monthly Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Monthly Statistics
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($monthly_stats)): ?>
                    <p class="text-muted">No data available yet.</p>
                <?php else: ?>
                    <?php foreach ($monthly_stats as $month_data): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong>
                                    <?php echo date('M Y', mktime(0, 0, 0, $month_data['month'], 1, $month_data['year'])); ?>
                                </strong><br>
                                <small class="text-muted">
                                    <?php echo $month_data['approved_count']; ?> approved of <?php echo $month_data['count']; ?> total
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge badge-primary"><?php echo $month_data['count']; ?></span>
                            </div>
                        </div>
                        <div class="progress mb-3" style="height: 4px;">
                            <div class="progress-bar bg-success" 
                                 style="width: <?php echo $month_data['count'] > 0 ? ($month_data['approved_count'] / $month_data['count']) * 100 : 0; ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="review_applications.php" class="btn btn-primary">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Review Applications (<?php echo $stats['pending_approval']; ?>)
                    </a>
                    <a href="review_applications.php?status=hod_approved" class="btn btn-outline-success">
                        <i class="fas fa-check-circle me-2"></i>
                        View Approved (<?php echo $stats['approved']; ?>)
                    </a>
                    <a href="review_applications.php?status=hod_rejected" class="btn btn-outline-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        View Rejected (<?php echo $stats['rejected']; ?>)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Overview -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    Department Overview - <?php echo getDepartmentName($user['department']); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Application Processing Status</h6>
                        <div class="progress-group mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted">Approved</span>
                                <span class="text-success"><?php echo $stats['approved']; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" 
                                     style="width: <?php echo $stats['total_applications'] > 0 ? ($stats['approved'] / $stats['total_applications']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="progress-group mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted">Pending</span>
                                <span class="text-warning"><?php echo $stats['pending_approval']; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" 
                                     style="width: <?php echo $stats['total_applications'] > 0 ? ($stats['pending_approval'] / $stats['total_applications']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="progress-group mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted">Rejected</span>
                                <span class="text-danger"><?php echo $stats['rejected']; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger" 
                                     style="width: <?php echo $stats['total_applications'] > 0 ? ($stats['rejected'] / $stats['total_applications']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Department Health</h6>
                        <?php 
                        $approval_rate = $stats['total_applications'] > 0 ? ($stats['approved'] / $stats['total_applications']) * 100 : 0;
                        ?>
                        
                        <div class="alert alert-<?php echo $stats['pending_approval'] > 5 ? 'warning' : 'info'; ?>">
                            <i class="fas fa-<?php echo $stats['pending_approval'] > 5 ? 'exclamation-triangle' : 'info-circle'; ?> me-2"></i>
                            <?php if ($stats['pending_approval'] > 5): ?>
                                High pending queue detected. Please review applications promptly.
                            <?php else: ?>
                                Department processing is up to date.
                            <?php endif; ?>
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-percentage me-2"></i>
                            Approval Rate: <strong><?php echo number_format($approval_rate, 1); ?>%</strong>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-calendar me-2"></i>
                            Today: <?php echo $stats['today_submissions']; ?> new submissions for your department.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>