<?php
$pageTitle = 'Manage Courses';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('admin');

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Handle filters
    $departmentFilter = isset($_GET['department']) ? sanitize($_GET['department']) : '';
    $yearFilter = isset($_GET['year']) ? (int)$_GET['year'] : 0;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // Build WHERE conditions
    $whereConditions = ['1=1'];
    $params = [];
    
    if (!empty($departmentFilter)) {
        $whereConditions[] = "department = ?";
        $params[] = $departmentFilter;
    }
    
    if (!empty($yearFilter)) {
        $whereConditions[] = "year = ?";
        $params[] = $yearFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE $whereClause");
    $countStmt->execute($params);
    $totalCourses = $countStmt->fetchColumn();
    $totalPages = ceil($totalCourses / $perPage);
    
    // Get courses
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as created_by_name
        FROM courses c 
        LEFT JOIN users u ON c.created_by = u.id
        WHERE $whereClause
        ORDER BY c.department, c.year, c.course_code
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $courses = $stmt->fetchAll();
    
    // Get course statistics
    $stmt = $pdo->prepare("
        SELECT 
            department,
            COUNT(*) as course_count,
            COUNT(DISTINCT year) as year_count
        FROM courses 
        GROUP BY department
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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Manage Courses</h2>
        <p class="text-muted mb-0">Add and manage courses for the medical excuse system</p>
    </div>
    <div>
        <a href="add_course.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Add New Course
        </a>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-tachometer-alt me-2"></i>
            Dashboard
        </a>
    </div>
</div>

<!-- Course Statistics -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Course Statistics
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($courseStats)): ?>
                    <p class="text-muted">No courses available yet.</p>
                <?php else: ?>
                    <?php foreach ($courseStats as $stat): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong><?php echo getDepartmentName($stat['department']); ?></strong><br>
                                <small class="text-muted"><?php echo $stat['year_count']; ?> years covered</small>
                            </div>
                            <div>
                                <span class="badge badge-primary"><?php echo $stat['course_count']; ?> courses</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Quick Stats
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
                <select name="department" id="department" class="form-select">
                    <option value="">All Departments</option>
                    <option value="survey_geodesy" <?php echo $departmentFilter === 'survey_geodesy' ? 'selected' : ''; ?>>Survey & Geodesy</option>
                    <option value="remote_sensing_gis" <?php echo $departmentFilter === 'remote_sensing_gis' ? 'selected' : ''; ?>>Remote Sensing & GIS</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="year" class="form-label">Filter by Year</label>
                <select name="year" id="year" class="form-select">
                    <option value="">All Years</option>
                    <option value="1" <?php echo $yearFilter === 1 ? 'selected' : ''; ?>>1st Year</option>
                    <option value="2" <?php echo $yearFilter === 2 ? 'selected' : ''; ?>>2nd Year</option>
                    <option value="3" <?php echo $yearFilter === 3 ? 'selected' : ''; ?>>3rd Year</option>
                    <option value="4" <?php echo $yearFilter === 4 ? 'selected' : ''; ?>>4th Year</option>
                </select>
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
            <i class="fas fa-book me-2"></i>
            Courses (<?php echo $totalCourses; ?> total)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($courses)): ?>
            <div class="text-center py-4">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <p class="text-muted">No courses found with the current filters.</p>
                <a href="add_course.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Add Your First Course
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="coursesTable">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($course['course_code']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                    <?php if ($course['description']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($course['description'], 0, 50)) . (strlen($course['description']) > 50 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $course['department'] === 'survey_geodesy' ? 'primary' : 'info'; ?>">
                                        <?php echo getDepartmentName($course['department']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">Year <?php echo $course['year']; ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($course['created_by_name'] ?: 'Unknown'); ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="editCourse(<?php echo $course['id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" 
                                                onclick="viewCourse(<?php echo $course['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo addslashes($course['course_code']); ?>')" 
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
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
                    <?php echo generatePagination($page, $totalPages, '?department=' . $departmentFilter . '&year=' . $yearFilter); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCourseForm">
                    <input type="hidden" id="editCourseId" name="course_id">
                    <input type="hidden" name="action" value="edit">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editCourseCode" class="form-label">Course Code *</label>
                                <input type="text" class="form-control" id="editCourseCode" name="course_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editCourseName" class="form-label">Course Name *</label>
                                <input type="text" class="form-control" id="editCourseName" name="course_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editDepartment" class="form-label">Department *</label>
                                <select class="form-select" id="editDepartment" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="survey_geodesy">Survey & Geodesy</option>
                                    <option value="remote_sensing_gis">Remote Sensing & GIS</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editYear" class="form-label">Academic Year *</label>
                                <select class="form-select" id="editYear" name="year" required>
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"
                                  placeholder="Brief description of the course"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateCourse()">Update Course</button>
            </div>
        </div>
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
    const tableRows = document.querySelectorAll('#coursesTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Auto-submit filters
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = ['department', 'year'];
    
    filterSelects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        }
    });
});

// Course management functions
function editCourse(courseId) {
    // Fetch course details and populate modal
    fetch(`../includes/get_course_details.php?id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const course = data.course;
                document.getElementById('editCourseId').value = course.id;
                document.getElementById('editCourseCode').value = course.course_code;
                document.getElementById('editCourseName').value = course.course_name;
                document.getElementById('editDepartment').value = course.department;
                document.getElementById('editYear').value = course.year;
                document.getElementById('editDescription').value = course.description || '';
                
                const modal = new bootstrap.Modal(document.getElementById('editCourseModal'));
                modal.show();
            } else {
                showAlert('Error loading course details', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading course details', 'danger');
        });
}

function updateCourse() {
    const form = document.getElementById('editCourseForm');
    const formData = new FormData(form);
    
    showLoading(true);
    
    fetch('process_course.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('editCourseModal'));
        modal.hide();
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    });
}

function viewCourse(courseId) {
    // Fetch and display course details
    fetch(`../includes/get_course_details.php?id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const course = data.course;
                const detailsHtml = `
                    <div class="row">
                        <div class="col-sm-4"><strong>Course Code:</strong></div>
                        <div class="col-sm-8">${course.course_name}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4"><strong>Department:</strong></div>
                        <div class="col-sm-8">${course.department === 'survey_geodesy' ? 'Survey & Geodesy' : 'Remote Sensing & GIS'}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4"><strong>Academic Year:</strong></div>
                        <div class="col-sm-8">Year ${course.year}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4"><strong>Description:</strong></div>
                        <div class="col-sm-8">${course.description || 'No description available'}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4"><strong>Created:</strong></div>
                        <div class="col-sm-8">${new Date(course.created_at).toLocaleDateString()}</div>
                    </div>
                `;
                
                document.getElementById('courseDetailsBody').innerHTML = detailsHtml;
                
                const modal = new bootstrap.Modal(document.getElementById('viewCourseModal'));
                modal.show();
            } else {
                showAlert('Error loading course details', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading course details', 'danger');
        });
}

function deleteCourse(courseId, courseCode) {
    if (confirm(`Are you sure you want to delete course "${courseCode}"? This action cannot be undone.`)) {
        showLoading(true);
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('course_id', courseId);
        
        fetch('process_course.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        });
    }
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>