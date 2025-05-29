<?php
session_start();
require 'database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['student_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_cgpa = $_POST['latest_cgpa'];

    $stmt = $conn->prepare("UPDATE students1 SET latest_cgpa = ? WHERE student_id = ?");
    $stmt->execute([$new_cgpa, $student_id]);

    header("Location: dashboard.php");
    exit();
}

// Fetch current data
$stmt = $conn->prepare("SELECT name, latest_cgpa FROM students1 WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Study Buddy</title>
    <link rel="icon" href="../static/pictures/Study Buddy Logo.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-color: #4CAF50;
            --accent-color: #1976d2;
            --danger-color: #f44336;
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
            align-items: center;
            justify-content: center;
        }
        .edit-box {
            background: rgba(255,255,255,0.98);
            padding: 48px 38px 36px 38px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 1s ease;
            position: relative;
        }
        .edit-box img {
            width: 110px;
            margin-bottom: 18px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(33,150,243,0.07);
        }
        .edit-box h2 {
            margin: 0 0 22px;
            color: #263238;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .edit-box input[type="text"],
        .edit-box input[type="number"] {
            width: 100%;
            padding: 13px 14px;
            margin: 18px 0 12px 0;
            border: 1.5px solid #bdbdbd;
            border-radius: 10px;
            font-size: 16px;
            background: #f7fafc;
            transition: border-color 0.2s;
        }
        .edit-box input[type="text"]:focus,
        .edit-box input[type="number"]:focus {
            border-color: var(--main-color);
            outline: none;
            background: #e8f5e9;
        }
        .edit-box input[readonly] {
            background-color: #f0f0f0;
            color: #666;
            cursor: default;
        }
        .edit-box label {
            display: block;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 4px;
            text-align: left;
        }
        .edit-box button {
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
        .edit-box button:hover {
            background: linear-gradient(90deg, #388e3c 60%, #4CAF50 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .edit-box .cancel-btn {
            background: linear-gradient(90deg, #f44336 60%, #e57373 100%);
            margin-top: 10px;
        }
        .edit-box .cancel-btn:hover {
            background: linear-gradient(90deg, #d32f2f 60%, #f44336 100%);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: translateY(0);}
        }
        @media (max-width: 480px) {
            .edit-box {
                padding: 28px 8vw;
            }
            .edit-box img {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="edit-box">
        <img src="../static/pictures/Study Buddy Logo.jpg" alt="Study Buddy Logo">
        <h2>Edit Profile</h2>
        <form method="POST">
            <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" readonly>
            <label for="latest_cgpa">Please enter new CGPA</label>
            <input type="number" id="latest_cgpa" step="0.01" min="0" max="4" name="latest_cgpa" placeholder="Latest CGPA" value="<?= htmlspecialchars($student['latest_cgpa']) ?>" required>
            <button type="submit">Update Profile</button>
        </form>
        <form method="get" action="dashboard.php">
            <button type="submit" class="cancel-btn">Cancel</button>
        </form>
    </div>
</body>
</html>
