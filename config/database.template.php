<?php


define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'medex_system');

function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function testConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return true;
    } catch(PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        return false;
    }
}

function initializeDatabase() {
    try {
        $pdo = getConnection();
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(20) UNIQUE,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                department VARCHAR(50) NOT NULL,
                year INT NOT NULL,
                role ENUM('student', 'admin', 'lecturer', 'hod') DEFAULT 'student',
                status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_code VARCHAR(20) UNIQUE NOT NULL,
                course_name VARCHAR(100) NOT NULL,
                department VARCHAR(50) NOT NULL,
                year INT NOT NULL,
                lecturer_id INT,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                course_id INT NOT NULL,
                application_type ENUM('exam', 'practical', 'assignment', 'other') NOT NULL,
                application_date DATE NOT NULL,
                application_time TIME,
                reason TEXT NOT NULL,
                letter_file VARCHAR(255) NOT NULL,
                medical_application_file VARCHAR(255) NOT NULL,
                medical_certificate_file VARCHAR(255) NOT NULL,
                certificate_type ENUM('government', 'private') NOT NULL,
                status ENUM('pending', 'approved_by_lecturer', 'approved_by_hod', 'rejected') DEFAULT 'pending',
                lecturer_comments TEXT,
                hod_comments TEXT,
                processed_by_lecturer INT,
                processed_by_hod INT,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (processed_by_lecturer) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (processed_by_hod) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        return true;
    } catch(PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

if (!function_exists('setupDatabase')) {
    function setupDatabase() {
        if (testConnection()) {
            return initializeDatabase();
        }
        return false;
    }
}
?>
