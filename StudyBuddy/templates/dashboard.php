<?php
session_start();
require 'database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Fetch subjects for this student
$stmt = $conn->prepare("SELECT * FROM subjects1 WHERE student_id = ?");
$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch CGPA
$stmtCGPA = $conn->prepare("SELECT latest_cgpa FROM students1 WHERE student_id = ?");
$stmtCGPA->execute([$student_id]);
$cgpa = $stmtCGPA->fetchColumn();

// Calculate days to all upcoming papers
$examReminders = [];
if (count($subjects) > 0) {
    // Only consider subjects with a valid exam_date and not "Continues Assessment"
    $examSubjects = array_filter($subjects, function($sub) {
        return isset($sub['assessment_type']) && $sub['assessment_type'] !== 'CA' && !empty($sub['exam_date']) && $sub['exam_date'] !== '0000-00-00';
    });

    // Sort by exam_date ascending
    usort($examSubjects, function($a, $b) {
        return strtotime($a['exam_date']) - strtotime($b['exam_date']);
    });

    $today = new DateTime();
    $counter = 1;
    foreach ($examSubjects as $examSub) {
        $examDate = new DateTime($examSub['exam_date']);
        $interval = $today->diff($examDate);
        $daysToExam = (int)$interval->format('%r%a');
        $ordinal = $counter === 1 ? "first" : ($counter === 2 ? "second" : ($counter === 3 ? "third" : "{$counter}th"));
        $examReminders[] = [
            'ordinal' => $ordinal,
            'days' => $daysToExam,
            'date' => $examSub['exam_date']
        ];
        $counter++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard - Study Buddy</title>
    <link rel="icon" href="../static/pictures/Study Buddy Logo.ico" type="image/x-icon" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
        body {
            margin: 0;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            background: linear-gradient(120deg, #e1f5fe 0%, #e8f5e9 100%);
            padding: 0;
            min-height: 100vh;
        }
        nav {
            background: rgba(76,175,80,0.95);
            color: white;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 24px 24px;
            box-shadow: 0 4px 24px rgba(76,175,80,0.10);
        }
        nav .logo-container {
            display: flex;
            align-items: center;
        }
        nav .logo-container img {
            height: 60px;
            margin-right: 14px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        nav strong {
            font-size: 2rem;
            letter-spacing: 2px;
        }
        nav a {
            color: white;
            margin-left: 28px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: color 0.2s;
            padding: 8px 16px;
            border-radius: 8px;
        }
        nav a:hover {
            background: rgba(255,255,255,0.13);
            color: #263238;
        }
        nav span {
            margin-right: 18px;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .container {
            max-width: 900px;
            margin: 40px auto 0 auto;
            padding: 0 20px;
        }
        .box {
            background: rgba(255,255,255,0.98);
            padding: 36px 40px 32px 40px;
            margin: 32px auto 0 auto;
            border-radius: 28px;
            box-shadow: 0 8px 32px rgba(76,175,80,0.08), 0 2px 8px rgba(33,150,243,0.06);
            max-width: 900px;
        }
        h2, h3 {
            text-align: center;
            font-weight: 700;
            color: #263238;
            margin-bottom: 18px;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 24px;
            background: #f9f9f9;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(33,150,243,0.04);
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
        .print-btn-container {
            text-align: center;
            margin-top: 28px;
        }
        .print-btn-container button {
            background: linear-gradient(90deg, #42a5f5 60%, #64b5f6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 13px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(33,150,243,0.10);
            transition: background 0.2s, transform 0.2s;
        }
        .print-btn-container button:hover {
            background: linear-gradient(90deg, #1976d2 60%, #42a5f5 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .register-button-container {
            display: flex;
            justify-content: center;
            gap: 18px;
            margin: 38px 0 0 0;
        }
        .register-button-container button {
            max-width: 260px;
            width: 100%;
            cursor: pointer;
            background: linear-gradient(90deg, #4CAF50 60%, #81c784 100%);
            color: white;
            padding: 14px 0;
            border: none;
            border-radius: 12px;
            font-size: 1.08rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(76,175,80,0.10);
            transition: background 0.2s, transform 0.2s;
        }
        .register-button-container button:hover {
            background: linear-gradient(90deg, #388e3c 60%, #4CAF50 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .register-button-container button.edit {
            background: linear-gradient(90deg, #ffca28 60%, #ffe082 100%);
            color: #263238;
        }
        .register-button-container button.edit:hover {
            background: linear-gradient(90deg, #ffb300 60%, #ffca28 100%);
        }
        .register-button-container button.revision {
            background: linear-gradient(90deg, #42a5f5 60%, #90caf9 100%);
        }
        .register-button-container button.revision:hover {
            background: linear-gradient(90deg, #1976d2 60%, #42a5f5 100%);
        }
        canvas {
            display: block;
            margin: 38px auto 0 auto;
            max-width: 340px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(33,150,243,0.07);
            padding: 18px;
        }
        @media (max-width: 700px) {
            .box, .container {
                padding: 16px 4vw;
            }
            nav {
                flex-direction: column;
                align-items: flex-start;
                padding: 18px 10px;
            }
            nav .logo-container img {
                height: 44px;
            }
            .register-button-container {
                flex-direction: column;
                gap: 12px;
            }
        }
        .shake-clock {
            animation: shake 0.7s infinite;
            display: inline-block;
        }
        @keyframes shake {
            0% { transform: rotate(0deg);}
            20% { transform: rotate(-15deg);}
            40% { transform: rotate(10deg);}
            60% { transform: rotate(-10deg);}
            80% { transform: rotate(8deg);}
            100% { transform: rotate(0deg);}
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo-container">
            <img src="../static/pictures/Study Buddy Logo.ico" alt="Study Buddy Logo" />
            <strong>Study Buddy</strong>
        </div>
        <div>
            <span>Welcome, <?php echo htmlspecialchars($student_name); ?>!</span>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="box">
            <h2>Your Registered Subjects</h2>

            <?php if (!empty($examReminders)): ?>
                <div style="text-align:center; margin-bottom:18px;">
                    <?php foreach ($examReminders as $reminder): ?>
                        <?php if ($reminder['days'] > 0): ?>
                            <span style="display:inline-block; background:rgba(146,10,0,0.8); color:#fff; font-weight:600; border-radius:10px; padding:10px 24px; font-size:1.08rem; margin-bottom:10px; margin-top:5px;">
                                <span class="shake-clock" style="display:inline-block; font-size:1.6em;">⏰</span>
                                <?php echo $reminder['days']; ?> day<?php echo $reminder['days'] > 1 ? 's' : ''; ?> to go for your <?php echo $reminder['ordinal']; ?> paper (<?php echo date('d M Y', strtotime($reminder['date'])); ?>)
                            </span><br>
                        <?php elseif ($reminder['days'] === 0): ?>
                            <span style="display:inline-block; background:rgba(146,10,0,0.8); color:#fff; font-weight:600; border-radius:10px; padding:10px 24px; font-size:1.08rem; margin-bottom:10px; margin-top:5px;">
                                🎉 Your <?php echo $reminder['ordinal']; ?> paper is today! Good luck! (<?php echo date('d M Y', strtotime($reminder['date'])); ?>)
                            </span><br>
                        <?php else: ?>
                            <span style="display:inline-block; background:rgba(146,10,0,0.8); color:#fff; font-weight:600; border-radius:10px; padding:10px 24px; font-size:1.08rem; margin-bottom:10px; margin-top:5px;">
                                📚 Your <?php echo $reminder['ordinal']; ?> paper was on <?php echo date('d M Y', strtotime($reminder['date'])); ?>
                            </span><br>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (count($subjects) > 0): ?>
            <table id="examTable">
                <thead>
                    <tr>
                        <th>Subject ID</th>
                        <th>Subject Name</th>
                        <th>Exam Date</th>
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="print-btn-container">
                <button onclick="printExamTable()">
                    <svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;margin-right:8px;" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M2 7a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v-2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v2h1a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H2zm11 5v2H3v-2h10zm-1-7V2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3h10z"/></svg>
                    Print Exam Table
                </button>
            </div>
            <?php else: ?>
                <p style="text-align:center; color:#555; font-size:1.1rem;">You have no subjects registered yet.</p>
            <?php endif; ?>

            <!-- Place this where you want the chart -->
            <canvas id="cgpaChart" width="340" height="340"></canvas>
        </div>

        <div class="register-button-container">
            <button type="button" onclick="window.location.href='register_subject.php'">
                Subject Registration
            </button>
            <button type="button" class="edit" onclick="window.location.href='edit_profile.php'">
                Edit Profile
            </button>
            <button type="button" class="revision" onclick="window.location.href='revision_planning.php'">
                Revision Planning
            </button>
        </div>
    </div>

    <script>
        const cgpa = <?php echo json_encode((float)$cgpa); ?>;
        const remaining = Math.max(0, 4.00 - cgpa);

        const ctx = document.getElementById("cgpaChart").getContext("2d");

        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: ["Your CGPA", "Remaining"],
                datasets: [{
                    data: [cgpa, remaining],
                    backgroundColor: [
                        "#43e97b", // Green for CGPA
                        "#e0e0e0"  // Light gray for remaining
                    ],
                    borderWidth: 0,
                    hoverOffset: 16,
                    borderRadius: 18,
                    cutout: "75%"
                }]
            },
            options: {
                responsive: true,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1800,
                    easing: 'easeOutBounce'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: "#263238",
                            font: {
                                size: 16,
                                weight: "bold",
                                family: "'Montserrat', 'Segoe UI', sans-serif"
                            },
                            padding: 24,
                            boxWidth: 24
                        }
                    },
                    title: {
                        display: true,
                        text: `CGPA Summary: ${cgpa.toFixed(2)} / 4.00`,
                        color: "#1976d2",
                        font: {
                            size: 22,
                            weight: "bold",
                            family: "'Montserrat', 'Segoe UI', sans-serif"
                        },
                        padding: {
                            top: 18,
                            bottom: 18
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: "#263238",
                        titleColor: "#fff",
                        bodyColor: "#fff",
                        borderColor: "#43e97b",
                        borderWidth: 2,
                        padding: 14,
                        caretSize: 8,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                if (context.label === "Your CGPA") {
                                    return `Your CGPA: ${cgpa.toFixed(2)}`;
                                } else {
                                    return `Remaining: ${(4.00 - cgpa).toFixed(2)}`;
                                }
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: chart => {
                    const {ctx, chartArea: {width, height}} = chart;
                    ctx.save();
                    ctx.font = "bold 2.6rem 'Montserrat', 'Segoe UI', sans-serif";
                    ctx.fillStyle = "#1976d2";
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.fillText(cgpa.toFixed(2), width / 2, height / 2 + 5);
                    ctx.restore();
                }
            }]
        });

        function printExamTable() {
            const table = document.getElementById('examTable').outerHTML;
            const win = window.open('', '', 'width=800,height=600');
            win.document.write(`
                <html>
                <head>
                    <title>Print Exam Table</title>
                    <style>
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ccc; padding: 12px; text-align: center; border-radius: 10px; }
                        th { background-color: #f2f2f2; }
                        body { font-family: 'Segoe UI', sans-serif; padding: 40px; }
                    </style>
                </head>
                <body>
                    <h2>Your Registered Subjects</h2>
                    ${table}
                </body>
                </html>
            `);
            win.document.close();
            win.print();
        }
    </script>
</body>
</html>
