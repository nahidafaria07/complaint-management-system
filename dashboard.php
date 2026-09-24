
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = (int) $_SESSION["user_id"];
$name = $_SESSION["name"];
$role = $_SESSION["role"];

if ($role !== "Student") {
    header("Location: login.php");
    exit();
}

$count_sql = "SELECT
                COUNT(*) AS total,
                SUM(status = 'Pending') AS pending,
                SUM(status = 'In Progress') AS in_progress,
                SUM(status = 'Resolved') AS resolved
              FROM complaints
              WHERE user_id = ?";

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$counts = $count_stmt->get_result()->fetch_assoc();

$total = (int) ($counts["total"] ?? 0);
$pending = (int) ($counts["pending"] ?? 0);
$in_progress = (int) ($counts["in_progress"] ?? 0);
$resolved = (int) ($counts["resolved"] ?? 0);

$notification_sql = "
    SELECT COUNT(*) AS notification_count
    FROM complaint_updates cu
    JOIN complaints c
    ON cu.complaint_id = c.complaint_id
    WHERE c.user_id = ?
";

$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param("i", $user_id);
$notification_stmt->execute();

$notification_data = $notification_stmt->get_result()->fetch_assoc();
$notification_count = (int) ($notification_data["notification_count"] ?? 0);

$recent_sql = "SELECT complaint_id, title, category, priority, status, created_at
               FROM complaints
               WHERE user_id = ?
               ORDER BY created_at DESC
               LIMIT 5";

$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();



?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Dashboard - Complaint Management System</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
font-family: Arial, Helvetica, sans-serif;
background: #eef6ff;
color: #1f2937;
}

.dashboard-container {
    min-height: 100vh;
    display: flex;
}

.sidebar {
    width: 250px;
    min-height: 100vh;
    background: linear-gradient(180deg, #445976 0%, #6a92c7 55%, #597dac 100%);
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    padding: 25px 16px;
    box-shadow: 8px 0 25px rgba(31, 62, 105, 0.12);
    z-index: 10;
}

.logo-area {
    text-align: center;
    padding: 5px 0 28px;
}

.logo-box {
    width: 55px;
    height: 55px;
    border-radius: 16px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 24px;
}

.logo-area h2 {
    font-size: 18px;
    font-weight: 700;
}

.logo-area p {
    margin-top: 4px;
    font-size: 10px;
    color: #dce8f5;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.menu a {
    color: #e9f1fa;
    text-decoration: none;
    padding: 13px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: 0.2s;
}

.menu a:hover {
    background: rgba(255,255,255,0.12);
    transform: translateX(2px);
}

.menu a.active {
    background: white;
    color: #2f5f9f;
    box-shadow: 0 5px 15px rgba(0,0,0,0.10);
}

.notification-badge {
    background: #e74c3c;
    color: white;
    padding: 3px 7px;
    border-radius: 10px;
    font-size: 10px;
    margin-left: auto;
}

.main-content {
    width: calc(100% - 250px);
    margin-left: 250px;
    min-height: 100vh;
}

.topbar {
    height: 76px;
    background: white;
    border-bottom: 1px solid #e5eaf0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 35px;
    position: sticky;
    top: 0;
    z-index: 5;
}

.topbar h2 {
    color: #263b59;
    font-size: 19px;
}

.user {
    background: #edf4fc;
    color: #2f5f9f;
    padding: 9px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.content {
padding: 32px 35px 45px;
max-width: 1400px;
background: #eef6ff;
min-height: calc(100vh - 76px);
}

.hero {
    background: linear-gradient(135deg, #2f5f9f, #477bb8);
    border-radius: 18px;
    padding: 28px 30px;
    color: white;
    margin-bottom: 25px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(47,95,159,0.18);
}

.hero::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    right: -45px;
    top: -65px;
}

.hero h1 {
    font-size: 25px;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
}

.hero p {
    color: #e7eff9;
    font-size: 13px;
    position: relative;
    z-index: 1;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}
.stat-card {
    background: #eaf3ff;
    border-radius: 15px;
    padding: 21px;
    border: 1px solid #b9d4f5;
    box-shadow: 0 6px 18px rgba(31,55,82,0.06);
    transition: 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(31,55,82,0.10);
}

.stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-title {
    color: #7a8797;
    font-size: 12px;
    font-weight: 600;
}

.stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #edf4fc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.stat-number {
    font-size: 29px;
    color: #263b59;
    font-weight: 700;
    margin-top: 12px;
}

.complaint-section {
    background: #eaf3ff;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 25px;
    border: 1px solid #b9d4f5;
    box-shadow: 0 6px 18px rgba(31,55,82,0.05);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.section-header h2 {
    font-size: 17px;
    color: #263b59;
}

.view-all {
    text-decoration: none;
    color: #2f5f9f;
    font-size: 12px;
    font-weight: 700;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.recent-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

.recent-table th {
    background: #dbeafe;
    color: #1e3a8a;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #b9d4f5;
}

.recent-table td {
    padding: 14px 13px;
    font-size: 12px;
    color: #374151;
    border-bottom: 1px solid #cfe0f5;
}

.recent-table tr:last-child td {
    border-bottom: none;
}

.recent-table tr:hover {
    background: #dbeafe;
}

.id-cell {
    color: #2f5f9f !important;
    font-weight: 700;
}

.priority {
    font-weight: 700;
}

.status-badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    background: #edf4fc;
    color: #2f5f9f;
}

.view-btn {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 7px;
    text-decoration: none;
    background: #2f5f9f;
    color: white;
    font-size: 11px;
    font-weight: 600;
}

.view-btn:hover {
    background: #254f87;
}



.empty {
    background: #dbeafe;
    border: 1px dashed #93c5fd;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    color: #475569;
    font-size: 13px;
}

.submit-btn {
    display: inline-block;
    margin-top: 12px;
    padding: 9px 15px;
    border-radius: 8px;
    text-decoration: none;
    background: #2f5f9f;
    color: white;
    font-size: 12px;
    font-weight: 600;
}

@media (max-width: 1000px) {

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

    
}

@media (max-width: 750px) {

    .sidebar {
        width: 210px;
    }

    .main-content {
        width: calc(100% - 210px);
        margin-left: 210px;
    }

    .content {
        padding: 25px 20px;
    }

    .topbar {
        padding: 0 20px;
    }

}

@media (max-width: 600px) {

    .sidebar {
        position: relative;
        width: 100%;
        min-height: auto;
    }

    .dashboard-container {
        display: block;
    }

    .main-content {
        width: 100%;
        margin-left: 0;
    }

    .menu {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .menu a {
        flex: 1 1 45%;
    }

    .cards,
    .feature-grid {
        grid-template-columns: 1fr;
    }

    .topbar {
        height: auto;
        padding: 18px 20px;
        gap: 12px;
    }

    .content {
        padding: 20px 15px;
    }

}

</style>

</head>

<body>

<div class="dashboard-container">

    <aside class="sidebar">

        <div class="logo-area">

            <div class="logo-box">
                ✓
            </div>

            <h2>CMS</h2>

            <p>Complaint Management System</p>

        </div>

        <div class="menu">

            <a href="dashboard.php" class="active">
                🏠 Dashboard
            </a>

            <a href="submit_complaint.php">
                📝 Submit Complaint
            </a>

            <a href="my_complaints.php">
                📋 My Complaints
            </a>

            <a href="updates.php">
                🔔 Updates

                <?php if ($notification_count > 0): ?>
                    <span class="notification-badge">
                        <?php echo $notification_count; ?>
                    </span>
                <?php endif; ?>

            </a>

            <a href="student_profile.php">
                👤 Profile
            </a>

            <a href="logout.php">
                🚪 Logout
            </a>

        </div>

    </aside>

    <main class="main-content">

        <div class="topbar">

            <h2>Student Dashboard</h2>

            <div class="user">
                👤 <?php echo htmlspecialchars($name); ?>
            </div>

        </div>

        <div class="content">

            <div class="hero">

                <h1>
                    Welcome, <?php echo htmlspecialchars($name); ?>! 👋
                </h1>

                <p>
                    Manage your complaints, track updates and stay informed from one place.
                </p>

            </div>

            <div class="cards">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-title">
                            TOTAL COMPLAINTS
                        </div>

                        <div class="stat-icon">
                            📋
                        </div>

                    </div>

                    <div class="stat-number">
                        <?php echo $total; ?>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-title">
                            PENDING
                        </div>

                        <div class="stat-icon">
                            ⏳
                        </div>

                    </div>

                    <div class="stat-number">
                        <?php echo $pending; ?>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-title">
                            IN PROGRESS
                        </div>

                        <div class="stat-icon">
                            🔄
                        </div>

                    </div>

                    <div class="stat-number">
                        <?php echo $in_progress; ?>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-title">
                            RESOLVED
                        </div>

                        <div class="stat-icon">
                            ✓
                        </div>

                    </div>

                    <div class="stat-number">
                        <?php echo $resolved; ?>
                    </div>

                </div>

            </div>

            <div class="complaint-section">

                <div class="section-header">

                    <h2>Recent Complaints</h2>

                    <a href="my_complaints.php" class="view-all">
                        View All →
                    </a>

                </div>

                <?php if ($recent_result->num_rows > 0): ?>

                    <div class="table-wrapper">

                        <table class="recent-table">

                            <thead>

                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php while ($complaint = $recent_result->fetch_assoc()): ?>

                                <tr>

                                    <td class="id-cell">
                                        #<?php echo htmlspecialchars($complaint["complaint_id"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($complaint["title"]); ?>
                                    </td>

                                    <td class="priority">
                                        <?php echo htmlspecialchars($complaint["priority"]); ?>
                                    </td>

                                    <td>

                                        <span class="status-badge">
                                            <?php echo htmlspecialchars($complaint["status"]); ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?php echo date("d M Y, h:i A", strtotime($complaint["created_at"])); ?>
                                    </td>

                                    <td>

                                        <a
                                            class="view-btn"
                                            href="complaint_details.php?id=<?php echo (int)$complaint["complaint_id"]; ?>"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty">

                        <p>
                            You haven't submitted any complaints yet.
                        </p>

                        <a href="submit_complaint.php" class="submit-btn">
                            + Submit Complaint
                        </a>

                    </div>

                <?php endif; ?>

            </div>
        </div>

    </main>

</div>

</body>

</html>
