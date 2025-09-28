<?php
// Simple test to debug course edit functionality
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/header.php';

requireRole('admin');

// Get course ID from URL
$courseId = (int)($_GET['id'] ?? 1); // Default to course ID 1 for testing

$user = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Test: Fetch course details
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    
    echo "<div class='container-fluid mt-4'>";
    echo "<h3>Course Edit Debug Test</h3>";
    
    if ($course) {
        echo "<div class='alert alert-success'>✅ Course found successfully!</div>";
        echo "<pre>" . json_encode($course, JSON_PRETTY_PRINT) . "</pre>";
        
        // Test AJAX endpoint
        echo "<h4>Testing AJAX Endpoint:</h4>";
        echo "<button class='btn btn-primary' onclick='testAjax()'>Test Get Course Details</button>";
        echo "<div id='ajaxResult' class='mt-3'></div>";
        
    } else {
        echo "<div class='alert alert-danger'>❌ No course found with ID: $courseId</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</div>";
?>

<script>
function testAjax() {
    fetch(`../includes/get_course_details.php?id=<?php echo $courseId; ?>`)
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('ajaxResult');
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">✅ AJAX call successful!</div>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">❌ AJAX call failed: ${data.message}</div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('ajaxResult').innerHTML = `
                <div class="alert alert-danger">❌ AJAX error: ${error.message}</div>
            `;
        });
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>