<?php
// Start output buffering to prevent header issues
ob_start();

$pageTitle = 'My Profile';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireLogin();
$user = getCurrentUser();

if (!$user) {
    $_SESSION['error_message'] = 'Unable to load your profile. Please try again.';
    ob_end_clean();
    header('Location: ' . ($_SESSION['role'] ?? '') . '/dashboard.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $errors = [];
    
    try {
        $pdo = getConnection();
        
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // Determine if user wants to change password
        $updating_password = !empty($new_password) || !empty($confirm_password) || !empty($current_password);
        
        // Basic validation
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Check email uniqueness (only if no errors so far)
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $errors[] = 'This email address is already in use by another user.';
            }
        }
        
        // Password validation
        if ($updating_password) {
            if (empty($current_password)) {
                $errors[] = 'Current password is required to change your password.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (empty($new_password)) {
                $errors[] = 'Please enter a new password.';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'New password must be at least 6 characters long.';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'New password and confirm password do not match.';
            }
        }
        
        // If no errors, proceed with update
        if (empty($errors)) {
            $pdo->beginTransaction();
            
            if ($updating_password) {
                // Update with password
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, password_hash($new_password, PASSWORD_DEFAULT), $user['id']]);
            } else {
                // Update without password
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $user['id']]);
            }
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], 'Profile Updated', 'User updated their profile information', $_SERVER['REMOTE_ADDR']]);
            
            $pdo->commit();
            
            // Update session data
            $_SESSION['full_name'] = $full_name;
            
            $_SESSION['success_message'] = $updating_password ? 
                'Your profile and password have been updated successfully.' : 
                'Your profile has been updated successfully.';
                
            ob_end_clean();
            header('Location: profile.php');
            exit();
        } else {
            // Set error message and redirect to prevent form resubmission
            $_SESSION['error_message'] = implode('<br>', $errors);
            ob_end_clean();
            header('Location: profile.php');
            exit();
        }
        
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        ob_end_clean();
        error_log("Database error in profile update: " . $e->getMessage());
        $_SESSION['error_message'] = 'A database error occurred. Please try again.';
        header('Location: profile.php');
        exit();
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        ob_end_clean();
        error_log("General error in profile update: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while updating your profile.';
        header('Location: profile.php');
        exit();
    }
}

// Refresh user data
$user = getCurrentUser();

// Helper functions
function getRoleColor($role) {
    $colors = ['admin' => 'danger', 'hod' => 'warning', 'lecturer' => 'info', 'student' => 'success'];
    return $colors[$role] ?? 'secondary';
}

function renderProfileRow($label, $value) {
    echo "<div class='row mb-3'><div class='col-sm-4'><strong>$label:</strong></div><div class='col-sm-8'>$value</div></div>";
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <!-- Profile Header -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar-large bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3">
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                    <h2 class="mb-1"><?= htmlspecialchars($user['full_name']) ?></h2>
                    <p class="text-muted mb-2">
                        <i class="fas fa-user-tag me-2"></i><?= getRoleName($user['role']) ?>
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-building me-2"></i><?= getDepartmentName($user['department']) ?>
                        <?= $user['role'] === 'student' && $user['year'] ? " - Year {$user['year']}" : '' ?>
                    </p>
                </div>
            </div>
            
            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Profile Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            renderProfileRow('Username', htmlspecialchars($user['username']));
                            renderProfileRow('Full Name', htmlspecialchars($user['full_name']));
                            renderProfileRow('Email', htmlspecialchars($user['email']));
                            renderProfileRow('Role', '<span class="badge bg-' . getRoleColor($user['role']) . '">' . getRoleName($user['role']) . '</span>');
                            renderProfileRow('Department', getDepartmentName($user['department']));
                            
                            if ($user['role'] === 'student' && $user['year']) {
                                renderProfileRow('Academic Year', "Year {$user['year']}");
                            }
                            
                            renderProfileRow('Status', '<span class="badge bg-' . ($user['status'] === 'active' ? 'success' : 'secondary') . '">' . ucfirst($user['status']) . '</span>');
                            renderProfileRow('Member Since', date('F j, Y', strtotime($user['created_at'])));
                            
                            if (!empty($user['updated_at'])) {
                                renderProfileRow('Last Updated', date('F j, Y g:i A', strtotime($user['updated_at'])));
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Update Profile Form -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-edit me-2"></i>Update Profile
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_profile">
                                
                                <?php
                                $formFields = [
                                    ['name' => 'full_name', 'label' => 'Full Name *', 'type' => 'text', 'value' => $user['full_name'], 'required' => true],
                                    ['name' => 'email', 'label' => 'Email Address *', 'type' => 'email', 'value' => $user['email'], 'required' => true]
                                ];
                                
                                foreach ($formFields as $field): ?>
                                    <div class="form-group mb-3">
                                        <label for="<?= $field['name'] ?>" class="form-label"><?= $field['label'] ?></label>
                                        <input type="<?= $field['type'] ?>" 
                                               class="form-control" 
                                               id="<?= $field['name'] ?>" 
                                               name="<?= $field['name'] ?>" 
                                               value="<?= htmlspecialchars($field['value']) ?>"
                                               <?= $field['required'] ? 'required' : '' ?>>
                                        <div class="invalid-feedback">Please enter a valid <?= strtolower(str_replace(' *', '', $field['label'])) ?>.</div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <hr>
                                <h6 class="text-muted mb-3">Change Password (Optional)</h6>
                                
                                <?php
                                $passwordFields = [
                                    ['name' => 'current_password', 'label' => 'Current Password', 'placeholder' => 'Enter current password', 'help' => 'Required only if you want to change your password.'],
                                    ['name' => 'new_password', 'label' => 'New Password', 'placeholder' => 'Enter new password', 'help' => 'Leave blank to keep current password.'],
                                    ['name' => 'confirm_password', 'label' => 'Confirm New Password', 'placeholder' => 'Confirm new password', 'help' => null]
                                ];
                                
                                foreach ($passwordFields as $field): ?>
                                    <div class="form-group <?= $field['name'] === 'confirm_password' ? 'mb-4' : 'mb-3' ?>">
                                        <label for="<?= $field['name'] ?>" class="form-label"><?= $field['label'] ?></label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="<?= $field['name'] ?>" 
                                                   name="<?= $field['name'] ?>" 
                                                   placeholder="<?= $field['placeholder'] ?>">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('<?= $field['name'] ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <?php if ($field['help']): ?>
                                            <small class="form-text text-muted"><?= $field['help'] ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    const passwordFields = ['current_password', 'new_password', 'confirm_password'];
    const [currentPassword, newPassword, confirmPassword] = passwordFields.map(id => document.getElementById(id));
    
    // Form validation
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Clear password fields on error
    <?php if (!empty($_SESSION['error_message'])): ?>
    passwordFields.forEach(id => document.getElementById(id).value = '');
    <?php endif; ?>
    
    // Dynamic password requirements
    newPassword.addEventListener('input', function() {
        const hasNewPassword = this.value.length > 0;
        
        [currentPassword, confirmPassword].forEach(field => {
            if (hasNewPassword) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        });
        
        this.setCustomValidity(hasNewPassword && this.value.length < 6 ? 
            'Password must be at least 6 characters long' : '');
            
        if (confirmPassword.value) validateConfirmPassword();
    });
    
    function validateConfirmPassword() {
        const match = !newPassword.value || newPassword.value === confirmPassword.value;
        confirmPassword.setCustomValidity(match ? '' : 'Passwords do not match');
    }
    
    confirmPassword.addEventListener('input', validateConfirmPassword);
});
</script>

<style>
.avatar-large {
    width: 100px;
    height: 100px;
    font-size: 2rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.btn-outline-secondary:hover {
    color: #fff;
}
</style>

<?php 
// End output buffering and display the page
ob_end_flush();
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; 
?>