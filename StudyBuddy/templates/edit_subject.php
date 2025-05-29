<?php
session_start();
require 'database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['student_id'];
$message = "";

// Fetch subject details
if (isset($_GET['subject_reg_id'])) {
    $subject_reg_id = $_GET['subject_reg_id'];
    $stmt = $conn->prepare("SELECT * FROM subjects1 WHERE subject_reg_id = ? AND student_id = ?");
    $stmt->execute([$subject_reg_id, $student_id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subject) {
        die("Subject not found or you do not have permission.");
    }
} else {
    die("No subject specified.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = trim($_POST['subject_name']);
    $assessment_type = $_POST['assessment_type'];
    $exam_date = ($assessment_type === 'CA') ? null : $_POST['exam_date'];

    $updateStmt = $conn->prepare("UPDATE subjects1 SET subject_name = ?, assessment_type = ?, exam_date = ? WHERE subject_reg_id = ? AND student_id = ?");
    $updateStmt->execute([$subject_name, $assessment_type, $exam_date, $subject_reg_id, $student_id]);
    header("Location: register_subject.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Subject | Study Buddy</title>
    <link rel="icon" href="../static/pictures/Study Buddy Logo.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(120deg, #e3f0ff 0%, #f7fafc 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .edit-container {
            background: #fff;
            max-width: 420px;
            width: 100%;
            padding: 40px 36px 32px 36px;
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(76,175,80,0.08), 0 2px 8px rgba(33,150,243,0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .edit-container img {
            width: 70px;
            margin-bottom: 18px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(33,150,243,0.13);
        }
        h2 {
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 1px;
        }
        form {
            width: 100%;
        }
        label {
            font-weight: 600;
            color: #263238;
            margin-top: 18px;
            display: block;
            margin-bottom: 6px;
        }
        input[type="text"], input[type="date"], select {
            width: 100%;
            padding: 12px 10px;
            border-radius: 8px;
            border: 1px solid #bdbdbd;
            font-size: 1rem;
            margin-bottom: 10px;
            background: #f7fafc;
            transition: border 0.2s;
        }
        input[disabled] {
            background: #f0f0f0;
            color: #888;
        }
        button {
            margin-top: 24px;
            padding: 14px 0;
            width: 100%;
            background: linear-gradient(90deg, #1976d2 60%, #43e97b 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.08rem;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(33,150,243,0.08);
            transition: background 0.2s;
        }
        button:hover {
            background: linear-gradient(90deg, #43e97b 60%, #1976d2 100%);
        }
        .back-link {
            display: block;
            margin-top: 22px;
            color: #1976d2;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #43e97b;
        }
        @media (max-width: 600px) {
            .edit-container {
                padding: 24px 8px 18px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <img src="../static/pictures/Study Buddy Logo.jpg" alt="Study Buddy Logo">
        <h2>Edit Subject</h2>
        <form method="POST">
            <label>Subject ID</label>
            <input type="text" value="<?php echo htmlspecialchars($subject['subject_id']); ?>" disabled>
            <label>Subject Name</label>
            <input type="text" name="subject_name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
            <label>Assessment Type</label>
            <select name="assessment_type" id="assessment_type" onchange="toggleExamDate()" required>
                <option value="CA" <?php if($subject['assessment_type'] === 'CA') echo 'selected'; ?>>Continuous Assessment</option>
                <option value="Final" <?php if($subject['assessment_type'] === 'Final') echo 'selected'; ?>>Final Exam</option>
            </select>
            <label id="exam_date_label" style="display:none;">Exam Date</label>
            <input type="date" name="exam_date" id="exam_date_input" value="<?php echo htmlspecialchars($subject['exam_date']); ?>" style="display:none;">
            <button type="submit">Save Changes</button>
        </form>
        <a href="register_subject.php" class="back-link">← Back to Subject List</a>
    </div>
    <script>
        function toggleExamDate() {
            var type = document.getElementById('assessment_type').value;
            var examDateInput = document.getElementById('exam_date_input');
            var examDateLabel = document.getElementById('exam_date_label');
            if (type === 'CA') {
                examDateInput.style.display = 'none';
                examDateLabel.style.display = 'none';
                examDateInput.value = '';
            } else {
                examDateInput.style.display = 'block';
                examDateLabel.style.display = 'block';
            }
        }
        window.onload = toggleExamDate;
    </script>
</body>
</html>
