
<?php

session_start();

include "db.php";

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "Staff"
) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"];

$sql = "SELECT staff_id
        FROM staff
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

if (!$staff) {
    die("Staff profile not found.");
}

$staff_id = $staff["staff_id"];

$sql = "SELECT status, COUNT(*) AS total
        FROM complaints
        WHERE staff_id = ?
        GROUP BY status";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

$total = 0;
$pending = 0;
$progress = 0;
$resolved = 0;

while ($row = $result->fetch_assoc()) {

    $total += $row["total"];

    if ($row["status"] === "Pending") {
        $pending = $row["total"];
    }

    if ($row["status"] === "In Progress") {
        $progress = $row["total"];
    }

    if ($row["status"] === "Resolved") {
        $resolved = $row["total"];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Staff Dashboard</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #b8d8f5, #d6eaff);
    color: #1e293b;
    min-height: 100vh;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 230px;
    height: 100vh;
    background: linear-gradient(180deg, #123e68, #1769aa);
    color: white;
    padding: 25px 15px;
}

.logo {
    text-align: center;
    margin-bottom: 30px;
}

.logo h2 {
    margin: 0;
    font-size: 21px;
}

.logo p {
    margin: 7px 0 0;
    font-size: 12px;
    opacity: .8;
}

.menu a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 12px 15px;
    margin-bottom: 8px;
    border-radius: 8px;
    font-size: 14px;
}

.menu a:hover,
.menu a.active {
    background: #2b82c8;
}

.main {
    margin-left: 230px;
    padding: 30px;
}

.navbar {
    background: #edf6ff;
    padding: 20px 24px;
    border-radius: 14px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    box-shadow: 0 5px 18px rgba(0,0,0,.07);

    margin-bottom: 25px;
}

.navbar h2 {
    margin: 0;
    color: #123e68;
    font-size: 23px;
}

.navbar p {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 13px;
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.nav-actions a {
    text-decoration: none;
    color: white;
    padding: 10px 17px;
    border-radius: 8px;
    font-size: 14px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width: 95px;
}

.profile-btn {
    background: #1769aa;
}

.profile-btn:hover {
    background: #12588f;
}

.logout-btn {
    background: #dc3545;
}

.logout-btn:hover {
    background: #bb2d3b;
}

.welcome {
    background: #edf6ff;
    padding: 25px;
    border-radius: 14px;
    margin-bottom: 25px;
    box-shadow: 0 5px 18px rgba(0,0,0,.07);
}

.welcome h1 {
    margin: 0 0 8px;
    color: #123e68;
    font-size: 25px;
}

.welcome p {
    margin: 0;
    color: #64748b;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: #edf6ff;
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 5px 18px rgba(0,0,0,.07);
    border: 1px solid #c7ddf2;
}

.stat-card h3 {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

.stat-card strong {
    display: block;
    margin-top: 8px;
    font-size: 30px;
    color: #123e68;
}

.info-box {
    background: #edf6ff;
    padding: 28px;
    border-radius: 14px;
    box-shadow: 0 5px 18px rgba(0,0,0,.07);
    border: 1px solid #c7ddf2;
}

.info-box h2 {
    margin: 0 0 10px;
    color: #123e68;
    font-size: 20px;
}

.info-box p {
    margin: 0;
    color: #64748b;
    line-height: 1.6;
}

.quick-actions {
    display: flex;
    gap: 15px;
    margin-top: 22px;
    flex-wrap: wrap;
}

.quick-btn {
    display: inline-block;
    background: #1769aa;
    color: white;
    text-decoration: none;
    padding: 11px 18px;
    border-radius: 8px;
    font-size: 14px;
}

.quick-btn:hover {
    background: #12588f;
}

@media (max-width: 1050px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 800px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .navbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .nav-actions {
        width: 100%;
        justify-content: flex-end;
    }

}

@media (max-width: 600px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .nav-actions {
        justify-content: stretch;
    }

    .nav-actions a {
        flex: 1;
    }

    .welcome h1 {
        font-size: 21px;
    }

}

</style>

</head>

<body>

<div class="sidebar">

    <div class="logo">

        <h2>Complaint System</h2>

        <p>Staff Panel</p>

    </div>

    <div class="menu">

        <a href="staff_dashboard.php" class="active">
            Dashboard
        </a>

        <a href="staff_complaints.php">
            Complaints
        </a>

        <a href="staff_updates.php">
            Updates
        </a>

        <a href="staff_profile.php">
            Profile
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</div>

<div class="main">

    <div class="navbar">

        <div>

            <h2>Staff Dashboard</h2>

            <p>Overview of complaints assigned to you</p>

        </div>

        <div class="nav-actions">

            <a
                href="staff_profile.php"
                class="profile-btn"
            >
                👤 Profile
            </a>

            <a
                href="logout.php"
                class="logout-btn"
            >
                ↪ Logout
            </a>

        </div>

    </div>

    <div class="welcome">

        <h1>
            Welcome, <?php echo htmlspecialchars($name); ?> 👋
        </h1>

        <p>
            Here you can monitor the complaints assigned to you.
        </p>

    </div>

    <div class="stats">

        <div class="stat-card">

            <h3>Total Complaints</h3>

            <strong>
                <?php echo $total; ?>
            </strong>

        </div>

        <div class="stat-card">

            <h3>Pending</h3>

            <strong>
                <?php echo $pending; ?>
            </strong>

        </div>

        <div class="stat-card">

            <h3>In Progress</h3>

            <strong>
                <?php echo $progress; ?>
            </strong>

        </div>

        <div class="stat-card">

            <h3>Resolved</h3>

            <strong>
                <?php echo $resolved; ?>
            </strong>

        </div>

    </div>

    <div class="info-box">

        <h2>Staff Complaint Management</h2>

        <p>
            From the Complaints page, you can view the complaints
            assigned to you and update their status. The Updates page
            shows the latest complaint status changes and messages.
        </p>

        <div class="quick-actions">

            <a
                href="staff_complaints.php"
                class="quick-btn"
            >
                📋 View Complaints
            </a>

            <a
                href="staff_updates.php"
                class="quick-btn"
            >
                🔔 View Updates
            </a>

        </div>

    </div>

</div>

</body>

</html>
