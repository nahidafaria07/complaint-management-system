
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"];
$role = $_SESSION["role"];

if ($role == "Student") {

    $sql = "SELECT 
                complaint_updates.*,
                complaints.title AS complaint_title,
                complaints.complaint_id,
                users.name AS actor_name,
                roles.role_name AS actor_role
            FROM complaint_updates
            INNER JOIN complaints
                ON complaint_updates.complaint_id = complaints.complaint_id
            LEFT JOIN users
                ON complaint_updates.teacher_id = users.user_id
            LEFT JOIN roles
                ON users.role_id = roles.role_id
            WHERE complaints.user_id = ?
            ORDER BY complaint_updates.updated_at DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

} elseif ($role == "Teacher") {

    $sql = "SELECT 
                complaint_updates.*,
                complaints.title AS complaint_title,
                complaints.complaint_id,
                students.name AS student_name,
                actors.name AS actor_name,
                roles.role_name AS actor_role
            FROM complaint_updates
            INNER JOIN complaints
                ON complaint_updates.complaint_id = complaints.complaint_id
            INNER JOIN users AS students
                ON complaints.user_id = students.user_id
            LEFT JOIN users AS actors
                ON complaint_updates.teacher_id = actors.user_id
            LEFT JOIN roles
                ON actors.role_id = roles.role_id
            ORDER BY complaint_updates.updated_at DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

} else {

    header("Location: login.php");
    exit();

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Updates</title>

<link rel="stylesheet" href="style.css">

<style>

.updates-page {
    background: #eef6ff;
    min-height: calc(100vh - 70px);
    padding: 35px;
}

.updates-page h1 {
    color: #1e3a8a;
    margin-bottom: 8px;
}

.welcome-text {
    color: #64748b;
    margin-bottom: 25px;
}

.updates-timeline {
    background: #dbeafe;
    padding: 25px 30px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(37, 99, 235, 0.15);
    border: 1px solid #93c5fd;
}

.updates-item {
    background: #eff6ff;
    display: flex;
    gap: 18px;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 10px;
    border: 1px solid #bfdbfe;
    position: relative;
}

.updates-item:last-child {
    margin-bottom: 0;
}

.updates-item h3 {
    color: #1d4ed8;
    font-size: 19px;
    margin-bottom: 10px;
}

.updates-item p {
    color: #475569;
    margin-top: 7px;
    line-height: 1.6;
}

.updates-item strong {
    color: #1e3a8a;
}

.updates-item small {
    display: block;
    margin-top: 12px;
    color: #475569;
}

.updates-dot {
    width: 15px;
    height: 15px;
    min-width: 15px;
    background: #2563eb;
    border-radius: 50%;
    margin-top: 5px;
    box-shadow: 0 0 0 5px #bfdbfe;
}

.student-info {
    background: #dbeafe;
    border: 1px solid #93c5fd;
    border-radius: 8px;
    padding: 9px 12px;
    margin-top: 12px;
    color: #1e3a8a;
    font-size: 13px;
}

.updates-empty {
    background: #ffffff;
    border: 1px solid #dbeafe;
    border-radius: 12px;
    padding: 50px;
    text-align: center;
    color: #64748b;
    box-shadow: 0 3px 12px rgba(37, 99, 235, 0.08);
}

.updates-empty .submit-btn {
    background: #2563eb;
    color: white;
}

.updates-empty .submit-btn:hover {
    background: #1d4ed8;
}

@media (max-width: 600px) {

    .updates-page {
        padding: 20px;
    }

    .updates-timeline {
        padding: 20px;
    }

    .updates-item {
        gap: 15px;
    }

}

</style>

</head>

<body>

<div class="dashboard-container">

<aside class="sidebar">

    <div class="logo">
        CMS
    </div>

    <div class="menu">

        <?php if ($role == "Student"): ?>

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="submit_complaint.php">
                📝 Submit Complaint
            </a>

            <a href="my_complaints.php">
                📋 My Complaints
            </a>

            <a href="updates.php" class="active">
                🔔 Updates
            </a>

            <a href="student_profile.php">
                👤 Profile
            </a>

            <a href="logout.php">
                🚪 Logout
            </a>

        <?php elseif ($role == "Teacher"): ?>

            <a href="teacher_dashboard.php">
                🏠 Dashboard
            </a>

            <a href="teacher_complaints.php">
                📋 All Complaints
            </a>

            <a href="updates.php" class="active">
                🔔 Updates
            </a>

            <a href="teacher_profile.php">
                👤 Profile
            </a>

            <a href="logout.php">
                🚪 Logout
            </a>

        <?php endif; ?>

    </div>

</aside>

<main class="main-content">

    <div class="topbar">

        <h2>Updates</h2>

        <div class="user">
            👤 <?php echo htmlspecialchars($name); ?>
        </div>

    </div>

    <div class="updates-page">

        <h1>Complaint Updates</h1>

        <?php if ($role == "Teacher"): ?>

            <p class="welcome-text">
                Here you can see the latest updates of all complaints.
            </p>

        <?php else: ?>

            <p class="welcome-text">
                Here you can see the latest updates of your complaints.
            </p>

        <?php endif; ?>


        <?php if ($result->num_rows > 0): ?>

            <div class="updates-timeline">

                <?php while ($row = $result->fetch_assoc()): ?>

                    <div class="updates-item">

                        <div class="updates-dot"></div>

                        <div>

                            <h3>
                                <?php echo htmlspecialchars($row["complaint_title"]); ?>
                            </h3>


                            <?php if ($role == "Teacher" && !empty($row["student_name"])): ?>

                                <div class="student-info">
                                    <strong>Student:</strong>
                                    <?php echo htmlspecialchars($row["student_name"]); ?>
                                </div>

                            <?php endif; ?>


                            <p>

                                <strong>Status:</strong>

                                <?php echo htmlspecialchars($row["old_status"]); ?>

                                →

                                <strong>
                                    <?php echo htmlspecialchars($row["new_status"]); ?>
                                </strong>

                            </p>


                            <?php if (!empty($row["update_message"])): ?>

                                <p>

                                    <strong>
                                        <?php
                                        if ($role == "Teacher") {
                                            echo "Update Message:";
                                        } else {
                                            echo "Teacher Message:";
                                        }
                                        ?>
                                    </strong>

                                    <br>

                                    <?php echo nl2br(htmlspecialchars($row["update_message"])); ?>

                                </p>

                            <?php endif; ?>


                            <small>

                                Updated by:

                                <?php

                                if (!empty($row["actor_name"])) {
                                    echo htmlspecialchars($row["actor_name"]);
                                } else {
                                    echo "System";
                                }

                                if (!empty($row["actor_role"])) {
                                    echo " (" . htmlspecialchars($row["actor_role"]) . ")";
                                }

                                ?>

                                |

                                <?php

                                echo date(
                                    "d M Y, h:i A",
                                    strtotime($row["updated_at"])
                                );

                                ?>

                            </small>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="updates-empty">

                <p>
                    No updates available yet.
                </p>

                <?php if ($role == "Student"): ?>

                    <a href="my_complaints.php" class="submit-btn">
                        View My Complaints
                    </a>

                <?php else: ?>

                    <a href="teacher_complaints.php" class="submit-btn">
                        View All Complaints
                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>

</main>

</div>

</body>

</html>
