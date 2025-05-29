<?php
session_start();
require 'database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Handle topic deletion (Done button)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_topic_id'])) {
    $topic_id = $_POST['delete_topic_id'];
    $delStmt = $conn->prepare("DELETE t FROM topics1 t
                      JOIN subjects1 s ON t.subject_reg_id = s.subject_reg_id
                      WHERE t.topic_id = ? AND s.student_id = ?");
    $delStmt->execute([$topic_id, $student_id]);
    header("Location: revision_planning.php");
    exit();
}

// Handle topic update (Edit -> Save)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_topic_id'])) {
    $topic_id = $_POST['update_topic_id'];
    $new_topic_name = trim($_POST['topic_name']);
    $new_study_date = $_POST['study_date'];

    if ($new_topic_name !== '' && $new_study_date !== '') {
        // Update topic_name and study_date for that topic_id only if owned by student
        $updateStmt = $conn->prepare("UPDATE topics1 t
                                     JOIN subjects1 s ON t.subject_reg_id = s.subject_reg_id
                                     SET t.topic_name = ?, t.study_date = ?
                                     WHERE t.topic_id = ? AND s.student_id = ?");
        $updateStmt->execute([$new_topic_name, $new_study_date, $topic_id, $student_id]);
    }
    header("Location: revision_planning.php");
    exit();
}

// Fetch subjects for this student
$stmt = $conn->prepare("SELECT * FROM subjects1 WHERE student_id = ?");
$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle new topic insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_reg_id']) && isset($_POST['topic_name']) && isset($_POST['study_date'])) {
    $subject_reg_id = $_POST['subject_reg_id'];
    $topic_name = trim($_POST['topic_name']);
    $study_date = $_POST['study_date'];

    if ($topic_name !== '' && $study_date !== '') {
        $topic_id = "TOPIC_" . uniqid();
        $insertStmt = $conn->prepare("INSERT INTO topics1 (topic_id, subject_reg_id, topic_name, study_date) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$topic_id, $subject_reg_id, $topic_name, $study_date]);

        // Increment total_topics_ever for this subject registration
        $conn->prepare("UPDATE subjects1 SET total_topics_ever = total_topics_ever + 1 WHERE subject_reg_id = ?")
             ->execute([$subject_reg_id]);

        header("Location: revision_planning.php");
        exit();
    }
}

// Fetch topics grouped by subject
$topicsBySubject = [];
if (count($subjects) > 0) {
    $subjectRegIds = array_column($subjects, 'subject_reg_id');
    if (count($subjectRegIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($subjectRegIds), '?'));
        $topicStmt = $conn->prepare(
            "SELECT t.*, s.exam_date, s.subject_name, s.subject_id, s.subject_reg_id
             FROM topics1 t
             JOIN subjects1 s ON t.subject_reg_id = s.subject_reg_id
             WHERE t.subject_reg_id IN ($placeholders) AND t.status = 'pending'
             ORDER BY s.subject_name, t.study_date"
        );
        $topicStmt->execute($subjectRegIds);
        $allTopics = $topicStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allTopics as $topic) {
            $topicsBySubject[$topic['subject_reg_id']][] = $topic;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Revision Planning - Study Buddy</title>
<link rel="icon" href="../static/pictures/Study Buddy Logo.ico" type="image/x-icon" />
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
        padding: 40px 0 20px 0;
    }
    .header-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 18px;
        background: rgba(255,255,255,0.98);
        box-shadow: var(--box-shadow);
        border-radius: 0 0 22px 22px;
        padding: 18px 0 14px 0;
        margin-bottom: 32px;
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }
    .header-bar img {
        height: 60px;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(33,150,243,0.07);
    }
    .header-bar h2 {
        margin: 0;
        color: #263238;
        font-weight: 700;
        letter-spacing: 1px;
        font-size: 1.5rem;
    }
    .planning-box {
        background: rgba(255,255,255,0.98);
        padding: 38px 38px 32px 38px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        max-width: 800px;
        margin: 0 auto 40px auto;
        animation: fadeIn 1s ease;
        position: relative;
    }
    h3 {
        text-align: center;
        margin-bottom: 30px;
        color: #263238;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .subject-section {
        border: 1.5px solid #e3f2fd;
        border-radius: 16px;
        margin-bottom: 28px;
        padding: 18px 22px 10px 22px;
        background: #f9f9f9;
        box-shadow: 0 2px 8px rgba(33,150,243,0.04);
    }
    .subject-header {
        font-weight: bold;
        font-size: 1.2rem;
        cursor: pointer;
        user-select: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #1976d2;
        margin-bottom: 8px;
    }
    .arrow {
        font-size: 22px;
        transition: transform 0.3s ease;
    }
    .arrow.down { transform: rotate(0deg);}
    .arrow.up { transform: rotate(180deg);}
    .topic-form {
        margin-top: 10px;
        display: none;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(76,175,80,0.06);
        padding: 18px 14px 8px 14px;
    }
    .topic-form.active { display: block; }
    .topic-form label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        margin-top: 10px;
        font-size: 15px;
        color: #263238;
        text-align: left;
    }
    .topic-form input[type="text"],
    .topic-form input[type="date"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 8px;
        border: 1.5px solid #bdbdbd;
        font-size: 15px;
        background: #f7fafc;
        transition: border-color 0.2s;
    }
    .topic-form input[type="text"]:focus,
    .topic-form input[type="date"]:focus {
        border-color: var(--main-color);
        outline: none;
        background: #e8f5e9;
    }
    .topic-form button {
        background: linear-gradient(90deg, #4CAF50 60%, #81c784 100%);
        color: white;
        border: none;
        padding: 12px 0;
        border-radius: 10px;
        font-size: 1.08rem;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        box-shadow: 0 2px 8px rgba(76,175,80,0.10);
        transition: background 0.2s, transform 0.2s;
        margin-top: 10px;
    }
    .topic-form button:hover {
        background: linear-gradient(90deg, #388e3c 60%, #4CAF50 100%);
        transform: translateY(-2px) scale(1.03);
    }
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 18px;
        margin-bottom: 10px;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(33,150,243,0.04);
    }
    th, td {
        border: none;
        padding: 16px 10px;
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
    .action-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        margin: 0 5px;
        font-size: 14px;
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
    .done-btn {
        background: linear-gradient(90deg, #4CAF50 60%, #81c784 100%);
        color: white;
    }
    .done-btn:hover {
        background: linear-gradient(90deg, #388e3c 60%, #4CAF50 100%);
    }
    .save-btn {
        background: linear-gradient(90deg, #2196F3 60%, #90caf9 100%);
        color: white;
    }
    .save-btn:hover {
        background: linear-gradient(90deg, #1976d2 60%, #2196F3 100%);
    }
    .cancel-btn {
        background: linear-gradient(90deg, #f44336 60%, #e57373 100%);
        color: white;
    }
    .cancel-btn:hover {
        background: linear-gradient(90deg, #d32f2f 60%, #f44336 100%);
    }
    input.editable-input {
        width: 90%;
        padding: 6px;
        font-size: 14px;
        border-radius: 6px;
        border: 1px solid #aaa;
    }
    #calendar {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        border-radius: 16px;
        background: rgba(255,255,255,0.98);
        box-shadow: var(--box-shadow);
        margin-bottom: 40px;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px);}
        to { opacity: 1; transform: translateY(0);}
    }
    @media (max-width: 900px) {
        .planning-box, #calendar, .header-bar { max-width: 98vw; }
    }
    @media (max-width: 600px) {
        .planning-box, #calendar, .header-bar { padding: 18px 4vw; }
        .header-bar img { height: 40px; }
        th, td { font-size: 0.98rem; padding: 10px 4px; }
    }
</style>
<!-- FullCalendar CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
</head>
<body>

<div class="header-bar" style="flex-direction: column; gap: 0; padding-bottom: 0; background: transparent; box-shadow: none; border-radius: 0; margin-bottom: 0;">
    <img src="../static/pictures/Study Buddy Logo.jpg" alt="Study Buddy Logo" style="height:150px; display:block; margin-bottom: 18px; border-radius: 18px; box-shadow: 0 2px 12px rgba(33,150,243,0.13);" />
</div>
<div style="text-align:center; margin-bottom: 32px;">
    <h2 style="margin:0; color:#263238; font-weight:700; letter-spacing:1px; font-size:1.5rem;">
        Revision Planning - Welcome, <?php echo htmlspecialchars($student_name); ?>
    </h2>
</div>

<div id="calendar"></div>

<div class="planning-box">
    <h3>Your Revision Topics</h3>
    <?php if (count($subjects) === 0): ?>
        <p style="text-align:center; color:#555; font-size:1.1rem;">You have no subjects registered yet. Please add subjects first.</p>
    <?php else: ?>
        <?php foreach ($subjects as $subject): ?>
    <div class="subject-section">
        <div class="subject-header" onclick="toggleSection('<?php echo $subject['subject_id']; ?>')">
            <?php echo htmlspecialchars($subject['subject_name']); ?>
            <span class="arrow down" id="arrow-<?php echo $subject['subject_id']; ?>">&#9660;</span>
        </div>

        <?php
        // Progress Bar Logic for each subject
        // Get total topics ever added for this subject
        $totalTopicsEver = 0;
        foreach ($subjects as $subj) {
            if ($subj['subject_id'] === $subject['subject_id']) {
                $totalTopicsEver = (int)$subj['total_topics_ever'];
                break;
            }
        }

        // Get current topics (pending) for this subject
        $topics = $topicsBySubject[$subject['subject_reg_id']] ?? [];
        $pendingTopics = count($topics);

        // Calculate done topics and progress
        $doneTopics = $totalTopicsEver - $pendingTopics;
        $progressPercent = $totalTopicsEver > 0 ? round(($doneTopics / $totalTopicsEver) * 100, 1) : 0;

        // Progress bar color logic
        if ($progressPercent <= 30) {
            $progressColor = 'rgba(244,67,54,0.85)'; // red
        } elseif ($progressPercent <= 50) {
            $progressColor = 'rgba(255,193,7,0.85)'; // yellow
        } elseif ($progressPercent <= 80) {
            $progressColor = 'rgba(33,150,243,0.85)'; // blue
        } else {
            $progressColor = 'rgba(76,175,80,0.85)'; // green
        }
        ?>
        <!-- Progress Bar -->
        <div style="margin: 12px 0 18px 0;">
            <div style="font-size: 0.98rem; color: #1976d2; font-weight: 600; margin-bottom: 6px;">
                Progress: <?php echo $totalTopicsEver === 0 ? 'No topics yet' : ($progressPercent . '% completed'); ?>
            </div>
            <div style="background: #e3f2fd; border-radius: 8px; height: 18px; width: 100%; box-shadow: 0 1px 4px rgba(33,150,243,0.07); overflow: hidden;">
                <div style="
                    height: 100%;
                    width: <?php echo $progressPercent; ?>%;
                    background: <?php echo $progressColor; ?>;
                    border-radius: 8px 0 0 8px;
                    transition: width 0.5s;
                    text-align: right;
                    color: #fff;
                    font-weight: 600;
                    font-size: 0.95rem;
                    line-height: 18px;
                    padding-right: 10px;
                ">
                    <?php echo $progressPercent; ?>%
                </div>
            </div>
        </div>
        <!-- End Progress Bar -->

        <div class="topic-form" id="form-<?php echo $subject['subject_id']; ?>">
            <form method="POST" action="">
                <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>" />
                <input type="hidden" name="subject_reg_id" value="<?php echo $subject['subject_reg_id']; ?>" />
                <label for="topic_name_<?php echo $subject['subject_id']; ?>">Topic name:</label>
                <input type="text" name="topic_name" id="topic_name_<?php echo $subject['subject_id']; ?>" required />
                <label for="study_date_<?php echo $subject['subject_id']; ?>">Study date:</label>
                <input type="date" name="study_date" id="study_date_<?php echo $subject['subject_id']; ?>" required />
                <button type="submit">Add topic</button>
            </form>
        </div>
        <?php
        if ($pendingTopics === 0): ?>
            <p style="margin-top:15px; color:#888;">No topics added yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Topic name</th>
                        <th>Study date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topics as $topic): ?>
                    <tr id="topic-row-<?php echo $topic['topic_id']; ?>">
                        <td class="topic-name"><?php echo htmlspecialchars($topic['topic_name']); ?></td>
                        <td class="study-date"><?php echo htmlspecialchars($topic['study_date']); ?></td>
                        <td class="actions">
                            <button class="action-btn edit-btn" onclick="enableEdit('<?php echo $topic['topic_id']; ?>')">Edit</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Mark this topic as done and delete?');">
                                <input type="hidden" name="delete_topic_id" value="<?php echo $topic['topic_id']; ?>">
                                <button type="submit" class="action-btn done-btn">Done</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
    <?php endif; ?>
</div>

<div style="display: flex; justify-content: center; margin: 40px 0 0 0;">
    <div class="register-box" style="background:transparent; box-shadow:none; max-width:420px; padding:0;">
        <a href="dashboard.php" style="display:block; margin-top:0; color:var(--accent-color); font-weight:bold; text-decoration:none; font-size:15px; transition:color 0.2s; text-align:center;">
            ← Back to Dashboard
        </a>
    </div>
</div>

<script>
function toggleSection(subjectId) {
    const form = document.getElementById('form-' + subjectId);
    const arrow = document.getElementById('arrow-' + subjectId);
    const allForms = document.querySelectorAll('.topic-form');
    const allArrows = document.querySelectorAll('.arrow');

    allForms.forEach(f => {
        if (f.id !== 'form-' + subjectId) {
            f.classList.remove('active');
        }
    });
    allArrows.forEach(a => {
        if (a.id !== 'arrow-' + subjectId) {
            a.classList.remove('up');
            a.classList.add('down');
        }
    });

    if (form.classList.contains('active')) {
        form.classList.remove('active');
        arrow.classList.remove('up');
        arrow.classList.add('down');
    } else {
        form.classList.add('active');
        arrow.classList.remove('down');
        arrow.classList.add('up');
    }
}

function enableEdit(topicId) {
    const row = document.getElementById('topic-row-' + topicId);
    const topicNameTd = row.querySelector('.topic-name');
    const studyDateTd = row.querySelector('.study-date');
    const actionsTd = row.querySelector('.actions');

    const currentName = topicNameTd.textContent.trim();
    const currentDate = studyDateTd.textContent.trim();

    topicNameTd.innerHTML = `<input type="text" class="editable-input" id="edit-topic-name-${topicId}" value="${currentName}">`;
    studyDateTd.innerHTML = `<input type="date" class="editable-input" id="edit-study-date-${topicId}" value="${currentDate}">`;

    actionsTd.innerHTML = `
        <button class="action-btn save-btn" onclick="saveEdit('${topicId}')">Save</button>
        <button class="action-btn cancel-btn" onclick="cancelEdit('${topicId}', '${currentName}', '${currentDate}')">Cancel</button>
    `;
}

function saveEdit(topicId) {
    const newName = document.getElementById('edit-topic-name-' + topicId).value.trim();
    const newDate = document.getElementById('edit-study-date-' + topicId).value;

    if (newName === '' || newDate === '') {
        alert("Please fill out both Topic name and Study date.");
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const updateIdInput = document.createElement('input');
    updateIdInput.name = 'update_topic_id';
    updateIdInput.value = topicId;
    form.appendChild(updateIdInput);

    const topicNameInput = document.createElement('input');
    topicNameInput.name = 'topic_name';
    topicNameInput.value = newName;
    form.appendChild(topicNameInput);

    const studyDateInput = document.createElement('input');
    studyDateInput.name = 'study_date';
    studyDateInput.value = newDate;
    form.appendChild(studyDateInput);

    document.body.appendChild(form);
    form.submit();
}

function cancelEdit(topicId, oldName, oldDate) {
    const row = document.getElementById('topic-row-' + topicId);
    const topicNameTd = row.querySelector('.topic-name');
    const studyDateTd = row.querySelector('.study-date');
    const actionsTd = row.querySelector('.actions');

    topicNameTd.textContent = oldName;
    studyDateTd.textContent = oldDate;

    actionsTd.innerHTML = `
        <button class="action-btn edit-btn" onclick="enableEdit('${topicId}')">Edit</button>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Mark this topic as done and delete?');">
            <input type="hidden" name="delete_topic_id" value="${topicId}">
            <button type="submit" class="action-btn done-btn">Done</button>
        </form>
    `;
}

const calendarEvents = [
<?php foreach ($topicsBySubject as $subjectId => $topics): 
    foreach ($topics as $topic): ?>
    {
        title: "<?php echo addslashes($topic['topic_name']); ?> (<?php echo addslashes($topic['subject_name']); ?>)",
        start: "<?php echo $topic['study_date']; ?>",
        allDay: true
    },
<?php endforeach; endforeach; ?>
];

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 500,
            events: calendarEvents,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            }
        });
        calendar.render();
    }
});
</script>
</body>
</html>
