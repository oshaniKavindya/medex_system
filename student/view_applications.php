<?php
$pageTitle = 'My Applications';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('student');

$user = getCurrentUser();

// Handle single application view
$viewSingle = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = getConnection();
    
    if ($viewSingle) {
        // Get single application details
        $stmt = $pdo->prepare("
            SELECT a.*, c.course_name, c.course_code, c.department, c.year,
                   admin.full_name as admin_name,
                   hod.full_name as hod_name
            FROM applications a 
            JOIN courses c ON a.course_id = c.id 
            LEFT JOIN users admin ON a.admin_reviewed_by = admin.id
            LEFT JOIN users hod ON a.hod_reviewed_by = hod.id
            WHERE a.id = ? AND a.student_id = ?
        ");
        $stmt->execute([$viewSingle, $user['id']]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $_SESSION['error_message'] = 'Application not found.';
            header('Location: view_applications.php');
            exit();
        }
    } else {
        // Get all applications with pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        // Filter options
        $statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'submitted_at';
        $sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
        
        // Build query conditions
        $whereConditions = ["a.student_id = ?"];
        $params = [$user['id']];
        
        if (!empty($statusFilter)) {
            $whereConditions[] = "a.status = ?";
            $params[] = $statusFilter;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM applications a WHERE $whereClause");
        $countStmt->execute($params);
        $totalApplications = $countStmt->fetchColumn();
        $totalPages = ceil($totalApplications / $perPage);
        
        // Get applications
        $stmt = $pdo->prepare("
            SELECT a.*, c.course_name, c.course_code 
            FROM applications a 
            JOIN courses c ON a.course_id = c.id 
            WHERE $whereClause 
            ORDER BY a.$sortBy $sortOrder 
            LIMIT ? OFFSET ?
        ");
        
        // Bind parameters with explicit types
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex, $param);
            $paramIndex++;
        }
        $stmt->bindValue($paramIndex, (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex + 1, (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $applications = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Database error in view applications: " . $e->getMessage());
    if ($viewSingle) {
        $_SESSION['error_message'] = 'Error loading application details.';
        header('Location: view_applications.php');
        exit();
    }
    $applications = [];
    $totalApplications = 0;
    $totalPages = 0;
}
?>

<?php if ($viewSingle && $application): ?>
    <!-- Single Application View -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Application Details</h2>
            <p class="text-muted mb-0">Application ID: #<?php echo $application['id']; ?></p>
        </div>
        <div>
            <a href="view_applications.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Back to All Applications
            </a>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i>
                Print
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Application Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Application Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
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
            
            <!-- Status Timeline -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-timeline me-2"></i>
                        Status Timeline
                    </h5>
                </div>
                <div class="card-body">
                    <div class="status-timeline">
                        <div class="status-step completed">
                            <div class="status-content">
                                <h6>Application Submitted</h6>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($application['submitted_at'])); ?></small>
                                <p class="mb-0">Application submitted and awaiting admin review.</p>
                            </div>
                        </div>
                        
                        <?php if (in_array($application['status'], ['admin_reviewed', 'hod_approved', 'hod_rejected', 'completed'])): ?>
                            <div class="status-step completed">
                                <div class="status-content">
                                    <h6>Admin Review</h6>
                                    <?php if ($application['reviewed_at']): ?>
                                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($application['reviewed_at'])); ?></small>
                                    <?php endif; ?>
                                    <p class="mb-0">
                                        Documents reviewed by <?php echo $application['admin_name'] ?: 'Admin'; ?>.
                                        <?php if ($application['status'] === 'admin_rejected'): ?>
                                            <span class="text-danger">Application rejected.</span>
                                        <?php else: ?>
                                            Application forwarded to HOD for approval.
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($application['admin_comments']): ?>
                                        <div class="alert alert-info mt-2">
                                            <strong>Admin Comments:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($application['admin_comments'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="status-step active">
                                <div class="status-content">
                                    <h6>Admin Review</h6>
                                    <p class="mb-0">Pending admin review...</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($application['status'], ['hod_approved', 'hod_rejected', 'completed'])): ?>
                            <div class="status-step completed">
                                <div class="status-content">
                                    <h6>HOD Decision</h6>
                                    <?php if ($application['approved_at']): ?>
                                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($application['approved_at'])); ?></small>
                                    <?php endif; ?>
                                    <p class="mb-0">
                                        Application <?php echo $application['status'] === 'hod_approved' ? 'approved' : 'rejected'; ?>
                                        by <?php echo $application['hod_name'] ?: 'HOD'; ?>.
                                    </p>
                                    <?php if ($application['hod_comments']): ?>
                                        <div class="alert alert-<?php echo $application['status'] === 'hod_approved' ? 'success' : 'warning'; ?> mt-2">
                                            <strong>HOD Comments:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($application['hod_comments'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ($application['status'] === 'admin_reviewed'): ?>
                            <div class="status-step active">
                                <div class="status-content">
                                    <h6>HOD Review</h6>
                                    <p class="mb-0">Pending HOD approval...</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($application['status'] === 'hod_approved'): ?>
                            <div class="status-step <?php echo $application['status'] === 'completed' ? 'completed' : 'active'; ?>">
                                <div class="status-content">
                                    <h6>Notification Sent</h6>
                                    <p class="mb-0">
                                        <?php if ($application['status'] === 'completed'): ?>
                                            Relevant lecturers have been notified.
                                        <?php else: ?>
                                            Notifying relevant lecturers...
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Uploaded Documents -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Uploaded Documents
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-envelope text-primary me-2"></i>
                                Letter
                            </div>
                            <a href="../assets/uploads/letters/<?php echo $application['letter_file']; ?>" 
                               class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-medical text-warning me-2"></i>
                                Medical Application
                            </div>
                            <a href="../assets/uploads/applications/<?php echo $application['medical_application_file']; ?>" 
                               class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-certificate text-success me-2"></i>
                                Medical Certificate
                            </div>
                            <a href="../assets/uploads/certificates/<?php echo $application['medical_certificate_file']; ?>" 
                               class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Application Actions -->
            <?php if (in_array($application['status'], ['admin_rejected'])): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0 text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Action Required
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">Your application was rejected by the admin. Please review the comments and resubmit with corrected documents.</p>
                        <a href="submit_application.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-2"></i>
                            Submit New Application
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- All Applications List -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>My Applications</h2>
            <p class="text-muted mb-0">Track all your medical excuse applications</p>
        </div>
        <div>
            <a href="submit_application.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>
                Submit New Application
            </a>
        </div>
    </div>
    
    <!-- Filters and Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="admin_reviewed" <?php echo $statusFilter === 'admin_reviewed' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="admin_rejected" <?php echo $statusFilter === 'admin_rejected' ? 'selected' : ''; ?>>Rejected by Admin</option>
                        <option value="hod_approved" <?php echo $statusFilter === 'hod_approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="hod_rejected" <?php echo $statusFilter === 'hod_rejected' ? 'selected' : ''; ?>>Rejected by HOD</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sort By</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="submitted_at" <?php echo $sortBy === 'submitted_at' ? 'selected' : ''; ?>>Date Submitted</option>
                        <option value="application_date" <?php echo $sortBy === 'application_date' ? 'selected' : ''; ?>>Absence Date</option>
                        <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="order" class="form-label">Order</label>
                    <select name="order" id="order" class="form-select">
                        <option value="desc" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="asc" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                    <a href="view_applications.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Applications List -->
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
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <?php if (!empty($statusFilter)): ?>
                        <p class="text-muted">No applications found with the selected filters.</p>
                        <a href="view_applications.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>
                            View All Applications
                        </a>
                    <?php else: ?>
                        <p class="text-muted">You haven't submitted any applications yet.</p>
                        <a href="submit_application.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Submit Your First Application
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Course</th>
                                <th>Type</th>
                                <th>Absence Date</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $app['id']; ?></strong>
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
                                        <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                        <?php if ($app['application_time']): ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($app['application_time'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($app['submitted_at'])); ?><br>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($app['submitted_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($app['status']); ?>">
                                            <?php echo formatStatus($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_applications.php?id=<?php echo $app['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($app['status'] === 'admin_rejected'): ?>
                                                <a href="submit_application.php" 
                                                   class="btn btn-outline-warning btn-sm" title="Resubmit">
                                                    <i class="fas fa-redo"></i>
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
                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <small class="text-muted">
                                Showing <?php echo (($page - 1) * $perPage + 1); ?> to 
                                <?php echo min($page * $perPage, $totalApplications); ?> of 
                                <?php echo $totalApplications; ?> applications
                            </small>
                        </div>
                        <nav aria-label="Applications pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?><?php echo $sortBy !== 'submitted_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=' . strtolower($sortOrder) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?><?php echo $sortBy !== 'submitted_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=' . strtolower($sortOrder) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?><?php echo $sortBy !== 'submitted_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=' . strtolower($sortOrder) : ''; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = ['status', 'sort', 'order'];
    
    filterSelects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        }
    });
});

// Print functionality for single application view
function printApplication() {
    window.print();
}

// Status timeline animation
<?php if ($viewSingle): ?>
document.addEventListener('DOMContentLoaded', function() {
    const steps = document.querySelectorAll('.status-step');
    steps.forEach((step, index) => {
        setTimeout(() => {
            step.style.opacity = '0';
            step.style.transform = 'translateY(20px)';
            step.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                step.style.opacity = '1';
                step.style.transform = 'translateY(0)';
            }, 100);
        }, index * 200);
    });
});
<?php endif; ?>
</script>

<!-- Additional CSS for status timeline -->
<style>
.status-timeline {
    position: relative;
    padding: 0;
}

.status-step {
    position: relative;
    padding-bottom: 2rem;
    border-left: 2px solid #e9ecef;
    margin-left: 1rem;
    padding-left: 2rem;
}

.status-step:last-child {
    border-left: none;
    padding-bottom: 0;
}

.status-step::before {
    content: '';
    position: absolute;
    left: -9px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #6c757d;
    border: 3px solid #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.status-step.completed::before {
    background: #28a745;
}

.status-step.active::before {
    background: #ffc107;
    animation: pulse 1.5s infinite;
}

.status-step.completed {
    border-left-color: #28a745;
}

.status-step.active {
    border-left-color: #ffc107;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
    }
}

.status-content h6 {
    color: #495057;
    margin-bottom: 0.25rem;
}

.status-content small {
    color: #6c757d;
}

@media print {
    .btn, .card-header, nav, .status-step::before {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .status-timeline {
        border-left: 1px solid #000;
    }
    
    .status-step {
        border-left: 1px solid #000;
    }
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>