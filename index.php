<?php
$pageTitle = 'Home';
require_once 'includes/header.php';

// Redirect logged-in users to their respective dashboards
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'student':
            header('Location: /medex_system/student/dashboard.php');
            exit();
        case 'admin':
            header('Location: /medex_system/admin/dashboard.php');
            exit();
        case 'hod':
            header('Location: /medex_system/hod/dashboard.php');
            exit();
        case 'lecturer':
            header('Location: /medex_system/lecturer/dashboard.php');
            exit();
    }
}
?>

<!-- Hero Section -->
<div class="hero-section text-center py-5 mb-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Medical Excuse Management System</h1>
                <p class="lead mb-4">
                    Streamlined digital platform for submitting and managing medical excuses for health-related academic absences at the Faculty of Geomatics.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="auth/login.php" class="btn btn-light btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                    <a href="auth/register.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-center">
                    <i class="fas fa-stethoscope" style="font-size: 12rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="row mb-5">
    <div class="col-md-4 mb-4">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="mb-3">
                    <i class="fas fa-upload fa-3x text-primary"></i>
                </div>
                <h5 class="card-title">Easy Submission</h5>
                <p class="card-text">
                    Submit medical excuse applications online with document uploads for letters, applications, and medical certificates.
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="mb-3">
                    <i class="fas fa-tasks fa-3x text-success"></i>
                </div>
                <h5 class="card-title">Streamlined Workflow</h5>
                <p class="card-text">
                    Automated workflow from student submission through admin review to HOD approval with real-time status tracking.
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="mb-3">
                    <i class="fas fa-bell fa-3x text-warning"></i>
                </div>
                <h5 class="card-title">Real-time Notifications</h5>
                <p class="card-text">
                    Instant notifications for status updates, approvals, and rejections to all relevant parties.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<div class="card mb-5">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>
            How It Works
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <h5>For Students:</h5>
                <ol class="list-group list-group-numbered">
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Create Account</div>
                            Register with your department and year details
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Submit Application</div>
                            Upload required documents (Letter, Medical Application, Certificate)
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Track Status</div>
                            Monitor your application progress in real-time
                        </div>
                    </li>
                </ol>
            </div>
            <div class="col-lg-6">
                <h5>Review Process:</h5>
                <ol class="list-group list-group-numbered">
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Admin Review</div>
                            Medical officer verifies document completeness
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">HOD Approval</div>
                            Head of Department reviews and approves/rejects
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Notification</div>
                            Relevant lecturers are notified of approved excuses
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Department Information -->
<div class="row mb-5">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-map-marked-alt fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Department of Survey & Geodesy</h5>
                <p class="card-text">
                    Specialized courses in surveying techniques, geodetic measurements, and spatial data collection.
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-satellite fa-3x text-info mb-3"></i>
                <h5 class="card-title">Department of Remote Sensing & GIS</h5>
                <p class="card-text">
                    Advanced programs in remote sensing technologies, GIS applications, and spatial analysis.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Requirements Section -->
<div class="card mb-5">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fas fa-file-alt me-2"></i>
            Required Documents
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="text-center mb-3">
                    <i class="fas fa-envelope fa-2x text-primary"></i>
                    <h5 class="mt-2">Letter</h5>
                </div>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success"></i> Subject code</li>
                    <li><i class="fas fa-check text-success"></i> Date of absence</li>
                    <li><i class="fas fa-check text-success"></i> Reason (Lecture/Practical/CA)</li>
                </ul>
            </div>
            <div class="col-md-4">
                <div class="text-center mb-3">
                    <i class="fas fa-file-medical fa-2x text-warning"></i>
                    <h5 class="mt-2">Medical Application</h5>
                </div>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success"></i> Subject/Course code</li>
                    <li><i class="fas fa-check text-success"></i> Date and time</li>
                    <li><i class="fas fa-check text-success"></i> Type of absence</li>
                </ul>
            </div>
            <div class="col-md-4">
                <div class="text-center mb-3">
                    <i class="fas fa-certificate fa-2x text-success"></i>
                    <h5 class="mt-2">Medical Certificate</h5>
                </div>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success"></i> Government hospital OR</li>
                    <li><i class="fas fa-check text-success"></i> Private center (certified)</li>
                    <li><i class="fas fa-clock text-info"></i> Within 14 days</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Contact Information -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fas fa-phone me-2"></i>
            Contact Information
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Faculty of Geomatics</h5>
                <p class="mb-1">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Sabaragamuwa University of Sri Lanka
                </p>
                <p class="mb-1">
                    <i class="fas fa-envelope me-2"></i>
                    geomatics@sab.ac.lk
                </p>
            </div>
            <div class="col-md-6">
                <h5>System Support</h5>
                <p class="mb-1">
                    <i class="fas fa-user-tie me-2"></i>
                    IT Support Team
                </p>
                <p class="mb-1">
                    <i class="fas fa-envelope me-2"></i>
                    support@sab.ac.lk
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>