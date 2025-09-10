<?php
$pageTitle = 'Application Details';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('lecturer');

$user = getCurrentUser();
$application_id = intval($_GET['id'] ?? 0);

if (!$application_id) {
    header('Location: view_assigned_applications.php');
    exit;
}

try {
    $pdo = getConnection();
    
    // Check if this application is assigned to the current lecturer
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code, c.year as course_year,
               u.full_name as student_name, u.email as student_email, 
               u.year as student_year, u.department as student_department,
               ln.notified_at, ln.is_acknowledged, ln.acknowledged_at,
               admin.full_name as assigned_by_name,
               admin_reviewer.full_name as admin_reviewed_by_name,
               hod_reviewer.full_name as hod_reviewed_by_name
        FROM lecturer_notifications ln
        JOIN applications a ON ln.application_id = a.id
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON a.student_id = u.id
        JOIN users admin ON ln.notified_by = admin.id
        LEFT JOIN users admin_reviewer ON a.admin_reviewed_by = admin_reviewer.id
        LEFT JOIN users hod_reviewer ON a.hod_reviewed_by = hod_reviewer.id
        WHERE ln.lecturer_id = ? AND a.id = ?
    ");
    $stmt->execute([$user['id'], $application_id]);
    $application = $stmt->fetch();
    
    if (!$application) {
        header('Location: view_assigned_applications.php?error=not_found');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Database error in lecturer view_application: " . $e->getMessage());
    header('Location: view_assigned_applications.php?error=database');
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Application Details</h2>
        <p class="text-muted mb-0">Application #<?php echo $application['id']; ?></p>
    </div>
    <div>
        <a href="view_assigned_applications.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Applications
        </a>
        <?php if (!$application['is_acknowledged']): ?>
            <a href="acknowledge_application.php?id=<?php echo $application['id']; ?>" class="btn btn-success">
                <i class="fas fa-check me-2"></i>
                Acknowledge Application
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Status Alert -->
<?php if (!$application['is_acknowledged']): ?>
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Action Required:</strong> This application is pending your acknowledgment.
    </div>
<?php else: ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Acknowledged:</strong> You acknowledged this application on 
        <?php echo date('F j, Y \a\t g:i A', strtotime($application['acknowledged_at'])); ?>.
    </div>
<?php endif; ?>

<div class="row">
    <!-- Main Application Details -->
    <div class="col-lg-8">
        <!-- Student Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-graduate me-2"></i>
                    Student Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%" class="text-muted">Name:</th>
                                <td><?php echo htmlspecialchars($application['student_name']); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Email:</th>
                                <td><?php echo htmlspecialchars($application['student_email']); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Department:</th>
                                <td><?php echo getDepartmentName($application['student_department']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%" class="text-muted">Academic Year:</th>
                                <td>Year <?php echo $application['student_year']; ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Course:</th>
                                <td>
                                    <?php echo htmlspecialchars($application['course_code']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($application['course_name']); ?></small>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Application Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong class="text-muted">Application Type:</strong><br>
                        <span class="badge bg-info fs-6">
                            <?php echo ucfirst(str_replace('_', ' ', $application['application_type'])); ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong class="text-muted">Date:</strong><br>
                        <?php echo date('F j, Y', strtotime($application['application_date'])); ?>
                    </div>
                    <div class="col-md-4">
                        <strong class="text-muted">Time:</strong><br>
                        <?php echo $application['application_time'] ? date('g:i A', strtotime($application['application_time'])) : 'Not specified'; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong class="text-muted">Reason for Medical Excuse:</strong>
                    <div class="mt-2 p-3 bg-light rounded">
                        <?php echo nl2br(htmlspecialchars($application['reason'])); ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong class="text-muted">Certificate Type:</strong><br>
                    <span class="badge <?php echo $application['certificate_type'] === 'government' ? 'bg-success' : 'bg-primary'; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $application['certificate_type'])); ?> Certificate
                    </span>
                </div>
            </div>
        </div>

        <!-- Review History -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Review History
                </h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <!-- Submission -->
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Application Submitted</h6>
                            <p class="text-muted mb-0">
                                <?php echo date('F j, Y \a\t g:i A', strtotime($application['submitted_at'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Admin Review -->
                    <?php if ($application['admin_reviewed_by']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Admin Review</h6>
                                <p class="mb-1">Reviewed by: <?php echo htmlspecialchars($application['admin_reviewed_by_name']); ?></p>
                                <?php if ($application['admin_comments']): ?>
                                    <div class="alert alert-light p-2 mb-1">
                                        <small><?php echo nl2br(htmlspecialchars($application['admin_comments'])); ?></small>
                                    </div>
                                <?php endif; ?>
                                <p class="text-muted mb-0">
                                    <?php echo $application['reviewed_at'] ? date('F j, Y \a\t g:i A', strtotime($application['reviewed_at'])) : 'Date not recorded'; ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- HOD Review -->
                    <?php if ($application['hod_reviewed_by']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">HOD Review</h6>
                                <p class="mb-1">Reviewed by: <?php echo htmlspecialchars($application['hod_reviewed_by_name']); ?></p>
                                <?php if ($application['hod_comments']): ?>
                                    <div class="alert alert-light p-2 mb-1">
                                        <small><?php echo nl2br(htmlspecialchars($application['hod_comments'])); ?></small>
                                    </div>
                                <?php endif; ?>
                                <p class="text-muted mb-0">
                                    <?php echo $application['approved_at'] ? date('F j, Y \a\t g:i A', strtotime($application['approved_at'])) : 'Date not recorded'; ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Lecturer Assignment -->
                    <div class="timeline-item">
                        <div class="timeline-marker bg-secondary"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Assigned to Lecturer</h6>
                            <p class="mb-1">Assigned by: <?php echo htmlspecialchars($application['assigned_by_name']); ?></p>
                            <p class="text-muted mb-0">
                                <?php echo date('F j, Y \a\t g:i A', strtotime($application['notified_at'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Lecturer Acknowledgment -->
                    <?php if ($application['is_acknowledged']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Acknowledged by Lecturer</h6>
                                <p class="mb-1">You acknowledged this application</p>
                                <p class="text-muted mb-0">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($application['acknowledged_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Application Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Application Status
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <span class="badge <?php echo getStatusBadgeClass($application['status']); ?> fs-6 mb-3">
                        <?php echo formatStatus($application['status']); ?>
                    </span>
                    
                    <?php if (!$application['is_acknowledged']): ?>
                        <div class="alert alert-warning p-2">
                            <small><i class="fas fa-clock me-1"></i> Waiting for your acknowledgment</small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success p-2">
                            <small><i class="fas fa-check me-1"></i> Acknowledged by you</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Attached Documents -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-paperclip me-2"></i>
                    Attached Documents
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php if ($application['letter_file']): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                            <div>
                                <i class="fas fa-file-alt me-2 text-primary"></i>
                                <small>Medical Letter</small>
                            </div>
                            <a href="<?php echo '/medex_system/assets/uploads/letters/' . basename($application['letter_file']); ?>" 
                               target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($application['medical_application_file']): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                            <div>
                                <i class="fas fa-file-medical me-2 text-info"></i>
                                <small>Medical Application</small>
                            </div>
                            <a href="<?php echo '/medex_system/assets/uploads/applications/' . basename($application['medical_application_file']); ?>" 
                               target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($application['medical_certificate_file']): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                            <div>
                                <i class="fas fa-certificate me-2 text-success"></i>
                                <small>Medical Certificate</small>
                            </div>
                            <a href="<?php echo '/medex_system/assets/uploads/certificates/' . basename($application['medical_certificate_file']); ?>" 
                               target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$application['letter_file'] && !$application['medical_application_file'] && !$application['medical_certificate_file']): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-inbox"></i>
                        <p class="mb-0 mt-2"><small>No documents attached</small></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <?php if (!$application['is_acknowledged']): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid">
                        <a href="acknowledge_application.php?id=<?php echo $application['id']; ?>" 
                           class="btn btn-success">
                            <i class="fas fa-check me-2"></i>
                            Acknowledge Application
                        </a>
                    </div>
                    <hr>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Acknowledging confirms you have reviewed this medical excuse application.
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Custom CSS for Timeline -->
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>
