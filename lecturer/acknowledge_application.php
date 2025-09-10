<?php
$pageTitle = 'Acknowledge Application';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('lecturer');

$user = getCurrentUser();
$application_id = intval($_GET['id'] ?? 0);

if (!$application_id) {
    header('Location: view_assigned_applications.php');
    exit;
}

try {
    $pdo = getConnection();
    
    // Check if this application is assigned to the current lecturer and not already acknowledged
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code, u.full_name as student_name,
               ln.is_acknowledged, ln.id as notification_id
        FROM lecturer_notifications ln
        JOIN applications a ON ln.application_id = a.id
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON a.student_id = u.id
        WHERE ln.lecturer_id = ? AND a.id = ?
    ");
    $stmt->execute([$user['id'], $application_id]);
    $application = $stmt->fetch();
    
    if (!$application) {
        header('Location: view_assigned_applications.php?error=not_found');
        exit;
    }
    
    if ($application['is_acknowledged']) {
        header('Location: view_application.php?id=' . $application_id . '&message=already_acknowledged');
        exit;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();
            
            // Update the lecturer notification to mark as acknowledged
            $stmt = $pdo->prepare("
                UPDATE lecturer_notifications 
                SET is_acknowledged = 1, acknowledged_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$application['notification_id']]);
            
            // Log the acknowledgment
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (user_id, action, description, created_at) 
                VALUES (?, 'lecturer_acknowledge', ?, NOW())
            ");
            $log_description = "Lecturer acknowledged application #" . $application_id . " for student " . $application['student_name'];
            $stmt->execute([$user['id'], $log_description]);
            
            $pdo->commit();
            
            header('Location: view_application.php?id=' . $application_id . '&message=acknowledged');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database error in lecturer acknowledge_application: " . $e->getMessage());
            $error_message = "An error occurred while acknowledging the application. Please try again.";
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in lecturer acknowledge_application: " . $e->getMessage());
    header('Location: view_assigned_applications.php?error=database');
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Acknowledge Application</h2>
        <p class="text-muted mb-0">Application #<?php echo $application['id']; ?></p>
    </div>
    <div>
        <a href="view_application.php?id=<?php echo $application['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Application
        </a>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    Acknowledgment Required
                </h5>
            </div>
            <div class="card-body">
                <!-- Application Summary -->
                <div class="alert alert-light border-left-warning" role="alert">
                    <h6 class="alert-heading">Application Summary</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Student:</strong> <?php echo htmlspecialchars($application['student_name']); ?><br>
                            <strong>Course:</strong> <?php echo htmlspecialchars($application['course_code']); ?><br>
                            <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $application['application_type'])); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($application['application_date'])); ?><br>
                            <?php if ($application['application_time']): ?>
                                <strong>Time:</strong> <?php echo date('g:i A', strtotime($application['application_time'])); ?><br>
                            <?php endif; ?>
                            <strong>Status:</strong> <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                <?php echo formatStatus($application['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Acknowledgment Information -->
                <div class="mb-4">
                    <h6 class="text-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        What does acknowledging mean?
                    </h6>
                    <div class="bg-light p-3 rounded">
                        <ul class="mb-0">
                            <li>You confirm that you have <strong>reviewed</strong> this medical excuse application</li>
                            <li>You acknowledge that the student has a <strong>valid medical excuse</strong> for the specified date/time</li>
                            <li>You understand that this student should be given <strong>appropriate consideration</strong> for any missed activities</li>
                            <li>This acknowledgment will be <strong>recorded</strong> in the system for future reference</li>
                        </ul>
                    </div>
                </div>

                <!-- Acknowledgment Form -->
                <form method="POST" class="text-center">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Once you acknowledge this application, the action cannot be undone.
                        Please ensure you have reviewed all details carefully.
                    </div>

                    <div class="mb-4">
                        <div class="form-check d-inline-block">
                            <input class="form-check-input" type="checkbox" id="confirmReview" required>
                            <label class="form-check-label" for="confirmReview">
                                I confirm that I have reviewed this application and its supporting documents
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="view_application.php?id=<?php echo $application['id']; ?>" 
                           class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-2"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success" id="acknowledgeBtn" disabled>
                            <i class="fas fa-check me-2"></i>
                            Acknowledge Application
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Application Reason
                </h6>
            </div>
            <div class="card-body">
                <div class="bg-light p-3 rounded">
                    <?php echo nl2br(htmlspecialchars($application['reason'])); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirmReview');
    const acknowledgeBtn = document.getElementById('acknowledgeBtn');
    
    confirmCheckbox.addEventListener('change', function() {
        acknowledgeBtn.disabled = !this.checked;
    });
    
    // Add confirmation dialog
    acknowledgeBtn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to acknowledge this application? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.border-left-warning {
    border-left: 4px solid #ffc107 !important;
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>
