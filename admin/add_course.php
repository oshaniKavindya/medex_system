<?php
$pageTitle = 'Add New Course';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('admin');

$user = getCurrentUser();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Course
                </h3>
                <p class="mb-0 text-muted">Add a new course to the medical excuse management system</p>
            </div>
            <div class="card-body p-4">
                <form action="process_course.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="course_code" class="form-label">
                                    <i class="fas fa-barcode me-1"></i>Course Code *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="course_code" 
                                       name="course_code" 
                                       placeholder="e.g., SG101, RS201"
                                       pattern="[A-Z]{2,4}[0-9]{3}"
                                       title="Course code should be 2-4 letters followed by 3 numbers"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter a valid course code (e.g., SG101).
                                </div>
                                <small class="form-text text-muted">
                                    Format: 2-4 letters followed by 3 numbers (e.g., SG101, GEOM201)
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="course_name" class="form-label">
                                    <i class="fas fa-book me-1"></i>Course Name *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="course_name" 
                                       name="course_name" 
                                       placeholder="e.g., Introduction to Surveying"
                                       maxlength="100"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter the course name.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="department" class="form-label">
                                    <i class="fas fa-building me-1"></i>Department *
                                </label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="survey_geodesy">Survey & Geodesy</option>
                                    <option value="remote_sensing_gis">Remote Sensing & GIS</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a department.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="year" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Academic Year *
                                </label>
                                <select class="form-select" id="year" name="year" required>
                                    <option value="">Select Academic Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select the academic year.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="description" class="form-label">
                            <i class="fas fa-file-alt me-1"></i>Course Description
                        </label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="4"
                                  placeholder="Provide a brief description of the course content and objectives..."
                                  maxlength="500"></textarea>
                        <div class="form-text text-muted">
                            Optional. Maximum 500 characters. <span id="charCount">0/500</span>
                        </div>
                    </div>
                    
                    <!-- Course Preview -->
                    <div class="card mb-4" style="display: none;" id="coursePreview">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-eye me-2"></i>
                                Course Preview
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Code:</strong><br>
                                    <span id="previewCode" class="text-primary"></span>
                                </div>
                                <div class="col-md-5">
                                    <strong>Name:</strong><br>
                                    <span id="previewName"></span>
                                </div>
                                <div class="col-md-2">
                                    <strong>Year:</strong><br>
                                    <span id="previewYear" class="badge badge-secondary"></span>
                                </div>
                                <div class="col-md-2">
                                    <strong>Department:</strong><br>
                                    <span id="previewDept" class="badge badge-primary"></span>
                                </div>
                            </div>
                            <div class="mt-3" id="previewDescContainer" style="display: none;">
                                <strong>Description:</strong><br>
                                <span id="previewDesc" class="text-muted"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Terms and Guidelines -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Course Guidelines</h6>
                        <ul class="mb-0 small">
                            <li>Course codes must be unique within the system</li>
                            <li>Use standard departmental prefixes (SG for Survey & Geodesy, RS/GIS for Remote Sensing & GIS)</li>
                            <li>Course numbers typically indicate academic year and sequence</li>
                            <li>Ensure course information is accurate as it will be used for medical excuse applications</li>
                        </ul>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                        <label class="form-check-label" for="confirm">
                            I confirm that the course information is accurate and the course code is unique. *
                        </label>
                        <div class="invalid-feedback">
                            You must confirm the course information is accurate.
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="manage_courses.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Back to Courses
                        </a>
                        <div>
                            <button type="reset" class="btn btn-outline-warning me-2">
                                <i class="fas fa-undo me-2"></i>
                                Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>
                                Add Course
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Reference -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Course Code Examples
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Survey & Geodesy Department</h6>
                        <ul class="list-unstyled small">
                            <li><code>SG101</code> - Introduction to Surveying</li>
                            <li><code>SG201</code> - Advanced Surveying Techniques</li>
                            <li><code>GEOD301</code> - Geodetic Measurements</li>
                            <li><code>CART401</code> - Digital Cartography</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Remote Sensing & GIS Department</h6>
                        <ul class="list-unstyled small">
                            <li><code>RS101</code> - Introduction to Remote Sensing</li>
                            <li><code>GIS201</code> - GIS Fundamentals</li>
                            <li><code>RS301</code> - Advanced Image Processing</li>
                            <li><code>GIS401</code> - Spatial Analysis</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character count for description
document.getElementById('description').addEventListener('input', function() {
    const maxLength = 500;
    const currentLength = this.value.length;
    document.getElementById('charCount').textContent = currentLength + '/' + maxLength;
    
    if (currentLength > maxLength * 0.9) {
        document.getElementById('charCount').style.color = '#dc3545';
    } else {
        document.getElementById('charCount').style.color = '#6c757d';
    }
});

// Course code validation and formatting
document.getElementById('course_code').addEventListener('input', function() {
    // Convert to uppercase
    this.value = this.value.toUpperCase();
    
    // Remove any characters that don't match the pattern
    this.value = this.value.replace(/[^A-Z0-9]/g, '');
    
    updatePreview();
});

// Real-time preview
function updatePreview() {
    const code = document.getElementById('course_code').value;
    const name = document.getElementById('course_name').value;
    const dept = document.getElementById('department').value;
    const year = document.getElementById('year').value;
    const desc = document.getElementById('description').value;
    
    if (code || name || dept || year) {
        document.getElementById('coursePreview').style.display = 'block';
        
        document.getElementById('previewCode').textContent = code || '[Course Code]';
        document.getElementById('previewName').textContent = name || '[Course Name]';
        
        if (year) {
            document.getElementById('previewYear').textContent = 'Year ' + year;
        } else {
            document.getElementById('previewYear').textContent = '[Year]';
        }
        
        if (dept) {
            const deptName = dept === 'survey_geodesy' ? 'Survey & Geodesy' : 'Remote Sensing & GIS';
            document.getElementById('previewDept').textContent = deptName;
            document.getElementById('previewDept').className = 'badge badge-' + (dept === 'survey_geodesy' ? 'primary' : 'info');
        } else {
            document.getElementById('previewDept').textContent = '[Department]';
            document.getElementById('previewDept').className = 'badge badge-secondary';
        }
        
        if (desc.trim()) {
            document.getElementById('previewDescContainer').style.display = 'block';
            document.getElementById('previewDesc').textContent = desc;
        } else {
            document.getElementById('previewDescContainer').style.display = 'none';
        }
    } else {
        document.getElementById('coursePreview').style.display = 'none';
    }
}

// Add event listeners for preview updates
['course_name', 'department', 'year', 'description'].forEach(id => {
    document.getElementById(id).addEventListener('input', updatePreview);
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    } else {
        showLoading(true);
        
        // Disable submit button to prevent double submission
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding Course...';
    }
    
    this.classList.add('was-validated');
});

// Reset form handler
document.querySelector('button[type="reset"]').addEventListener('click', function() {
    setTimeout(() => {
        document.getElementById('coursePreview').style.display = 'none';
        document.getElementById('charCount').textContent = '0/500';
        document.getElementById('charCount').style.color = '#6c757d';
        document.querySelector('form').classList.remove('was-validated');
    }, 100);
});

// Course code suggestions based on department and year
document.getElementById('department').addEventListener('change', function() {
    const year = document.getElementById('year').value;
    if (this.value && year) {
        suggestCourseCode();
    }
});

document.getElementById('year').addEventListener('change', function() {
    const dept = document.getElementById('department').value;
    if (this.value && dept) {
        suggestCourseCode();
    }
});

function suggestCourseCode() {
    const dept = document.getElementById('department').value;
    const year = document.getElementById('year').value;
    const courseCodeInput = document.getElementById('course_code');
    
    if (!courseCodeInput.value && dept && year) {
        let prefix = '';
        if (dept === 'survey_geodesy') {
            prefix = 'SG';
        } else if (dept === 'remote_sensing_gis') {
            prefix = year <= 2 ? 'RS' : 'GIS'; // RS for lower years, GIS for upper years
        }
        
        if (prefix) {
            const suggestedCode = prefix + year + '01';
            courseCodeInput.placeholder = 'e.g., ' + suggestedCode;
        }
    }
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>