<?php
require 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $latest_cgpa = $_POST['latest_cgpa'];

    try {
        $stmt = $conn->prepare("SELECT * FROM students1 WHERE student_id = ?");
        $stmt->execute([$student_id]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Student ID already registered!'); window.location.href='register.html';</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO students1 (student_id, name, latest_cgpa) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $name, $latest_cgpa]);
            echo "<script>alert('🎉 Congratulations! Your account is successfully registered. Please Login.'); window.location.href='login.html';</script>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
