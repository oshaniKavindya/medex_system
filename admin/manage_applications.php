<?php
$pageTitle = 'Manage Applications';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('admin');

$user = getCurrentUser();
$viewSingle = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = getConnection();
    
    if ($viewSingle) {
        $stmt = $pdo->prepare("
            SELECT a.*, c.course_name, c.course_code, c.department, c.year,
                   u.full_name as student_name, u.email as student_email, u.department as student_dept, u.year as student_year
            FROM applications a 
            JOIN courses c ON a.course_id = c.id 
            JOIN users u ON a.student_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$viewSingle]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $_SESSION['error_message'] = 'Application not found.';
            header('Location: manage_applications.php');
            exit();
        }
    } else {
        // Extract filter parameters
        $filters = [
            'status' => sanitize($_GET['status'] ?? ''),
            'department' => sanitize($_GET['department'] ?? ''),
            'sort' => sanitize($_GET['sort'] ?? 'submitted_at'),
            'order' => ($_GET['order'] ?? '') === 'asc' ? 'ASC' : 'DESC',
            'page' => max(1, (int)($_GET['page'] ?? 1))
        ];
        
        $perPage = 15;
        $offset = ($filters['page'] - 1) * $perPage;
        
        // Build dynamic WHERE clause
        $whereConditions = ['1=1'];
        $params = [];
        
        foreach (['status', 'department'] as $field) {
            if (!empty($filters[$field])) {
                $column = $field === 'department' ? 'u.department' : "a.$field";
                $whereConditions[] = "$column = ?";
                $params[] = $filters[$field];
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count and applications in one efficient query pattern
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN users u ON a.student_id = u.id WHERE $whereClause");
        $countStmt->execute($params);
        $totalApplications = $countStmt->fetchColumn();
        $totalPages = ceil($totalApplications / $perPage);
        
        $stmt = $pdo->prepare("
            SELECT a.*, c.course_name, c.course_code, 
                   u.full_name as student_name, u.department as student_dept, u.year as student_year
            FROM applications a 
            JOIN courses c ON a.course_id = c.id 
            JOIN users u ON a.student_id = u.id
            WHERE $whereClause
            ORDER BY a.{$filters['sort']} {$filters['order']} 
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $applications = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Database error in manage applications: " . $e->getMessage());
    if ($viewSingle) {
        $_SESSION['error_message'] = 'Error loading application details.';
        header('Location: manage_applications.php');
        exit();
    }
    $applications = [];
    $totalApplications = 0;
    $totalPages = 0;
}

// Helper functions for cleaner HTML output
function renderDocumentCard($type, $file, $icon, $color) {
    $path = ['letter' => 'letters', 'application' => 'applications', 'certificate' => 'certificates'][$type] ?? $type;
    return "
        <div class='col-md-4 text-center mb-3'>
            <div class='border rounded p-3'>
                <i class='fas fa-$icon fa-3x text-$color mb-3'></i>
                <h6>" . ucfirst($type) . "</h6>
                <a href='../assets/uploads/$path/$file' class='btn btn-outline-primary btn-sm me-1' target='_blank'>
                    <i class='fas fa-eye me-1'></i>View
                </a>
                <a href='../assets/uploads/$path/$file' class='btn btn-outline-secondary btn-sm' download>
                    <i class='fas fa-download me-1'></i>Download
                </a>
            </div>
        </div>";
}

function renderTimelineItem($title, $time, $status = 'completed') {
    $statusClass = $status === 'active' ? 'active' : 'completed';
    $timeText = $time ? date('M j, Y g:i A', strtotime($time)) : ($status === 'active' ? 'Current step' : 'Completed');
    
    return "
        <div class='timeline-item $statusClass'>
            <div class='timeline-marker'></div>
            <div class='timeline-content'>
                <h6>$title</h6>
                <small class='text-muted'>$timeText</small>
            </div>
        </div>";
}

function renderFilterSelect($name, $options, $selected = '') {
    $html = "<select name='$name' id='$name' class='form-select'>";
    foreach ($options as $value => $label) {
        $sel = $selected === $value ? 'selected' : '';
        $html .= "<option value='$value' $sel>$label</option>";
    }
    return $html . "</select>";
}
?>

<?php if ($viewSingle && $application): ?>
    <!-- Single Application Review -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Review Application</h2>
            <p class="text-muted mb-0">Application ID: #<?php echo $application['id']; ?></p>
        </div>
        <div>
            <a href="manage_applications.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Applications
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Application Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Application Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $details = [
                            ['Student', $application['student_name'] . '<br><small class="text-muted">' . htmlspecialchars($application['student_email']) . '</small>'],
                            ['Department & Year', getDepartmentName($application['student_dept']) . ' - Year ' . $application['student_year']],
                            ['Course', htmlspecialchars($application['course_code'] . ' - ' . $application['course_name'])],
                            ['Absence Type', '<span class="badge badge-secondary">' . ucfirst($application['application_type']) . '</span>'],
                            ['Date of Absence', date('F j, Y', strtotime($application['application_date'])) . ($application['application_time'] ? ' at ' . date('g:i A', strtotime($application['application_time'])) : '')],
                            ['Submitted On', date('F j, Y g:i A', strtotime($application['submitted_at']))],
                            ['Certificate Type', $application['certificate_type'] === 'government' ? 'Government Hospital' : 'Private (Certified)', 'col-12'],
                            ['Reason', nl2br(htmlspecialchars($application['reason'])), 'col-12'],
                            ['Current Status', '<span class="badge ' . getStatusBadgeClass($application['status']) . '">' . formatStatus($application['status']) . '</span>']
                        ];
                        
                        foreach ($details as $detail) {
                            $colClass = $detail[2] ?? 'col-md-6';
                            echo "<div class='$colClass mb-3'><strong>{$detail[0]}:</strong><br>{$detail[1]}</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Documents Review -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i>Documents Review</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        echo renderDocumentCard('letter', $application['letter_file'], 'envelope', 'primary');
                        echo renderDocumentCard('application', $application['medical_application_file'], 'file-medical', 'warning');
                        echo renderDocumentCard('certificate', $application['medical_certificate_file'], 'certificate', 'success');
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Review Actions -->
            <?php if ($application['status'] === 'pending'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-check-circle me-2"></i>Review Actions</h5>
                    </div>
                    <div class="card-body">
                        <form action="process_applications.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <input type="hidden" name="action" value="review">
                            
                            <div class="form-group mb-3">
                                <label for="decision" class="form-label">Decision *</label>
                                <select class="form-select" id="decision" name="decision" required>
                                    <option value="">Select decision</option>
                                    <option value="approve">Approve and Forward to HOD</option>
                                    <option value="reject">Reject Application</option>
                                </select>
                                <div class="invalid-feedback">Please select a decision.</div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="comments" class="form-label">Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Add your review comments here..."></textarea>
                                <small class="form-text text-muted">Comments will be visible to the student and HOD.</small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-2"></i>Submit Review
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Review Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info alert-permanent">
                            <h6>Already Reviewed</h6>
                            <p class="mb-0">This application has been reviewed and is currently: <strong><?php echo formatStatus($application['status']); ?></strong></p>
                        </div>
                        
                        <?php if ($application['admin_comments']): ?>
                            <div class="alert alert-secondary alert-permanent mt-3">
                                <h6>Previous Comments:</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($application['admin_comments'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Application Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-timeline me-2"></i>Application Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php
                        echo renderTimelineItem('Application Submitted', $application['submitted_at']);
                        
                        if (in_array($application['status'], ['admin_reviewed', 'admin_rejected', 'hod_approved', 'hod_rejected', 'completed'])) {
                            echo renderTimelineItem('Admin Review', $application['reviewed_at']);
                        } else {
                            echo renderTimelineItem('Pending Admin Review', null, 'active');
                        }
                        
                        if (in_array($application['status'], ['hod_approved', 'hod_rejected', 'completed'])) {
                            echo renderTimelineItem('HOD Decision', $application['approved_at']);
                        } elseif ($application['status'] === 'admin_reviewed') {
                            echo renderTimelineItem('Pending HOD Review', null, 'active');
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Applications List -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Manage Applications</h2>
            <p class="text-muted mb-0">Review and process medical excuse applications</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Filter by Status</label>
                    <?php echo renderFilterSelect('status', [
                        '' => 'All Status',
                        'pending' => 'Pending Review',
                        'admin_reviewed' => 'Reviewed',
                        'admin_rejected' => 'Rejected',
                        'hod_approved' => 'Approved',
                        'completed' => 'Completed'
                    ], $filters['status'] ?? ''); ?>
                </div>
                
                <div class="col-md-3">
                    <label for="department" class="form-label">Filter by Department</label>
                    <?php echo renderFilterSelect('department', [
                        '' => 'All Departments',
                        'survey_geodesy' => 'Survey & Geodesy',
                        'remote_sensing_gis' => 'Remote Sensing & GIS'
                    ], $filters['department'] ?? ''); ?>
                </div>
                
                <div class="col-md-2">
                    <label for="sort" class="form-label">Sort By</label>
                    <?php echo renderFilterSelect('sort', [
                        'submitted_at' => 'Date Submitted',
                        'application_date' => 'Absence Date',
                        'status' => 'Status'
                    ], $filters['sort'] ?? ''); ?>
                </div>
                
                <div class="col-md-2">
                    <label for="order" class="form-label">Order</label>
                    <?php echo renderFilterSelect('order', [
                        'desc' => 'Newest First',
                        'asc' => 'Oldest First'
                    ], strtolower($filters['order'] ?? '')); ?>
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
                <i class="fas fa-list me-2"></i>Applications (<?php echo $totalApplications; ?> total)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No applications found with the current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th><th>Student</th><th>Course</th><th>Type</th>
                                <th>Absence Date</th><th>Submitted</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>#<?php echo $app['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($app['student_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo getDepartmentName($app['student_dept']); ?> - Year <?php echo $app['student_year']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($app['course_code']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['course_name']); ?></small>
                                    </td>
                                    <td><span class="badge badge-secondary"><?php echo ucfirst($app['application_type']); ?></span></td>
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
                                            <a href="manage_applications.php?id=<?php echo $app['id']; ?>" class="btn btn-primary btn-sm" title="Review">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($app['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-success btn-sm" onclick="quickApprove(<?php echo $app['id']; ?>)" title="Quick Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="quickReject(<?php echo $app['id']; ?>)" title="Quick Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($app['status'] === 'hod_approved'): ?>
                                                <a href="assign_lecturer.php?id=<?php echo $app['id']; ?>" class="btn btn-warning btn-sm" title="Assign to Lecturer">
                                                    <i class="fas fa-user-plus"></i>
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
                    <div class="d-flex justify-content-center mt-4">
                        <?php echo generatePagination($page, $totalPages, '?status=' . ($filters['status'] ?? '') . '&department=' . ($filters['department'] ?? '')); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Action Modal -->
<div class="modal fade" id="quickActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickActionTitle">Quick Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickActionForm" action="process_applications.php" method="POST">
                    <input type="hidden" id="quickApplicationId" name="application_id">
                    <input type="hidden" id="quickAction" name="action" value="review">
                    <input type="hidden" id="quickDecision" name="decision">
                    
                    <div class="form-group mb-3">
                        <label for="quickComments" class="form-label">Comments (Optional)</label>
                        <textarea class="form-control" id="quickComments" name="comments" rows="3" placeholder="Add your comments here..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="quickActionForm" class="btn btn-primary">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
function quickApprove(applicationId) {
    setupQuickAction(applicationId, 'approve', 'Quick Approve Application', 'Add approval comments (optional)...');
}

function quickReject(applicationId) {
    setupQuickAction(applicationId, 'reject', 'Quick Reject Application', 'Please provide reason for rejection...');
}

function setupQuickAction(id, decision, title, placeholder) {
    document.getElementById('quickApplicationId').value = id;
    document.getElementById('quickDecision').value = decision;
    document.getElementById('quickActionTitle').textContent = title;
    document.getElementById('quickComments').placeholder = placeholder;
    new bootstrap.Modal(document.getElementById('quickActionModal')).show();
}

// Auto-submit filters
document.addEventListener('DOMContentLoaded', function() {
    ['status', 'department', 'sort', 'order'].forEach(id => {
        const select = document.getElementById(id);
        if (select) select.addEventListener('change', () => select.form.submit());
    });
});
</script>

<style>
.timeline {
    position: relative;
    padding-left: 1rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
    border-left: 2px solid #e9ecef;
    padding-left: 1.5rem;
}

.timeline-item:last-child {
    border-left: none;
}

.timeline-marker {
    position: absolute;
    left: -6px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #6c757d;
    border: 2px solid #fff;
}

.timeline-item.completed .timeline-marker {
    background: #28a745;
}

.timeline-item.active .timeline-marker {
    background: #ffc107;
    animation: pulse 1.5s infinite;
}

.timeline-item.completed {
    border-left-color: #28a745;
}

.timeline-item.active {
    border-left-color: #ffc107;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>