<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['department']) || !isset($_GET['year'])) {
    echo json_encode([]);
    exit();
}

$department = sanitize($_GET['department']);
$year = (int)$_GET['year'];

try {
    $courses = getCoursesByDepartmentYear($department, $year);
    echo json_encode($courses);
} catch (Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    echo json_encode([]);
}
?>