<?php
require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    echo "Adding performance indexes...\n";
    
    $indexes = [
        "ALTER TABLE applications ADD INDEX idx_status (status)",
        "ALTER TABLE applications ADD INDEX idx_student_id (student_id)", 
        "ALTER TABLE applications ADD INDEX idx_course_id (course_id)",
        "ALTER TABLE applications ADD INDEX idx_created_at (created_at)",
        
        "ALTER TABLE users ADD INDEX idx_role_status (role, status)",
        "ALTER TABLE users ADD INDEX idx_department (department)",
        "ALTER TABLE users ADD INDEX idx_email (email)",
        
        "ALTER TABLE notifications ADD INDEX idx_user_id (user_id)",
        "ALTER TABLE notifications ADD INDEX idx_is_read (is_read)",
        "ALTER TABLE notifications ADD INDEX idx_created_at (created_at)",
        "ALTER TABLE notifications ADD INDEX idx_user_read (user_id, is_read)",
        
        "ALTER TABLE lecturer_notifications ADD INDEX idx_application_id (application_id)",
        "ALTER TABLE lecturer_notifications ADD INDEX idx_lecturer_id (lecturer_id)",
        "ALTER TABLE lecturer_notifications ADD INDEX idx_is_acknowledged (is_acknowledged)",
        
        "ALTER TABLE system_logs ADD INDEX idx_user_id (user_id)",
        "ALTER TABLE system_logs ADD INDEX idx_created_at (created_at)",
        
        "ALTER TABLE courses ADD INDEX idx_department (department)",
        "ALTER TABLE courses ADD INDEX idx_year (year)",
        "ALTER TABLE courses ADD INDEX idx_status (status)"
    ];
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($indexes as $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ Added index successfully\n";
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "- Index already exists, skipping\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\nCompleted! Added $successCount indexes, $errorCount errors.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>