<?php
$pageTitle = 'Admin Dashboard';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('admin');

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Get application statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_applications,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'admin_reviewed' THEN 1 ELSE 0 END) as reviewed,
            SUM(CASE WHEN status = 'hod_approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status IN ('admin_rejected', 'hod_rejected') THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN DATE(submitted_at) = CURDATE() THEN 1 ELSE 0 END) as today_submissions
        FROM applications
    ");
    $stmt->execute();
    $stats = $stmt->fetch();
    
    // Get recent applications needing review
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code, u.full_name as student_name, u.department, u.year
        FROM applications a 
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON a.student_id = u.id
        WHERE a.status = 'pending'
        ORDER BY a.submitted_at ASC 
        LIMIT 8
    ");
    $stmt->execute();
    $pending_applications = $stmt->fetchAll();
    
    // Get user statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students,
            SUM(CASE WHEN role = 'lecturer' THEN 1 ELSE 0 END) as lecturers,
            SUM(CASE WHEN role = 'hod' THEN 1 ELSE 0 END) as hods
        FROM users 
        WHERE status = 'active'
    ");
    $stmt->execute();
    $user_stats = $stmt->fetch();
    
    // Get department-wise application stats
    $stmt = $pdo->prepare("
        SELECT 
            u.department,
            COUNT(*) as app_count,
            SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_count
        FROM applications a
        JOIN users u ON a.student_id = u.id
        GROUP BY u.department
    ");
    $stmt->execute();
    $dept_stats = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in admin dashboard: " . $e->getMessage());
    $stats = ['total_applications' => 0, 'pending' => 0, 'reviewed' => 0, 'approved' => 0, 'rejected' => 0, 'today_submissions' => 0];
    $pending_applications = [];
    $user_stats = ['total_users' => 0, 'students' => 0, 'lecturers' => 0, 'hods' => 0];
    $dept_stats = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Admin Dashboard</h2>
        <p class="text-muted mb-0">Medical Officer Control Panel</p>
    </div>
    <div class="d-flex gap-2">
        <a href="manage_applications.php" class="btn btn-primary">
            <i class="fas fa-clipboard-list me-2"></i>
            Review Applications
        </a>
        <a href="manage_courses.php" class="btn btn-outline-primary">
            <i class="fas fa-book me-2"></i>
            Manage Courses
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
            <div class="stats-number"><?php echo $stats['pending']; ?></div>
            <div class="stats-label">Pending Review</div>
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
                    Applications Pending Review
                </h5>
                <a href="manage_applications.php" class="btn btn-outline-primary btn-sm">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($pending_applications)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">All applications have been reviewed!</p>
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
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_applications as $app): ?>
                                    <tr>
                                        <td>#<?php echo $app['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($app['student_name']); ?></strong><br>
                                            <small class="text-muted">
                                                <?php echo getDepartmentName($app['department']); ?> - Year <?php echo $app['year']; ?>
                                            </small>
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
                                            <?php echo date('M j, g:i A', strtotime($app['submitted_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="manage_applications.php?id=<?php echo $app['id']; ?>" 
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
    
    <!-- Quick Stats & Actions -->
    <div class="col-lg-4">
        <!-- User Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>
                    User Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="text-primary">
                            <i class="fas fa-user-graduate fa-2x mb-2"></i>
                            <h4><?php echo $user_stats['students']; ?></h4>
                            <small class="text-muted">Students</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-success">
                            <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                            <h4><?php echo $user_stats['lecturers']; ?></h4>
                            <small class="text-muted">Lecturers</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-warning">
                            <i class="fas fa-user-tie fa-2x mb-2"></i>
                            <h4><?php echo $user_stats['hods']; ?></h4>
                            <small class="text-muted">HODs</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-info">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h4><?php echo $user_stats['total_users']; ?></h4>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Department Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    Department Statistics
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($dept_stats)): ?>
                    <p class="text-muted">No applications yet.</p>
                <?php else: ?>
                    <?php foreach ($dept_stats as $dept): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1"><?php echo getDepartmentName($dept['department']); ?></h6>
                                <small class="text-muted">
                                    <?php echo $dept['pending_count']; ?> pending of <?php echo $dept['app_count']; ?> total
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge badge-primary"><?php echo $dept['app_count']; ?></span>
                            </div>
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
                    <a href="manage_applications.php?status=pending" class="btn btn-warning">
                        <i class="fas fa-clock me-2"></i>
                        Review Pending (<?php echo $stats['pending']; ?>)
                    </a>
                    <a href="manage_users.php" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>
                        Manage Users
                    </a>
                    <a href="add_course.php" class="btn btn-outline-success">
                        <i class="fas fa-plus me-2"></i>
                        Add New Course
                    </a>
                    <a href="manage_applications.php" class="btn btn-outline-info">
                        <i class="fas fa-list me-2"></i>
                        All Applications
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity (Optional) -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    System Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Application Status Distribution</h6>
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
                                <span class="text-warning"><?php echo $stats['pending']; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" 
                                     style="width: <?php echo $stats['total_applications'] > 0 ? ($stats['pending'] / $stats['total_applications']) * 100 : 0; ?>%"></div>
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
                        <h6>System Health</h6>
                        <div class="alert alert-<?php echo $stats['pending'] > 10 ? 'warning' : 'success'; ?>">
                            <i class="fas fa-<?php echo $stats['pending'] > 10 ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
                            <?php if ($stats['pending'] > 10): ?>
                                High pending queue detected. Consider reviewing applications.
                            <?php else: ?>
                                System running smoothly. All applications are being processed timely.
                            <?php endif; ?>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Today: <?php echo $stats['today_submissions']; ?> new submissions received.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>