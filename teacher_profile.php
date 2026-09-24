
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];

$sql = "SELECT
            users.user_id,
            users.name,
            users.email,
            users.status,
            users.created_at,
            roles.role_name
        FROM users
        LEFT JOIN roles ON users.role_id = roles.role_id
        WHERE users.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Teacher Profile</title>

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

.dashboard-container {
    min-height: 100vh;
    display: flex;
}

.sidebar {
    width: 230px;
    min-height: 100vh;
    background: linear-gradient(180deg, #123e68, #1769aa);
    color: white;
    padding: 25px 15px;
    position: fixed;
    left: 0;
    top: 0;
}

.logo {
    text-align: center;
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 35px;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.menu a {
    color: white;
    text-decoration: none;
    padding: 13px 15px;
    border-radius: 8px;
    font-size: 15px;
}

.menu a:hover,
.menu a.active {
    background: #2b82c8;
}

.main-content {
    margin-left: 230px;
    width: calc(100% - 230px);
    min-height: 100vh;
}

.topbar {
    background: rgba(255, 255, 255, 0.75);
    padding: 18px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.topbar h2 {
    margin: 0;
    color: #123e68;
}

.user {
    font-weight: bold;
    color: #1769aa;
}

.content {
    padding: 35px;
}

.content h1 {
    margin-bottom: 8px;
    color: #123e68;
}

.welcome-text {
    margin-top: 0;
    color: #475569;
}

.profile-card {
    width: 100%;
    max-width: 650px;
    margin: 30px auto;
    background: #edf6ff;
    border-radius: 16px;
    padding: 35px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.profile-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 15px;
    border-radius: 50%;
    background: #1769aa;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
}

.profile-card h2 {
    margin: 10px 0 25px;
    color: #123e68;
}

.profile-info {
    text-align: left;
    background: white;
    border-radius: 10px;
    padding: 20px;
}

.profile-info p {
    margin: 0;
    padding: 13px 5px;
    border-bottom: 1px solid #dbeafe;
}

.profile-info p:last-child {
    border-bottom: none;
}

.profile-info strong {
    display: inline-block;
    width: 100px;
    color: #1769aa;
}

.password-link {
    display: inline-block;
    margin-top: 22px;
    padding: 12px 20px;
    background: #1769aa;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
}

.password-link:hover {
    background: #12598f;
}

.empty {
    max-width: 650px;
    margin: 30px auto;
    background: #edf6ff;
    padding: 30px;
    text-align: center;
    border-radius: 12px;
}

@media (max-width: 700px) {

    .sidebar {
        width: 100%;
        min-height: auto;
        position: relative;
    }

    .dashboard-container {
        display: block;
    }

    .menu {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }

    .main-content {
        margin-left: 0;
        width: 100%;
    }

    .topbar {
        padding: 15px 20px;
        flex-direction: column;
        gap: 8px;
    }

    .content {
        padding: 20px;
    }

    .profile-card {
        padding: 25px 18px;
    }

    .profile-info strong {
        width: 85px;
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

        <a href="teacher_dashboard.php">
            🏠 Dashboard
        </a>

        <a href="teacher_dashboard.php">
            📋 Complaints
        </a>

        <a href="teacher_profile.php" class="active">
            👤 Profile
        </a>

        

        <a href="logout.php">
            🚪 Logout
        </a>

    </div>

</aside>

<main class="main-content">

    <div class="topbar">

        <h2>Teacher Profile</h2>

        <div class="user">
            👨‍🏫 <?php echo htmlspecialchars($user["name"]); ?>
        </div>

    </div>

    <div class="content">

        <h1>My Profile</h1>

        <p class="welcome-text">
            View your personal account information.
        </p>

        <?php if ($user): ?>

        <div class="profile-card">

            <div class="profile-icon">
                👨‍🏫
            </div>

            <h2>
                <?php echo htmlspecialchars($user["name"]); ?>
            </h2>

            <div class="profile-info">

                <p>
                    <strong>User ID:</strong>
                    <?php echo htmlspecialchars($user["user_id"]); ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($user["email"]); ?>
                </p>

                <p>
                    <strong>Role:</strong>
                    <?php echo htmlspecialchars($user["role_name"]); ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?php echo htmlspecialchars($user["status"]); ?>
                </p>

                <p>
                    <strong>Joined:</strong>
                    <?php
                    echo date(
                        "d M Y, h:i A",
                        strtotime($user["created_at"])
                    );
                    ?>
                </p>

            </div>

            <a href="change_password.php" class="password-link">
                🔑 Change Password
            </a>

        </div>

        <?php else: ?>

        <div class="empty">
            <p>Profile information not found.</p>
        </div>

        <?php endif; ?>

    </div>

</main>

</div>

</body>

</html>

