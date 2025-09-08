<?php
$pageTitle = 'Lecturer Dashboard';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('lecturer');

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Get approved applications for this lecturer's department
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_approved,
            SUM(CASE WHEN DATE(a.approved_at) = CURDATE() THEN 1 ELSE 0 END) as today_approved,
            SUM(CASE WHEN DATE(a.approved_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_approved,
            SUM(CASE WHEN DATE(a.approved_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_approved
        FROM applications a
        JOIN users u ON a.student_id = u.id
        WHERE u.department = ? AND a.status IN ('hod_approved', 'completed')
    ");
    $stmt->execute([$user['department']]);
    $stats = $stmt->fetch();
    
    // Get recent approved applications
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code, u.full_name as student_name, u.year as student_year
        FROM applications a 
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON a.student_id = u.id
        WHERE u.department = ? AND a.status IN ('hod_approved', 'completed')
        ORDER BY a.approved_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user['department']]);
    $recent_approved = $stmt->fetchAll();
    
    // Get applications by course
    $stmt = $pdo->prepare("
        SELECT 
            c.course_code,
            c.course_name,
            c.year,
            COUNT(*) as app_count,
            SUM(CASE WHEN a.application_type = 'lecture' THEN 1 ELSE 0 END) as lecture_count,
            SUM(CASE WHEN a.application_type = 'practical' THEN 1 ELSE 0 END) as practical_count,
            SUM(CASE WHEN a.application_type = 'ca' THEN 1 ELSE 0 END) as ca_count
        FROM applications a
        JOIN courses c ON a.course_id = c.id
        JOIN users u ON a.student_id = u.id
        WHERE u.department = ? AND a.status IN ('hod_approved', 'completed')
        GROUP BY c.id
        ORDER BY app_count DESC, c.course_code
        LIMIT 10
    ");
    $stmt->execute([$user['department']]);
    $course_stats = $stmt->fetchAll();
    
    // Get monthly trends
    $stmt = $pdo->prepare("
        SELECT 
            YEAR(a.approved_at) as year,
            MONTH(a.approved_at) as month,
            COUNT(*) as count
        FROM applications a
        JOIN users u ON a.student_id = u.id
        WHERE u.department = ? AND a.status IN ('hod_approved', 'completed')
        AND a.approved_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY YEAR(a.approved_at), MONTH(a.approved_at)
        ORDER BY year DESC, month DESC
    ");
    $stmt->execute([$user['department']]);
    $monthly_trends = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in lecturer dashboard: " . $e->getMessage());
    $stats = ['total_approved' => 0, 'today_approved' => 0, 'week_approved' => 0, 'month_approved' => 0];
    $recent_approved = [];
    $course_stats = [];
    $monthly_trends = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Lecturer Dashboard</h2>
        <p class="text-muted mb-0">
            <?php echo getDepartmentName($user['department']); ?> Department
        </p>
    </div>
    <div>
        <a href="view_approved.php" class="btn btn-primary">
            <i class="fas fa-eye me-2"></i>
            View All Approved Applications
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-5">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stats-card stats-primary">
            <div class="stats-number"><?php echo $stats['total_approved']; ?></div>
            <div class="stats-label">Total Approved</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stats-card stats-info">
            <div class="stats-number"><?php echo $stats['today_approved']; ?></div>
            <div class="stats-label">Today</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stats-card stats-success">
            <div class="stats-number"><?php echo $stats['week_approved']; ?></div>
            <div class="stats-label">This Week</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="stats-card stats-warning">
            <div class="stats-number"><?php echo $stats['month_approved']; ?></div>
            <div class="stats-label">This Month</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Approved Applications -->
    <div class="col-lg-9 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    Recent Approved Applications
                </h5>
                <a href="view_approved.php" class="btn btn-outline-primary btn-sm">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_approved)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No approved applications yet.</p>
                        <small class="text-muted">Approved medical excuses will appear here for your reference.</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Absence Date</th>
                                    <th>Approved</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_approved, 0, 5) as $app): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($app['student_name']); ?></strong><br>
                                            <small class="text-muted">Year <?php echo $app['student_year']; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($app['course_code']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($app['course_name'], 0, 30)); ?>...</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $app['application_type'] === 'lecture' ? 'primary' : 
                                                    ($app['application_type'] === 'practical' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($app['application_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($app['application_date'])); ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M j, g:i A', strtotime($app['approved_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="view_approved.php?id=<?php echo $app['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Combined Sidebar -->
    <div class="col-lg-3">
        <div class="row">
            <!-- Course Statistics -->
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-book me-2"></i>
                            Applications by Course
                        </h6>
                    </div>
                    <div class="card-body p-2">
                        <?php if (empty($course_stats)): ?>
                            <p class="text-muted small">No data available yet.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($course_stats, 0, 3) as $course): ?>
                            <div class="text-center p-2 mb-2 border rounded">
                                <strong class="small"><?php echo htmlspecialchars($course['course_name']); ?></strong>
                                <div><span class="badge bg-primary"><?php echo $course['app_count']; ?></span></div>
                                <small class="text-muted">Year <?php echo $course['year']; ?></small>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="d-grid gap-1">
                            <a href="view_approved.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>View All
                            </a>
                            <a href="view_approved.php?status=hod_approved" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-check me-1"></i>Recent
                            </a>
                            <a href="view_approved.php?type=lecture" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-chalkboard-teacher me-1"></i>Lecture
                            </a>
                            <a href="view_approved.php?type=practical" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-flask me-1"></i>Practical
                            </a>
                            <a href="view_approved.php?type=ca" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-file-alt me-1"></i>Assessment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Information Row -->
<div class="row mt-4">
    <!-- Application Type Distribution Chart -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Type Distribution
                </h5>
            </div>
            <div class="card-body">
                <canvas id="typeChart" width="300" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Monthly Trends -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Monthly Trends
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($monthly_trends)): ?>
                    <p class="text-muted text-center">No data available for the last 6 months.</p>
                <?php else: ?>
                    <canvas id="monthlyChart" width="300" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Information & Guidelines -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Information & Guidelines
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info alert-permanent mb-3">
                    <h6><i class="fas fa-lightbulb me-2"></i>Lecturer Guidelines</h6>
                    <ul class="mb-0 small">
                        <li>Approved medical excuses are automatically displayed here</li>
                        <li>No action required - this is for your information only</li>
                        <li>Students are responsible for any missed work</li>
                        <li>Consider alternative assessment arrangements if needed</li>
                        <li>Contact the HOD for any questions about specific cases</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning alert-permanent">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Important Notes</h6>
                    <ul class="mb-0 small">
                        <li><strong>Lecture:</strong> Regular class attendance excuse</li>
                        <li><strong>Practical:</strong> Laboratory/practical session excuse</li>
                        <li><strong>CA:</strong> Continuous Assessment excuse</li>
                    </ul>
                </div>
            </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Trends Chart
<?php if (!empty($monthly_trends)): ?>
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach (array_reverse($monthly_trends) as $trend): ?>
                '<?php echo date('M Y', mktime(0, 0, 0, $trend['month'], 1, $trend['year'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Approved Applications',
            data: [
                <?php foreach (array_reverse($monthly_trends) as $trend): ?>
                    <?php echo $trend['count']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
<?php endif; ?>

// Application Type Distribution Chart
<?php 
$typeData = [
    'lecture' => 0,
    'practical' => 0,
    'ca' => 0
];
foreach ($course_stats as $course) {
    $typeData['lecture'] += $course['lecture_count'];
    $typeData['practical'] += $course['practical_count'];
    $typeData['ca'] += $course['ca_count'];
}
?>

const typeCtx = document.getElementById('typeChart').getContext('2d');
const typeChart = new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: ['Lecture', 'Practical', 'Assessment'],
        datasets: [{
            data: [
                <?php echo $typeData['lecture']; ?>,
                <?php echo $typeData['practical']; ?>,
                <?php echo $typeData['ca']; ?>
            ],
            backgroundColor: [
                '#007bff',
                '#ffc107',
                '#6c757d'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

// Auto-refresh dashboard every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>