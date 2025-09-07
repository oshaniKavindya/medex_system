<?php
$pageTitle = 'Login';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'student':
            header('Location: ../student/dashboard.php');
            break;
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'hod':
            header('Location: ../hod/dashboard.php');
            break;
        case 'lecturer':
            header('Location: ../lecturer/dashboard.php');
            break;
    }
    exit();
}
?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card">
            <div class="card-header text-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Login to Your Account
                </h3>
            </div>
            <div class="card-body p-4">
                <form action="process_auth.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i>Username
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="username" 
                               name="username" 
                               placeholder="Enter your username"
                               required>
                        <div class="invalid-feedback">
                            Please enter your username.
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control form-control-lg" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   required>
                            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Please enter your password.
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login
                        </button>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Don't have an account?</p>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>
                            Create New Account
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-info-circle me-2"></i>
                    System Information
                </h6>
                <div class="row text-center">
                    <div class="col-6 col-md-3">
                        <div class="mb-2">
                            <i class="fas fa-user-graduate fa-2x text-primary"></i>
                        </div>
                        <small class="text-muted">Students</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="mb-2">
                            <i class="fas fa-user-tie fa-2x text-success"></i>
                        </div>
                        <small class="text-muted">Admin</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="mb-2">
                            <i class="fas fa-user-cog fa-2x text-warning"></i>
                        </div>
                        <small class="text-muted">HOD</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="mb-2">
                            <i class="fas fa-chalkboard-teacher fa-2x text-info"></i>
                        </div>
                        <small class="text-muted">Lecturer</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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

// Form submission handling
document.querySelector('form').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    } else {
        showLoading(true);
    }
    this.classList.add('was-validated');
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>