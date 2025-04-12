<?php
require_once 'vendor/autoload.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login to enroll in this course.'); window.location.href='login.html';</script>";
    exit();
}

$host = 'localhost';
$dbname = 'content_marketing_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $course_id]);

    echo "<script>alert('Successfully enrolled in the course!'); window.location.href='dashboard.php';</script>";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>