```php
<?php

session_start();

include("db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"];
$role = $_SESSION["role"];

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $current_password = trim($_POST["current_password"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if ($new_password != $confirm_password) {

        $message = "New password and confirm password do not match.";
        $message_type = "error";

    } elseif (strlen($new_password) < 4) {

        $message = "New password must be at least 4 characters.";
        $message_type = "error";

    } else {

        $sql = "SELECT password FROM users WHERE user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $user["password"] === $current_password) {

            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";

            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_password, $user_id);

            if ($update_stmt->execute()) {
                $message = "Password changed successfully.";
                $message_type = "success";
            } else {
                $message = "Failed to change password.";
                $message_type = "error";
            }

        } else {

            $message = "Current password is incorrect.";
            $message_type = "error";
        }
    }
}

if ($role == "Teacher") {
    $dashboard = "teacher_dashboard.php";
    $profile = "teacher_profile.php";
} elseif ($role == "Admin") {
    $dashboard = "admin_dashboard.php";
    $profile = "admin_profile.php";
} elseif ($role == "Staff") {
    $dashboard = "staff_dashboard.php";
    $profile = "staff_profile.php";
} else {
    $dashboard = "dashboard.php";
    $profile = "student_profile.php";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Change Password - Complaint Management System</title>

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
    color: #123e68;
    margin-bottom: 8px;
}

.intro {
    color: #475569;
    margin-top: 0;
}

.password-box {
    max-width: 550px;
    margin-top: 25px;
    background: #edf6ff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.password-box h2 {
    margin-top: 0;
    color: #123e68;
}

.password-box p {
    color: #475569;
    margin-bottom: 25px;
}

.message {
    max-width: 550px;
    padding: 13px 16px;
    margin: 20px 0;
    border-radius: 8px;
    font-weight: bold;
}

.success {
    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

label {
    display: block;
    margin-bottom: 7px;
    font-weight: bold;
    color: #334155;
}

input {
    width: 100%;
    padding: 12px;
    margin-bottom: 18px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 15px;
    background: white;
}

input:focus {
    outline: none;
    border-color: #1769aa;
    box-shadow: 0 0 0 2px rgba(23,105,170,0.12);
}

button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 7px;
    background: #1769aa;
    color: white;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #12598f;
}

.back {
    display: inline-block;
    margin-top: 18px;
    text-decoration: none;
    color: #1769aa;
    font-weight: bold;
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

    .password-box {
        padding: 22px;
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

        <a href="<?php echo $dashboard; ?>">
            🏠 Dashboard
        </a>

        <a href="<?php echo $profile; ?>">
            👤 Profile
        </a>

        <a href="change_password.php" class="active">
            🔑 Change Password
        </a>

        <a href="logout.php">
            🚪 Logout
        </a>

    </div>

</aside>

<main class="main-content">

    <div class="topbar">

        <h2>Change Password</h2>

        <div class="user">
            👤 <?php echo htmlspecialchars($name); ?>
        </div>

    </div>

    <div class="content">

        <h1>Change Password</h1>

        <p class="intro">
            Update your account password securely.
        </p>

        <?php if ($message != ""): ?>

        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <?php endif; ?>

        <div class="password-box">

            <h2>Update Password</h2>

            <p>
                Enter your current password and choose a new password.
            </p>

            <form method="POST">

                <label>Current Password</label>

                <input
                    type="password"
                    name="current_password"
                    placeholder="Enter current password"
                    required
                >

                <label>New Password</label>

                <input
                    type="password"
                    name="new_password"
                    placeholder="Enter new password"
                    required
                >

                <label>Confirm New Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm new password"
                    required
                >

                <button type="submit">
                    CHANGE PASSWORD
                </button>

            </form>

            <a href="<?php echo $profile; ?>" class="back">
                ← Back to Profile
            </a>

        </div>

    </div>

</main>

</div>

</body>

</html>

