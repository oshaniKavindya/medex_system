<?php
$pageTitle = 'Submit Medical Application';
require_once '../includes/header.php';

requireRole('student');

$user = getCurrentUser();

// Get available courses for the student's department and year
$courses = getCoursesByDepartmentYear($user['department'], $user['year']);
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    Submit Medical Excuse Application
                </h3>
                <p class="mb-0 text-muted">Fill in all required information and upload the necessary documents</p>
            </div>
            <div class="card-body">
                <form action="process_submission.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <!-- Application Details -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Application Details
                            </h5>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="course_id" class="form-label">Course *</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a course.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="application_type" class="form-label">Absence Type *</label>
                                <select class="form-select" id="application_type" name="application_type" required>
                                    <option value="">Select type</option>
                                    <option value="lecture">Lecture</option>
                                    <option value="practical">Practical</option>
                                    <option value="ca">Continuous Assessment (CA)</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select the type of absence.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="application_date" class="form-label">Date of Absence *</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="application_date" 
                                       name="application_date" 
                                       max="<?php echo date('Y-m-d'); ?>"
                                       min="<?php echo date('Y-m-d', strtotime('-14 days')); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter the date of absence (within last 14 days).
                                </div>
                                <small class="form-text text-muted">
                                    Applications must be submitted within 14 days of absence.
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="application_time" class="form-label">Time (if applicable)</label>
                                <input type="time" 
                                       class="form-control" 
                                       id="application_time" 
                                       name="application_time">
                                <small class="form-text text-muted">
                                    Enter time for practicals or specific sessions.
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group mb-3">
                                <label for="reason" class="form-label">Reason for Absence *</label>
                                <textarea class="form-control" 
                                          id="reason" 
                                          name="reason" 
                                          rows="3"
                                          placeholder="Briefly describe the medical reason for your absence"
                                          required></textarea>
                                <div class="invalid-feedback">
                                    Please provide a reason for the absence.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group mb-3">
                                <label for="certificate_type" class="form-label">Medical Certificate Type *</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="certificate_type" 
                                           id="government" value="government" required>
                                    <label class="form-check-label" for="government">
                                        Government Hospital Certificate
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="certificate_type" 
                                           id="private_certified" value="private_certified" required>
                                    <label class="form-check-label" for="private_certified">
                                        Private Medical Center (Certified by University Medical Center)
                                    </label>
                                </div>
                                <div class="invalid-feedback">
                                    Please select the type of medical certificate.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Document Upload -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-upload me-2"></i>
                                Document Upload
                            </h5>
                            <p class="text-muted">
                                All documents must be in PDF, JPG, or PNG format (max 5MB each).
                            </p>
                        </div>
                        
                        <!-- Letter Upload -->
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                    <h6>Letter *</h6>
                                    <div class="file-upload">
                                        <input type="file" 
                                               name="letter_file" 
                                               id="letter_file" 
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               required>
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <p>Click to upload letter</p>
                                    </div>
                                    <div id="letter_file_preview" class="mt-2"></div>
                                    <div class="invalid-feedback">
                                        Please upload the letter document.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Application Upload -->
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-medical fa-3x text-warning mb-3"></i>
                                    <h6>Medical Application *</h6>
                                    <div class="file-upload">
                                        <input type="file" 
                                               name="medical_application_file" 
                                               id="medical_application_file" 
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               required>
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <p>Click to upload medical application</p>
                                    </div>
                                    <div id="medical_application_file_preview" class="mt-2"></div>
                                    <div class="invalid-feedback">
                                        Please upload the medical application form.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Certificate Upload -->
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-certificate fa-3x text-success mb-3"></i>
                                    <h6>Medical Certificate *</h6>
                                    <div class="file-upload">
                                        <input type="file" 
                                               name="medical_certificate_file" 
                                               id="medical_certificate_file" 
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               required>
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <p>Click to upload medical certificate</p>
                                    </div>
                                    <div id="medical_certificate_file_preview" class="mt-2"></div>
                                    <div class="invalid-feedback">
                                        Please upload the medical certificate.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="declaration" name="declaration" required>
                                <label class="form-check-label" for="declaration">
                                    I hereby declare that all the information provided and documents uploaded are true and accurate. 
                                    I understand that submitting false information may result in disciplinary action. *
                                </label>
                                <div class="invalid-feedback">
                                    You must agree to this declaration.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Submit Application
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Information Sidebar -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Important Guidelines
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-clock text-warning me-2"></i>Time Limits</h6>
                        <ul class="list-unstyled small">
                            <li>• Submit within 14 days of absence</li>
                            <li>• Processing takes 3-5 business days</li>
                            <li>• Check status regularly for updates</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-file-alt text-info me-2"></i>Document Requirements</h6>
                        <ul class="list-unstyled small">
                            <li>• All documents must be clear and readable</li>
                            <li>• Maximum file size: 5MB per document</li>
                            <li>• Accepted formats: PDF, JPG, PNG</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation and file upload handling
document.addEventListener('DOMContentLoaded', function() {
    // File upload preview functionality
    const fileInputs = ['letter_file', 'medical_application_file', 'medical_certificate_file'];
    
    fileInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(inputId + '_preview');
        
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('File size too large. Maximum 5MB allowed.', 'danger');
                    input.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                // Update preview
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" class="file-preview" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = `
                        <div class="alert alert-info alert-sm">
                            <i class="fas fa-file-pdf me-2"></i>
                            ${file.name}
                        </div>
                    `;
                }
                
                // Update upload area
                const uploadArea = input.closest('.file-upload');
                uploadArea.classList.add('file-selected');
                uploadArea.querySelector('p').textContent = file.name;
            }
        });
    });
    
    // Form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        } else {
            // Show loading
            showLoading(true);
            
            // Disable submit button to prevent double submission
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        }
        
        form.classList.add('was-validated');
    });
    
    // Date validation
    const dateInput = document.getElementById('application_date');
    dateInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        const fourteenDaysAgo = new Date(today.getTime() - (14 * 24 * 60 * 60 * 1000));
        
        if (selectedDate < fourteenDaysAgo) {
            showAlert('Applications must be submitted within 14 days of absence.', 'warning');
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>