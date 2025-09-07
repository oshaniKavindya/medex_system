<?php
$pageTitle = 'Student Dashboard';
require_once '../includes/header.php';

requireRole('student');

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Get application statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_applications,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'admin_reviewed' THEN 1 ELSE 0 END) as under_review,
            SUM(CASE WHEN status = 'hod_approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status IN ('admin_rejected', 'hod_rejected') THEN 1 ELSE 0 END) as rejected
        FROM applications 
        WHERE student_id = ?
    ");
    $stmt->execute([$user['id']]);
    $stats = $stmt->fetch();
    
    // Get recent applications
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code 
        FROM applications a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.student_id = ? 
        ORDER BY a.submitted_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $recent_applications = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in student dashboard: " . $e->getMessage());
    $stats = ['total_applications' => 0, 'pending' => 0, 'under_review' => 0, 'approved' => 0, 'rejected' => 0];
    $recent_applications = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
        <p class="text-muted mb-0">
            <?php echo getDepartmentName($user['department']); ?> - Year <?php echo $user['year']; ?>
        </p>
    </div>
    <div>
        <a href="submit_application.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>
            Submit New Application
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
            <div class="stats-number"><?php echo $stats['pending'] + $stats['under_review']; ?></div>
            <div class="stats-label">Under Review</div>
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
            <div class="stats-number"><?php echo $stats['rejected']; ?></div>
            <div class="stats-label">Rejected</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Applications -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Recent Applications
                </h5>
                <a href="view_applications.php" class="btn btn-outline-primary btn-sm">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_applications)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No applications submitted yet.</p>
                        <a href="submit_application.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Submit Your First Application
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_applications as $app): ?>
                                    <tr>
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
                                            <?php echo date('M j, Y', strtotime($app['submitted_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($app['status']); ?>">
                                                <?php echo formatStatus($app['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_applications.php?id=<?php echo $app['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
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
    
    <!-- Quick Actions & Info -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="submit_application.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>
                        Submit New Application
                    </a>
                    <a href="view_applications.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>
                        View All Applications
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Important Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Important Information
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-calendar-alt me-1"></i> Submission Deadline</h6>
                    <small>Medical excuses must be submitted within <strong>14 days</strong> of absence.</small>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="fas fa-file-medical me-1"></i> Required Documents</h6>
                    <small>
                        • Letter with subject/date/reason<br>
                        • Medical Application form<br>
                        • Government medical certificate
                    </small>
                </div>
                
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle me-1"></i> Processing Time</h6>
                    <small>Applications are typically processed within <strong>3-5 business days</strong>.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>