<?php
$pageTitle = 'Register';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card">
            <div class="card-header text-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Create New Account
                </h3>
                <p class="mb-0 text-muted">Join the Medical Excuse Management System</p>
            </div>
            <div class="card-body p-4">
                <form action="process_auth.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="register">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="full_name" class="form-label">
                                    <i class="fas fa-id-card me-1"></i>Full Name *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="full_name" 
                                       name="full_name" 
                                       placeholder="Enter your full name"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter your full name.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-1"></i>Username *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Choose a username"
                                       required>
                                <div class="invalid-feedback">
                                    Please choose a username.
                                </div>
                                <small class="form-text text-muted">
                                    Username must be unique and contain only letters, numbers, and underscores.
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email Address *
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="your.email@domain.com"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="role" class="form-label">
                                    <i class="fas fa-user-tag me-1"></i>Role *
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select your role</option>
                                    <option value="student">Student</option>
                                    <option value="lecturer">Lecturer</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select your role.
                                </div>
                                <small class="form-text text-muted">
                                    Admin and HOD accounts are created by system administrators.
                                </small>
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
                                    <option value="">Select department</option>
                                    <option value="survey_geodesy">Survey & Geodesy</option>
                                    <option value="remote_sensing_gis">Remote Sensing & GIS</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select your department.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3" id="year_group" style="display: none;">
                                <label for="year" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Academic Year *
                                </label>
                                <select class="form-select" id="year" name="year">
                                    <option value="">Select year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select your academic year.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Password *
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Create a strong password"
                                           minlength="8"
                                           required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Password must be at least 8 characters long.
                                </div>
                                <small class="form-text text-muted">
                                    Use at least 8 characters with a mix of letters, numbers, and symbols.
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Confirm Password *
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Confirm your password"
                                       required>
                                <div class="invalid-feedback">
                                    Passwords do not match.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> *
                        </label>
                        <div class="invalid-feedback">
                            You must agree to the terms and conditions.
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Create Account
                        </button>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Already have an account?</p>
                        <a href="login.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login Here
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Medical Excuse Management System - Terms of Use</h6>
                <p>By registering for this system, you agree to:</p>
                <ul>
                    <li>Provide accurate and truthful information in all submissions</li>
                    <li>Submit only genuine medical documents and certificates</li>
                    <li>Use the system only for legitimate medical excuse purposes</li>
                    <li>Respect the confidentiality of medical information</li>
                    <li>Follow university policies and procedures</li>
                    <li>Report any system issues or security concerns immediately</li>
                </ul>
                <p class="text-muted">
                    <small>Last updated: <?php echo date('F j, Y'); ?></small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide year field based on role
document.getElementById('role').addEventListener('change', function() {
    const yearGroup = document.getElementById('year_group');
    const yearSelect = document.getElementById('year');
    
    if (this.value === 'student') {
        yearGroup.style.display = 'block';
        yearSelect.required = true;
    } else {
        yearGroup.style.display = 'none';
        yearSelect.required = false;
        yearSelect.value = '';
    }
});

// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        password.type = 'password';
        icon.className = 'fas fa-eye';
    }
});

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Form submission handling
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
    }
    
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    } else {
        showLoading(true);
    }
    this.classList.add('was-validated');
});

// Username validation
document.getElementById('username').addEventListener('input', function() {
    const username = this.value;
    const pattern = /^[a-zA-Z0-9_]+$/;
    
    if (username && !pattern.test(username)) {
        this.setCustomValidity('Username can only contain letters, numbers, and underscores');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>