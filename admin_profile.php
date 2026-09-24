
<?php

session_start();

include "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];

$message = "";
$message_type = "";

$edit_field = $_GET["edit"] ?? "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $field = $_POST["field"] ?? "";

    if ($field == "name") {

        $name = trim($_POST["name"] ?? "");

        if ($name == "") {

            $message = "Name cannot be empty.";
            $message_type = "error";

        } else {

            $sql = "UPDATE users SET name = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param("si", $name, $user_id);

                if ($stmt->execute()) {

                    $_SESSION["name"] = $name;
                    $message = "Name updated successfully.";
                    $message_type = "success";
                    $edit_field = "";

                } else {

                    $message = "Failed to update name.";
                    $message_type = "error";

                }

                $stmt->close();

            } else {

                $message = "Database error.";
                $message_type = "error";

            }
        }
    }

    elseif ($field == "email") {

        $email = trim($_POST["email"] ?? "");

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $message_type = "error";

        } else {

            $check_sql = "SELECT user_id
                          FROM users
                          WHERE email = ?
                          AND user_id != ?";

            $check_stmt = $conn->prepare($check_sql);

            if ($check_stmt) {

                $check_stmt->bind_param("si", $email, $user_id);
                $check_stmt->execute();

                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {

                    $message = "This email is already in use.";
                    $message_type = "error";

                } else {

                    $sql = "UPDATE users SET email = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);

                    if ($stmt) {

                        $stmt->bind_param("si", $email, $user_id);

                        if ($stmt->execute()) {

                            $message = "Email updated successfully.";
                            $message_type = "success";
                            $edit_field = "";

                        } else {

                            $message = "Failed to update email.";
                            $message_type = "error";

                        }

                        $stmt->close();

                    } else {

                        $message = "Database error.";
                        $message_type = "error";

                    }
                }

                $check_stmt->close();

            } else {

                $message = "Database error.";
                $message_type = "error";

            }
        }
    }
}

$sql = "SELECT
            users.user_id,
            users.name,
            users.email,
            users.status,
            users.created_at,
            roles.role_name
        FROM users
        INNER JOIN roles
            ON users.role_id = roles.role_id
        WHERE users.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database query error.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

if (!$admin) {
    die("Admin profile not found.");
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Profile</title>

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

.page {
    max-width: 1000px;
    margin: 0 auto;
}

.page h1 {
    margin: 0 0 8px;
    color: #173f70;
    font-size: 27px;
}

.welcome-text {
    color: #64748b;
    margin: 0 0 25px;
    font-size: 14px;
}

.message {
    margin-bottom: 20px;
    padding: 13px 16px;
    border-radius: 8px;
    font-weight: bold;
}

.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.profile-card {
    background: #eaf3ff;
    border: 1px solid #b9d4f5;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 6px 18px rgba(31,55,82,0.06);
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 18px;
    padding-bottom: 24px;
    margin-bottom: 22px;
    border-bottom: 1px solid #b9d4f5;
}

.profile-icon {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: #dbeafe;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

.profile-header h2 {
    margin: 0 0 6px;
    color: #173f70;
    font-size: 21px;
}

.profile-header p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.profile-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.info-box {
    background: #dbeafe;
    border: 1px solid #b9d4f5;
    border-radius: 11px;
    padding: 17px;
}

.info-label {
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 7px;
}

.info-value {
    color: #173f70;
    font-size: 14px;
    font-weight: 600;
    word-break: break-word;
}

.edit-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.edit-value {
    flex: 1;
    color: #173f70;
    font-size: 14px;
    font-weight: 600;
    word-break: break-word;
}

.edit-button {
    background: #1769aa;
    color: white;
    text-decoration: none;
    padding: 7px 13px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: bold;
    white-space: nowrap;
}

.edit-button:hover {
    background: #12588f;
}

.edit-form {
    display: flex;
    gap: 8px;
    width: 100%;
}

.edit-form input {
    flex: 1;
    min-width: 0;
    padding: 9px 10px;
    border: 1px solid #9fc1dd;
    border-radius: 7px;
    font-size: 14px;
    background: white;
}

.edit-form input:focus {
    outline: none;
    border-color: #1769aa;
}

.save-button {
    background: #1769aa;
    color: white;
    border: none;
    padding: 9px 14px;
    border-radius: 7px;
    font-weight: bold;
    cursor: pointer;
}

.save-button:hover {
    background: #12588f;
}

.cancel-button {
    background: #64748b;
    color: white;
    text-decoration: none;
    padding: 9px 14px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: bold;
    white-space: nowrap;
}

.cancel-button:hover {
    background: #475569;
}

.active-status {
    color: #15803d;
}

.inactive-status {
    color: #b91c1c;
}

.password-section {
    margin-top: 25px;
    padding-top: 22px;
    border-top: 1px solid #b9d4f5;
    text-align: right;
}

.password-button {
    display: inline-block;
    background: #1769aa;
    color: white;
    text-decoration: none;
    padding: 11px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
}

.password-button:hover {
    background: #12588f;
}

@media (max-width: 700px) {

    .sidebar {
        width: 200px;
    }

    .main-content {
        margin-left: 200px;
        width: calc(100% - 200px);
    }

    .topbar {
        padding: 0 18px;
    }

    .content {
        padding: 20px;
    }

    .profile-info {
        grid-template-columns: 1fr;
    }

    .edit-form {
        flex-wrap: wrap;
    }

    .edit-form input {
        width: 100%;
        flex-basis: 100%;
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

            <a href="admin_dashboard.php">
                🏠 Dashboard
            </a>

            <a href="admin_complaints.php">
                📋 All Complaints
            </a>

            <a href="admin_users.php">
                👥 Manage Users
            </a>

            <a href="admin_profile.php" class="active">
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
                Admin Profile
            </h2>

            <div class="user">
                👤 <?php echo htmlspecialchars($admin["name"]); ?>
            </div>

        </div>

        <div class="content">

            <div class="page">

                <h1>
                    👤 My Profile
                </h1>

                <p class="welcome-text">
                    View and manage your administrator account information.
                </p>

                <?php if ($message != ""): ?>

                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php endif; ?>

                <div class="profile-card">

                    <div class="profile-header">

                        <div class="profile-icon">
                            👤
                        </div>

                        <div>

                            <h2>
                                <?php echo htmlspecialchars($admin["name"]); ?>
                            </h2>

                            <p>
                                Administrator Account
                            </p>

                        </div>

                    </div>

                    <div class="profile-info">

                        <div class="info-box">

                            <div class="info-label">
                                User ID
                            </div>

                            <div class="info-value">
                                <?php echo (int)$admin["user_id"]; ?>
                            </div>

                        </div>

                        <div class="info-box">

                            <div class="info-label">
                                Name
                            </div>

                            <?php if ($edit_field == "name"): ?>

                                <form method="POST" class="edit-form">

                                    <input
                                        type="hidden"
                                        name="field"
                                        value="name"
                                    >

                                    <input
                                        type="text"
                                        name="name"
                                        value="<?php echo htmlspecialchars($admin["name"]); ?>"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="save-button"
                                    >
                                        Save
                                    </button>

                                    <a
                                        href="admin_profile.php"
                                        class="cancel-button"
                                    >
                                        Cancel
                                    </a>

                                </form>

                            <?php else: ?>

                                <div class="edit-row">

                                    <div class="edit-value">
                                        <?php echo htmlspecialchars($admin["name"]); ?>
                                    </div>

                                    <a
                                        href="admin_profile.php?edit=name"
                                        class="edit-button"
                                    >
                                        Edit
                                    </a>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="info-box">

                            <div class="info-label">
                                Email
                            </div>

                            <?php if ($edit_field == "email"): ?>

                                <form method="POST" class="edit-form">

                                    <input
                                        type="hidden"
                                        name="field"
                                        value="email"
                                    >

                                    <input
                                        type="email"
                                        name="email"
                                        value="<?php echo htmlspecialchars($admin["email"]); ?>"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="save-button"
                                    >
                                        Save
                                    </button>

                                    <a
                                        href="admin_profile.php"
                                        class="cancel-button"
                                    >
                                        Cancel
                                    </a>

                                </form>

                            <?php else: ?>

                                <div class="edit-row">

                                    <div class="edit-value">
                                        <?php echo htmlspecialchars($admin["email"]); ?>
                                    </div>

                                    <a
                                        href="admin_profile.php?edit=email"
                                        class="edit-button"
                                    >
                                        Edit
                                    </a>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="info-box">

                            <div class="info-label">
                                Role
                            </div>

                            <div class="info-value">
                                <?php echo htmlspecialchars($admin["role_name"]); ?>
                            </div>

                        </div>

                        <div class="info-box">

                            <div class="info-label">
                                Status
                            </div>

                            <div class="info-value">

                                <?php if ($admin["status"] === "Active"): ?>

                                    <span class="active-status">
                                        🟢 Active
                                    </span>

                                <?php else: ?>

                                    <span class="inactive-status">
                                        🔴 Inactive
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <div class="info-box">

                            <div class="info-label">
                                Joined
                            </div>

                            <div class="info-value">
                                <?php echo htmlspecialchars($admin["created_at"]); ?>
                            </div>

                        </div>

                    </div>

                    <div class="password-section">

                        <a
                            href="change_password.php"
                            class="password-button"
                        >
                            🔑 Change Password
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>