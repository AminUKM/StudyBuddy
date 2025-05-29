<?php
session_start();
require 'database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

$message = "";
$registered_subject_id = "";

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = trim($_POST['subject_id']);
    $subject_name = trim($_POST['subject_name']);
    $assessment_type = $_POST['assessment_type'];
    $exam_date = ($assessment_type === 'CA') ? null : $_POST['exam_date'];

    // Generate unique subject_reg_id for this student+subject
    $subject_reg_id = $student_id . '_' . $subject_id;

    // Check if already registered (optional: prevent duplicate registration)
    $checkStmt = $conn->prepare("SELECT * FROM subjects1 WHERE subject_reg_id = ?");
    $checkStmt->execute([$subject_reg_id]);
    if ($checkStmt->rowCount() > 0) {
        $message = "You have already registered this subject.";
    } else {
        $stmt = $conn->prepare("INSERT INTO subjects1 (subject_reg_id, student_id, subject_id, subject_name, assessment_type, exam_date) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$subject_reg_id, $student_id, $subject_id, $subject_name, $assessment_type, $exam_date]);
            $message = "Congratulations! $subject_id has successfully registered.";
            $registered_subject_id = $subject_id;
        } catch (PDOException $e) {
            $message = "Error: Could not register subject. " . $e->getMessage();
        }
    }
}

// Fetch subjects for this student
$stmt = $conn->prepare("SELECT * FROM subjects1 WHERE student_id = ?");
$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Subject - Study Buddy</title>
    <link rel="icon" href="../static/pictures/Study Buddy Logo.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-color: #4CAF50;
            --accent-color: #1976d2;
            --danger-color: #f44336;
            --warning-color: #fbc02d;
            --bg-gradient: linear-gradient(120deg, #e1f5fe 0%, #e8f5e9 100%);
            --box-shadow: 0 8px 32px rgba(76,175,80,0.08), 0 2px 8px rgba(33,150,243,0.06);
            --border-radius: 22px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Montserrat', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 10px;
        }
        .register-box {
            background: rgba(255,255,255,0.98);
            padding: 48px 38px 36px 38px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 420px;
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 1s ease;
            position: relative;
        }
        .register-box img {
            width: 110px;
            margin-bottom: 18px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(33,150,243,0.07);
        }
        .register-box h2 {
            margin: 0 0 22px;
            color: #263238;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .register-box input[type="text"],
        .register-box input[type="date"] {
            width: 100%;
            padding: 13px 14px;
            margin: 18px 0 12px 0;
            border: 1.5px solid #bdbdbd;
            border-radius: 10px;
            font-size: 16px;
            background: #f7fafc;
            transition: border-color 0.2s;
        }
        .register-box input[type="text"]:focus,
        .register-box input[type="date"]:focus {
            border-color: var(--main-color);
            outline: none;
            background: #e8f5e9;
        }
        .register-box button {
            background: linear-gradient(90deg, #4CAF50 60%, #81c784 100%);
            color: white;
            padding: 13px 0;
            border: none;
            border-radius: 10px;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 2px 8px rgba(76,175,80,0.10);
            transition: background 0.2s, transform 0.2s;
            margin-top: 10px;
        }
        .register-box button:hover {
            background: linear-gradient(90deg, #388e3c 60%, #4CAF50 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .register-box a {
            display: block;
            margin-top: 18px;
            color: var(--accent-color);
            font-weight: bold;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.2s;
        }
        .register-box a:hover {
            color: #0d47a1;
            text-decoration: underline;
        }
        .message {
            margin-bottom: 18px;
            color: var(--main-color);
            font-weight: 600;
            font-size: 1.08rem;
            text-align: center;
        }
        table {
            width: 100%;
            max-width: 900px;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 40px;
            animation: fadeIn 1s;
        }
        th, td {
            border: none;
            padding: 18px 12px;
            text-align: center;
            font-size: 1.08rem;
        }
        th {
            background: #e3f2fd;
            color: #1976d2;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) td {
            background: #f1f8e9;
        }
        tr:hover td {
            background: #e3f2fd;
            transition: background 0.2s;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        .action-buttons a {
            padding: 7px 18px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }
        .edit-btn {
            background: linear-gradient(90deg, #ffca28 60%, #ffe082 100%);
            color: #263238;
        }
        .edit-btn:hover {
            background: linear-gradient(90deg, #ffb300 60%, #ffca28 100%);
            color: #212121;
        }
        .delete-btn {
            background: linear-gradient(90deg, #f44336 60%, #e57373 100%);
        }
        .delete-btn:hover {
            background: linear-gradient(90deg, #d32f2f 60%, #f44336 100%);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: translateY(0);}
        }
        @media (max-width: 700px) {
            .register-box {
                padding: 28px 8vw;
            }
            .register-box img {
                width: 80px;
            }
            table, th, td {
                font-size: 0.98rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-box">
        <img src="../static/pictures/Study Buddy Logo.jpg" alt="Study Buddy Logo">
        <h2>Register Subject</h2>
        <form method="POST" action="register_subject.php" autocomplete="off">
            <input type="text" name="subject_id" placeholder="Subject ID (e.g., TTTK2003)" required>
            <input type="text" name="subject_name" placeholder="Subject Name" required>
            <div style="text-align:left; margin:18px 0 12px 0;">
                <label style="font-weight:600; color:#263238; display:block; margin-bottom:8px;">Assessment Type:</label>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <label style="display:flex; align-items:center;">
                        <input type="radio" id="ca" name="assessment_type" value="CA" checked onchange="toggleExamDate()" style="margin-right:8px;">
                        Continues Assessment
                    </label>
                    <label style="display:flex; align-items:center;">
                        <input type="radio" id="final" name="assessment_type" value="Final" onchange="toggleExamDate()" style="margin-right:8px;">
                        Final Exam
                    </label>
                </div>
            </div>
            <input type="date" name="exam_date" id="exam_date_input" placeholder="Exam Date" style="display:none;">
            <button type="submit">Register Subject</button>
        </form>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
    <script>
        function toggleExamDate() {
            var ca = document.getElementById('ca').checked;
            var examDateInput = document.getElementById('exam_date_input');
            if (ca) {
                examDateInput.style.display = 'none';
                examDateInput.required = false;
                examDateInput.value = '';
            } else {
                examDateInput.style.display = 'block';
                examDateInput.required = true;
            }
        }
        // Initialize on page load
        window.onload = toggleExamDate;
    </script>

    <?php if (!empty($message)): ?>
    <script>
        window.onload = function() {
            alert("<?php echo addslashes($message); ?>");
            <?php if ($message && strpos($message, 'successfully registered') !== false): ?>
            // Optionally, you can clear the form fields after success
            if (document.querySelector('form')) {
                document.querySelector('form').reset();
                toggleExamDate();
            }
            <?php endif; ?>
        };
    </script>
    <?php endif; ?>

    <?php if (count($subjects) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Subject ID</th>
                <th>Subject Name</th>
                <th>Exam Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $sub): ?>
            <tr>
                <td><?php echo htmlspecialchars($sub['subject_id']); ?></td>
                <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                <td>
                    <?php
                        if (isset($sub['assessment_type']) && $sub['assessment_type'] === 'CA') {
                            echo "Continues Assessment";
                        } else {
                            echo htmlspecialchars($sub['exam_date']);
                        }
                    ?>
                </td>
                <td class="action-buttons">
                    <a href="edit_subject.php?subject_reg_id=<?php echo urlencode($sub['subject_reg_id']); ?>" class="edit-btn">Edit</a>
                    <a href="delete_subject.php?subject_reg_id=<?php echo urlencode($sub['subject_reg_id']); ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this subject?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</body>
</html>
