<?php
$pageTitle = 'Review Applications';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('hod');

$user = getCurrentUser();

// Handle single application view
$viewSingle = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = getConnection();
    
    if ($viewSingle) {
        // Get single application details
        $stmt = $pdo->prepare("
            SELECT a.*, c.course_name, c.course_code, c.department, c.year,
                   u.full_name as student_name, u.email as student_email, 
                   u.department as student_dept, u.year as student_year,
                   admin.full_name as admin_name
            FROM applications a 
            JOIN courses c ON a.course_id = c.id 
            JOIN users u ON a.student_id = u.id
            LEFT JOIN users admin ON a.admin_reviewed_by = admin.id
            WHERE a.id = ? AND u.department = ?
        ");
        $stmt->execute([$viewSingle, $user['department']]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $_SESSION['error_message'] = 'Application not found or not in your department.';
            header('Location: review_applications.php');
            exit();
        }
    } else {
        // Get applications for this HOD's department
        $statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : 'admin_reviewed';
        $sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'submitted_at';
        $sortOrder = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE conditions
        $whereConditions = ['u.department = ?'];
        $params = [$user['department']];
        
        if (!empty($statusFilter)) {
            $whereConditions[] = "a.status = ?";
            $params[] = $statusFilter;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM applications a 
            LEFT JOIN courses c ON a.course_id = c.id
            JOIN users u ON a.student_id = u.id 
            WHERE $whereClause
        ");
        $countStmt->execute($params);
        $totalApplications = $countStmt->fetchColumn();
        $totalPages = ceil($totalApplications / $perPage);
        
        // Get applications
        $stmt = $pdo->prepare("
            SELECT a.*, c.course_name, c.course_code, 
                   u.full_name as student_name, u.year as student_year,
                   admin.full_name as admin_name
            FROM applications a 
            LEFT JOIN courses c ON a.course_id = c.id 
            JOIN users u ON a.student_id = u.id
            LEFT JOIN users admin ON a.admin_reviewed_by = admin.id
            WHERE $whereClause
            ORDER BY a.$sortBy $sortOrder 
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $applications = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Database error in HOD review applications: " . $e->getMessage());
    if ($viewSingle) {
        $_SESSION['error_message'] = 'Error loading application details.';
        header('Location: review_applications.php');
        exit();
    }
    $applications = [];
    $totalApplications = 0;
    $totalPages = 0;
}
?>

<?php if ($viewSingle && $application): ?>
    <!-- Single Application Review -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Application Review</h2>
            <p class="text-muted mb-0">Application ID: #<?php echo $application['id']; ?></p>
        </div>
        <div>
            <a href="review_applications.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Applications
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Application Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Application Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Student:</strong><br>
                            <?php echo htmlspecialchars($application['student_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($application['student_email']); ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Year:</strong><br>
                            Year <?php echo $application['student_year']; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Course:</strong><br>
                            <?php echo htmlspecialchars($application['course_code'] . ' - ' . $application['course_name']); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Absence Type:</strong><br>
                            <span class="badge badge-secondary"><?php echo ucfirst($application['application_type']); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Date of Absence:</strong><br>
                            <?php echo date('F j, Y', strtotime($application['application_date'])); ?>
                            <?php if ($application['application_time']): ?>
                                at <?php echo date('g:i A', strtotime($application['application_time'])); ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Submitted On:</strong><br>
                            <?php echo date('F j, Y g:i A', strtotime($application['submitted_at'])); ?>
                        </div>
                        <div class="col-12 mb-3">
                            <strong>Reason:</strong><br>
                            <?php echo nl2br(htmlspecialchars($application['reason'])); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Certificate Type:</strong><br>
                            <?php echo $application['certificate_type'] === 'government' ? 'Government Hospital' : 'Private (Certified)'; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Current Status:</strong><br>
                            <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                <?php echo formatStatus($application['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Admin Review -->
            <?php if ($application['admin_comments']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        Medical Officer Review
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info alert-permanent">
                        <h6>Reviewed by: <?php echo htmlspecialchars($application['admin_name'] ?: 'Medical Officer'); ?></h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($application['admin_comments'])); ?></p>
                        <small class="text-muted">
                            Reviewed on: <?php echo date('F j, Y g:i A', strtotime($application['reviewed_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Documents -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Supporting Documents
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="border rounded p-3">
                                <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                <h6>Letter</h6>
                                <div class="btn-group">
                                    <a href="../assets/uploads/letters/<?php echo $application['letter_file']; ?>" 
                                       class="btn btn-outline-primary btn-sm" target="_blank">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="../assets/uploads/letters/<?php echo $application['letter_file']; ?>" 
                                       class="btn btn-outline-secondary btn-sm" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="border rounded p-3">
                                <i class="fas fa-file-medical fa-3x text-warning mb-3"></i>
                                <h6>Medical Application</h6>
                                <div class="btn-group">
                                    <a href="../assets/uploads/applications/<?php echo $application['medical_application_file']; ?>" 
                                       class="btn btn-outline-primary btn-sm" target="_blank">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="../assets/uploads/applications/<?php echo $application['medical_application_file']; ?>" 
                                       class="btn btn-outline-secondary btn-sm" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="border rounded p-3">
                                <i class="fas fa-certificate fa-3x text-success mb-3"></i>
                                <h6>Medical Certificate</h6>
                                <div class="btn-group">
                                    <a href="../assets/uploads/certificates/<?php echo $application['medical_certificate_file']; ?>" 
                                       class="btn btn-outline-primary btn-sm" target="_blank">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="../assets/uploads/certificates/<?php echo $application['medical_certificate_file']; ?>" 
                                       class="btn btn-outline-secondary btn-sm" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- HOD Decision -->
            <?php if ($application['status'] === 'admin_reviewed'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-gavel me-2"></i>
                            HOD Decision
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="process_approval.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            
                            <div class="form-group mb-3">
                                <label for="decision" class="form-label">Decision *</label>
                                <select class="form-select" id="decision" name="decision" required>
                                    <option value="">Select decision</option>
                                    <option value="approve">Approve Application</option>
                                    <option value="reject">Reject Application</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a decision.
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="comments" class="form-label">Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="4"
                                          placeholder="Add your decision comments here..."></textarea>
                                <small class="form-text text-muted">
                                    Comments will be visible to the student and medical officer.
                                </small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-2"></i>
                                    Submit Decision
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Decision Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-<?php echo $application['status'] === 'hod_approved' ? 'success' : 'warning'; ?>">
                            <h6>Decision Made</h6>
                            <p class="mb-0">This application has been 
                               <strong><?php echo $application['status'] === 'hod_approved' ? 'approved' : 'rejected'; ?></strong>
                            </p>
                        </div>
                        
                        <?php if ($application['hod_comments']): ?>
                            <div class="alert alert-secondary">
                                <h6>Your Comments:</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($application['hod_comments'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <small class="text-muted">
                            Decision made on: <?php echo date('F j, Y g:i A', strtotime($application['approved_at'])); ?>
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- Applications List -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Review Applications</h2>
            <p class="text-muted mb-0"><?php echo getDepartmentName($user['department']); ?> Applications</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="admin_reviewed" <?php echo $statusFilter === 'admin_reviewed' ? 'selected' : ''; ?>>Pending Approval</option>
                        <option value="hod_approved" <?php echo $statusFilter === 'hod_approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="hod_rejected" <?php echo $statusFilter === 'hod_rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sort By</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="reviewed_at" <?php echo $sortBy === 'reviewed_at' ? 'selected' : ''; ?>>Date Reviewed</option>
                        <option value="submitted_at" <?php echo $sortBy === 'submitted_at' ? 'selected' : ''; ?>>Date Submitted</option>
                        <option value="application_date" <?php echo $sortBy === 'application_date' ? 'selected' : ''; ?>>Absence Date</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="order" class="form-label">Order</label>
                    <select name="order" id="order" class="form-select">
                        <option value="asc" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="desc" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Applications Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Applications (<?php echo $totalApplications; ?> total)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No applications found with the current filters.</p>
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
                                <th>Absence Date</th>
                                <th>Reviewed By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>#<?php echo $app['id']; ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($app['student_name']); ?></strong><br>
                                            <small class="text-muted">Year <?php echo $app['student_year']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($app['course_code']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($app['course_name']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?php echo ucfirst($app['application_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                        <?php if ($app['application_time']): ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($app['application_time'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($app['admin_name']): ?>
                                            <small><?php echo htmlspecialchars($app['admin_name']); ?></small><br>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($app['reviewed_at'])); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($app['status']); ?>">
                                            <?php echo formatStatus($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="review_applications.php?id=<?php echo $app['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm" 
                                               title="Review Application">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($app['status'] === 'admin_reviewed'): ?>
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                        onclick="quickApprove(<?php echo $app['id']; ?>)" 
                                                        title="Quick Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="quickReject(<?php echo $app['id']; ?>)" 
                                                        title="Quick Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Applications pagination" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Action Modals -->
    <div class="modal fade" id="quickApproveModal" tabindex="-1" aria-labelledby="quickApproveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickApproveModalLabel">Quick Approve Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickApproveForm" action="process_approval.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="application_id" id="approveAppId">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="decision" value="approve">
                        
                        <div class="form-group">
                            <label for="approveComments" class="form-label">Comments (Optional)</label>
                            <textarea class="form-control" id="approveComments" name="comments" rows="3"
                                      placeholder="Add approval comments..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="quickRejectModal" tabindex="-1" aria-labelledby="quickRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickRejectModalLabel">Quick Reject Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickRejectForm" action="process_approval.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="application_id" id="rejectAppId">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="decision" value="reject">
                        
                        <div class="form-group">
                            <label for="rejectComments" class="form-label">Rejection Reason *</label>
                            <textarea class="form-control" id="rejectComments" name="comments" rows="3"
                                      placeholder="Please provide reason for rejection..." required></textarea>
                            <div class="invalid-feedback">
                                Please provide a reason for rejection.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
// Quick approve function
function quickApprove(applicationId) {
    document.getElementById('approveAppId').value = applicationId;
    new bootstrap.Modal(document.getElementById('quickApproveModal')).show();
}

// Quick reject function
function quickReject(applicationId) {
    document.getElementById('rejectAppId').value = applicationId;
    new bootstrap.Modal(document.getElementById('quickRejectModal')).show();
}

// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Auto-hide alerts (but not content alerts)
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent):not(.alert-info):not(.alert-secondary)');
    alerts.forEach(function(alert) {
        // Only hide success, warning, danger alerts - not info or secondary
        if (alert.classList.contains('alert-success') || 
            alert.classList.contains('alert-warning') || 
            alert.classList.contains('alert-danger')) {
            setTimeout(function() {
                if (alert && alert.parentNode) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        if (alert && alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }
            }, 5000);
        }
    });
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>