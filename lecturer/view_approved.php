<?php
$pageTitle = 'View Approved Applications';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('lecturer');

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
                   admin.full_name as admin_name,
                   hod.full_name as hod_name
            FROM applications a 
            JOIN courses c ON a.course_id = c.id 
            JOIN users u ON a.student_id = u.id
            LEFT JOIN users admin ON a.admin_reviewed_by = admin.id
            LEFT JOIN users hod ON a.hod_reviewed_by = hod.id
            WHERE a.id = ? AND u.department = ? AND a.status IN ('hod_approved', 'completed')
        ");
        $stmt->execute([$viewSingle, $user['department']]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $_SESSION['error_message'] = 'Application not found or not approved yet.';
            header('Location: view_approved.php');
            exit();
        }
    } else {
        // Get approved applications for this lecturer's department
        $statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $typeFilter = isset($_GET['type']) ? sanitize($_GET['type']) : '';
        $courseFilter = isset($_GET['course']) ? sanitize($_GET['course']) : '';
        $sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'approved_at';
        $sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Debug logging
        error_log("Lecturer View Approved - GET params: " . print_r($_GET, true));
        error_log("Lecturer View Approved - Filters: status=$statusFilter, type=$typeFilter, course=$courseFilter");
        
        // Build WHERE conditions
        $whereConditions = ['u.department = ?'];
        $params = [$user['department']];
        
        // Handle status filter
        if (!empty($statusFilter)) {
            $whereConditions[] = "a.status = ?";
            $params[] = $statusFilter;
        } else {
            // Default: show both hod_approved and completed
            $whereConditions[] = "a.status IN ('hod_approved', 'completed')";
        }
        
        if (!empty($typeFilter)) {
            $whereConditions[] = "a.application_type = ?";
            $params[] = $typeFilter;
        }
        
        if (!empty($courseFilter)) {
            $whereConditions[] = "c.id = ?";
            $params[] = $courseFilter;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Debug logging
        error_log("Lecturer View Approved - WHERE clause: " . $whereClause);
        error_log("Lecturer View Approved - Params: " . print_r($params, true));
        
        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM applications a 
            JOIN courses c ON a.course_id = c.id 
            JOIN users u ON a.student_id = u.id 
            WHERE $whereClause
        ");
        $countStmt->execute($params);
        $totalApplications = $countStmt->fetchColumn();
        $totalPages = ceil($totalApplications / $perPage);
        
        // Get applications
        $stmt = $pdo->prepare("
            SELECT a.*, c.course_name, c.course_code, c.year as course_year,
                   u.full_name as student_name, u.year as student_year,
                   admin.full_name as admin_name,
                   hod.full_name as hod_name
            FROM applications a 
            JOIN courses c ON a.course_id = c.id 
            JOIN users u ON a.student_id = u.id
            LEFT JOIN users admin ON a.admin_reviewed_by = admin.id
            LEFT JOIN users hod ON a.hod_reviewed_by = hod.id
            WHERE $whereClause
            ORDER BY a.$sortBy $sortOrder 
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $applications = $stmt->fetchAll();
        
        // Get courses for filter dropdown
        $courseStmt = $pdo->prepare("
            SELECT DISTINCT c.id, c.course_code, c.course_name, c.year
            FROM courses c 
            WHERE c.department = ?
            ORDER BY c.year, c.course_code
        ");
        $courseStmt->execute([$user['department']]);
        $courses = $courseStmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Database error in lecturer view approved: " . $e->getMessage());
    if ($viewSingle) {
        $_SESSION['error_message'] = 'Error loading application details.';
        header('Location: view_approved.php');
        exit();
    }
    $applications = [];
    $totalApplications = 0;
    $totalPages = 0;
    $courses = [];
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
            <a href="view_approved.php" class="btn btn-outline-secondary">
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
                            <strong>Student Year:</strong><br>
                            Year <?php echo $application['student_year']; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Course:</strong><br>
                            <?php echo htmlspecialchars($application['course_code'] . ' - ' . $application['course_name']); ?><br>
                            <small class="text-muted">Year <?php echo $application['year']; ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Absence Type:</strong><br>
                            <span class="badge badge-<?php 
                                echo $application['application_type'] === 'assignment' ? 'primary' : 
                                    ($application['application_type'] === 'field_practical' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo ucfirst($application['application_type']); ?>
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Date of Absence:</strong><br>
                            <?php echo date('F j, Y', strtotime($application['application_date'])); ?>
                            <?php if ($application['application_time']): ?>
                                <br><small class="text-muted">Time: <?php echo date('g:i A', strtotime($application['application_time'])); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Status:</strong><br>
                            <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                <?php echo formatStatus($application['status']); ?>
                            </span>
                        </div>
                        <div class="col-12 mb-3">
                            <strong>Reason for Absence:</strong><br>
                            <?php echo nl2br(htmlspecialchars($application['reason'])); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Submitted On:</strong><br>
                            <?php echo date('F j, Y g:i A', strtotime($application['submitted_at'])); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Approved On:</strong><br>
                            <?php echo date('F j, Y g:i A', strtotime($application['approved_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Medical Officer Review -->
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
            
            <!-- HOD Decision -->
            <?php if ($application['hod_comments']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-gavel me-2"></i>
                        HOD Decision
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success alert-permanent">
                        <h6>Approved by: <?php echo htmlspecialchars($application['hod_name'] ?: 'HOD'); ?></h6>
                        <?php if ($application['hod_comments']): ?>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($application['hod_comments'])); ?></p>
                        <?php else: ?>
                            <p class="mb-0">Application approved without additional comments.</p>
                        <?php endif; ?>
                        <small class="text-muted">
                            Approved on: <?php echo date('F j, Y g:i A', strtotime($application['approved_at'])); ?>
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
            <!-- Information Panel -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>For Your Information</h6>
                        <ul class="mb-0">
                            <li>This medical excuse has been approved by the HOD</li>
                            <li>Student was absent due to medical reasons</li>
                            <li>Consider alternative arrangements if needed</li>
                            <li>All documentation has been verified</li>
                        </ul>
                    </div>
                    
                    <?php if ($application['application_type'] === 'exam'): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Exam Notice</h6>
                            <p class="mb-0">This is for an Exam. You may need to arrange an alternative exam date or make-up assessment for this student.</p>
                        </div>
                    <?php elseif ($application['application_type'] === 'field_practical'): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-flask me-2"></i>Field Practical Notice</h6>
                            <p class="mb-0">This is for a Field Practical session. Please consider arranging a make-up practical or alternative fieldwork if possible.</p>
                        </div>
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
                        <a href="view_approved.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>
                            View All Applications
                        </a>
                        <a href="view_approved.php?course=<?php echo $application['course_id']; ?>" class="btn btn-outline-info">
                            <i class="fas fa-book me-2"></i>
                            Same Course Applications
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Applications List -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Approved Applications</h2>
            <p class="text-muted mb-0"><?php echo getDepartmentName($user['department']); ?> Department</p>
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
                <div class="col-md-3">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Approved</option>
                        <option value="hod_approved" <?php echo $statusFilter === 'hod_approved' ? 'selected' : ''; ?>>Recently Approved</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="type" class="form-label">Filter by Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="assignment" <?php echo $typeFilter === 'assignment' ? 'selected' : ''; ?>>Assignment</option>
                        <option value="field_practical" <?php echo $typeFilter === 'field_practical' ? 'selected' : ''; ?>>Field Practical</option>
                        <option value="exam" <?php echo $typeFilter === 'exam' ? 'selected' : ''; ?>>Exam</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="course" class="form-label">Filter by Course</label>
                    <select name="course" id="course" class="form-select">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                    <?php echo $courseFilter == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?> (Year <?php echo $course['year']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="sort" class="form-label">Sort By</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="approved_at" <?php echo $sortBy === 'approved_at' ? 'selected' : ''; ?>>Date Approved</option>
                        <option value="application_date" <?php echo $sortBy === 'application_date' ? 'selected' : ''; ?>>Absence Date</option>
                        <option value="submitted_at" <?php echo $sortBy === 'submitted_at' ? 'selected' : ''; ?>>Date Submitted</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Applications Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-check-circle me-2"></i>
                Approved Applications (<?php echo $totalApplications; ?> total)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No approved applications found with the current filters.</p>
                    <small class="text-muted">Approved medical excuses from your department will appear here.</small>
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
                                <th>Approved By</th>
                                <th>Approved On</th>
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
                                        <span class="badge badge-<?php 
                                            echo $app['application_type'] === 'assignment' ? 'primary' : 
                                                ($app['application_type'] === 'field_practical' ? 'warning' : 'info'); 
                                        ?>">
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
                                        <?php if ($app['hod_name']): ?>
                                            <small><?php echo htmlspecialchars($app['hod_name']); ?></small><br>
                                            <small class="text-muted">HOD</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M j, Y', strtotime($app['approved_at'])); ?></small><br>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($app['approved_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($app['status']); ?>">
                                            <?php echo formatStatus($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_approved.php?id=<?php echo $app['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

    <!-- Information Panel -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Information for Lecturers
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="alert alert-info alert-permanent">
                                <h6><i class="fas fa-chalkboard-teacher me-2"></i>Assignment Excuses</h6>
                                <p class="mb-0">Students with approved assignment excuses missed assignment deadlines due to medical reasons. Please consider allowing them to submit late or provide an alternative assignment if appropriate.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning alert-permanent">
                                <h6><i class="fas fa-flask me-2"></i>Practical Excuses</h6>
                                <p class="mb-0">Students missed practical sessions. Consider if they need to make up the practical work or if alternative arrangements are needed.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-secondary alert-permanent">
                                <h6><i class="fas fa-file-alt me-2"></i>Exam Excuses</h6>
                                <p class="mb-0">Students missed exams. You may need to arrange alternative exam dates or methods.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-primary alert-permanent">
                        <h6><i class="fas fa-lightbulb me-2"></i>General Guidelines</h6>
                        <ul class="mb-0">
                            <li>All applications shown here have been verified by medical officers and approved by HODs</li>
                            <li>Students are responsible for catching up on missed work</li>
                            <li>For assessment-related absences, consider flexible arrangements</li>
                            <li>Contact the student directly if you need to discuss make-up work</li>
                            <li>Refer to departmental policies for specific guidance</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
// Auto-hide alerts (but not content alerts)
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        // Only hide temporary notification alerts - not content displays
        if (alert.classList.contains('alert-success') && !alert.closest('.card-body')) {
            // Only auto-hide success alerts that are not inside card bodies (i.e., notification alerts)
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
        } else if (alert.classList.contains('alert-danger') || alert.classList.contains('alert-warning')) {
            // Auto-hide error and warning notifications
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

// Clear filters function
function clearFilters() {
    window.location.href = 'view_approved.php';
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>
