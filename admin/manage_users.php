<?php
$pageTitle = 'Manage Users';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('admin');

$user = getCurrentUser();

try {
    $pdo = getConnection();
    

    
    // Handle filters
    $roleFilter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
    $departmentFilter = isset($_GET['department']) ? sanitize($_GET['department']) : '';
    $statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // Build WHERE conditions
    $whereConditions = ['1=1'];
    $params = [];
    
    if (!empty($roleFilter)) {
        $whereConditions[] = "role = ?";
        $params[] = $roleFilter;
    }
    
    if (!empty($departmentFilter)) {
        $whereConditions[] = "department = ?";
        $params[] = $departmentFilter;
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    

    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $whereClause");
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
    

    
    // Get users
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE $whereClause
        ORDER BY role, department, full_name
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    

    
    // Get user statistics
    $stmt = $pdo->prepare("
        SELECT 
            role,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
        FROM users 
        GROUP BY role
        ORDER BY role
    ");
    $stmt->execute();
    $user_stats = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in manage users: " . $e->getMessage());
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
    $user_stats = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Manage Users</h2>
        <p class="text-muted mb-0">View system users and manage their status</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-tachometer-alt me-2"></i>
            Dashboard
        </a>
    </div>
</div>

<!-- User Statistics -->
<div class="row mb-4">
    <?php foreach ($user_stats as $stat): ?>
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-<?php 
                    echo $stat['role'] === 'student' ? 'user-graduate' : 
                        ($stat['role'] === 'admin' ? 'user-shield' : 
                        ($stat['role'] === 'hod' ? 'user-tie' : 'chalkboard-teacher')); 
                ?> fa-2x text-<?php 
                    echo $stat['role'] === 'student' ? 'primary' : 
                        ($stat['role'] === 'admin' ? 'danger' : 
                        ($stat['role'] === 'hod' ? 'warning' : 'success')); 
                ?> mb-2"></i>
                <h4><?php echo $stat['count']; ?></h4>
                <p class="text-muted mb-0"><?php echo ucfirst($stat['role']) . 's'; ?></p>
                <small class="text-success"><?php echo $stat['active_count']; ?> active</small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="role" class="form-label">Filter by Role</label>
                <select name="role" id="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Students</option>
                    <option value="lecturer" <?php echo $roleFilter === 'lecturer' ? 'selected' : ''; ?>>Lecturers</option>
                    <option value="hod" <?php echo $roleFilter === 'hod' ? 'selected' : ''; ?>>HODs</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="department" class="form-label">Filter by Department</label>
                <select name="department" id="department" class="form-select">
                    <option value="">All Departments</option>
                    <option value="survey_geodesy" <?php echo $departmentFilter === 'survey_geodesy' ? 'selected' : ''; ?>>Survey & Geodesy</option>
                    <option value="remote_sensing_gis" <?php echo $departmentFilter === 'remote_sensing_gis' ? 'selected' : ''; ?>>Remote Sensing & GIS</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Filter by Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </div>
            
            <div class="col-md-2">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search users...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-users me-2"></i>
            Users (<?php echo $totalUsers; ?> total)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No users found with the current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Status</th>
                            <th>Joined</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $u['role'] === 'student' ? 'primary' : 
                                            ($u['role'] === 'admin' ? 'danger' : 
                                            ($u['role'] === 'hod' ? 'warning' : 'success')); 
                                    ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo getDepartmentName($u['department']); ?>
                                </td>
                                <td>
                                    <?php echo $u['year'] ? 'Year ' . $u['year'] : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $u['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M j, Y', strtotime($u['created_at'])); ?></small>
                                </td>
                               
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <?php echo generatePagination($page, $totalPages, '?role=' . $roleFilter . '&department=' . $departmentFilter . '&status=' . $statusFilter); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>



<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#usersTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Auto-submit filters
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = ['role', 'department', 'status'];
    
    filterSelects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        }
    });
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>