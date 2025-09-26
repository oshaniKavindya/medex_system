<?php
$pageTitle = 'Assign to Lecturer';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('admin');

$user = getCurrentUser();
$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$application_id) {
    $_SESSION['error_message'] = 'Invalid application ID.';
    header('Location: manage_applications.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code, c.department as course_dept, c.year as course_year,
               u.full_name as student_name, u.department as student_dept, u.year as student_year,
               hod.full_name as hod_name
        FROM applications a 
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON a.student_id = u.id
        LEFT JOIN users hod ON a.hod_reviewed_by = hod.id
        WHERE a.id = ? AND a.status = 'hod_approved'
    ");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();
    
    if (!$application) {
        $_SESSION['error_message'] = 'Application not found or not ready for lecturer assignment.';
        header('Location: manage_applications.php');
        exit();
    }
    
    // Get all active lecturers (not department-specific as per requirement)
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, department 
        FROM users 
        WHERE role = 'lecturer' AND status = 'active' 
        ORDER BY department, full_name
    ");
    $stmt->execute();
    $lecturers = $stmt->fetchAll();
    
    // Check if already assigned to any lecturers
    $stmt = $pdo->prepare("
        SELECT ln.*, u.full_name as lecturer_name 
        FROM lecturer_notifications ln
        JOIN users u ON ln.lecturer_id = u.id
        WHERE ln.application_id = ?
        ORDER BY ln.notified_at DESC
    ");
    $stmt->execute([$application_id]);
    $existing_assignments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in assign lecturer: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error loading application details.';
    header('Location: manage_applications.php');
    exit();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Assign to Lecturer</h2>
        <p class="text-muted mb-0">Application ID: #<?php echo $application['id']; ?></p>
    </div>
    <div>
        <a href="manage_applications.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Applications
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Application Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Application Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Student:</strong><br>
                        <?php echo htmlspecialchars($application['student_name']); ?><br>
                        <small class="text-muted">
                            <?php echo getDepartmentName($application['student_dept']); ?> - Year <?php echo $application['student_year']; ?>
                        </small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Course:</strong><br>
                        <?php echo htmlspecialchars($application['course_code'] . ' - ' . $application['course_name']); ?><br>
                        <small class="text-muted">Year <?php echo $application['course_year']; ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Absence Type:</strong><br>
                        <span class="badge badge-secondary"><?php echo ucfirst($application['application_type']); ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Absence Date:</strong><br>
                        <?php echo date('F j, Y', strtotime($application['application_date'])); ?>
                        <?php if ($application['application_time']): ?>
                            at <?php echo date('g:i A', strtotime($application['application_time'])); ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 mb-3">
                        <strong>Reason:</strong><br>
                        <?php echo nl2br(htmlspecialchars($application['reason'])); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Approved by:</strong><br>
                        <?php echo htmlspecialchars($application['hod_name']); ?> (HOD)<br>
                        <small class="text-muted"><?php echo date('F j, Y g:i A', strtotime($application['approved_at'])); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Assignment Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Assign to Lecturer(s)
                </h5>
            </div>
            <div class="card-body">
                <form action="process_lecturer_assignment.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                    
                    <div class="form-group mb-4">
                        <label for="lecturers" class="form-label">
                            <i class="fas fa-chalkboard-teacher me-1"></i>Select Lecturer(s) *
                        </label>
                        <select class="form-select" id="lecturers" name="lecturers[]" multiple required>
                            <?php 
                            $currentDept = '';
                            foreach ($lecturers as $lecturer): 
                                if ($currentDept !== $lecturer['department']):
                                    if ($currentDept !== '') echo '</optgroup>';
                                    $currentDept = $lecturer['department'];
                                    echo '<optgroup label="' . getDepartmentName($lecturer['department']) . '">';
                                endif;
                            ?>
                                <option value="<?php echo $lecturer['id']; ?>">
                                    <?php echo htmlspecialchars($lecturer['full_name']); ?>
                                    <small>(<?php echo htmlspecialchars($lecturer['email']); ?>)</small>
                                </option>
                            <?php 
                            endforeach; 
                            if ($currentDept !== '') echo '</optgroup>';
                            ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select at least one lecturer.
                        </div>
                        <small class="form-text text-muted">
                            Hold Ctrl (Windows) or Cmd (Mac) to select multiple lecturers. You can assign to lecturers from any department.
                        </small>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="message" class="form-label">
                            <i class="fas fa-comment me-1"></i>Message to Lecturer(s)
                        </label>
                        <textarea class="form-control" id="message" name="message" rows="4"
                                  placeholder="Optional message to include with the notification..."><?php 
                            echo "Medical excuse approved for " . htmlspecialchars($application['student_name']) . 
                                 " (" . getDepartmentName($application['student_dept']) . " - Year " . $application['student_year'] . ") " .
                                 "for " . htmlspecialchars($application['course_code']) . " - " . htmlspecialchars($application['course_name']) . ". " .
                                 "Student was absent from " . ucfirst($application['application_type']) . " on " . 
                                 date('F j, Y', strtotime($application['application_date'])) . " due to medical reasons.";
                        ?></textarea>
                        <small class="form-text text-muted">
                            This message will be sent along with the application details.
                        </small>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Assignment Guidelines</h6>
                        <ul class="mb-0">
                            <li>Select lecturers who teach the specific course or are relevant to this application</li>
                            <li>You can assign to lecturers from any department</li>
                            <li>Selected lecturers will receive notifications and can view the full application details</li>
                            <li>The application status will be updated to "Completed" after assignment</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="manage_applications.php?id=<?php echo $application_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>
                            Assign to Selected Lecturer(s)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Previous Assignments -->
        <?php if (!empty($existing_assignments)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Previous Assignments
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($existing_assignments as $assignment): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                        <div>
                            <strong><?php echo htmlspecialchars($assignment['lecturer_name']); ?></strong><br>
                            <small class="text-muted">
                                Assigned: <?php echo date('M j, Y g:i A', strtotime($assignment['notified_at'])); ?>
                            </small>
                            <?php if ($assignment['is_acknowledged']): ?>
                                <br><small class="text-success">
                                    <i class="fas fa-check-circle"></i> Acknowledged
                                </small>
                            <?php endif; ?>
                        </div>
                        <span class="badge <?php echo $assignment['is_acknowledged'] ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $assignment['is_acknowledged'] ? 'Seen' : 'Pending'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                
                <div class="alert alert-warning">
                    <small><i class="fas fa-exclamation-triangle me-1"></i> 
                    This application was already assigned. Assigning again will send additional notifications.</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Lecturer Distribution -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>
                    Available Lecturers
                </h5>
            </div>
            <div class="card-body">
                <?php 
                $dept_count = [];
                foreach ($lecturers as $lecturer) {
                    $dept_count[$lecturer['department']] = ($dept_count[$lecturer['department']] ?? 0) + 1;
                }
                ?>
                <?php foreach ($dept_count as $dept => $count): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><?php echo getDepartmentName($dept); ?></span>
                        <span class="badge badge-primary"><?php echo $count; ?> lecturers</span>
                    </div>
                <?php endforeach; ?>
                
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <strong>Total Available</strong>
                    <span class="badge badge-success"><?php echo count($lecturers); ?></span>
                </div>
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
                    <button type="button" class="btn btn-outline-info" onclick="selectRecommended()">
                        <i class="fas fa-magic me-2"></i>
                        Select Recommended
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="selectDepartment('<?php echo $application['course_dept']; ?>')">
                        <i class="fas fa-building me-2"></i>
                        Select Course Department
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                        <i class="fas fa-times me-2"></i>
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced select functionality
function selectRecommended() {
    const courseId = <?php echo $application['course_id']; ?>;
    const courseDept = '<?php echo $application['course_dept']; ?>';
    
    // Select lecturers from the same department as the course
    selectDepartment(courseDept);
}

function selectDepartment(department) {
    const select = document.getElementById('lecturers');
    const options = select.options;
    
    // Clear previous selection
    for (let i = 0; i < options.length; i++) {
        options[i].selected = false;
    }
    
    // Select lecturers from specified department
    const lecturers = <?php echo json_encode($lecturers); ?>;
    lecturers.forEach(lecturer => {
        if (lecturer.department === department) {
            const option = select.querySelector(`option[value="${lecturer.id}"]`);
            if (option) option.selected = true;
        }
    });
    
    updateSelectionInfo();
}

function clearSelection() {
    const select = document.getElementById('lecturers');
    const options = select.options;
    
    for (let i = 0; i < options.length; i++) {
        options[i].selected = false;
    }
    
    updateSelectionInfo();
}

function updateSelectionInfo() {
    const select = document.getElementById('lecturers');
    const selectedCount = select.selectedOptions.length;
    
    // You can add visual feedback here if needed
}

// Form validation and submission
document.querySelector('form').addEventListener('submit', function(e) {
    const selectedLecturers = document.getElementById('lecturers').selectedOptions.length;
    
    if (selectedLecturers === 0) {
        e.preventDefault();
        e.stopPropagation();
        showAlert('Please select at least one lecturer.', 'danger');
        return false;
    }
    
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    } else {
        showLoading(true);
        
        // Disable submit button to prevent double submission
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Assigning...';
        
        // Confirmation
        const confirmMsg = `Are you sure you want to assign this application to ${selectedLecturers} lecturer(s)?`;
        if (!confirm(confirmMsg)) {
            e.preventDefault();
            showLoading(false);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Assign to Selected Lecturer(s)';
            return false;
        }
    }
    
    this.classList.add('was-validated');
});

// Update selection info on change
document.getElementById('lecturers').addEventListener('change', updateSelectionInfo);
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>