<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$name = $_SESSION["name"];
$role = $_SESSION["role"];

if ($role != "Teacher") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $complaint_id = intval($_POST["complaint_id"]);
    $staff_id = intval($_POST["staff_id"]);

    $assign_sql = "UPDATE complaints
                   SET staff_id = ?
                   WHERE complaint_id = ?";

    $assign_stmt = $conn->prepare($assign_sql);

    $assign_stmt->bind_param(
        "ii",
        $staff_id,
        $complaint_id
    );

    $assign_stmt->execute();

    header("Location: teacher_dashboard.php");
    exit();
}

$total_sql = "SELECT COUNT(*) AS total FROM complaints";
$total_result = $conn->query($total_sql);
$total = $total_result->fetch_assoc()["total"];

$pending_sql = "SELECT COUNT(*) AS total
                FROM complaints
                WHERE status = 'Pending'";
$pending_result = $conn->query($pending_sql);
$pending = $pending_result->fetch_assoc()["total"];

$progress_sql = "SELECT COUNT(*) AS total
                 FROM complaints
                 WHERE status = 'In Progress'";
$progress_result = $conn->query($progress_sql);
$in_progress = $progress_result->fetch_assoc()["total"];

$resolved_sql = "SELECT COUNT(*) AS total
                 FROM complaints
                 WHERE status = 'Resolved'";
$resolved_result = $conn->query($resolved_sql);
$resolved = $resolved_result->fetch_assoc()["total"];

$assigned_sql = "SELECT COUNT(*) AS total
                 FROM complaints
                 WHERE staff_id IS NOT NULL";
$assigned_result = $conn->query($assigned_sql);
$assigned = $assigned_result->fetch_assoc()["total"];

$unassigned_sql = "SELECT COUNT(*) AS total
                   FROM complaints
                   WHERE staff_id IS NULL";
$unassigned_result = $conn->query($unassigned_sql);
$unassigned = $unassigned_result->fetch_assoc()["total"];

$category_sql = "SELECT category, COUNT(*) AS total
                 FROM complaints
                 WHERE category IS NOT NULL
                 AND category != ''
                 GROUP BY category
                 ORDER BY total DESC";
$category_result = $conn->query($category_sql);

$department_sql = "SELECT department, COUNT(*) AS total
                   FROM complaints
                   WHERE department IS NOT NULL
                   AND department != ''
                   GROUP BY department
                   ORDER BY total DESC";
$department_result = $conn->query($department_sql);

$priority_sql = "SELECT priority, COUNT(*) AS total
                 FROM complaints
                 WHERE priority IS NOT NULL
                 AND priority != ''
                 GROUP BY priority
                 ORDER BY total DESC";
$priority_result = $conn->query($priority_sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Teacher Dashboard</title>

<link rel="stylesheet" href="style.css">

<style>

    body {
        background: #eef6ff;
    }

    .main-content {
        background: #eef6ff;
    }

    .content {
        background: #eef6ff;
        min-height: calc(100vh - 70px);
        padding: 30px;
    }

    .welcome-text {
        color: #64748b;
        margin-bottom: 25px;
    }

    .cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        background: #eaf3ff;
        border: 1px solid #b9d4f5;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 6px 18px rgba(31, 55, 82, 0.06);
    }

    .card-title {
        color: #475569;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .card-number {
        color: #1e3a8a;
        font-size: 30px;
        font-weight: bold;
    }

    .dashboard-section {
        background: #eaf3ff;
        border: 1px solid #b9d4f5;
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 6px 18px rgba(31, 55, 82, 0.05);
    }

    .dashboard-section h2 {
        color: #1e3a8a;
        margin-top: 0;
    }

    .statistics-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 20px;
    }

    .stat-box {
        background: #f8fbff;
        border: 1px solid #c7dcf5;
        border-radius: 12px;
        padding: 20px;
    }

    .stat-box h3 {
        color: #1e3a8a;
        margin-top: 0;
        margin-bottom: 15px;
    }

    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .stat-row:last-child {
        border-bottom: none;
    }

    .stat-name {
        color: #475569;
    }

    .stat-value {
        background: #dbeafe;
        color: #1e40af;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: bold;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 20px;
    }

    .quick-btn {
        display: block;
        text-decoration: none;
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
        padding: 18px;
        border-radius: 12px;
        text-align: center;
        font-weight: bold;
        transition: 0.2s;
    }

    .quick-btn:hover {
        background: #bfdbfe;
        transform: translateY(-2px);
    }

    .no-data {
        color: #64748b;
        font-size: 14px;
    }

    @media (max-width: 900px) {

        .cards {
            grid-template-columns: repeat(2, 1fr);
        }

        .statistics-grid {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 600px) {

        .cards {
            grid-template-columns: 1fr;
        }

        .content {
            padding: 20px;
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

        <a href="teacher_dashboard.php" class="active">
            🏠 Dashboard
        </a>

        <a href="teacher_complaints.php">
            📋 All Complaints
        </a>

        <a href="updates.php">
            🔔 Updates
        </a>

        <a href="teacher_profile.php">
            👤 Profile
        </a>

        <a href="logout.php">
            🚪 Logout
        </a>

    </div>

</aside>

<main class="main-content">

    <div class="topbar">

        <h2>
            Teacher Dashboard
        </h2>

        <div class="user">

            👤

            <?php
            echo htmlspecialchars($name);
            ?>

        </div>

    </div>

    <div class="content">

        <h1>

            Welcome,
            <?php
            echo htmlspecialchars($name);
            ?>! 👋

        </h1>

        <p class="welcome-text">
            Manage and monitor student complaints from here.
        </p>

        <div class="cards">

            <div class="card">

                <div class="card-title">
                    Total Complaints
                </div>

                <div class="card-number">
                    <?php echo $total; ?>
                </div>

            </div>

            <div class="card">

                <div class="card-title">
                    Pending
                </div>

                <div class="card-number">
                    <?php echo $pending; ?>
                </div>

            </div>

            <div class="card">

                <div class="card-title">
                    In Progress
                </div>

                <div class="card-number">
                    <?php echo $in_progress; ?>
                </div>

            </div>

            <div class="card">

                <div class="card-title">
                    Resolved
                </div>

                <div class="card-number">
                    <?php echo $resolved; ?>
                </div>

            </div>

            <div class="card">

                <div class="card-title">
                    Assigned
                </div>

                <div class="card-number">
                    <?php echo $assigned; ?>
                </div>

            </div>

            <div class="card">

                <div class="card-title">
                    Unassigned
                </div>

                <div class="card-number">
                    <?php echo $unassigned; ?>
                </div>

            </div>

        </div>

        <div class="dashboard-section">

            <h2>
                📊 Complaint Statistics
            </h2>

            <p>
                Overview of complaints by category, department and priority.
            </p>

            <div class="statistics-grid">

                <div class="stat-box">

                    <h3>
                        Category-wise
                    </h3>

                    <?php

                    if ($category_result->num_rows > 0) {

                        while ($row = $category_result->fetch_assoc()) {

                    ?>

                            <div class="stat-row">

                                <span class="stat-name">
                                    <?php
                                    echo htmlspecialchars($row["category"]);
                                    ?>
                                </span>

                                <span class="stat-value">
                                    <?php
                                    echo $row["total"];
                                    ?>
                                </span>

                            </div>

                    <?php

                        }

                    } else {

                        echo '<div class="no-data">No category data available.</div>';

                    }

                    ?>

                </div>

                <div class="stat-box">

                    <h3>
                        Department-wise
                    </h3>

                    <?php

                    if ($department_result->num_rows > 0) {

                        while ($row = $department_result->fetch_assoc()) {

                    ?>

                            <div class="stat-row">

                                <span class="stat-name">
                                    <?php
                                    echo htmlspecialchars($row["department"]);
                                    ?>
                                </span>

                                <span class="stat-value">
                                    <?php
                                    echo $row["total"];
                                    ?>
                                </span>

                            </div>

                    <?php

                        }

                    } else {

                        echo '<div class="no-data">No department data available.</div>';

                    }

                    ?>

                </div>

                <div class="stat-box">

                    <h3>
                        Priority-wise
                    </h3>

                    <?php

                    if ($priority_result->num_rows > 0) {

                        while ($row = $priority_result->fetch_assoc()) {

                    ?>

                            <div class="stat-row">

                                <span class="stat-name">
                                    <?php
                                    echo htmlspecialchars($row["priority"]);
                                    ?>
                                </span>

                                <span class="stat-value">
                                    <?php
                                    echo $row["total"];
                                    ?>
                                </span>

                            </div>

                    <?php

                        }

                    } else {

                        echo '<div class="no-data">No priority data available.</div>';

                    }

                    ?>

                </div>

            </div>

        </div>

        <div class="dashboard-section">

            <h2>
                Quick Access
            </h2>

            <p>
                Use the options below to manage student complaints and view updates.
            </p>

            <div class="quick-actions">

                <a href="teacher_complaints.php" class="quick-btn">
                    📋 View All Complaints
                </a>

                <a href="teacher_updates.php" class="quick-btn">
                    🔔 View Updates
                </a>

                <a href="teacher_profile.php" class="quick-btn">
                    👤 View Profile
                </a>

            </div>

        </div>

    </div>

</main>


</div>

</body>

</html>
