<?php
require 'database.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];

    try {
        $stmt = $conn->prepare("SELECT * FROM students1 WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $_SESSION['student_id'] = $student['student_id'];
            $_SESSION['student_name'] = $student['name'];
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Student ID not found! Please register.'); window.location.href='login.html';</script>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
