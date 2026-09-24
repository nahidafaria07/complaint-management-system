
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$name = $_SESSION["name"];
$role = $_SESSION["role"];

if ($role != "Admin") {
    header("Location: login.php");
    exit();
}

$total_users_sql = "SELECT COUNT(*) AS total FROM users";
$total_users_result = $conn->query($total_users_sql);
$total_users = $total_users_result->fetch_assoc()["total"];

$total_complaints_sql = "SELECT COUNT(*) AS total FROM complaints";
$total_complaints_result = $conn->query($total_complaints_sql);
$total_complaints = $total_complaints_result->fetch_assoc()["total"];

$pending_sql = "SELECT COUNT(*) AS total
                FROM complaints
                WHERE status = 'Pending'";

$pending_result = $conn->query($pending_sql);
$pending = $pending_result->fetch_assoc()["total"];

$resolved_sql = "SELECT COUNT(*) AS total
                 FROM complaints
                 WHERE status = 'Resolved'";

$resolved_result = $conn->query($resolved_sql);
$resolved = $resolved_result->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #eef6ff;
    color: #1f2937;
}

.dashboard-container {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 240px;
    background: #0f3d70;
    color: white;
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
}

.logo {
    font-size: 25px;
    font-weight: bold;
    padding: 25px 22px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
}

.menu {
    padding: 18px 12px;
}

.menu a {
    display: block;
    text-decoration: none;
    color: #e5efff;
    padding: 13px 15px;
    margin-bottom: 7px;
    border-radius: 8px;
    font-size: 14px;
    transition: 0.2s;
}

.menu a:hover {
    background: #1d5a96;
    color: white;
}

.menu a.active {
    background: #2563eb;
    color: white;
    font-weight: bold;
}

.main-content {
    margin-left: 240px;
    width: calc(100% - 240px);
    min-height: 100vh;
}

.topbar {
    height: 72px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    border-bottom: 1px solid #dbeafe;
}

.topbar h2 {
    margin: 0;
    color: #173f70;
    font-size: 21px;
}

.user {
    background: #eaf3ff;
    color: #174a7c;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
}

.content {
    padding: 30px;
    background: #eef6ff;
    min-height: calc(100vh - 72px);
}

.content h1 {
    margin: 0 0 8px;
    color: #173f70;
    font-size: 27px;
}

.welcome-text {
    color: #64748b;
    margin: 0 0 25px;
    font-size: 14px;
}

.admin-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin: 25px 0 32px;
}

.admin-card {
    background: #eaf3ff;
    padding: 22px;
    border-radius: 15px;
    border: 1px solid #b9d4f5;
    box-shadow: 0 6px 18px rgba(31,55,82,0.06);
    transition: 0.2s;
}

.admin-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(31,55,82,0.09);
}

.admin-card h3 {
    margin: 0 0 13px;
    font-size: 15px;
    color: #1e3a8a;
}

.admin-number {
    font-size: 30px;
    font-weight: bold;
    color: #173f70;
}

.content > h2 {
    color: #173f70;
    font-size: 21px;
    margin-bottom: 18px;
}

.admin-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 20px;
}

.admin-action {
    padding: 25px;
    background: #eaf3ff;
    border: 1px solid #b9d4f5;
    border-radius: 15px;
    text-decoration: none;
    color: #1f2937;
    box-shadow: 0 6px 18px rgba(31,55,82,0.06);
    transition: 0.2s;
}

.admin-action:hover {
    transform: translateY(-2px);
    background: #dbeafe;
    border-color: #93c5fd;
}

.admin-action h3 {
    margin: 0 0 9px;
    color: #1e3a8a;
    font-size: 16px;
}

.admin-action p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    line-height: 1.5;
}

@media (max-width: 1000px) {

    .admin-cards {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .sidebar {
        width: 200px;
    }

    .main-content {
        margin-left: 200px;
        width: calc(100% - 200px);
    }

    .admin-cards {
        grid-template-columns: 1fr;
    }

    .admin-actions {
        grid-template-columns: 1fr;
    }

    .topbar {
        padding: 0 18px;
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

            <a href="admin_dashboard.php" class="active">
                🏠 Dashboard
            </a>

            <a href="admin_complaints.php">
                📋 All Complaints
            </a>

            <a href="admin_users.php">
                👥 Manage Users
            </a>

            <a href="admin_profile.php">
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
                Admin Dashboard
            </h2>

            <div class="user">
                👤 <?php echo htmlspecialchars($name); ?>
            </div>

        </div>

        <div class="content">

            <h1>
                Welcome, <?php echo htmlspecialchars($name); ?>! 👋
            </h1>

            <p class="welcome-text">
                Manage and monitor the Complaint Management System.
            </p>

            <div class="admin-cards">

                <div class="admin-card">

                    <h3>
                        👥 Total Users
                    </h3>

                    <div class="admin-number">
                        <?php echo $total_users; ?>
                    </div>

                </div>

                <div class="admin-card">

                    <h3>
                        📋 Total Complaints
                    </h3>

                    <div class="admin-number">
                        <?php echo $total_complaints; ?>
                    </div>

                </div>

                <div class="admin-card">

                    <h3>
                        ⏳ Pending
                    </h3>

                    <div class="admin-number">
                        <?php echo $pending; ?>
                    </div>

                </div>

                <div class="admin-card">

                    <h3>
                        ✅ Resolved
                    </h3>

                    <div class="admin-number">
                        <?php echo $resolved; ?>
                    </div>

                </div>

            </div>

            <h2>
                Admin Controls
            </h2>

            <div class="admin-actions">

                <a href="admin_users.php" class="admin-action">

                    <h3>
                        👥 Manage Users
                    </h3>

                    <p>
                        Manage student, teacher and staff accounts.
                    </p>

                </a>

                <a href="admin_complaints.php" class="admin-action">

                    <h3>
                        📋 All Complaints
                    </h3>

                    <p>
                        View all complaints in the system.
                    </p>

                </a>

            </div>

        </div>

    </main>

</div>

</body>
</html>

