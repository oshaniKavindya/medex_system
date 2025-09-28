<?php
$pageTitle = 'Manage Courses';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('admin');

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Extract filters
    $filters = [
        'department' => sanitize($_GET['department'] ?? ''),
        'year' => max(0, (int)($_GET['year'] ?? 0)),
        'page' => max(1, (int)($_GET['page'] ?? 1))
    ];
    
    $perPage = 20;
    $offset = ($filters['page'] - 1) * $perPage;
    
    // Build dynamic WHERE clause
    $whereConditions = ['1=1'];
    $params = [];
    
    foreach (['department', 'year'] as $field) {
        if (!empty($filters[$field])) {
            $whereConditions[] = "c.$field = ?";
            $params[] = $filters[$field];
        }
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Execute queries
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM courses c WHERE $whereClause");
    $countStmt->execute($params);
    $totalCourses = $countStmt->fetchColumn();
    $totalPages = ceil($totalCourses / $perPage);
    
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as created_by_name
        FROM courses c 
        LEFT JOIN users u ON c.created_by = u.id
        WHERE $whereClause
        ORDER BY c.department, c.year, c.course_code
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
    
    // Get course statistics
    $stmt = $pdo->prepare("
        SELECT department, COUNT(*) as course_count, COUNT(DISTINCT year) as year_count
        FROM courses GROUP BY department
    ");
    $stmt->execute();
    $courseStats = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in manage courses: " . $e->getMessage());
    $courses = [];
    $totalCourses = 0;
    $totalPages = 0;
    $courseStats = [];
}

// Helper functions
function renderSelect($name, $options, $selected = '', $placeholder = '') {
    $html = "<select name='$name' id='$name' class='form-select'>";
    if ($placeholder) $html .= "<option value=''>$placeholder</option>";
    
    foreach ($options as $value => $label) {
        $sel = (string)$selected === (string)$value ? 'selected' : '';
        $html .= "<option value='$value' $sel>$label</option>";
    }
    return $html . "</select>";
}

function renderStatCard($stat) {
    return "
        <div class='d-flex justify-content-between align-items-center mb-3'>
            <div>
                <strong>" . getDepartmentName($stat['department']) . "</strong><br>
                <small class='text-muted'>{$stat['year_count']} years covered</small>
            </div>
            <div>
                <span class='badge badge-primary'>{$stat['course_count']} courses</span>
            </div>
        </div>";
}

function formatDescription($desc, $limit = 50) {
    if (!$desc) return '';
    return '<br><small class="text-muted">' . htmlspecialchars(substr($desc, 0, $limit)) . (strlen($desc) > $limit ? '...' : '') . '</small>';
}

$departmentOptions = [
    '' => 'All Departments',
    'survey_geodesy' => 'Survey & Geodesy',
    'remote_sensing_gis' => 'Remote Sensing & GIS'
];

$yearOptions = [
    '' => 'All Years',
    '1' => '1st Year',
    '2' => '2nd Year', 
    '3' => '3rd Year',
    '4' => '4th Year'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Manage Courses</h2>
        <p class="text-muted mb-0">Add and manage courses for the medical excuse system</p>
    </div>
    <div>
        <a href="add_course.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Course
        </a>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
    </div>
</div>

<!-- Course Statistics -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Course Statistics
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($courseStats)): ?>
                    <p class="text-muted">No courses available yet.</p>
                <?php else: ?>
                    <?php foreach ($courseStats as $stat) echo renderStatCard($stat); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Quick Stats
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h3 class="text-primary"><?php echo $totalCourses; ?></h3>
                        <small class="text-muted">Total Courses</small>
                    </div>
                    <div class="col-6">
                        <h3 class="text-success"><?php echo count($courseStats); ?></h3>
                        <small class="text-muted">Departments</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="department" class="form-label">Filter by Department</label>
                <?php echo renderSelect('department', $departmentOptions, $filters['department']); ?>
            </div>
            
            <div class="col-md-3">
                <label for="year" class="form-label">Filter by Year</label>
                <?php echo renderSelect('year', $yearOptions, $filters['year']); ?>
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
                <a href="manage_courses.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
            
            <div class="col-md-2">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search courses...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Courses Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-book me-2"></i>Courses (<?php echo $totalCourses; ?> total)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($courses)): ?>
            <div class="text-center py-4">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <p class="text-muted">No courses found with the current filters.</p>
                <a href="add_course.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Your First Course
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="coursesTable">
                    <thead>
                        <tr>
                            <th>Course Code</th><th>Course Name</th><th>Department</th><th>Year</th>
                            <th>Submission End</th><th>Created By</th><th>Created</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($course['course_name']) . formatDescription($course['description']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $course['department'] === 'survey_geodesy' ? 'primary' : 'info'; ?>">
                                        <?php echo getDepartmentName($course['department']); ?>
                                    </span>
                                </td>
                                <td><span class="badge badge-secondary">Year <?php echo $course['year']; ?></span></td>
                                <td>
                                    <?php if ($course['submission_end_date']): 
                                        $isExpired = strtotime($course['submission_end_date']) < time();
                                        $color = $isExpired ? 'danger' : 'success'; ?>
                                        <small class="text-<?php echo $color; ?>">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('M j, Y', strtotime($course['submission_end_date'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">No deadline</small>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($course['created_by_name'] ?: 'Unknown'); ?></small></td>
                                <td><small class="text-muted"><?php echo date('M j, Y', strtotime($course['created_at'])); ?></small></td>
                                <td>
                                    <div class="btn-group" role="group">
                                       
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="viewCourse(<?php echo $course['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
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
                    <?php echo generatePagination($filters['page'], $totalPages, '?department=' . $filters['department'] . '&year=' . $filters['year']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- View Course Modal -->
<div class="modal fade" id="viewCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Course Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="courseDetailsBody">
                <!-- Course details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('#coursesTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
    });
});

// Auto-submit filters
document.addEventListener('DOMContentLoaded', function() {
    ['department', 'year'].forEach(id => {
        const select = document.getElementById(id);
        if (select) select.addEventListener('change', () => select.form.submit());
    });
});



function viewCourse(courseId) {
    fetchCourseData(courseId, course => {
        document.getElementById('courseDetailsBody').innerHTML = generateCourseDetails(course);
        new bootstrap.Modal(document.getElementById('viewCourseModal')).show();
    });
}

function fetchCourseData(courseId, callback) {
    fetch(`../includes/get_course_details.php?id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                callback(data.course);
            } else {
                showAlert('Error loading course details', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading course details', 'danger');
        });
}

function generateCourseDetails(course) {
    const details = [
        ['Course Code', course.course_code],
        ['Course Name', course.course_name],
        ['Department', course.department === 'survey_geodesy' ? 'Survey & Geodesy' : 'Remote Sensing & GIS'],
        ['Academic Year', `Year ${course.year}`],
        ['Submission End Date', course.submission_end_date ? new Date(course.submission_end_date).toLocaleDateString() : 'No deadline set'],
        ['Description', course.description || 'No description available'],
        ['Created', new Date(course.created_at).toLocaleDateString()]
    ];

    return details.map(([label, value]) => `
        <div class="row">
            <div class="col-sm-4"><strong>${label}:</strong></div>
            <div class="col-sm-8">${value}</div>
        </div>
        <hr>
    `).join('');
}



// Utility functions
function showAlert(message, type) {
    // Remove any existing alerts
    const existingAlert = document.querySelector('.alert-dismissible');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert-dismissible');
        if (alert) alert.remove();
    }, 5000);
}

function processRequest(formData, successMessage) {
    fetch('process_course.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    });
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>