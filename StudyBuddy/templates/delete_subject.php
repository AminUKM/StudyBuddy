<?php
require 'database.php';
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.html");
    exit();
}

if (isset($_GET['subject_reg_id'])) {
    $subject_reg_id = $_GET['subject_reg_id'];

    // Optionally delete related topics
    $conn->prepare("DELETE FROM topics1 WHERE subject_reg_id = ?")->execute([$subject_reg_id]);

    // Delete the subject registration
    $conn->prepare("DELETE FROM subjects1 WHERE subject_reg_id = ?")->execute([$subject_reg_id]);
}

header("Location: register_subject.php");
exit();
?>
