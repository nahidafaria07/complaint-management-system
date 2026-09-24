
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];

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
        LEFT JOIN roles
        ON users.role_id = roles.role_id
        WHERE users.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Profile</title>

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

.message {
    max-width: 650px;
    margin: 20px auto;
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

.profile-row {
    margin: 0;
    padding: 13px 5px;
    border-bottom: 1px solid #dbeafe;
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-row:last-child {
    border-bottom: none;
}

.profile-label {
    width: 105px;
    flex-shrink: 0;
    font-weight: bold;
    color: #1769aa;
}

.profile-value {
    flex: 1;
    color: #334155;
    word-break: break-word;
}

.edit-button {
    padding: 6px 12px;
    background: #1769aa;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    white-space: nowrap;
}

.edit-button:hover {
    background: #12598f;
}

.edit-area {
    flex: 1;
}

.edit-form {
    display: flex;
    gap: 7px;
    align-items: center;
    width: 100%;
}

.edit-form input {
    flex: 1;
    min-width: 0;
    padding: 9px;
    border: 1px solid #b7cfe8;
    border-radius: 6px;
    font-size: 14px;
    background: #f8fbff;
}

.edit-form input:focus {
    outline: none;
    border-color: #1769aa;
}

.save-button {
    padding: 9px 13px;
    background: #1769aa;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}

.save-button:hover {
    background: #12598f;
}

.cancel-button {
    padding: 9px 13px;
    background: #64748b;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
    font-size: 13px;
}

.cancel-button:hover {
    background: #475569;
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

.status-active {
    color: #15803d;
    font-weight: bold;
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

    .profile-row {
        align-items: flex-start;
    }

    .profile-label {
        width: 90px;
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

        <a href="dashboard.php">
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
        </a>

        <a href="student_profile.php" class="active">
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
            Student Profile
        </h2>

        <div class="user">

            👨‍🎓

            <?php echo htmlspecialchars($user["name"]); ?>

        </div>

    </div>

    <div class="content">

        <h1>
            My Profile
        </h1>

        <p class="welcome-text">
            View and manage your personal account information.
        </p>

        <?php if ($message != ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>

        <?php if ($user): ?>

        <div class="profile-card">

            <div class="profile-icon">
                👨‍🎓
            </div>

            <h2>
                <?php echo htmlspecialchars($user["name"]); ?>
            </h2>

            <div class="profile-info">

                <div class="profile-row">

                    <div class="profile-label">
                        Name:
                    </div>

                    <?php if ($edit_field == "name"): ?>

                        <div class="edit-area">

                            <form method="POST" class="edit-form">

                                <input
                                    type="hidden"
                                    name="field"
                                    value="name"
                                >

                                <input
                                    type="text"
                                    name="name"
                                    value="<?php echo htmlspecialchars($user["name"]); ?>"
                                    required
                                >

                                <button
                                    type="submit"
                                    class="save-button"
                                >
                                    Save
                                </button>

                                <a
                                    href="student_profile.php"
                                    class="cancel-button"
                                >
                                    Cancel
                                </a>

                            </form>

                        </div>

                    <?php else: ?>

                        <div class="profile-value">

                            <?php echo htmlspecialchars($user["name"]); ?>

                        </div>

                        <a
                            href="student_profile.php?edit=name"
                            class="edit-button"
                        >
                            Edit
                        </a>

                    <?php endif; ?>

                </div>

                <div class="profile-row">

                    <div class="profile-label">
                        Email:
                    </div>

                    <?php if ($edit_field == "email"): ?>

                        <div class="edit-area">

                            <form method="POST" class="edit-form">

                                <input
                                    type="hidden"
                                    name="field"
                                    value="email"
                                >

                                <input
                                    type="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($user["email"]); ?>"
                                    required
                                >

                                <button
                                    type="submit"
                                    class="save-button"
                                >
                                    Save
                                </button>

                                <a
                                    href="student_profile.php"
                                    class="cancel-button"
                                >
                                    Cancel
                                </a>

                            </form>

                        </div>

                    <?php else: ?>

                        <div class="profile-value">

                            <?php echo htmlspecialchars($user["email"]); ?>

                        </div>

                        <a
                            href="student_profile.php?edit=email"
                            class="edit-button"
                        >
                            Edit
                        </a>

                    <?php endif; ?>

                </div>

                <div class="profile-row">

                    <div class="profile-label">
                        User ID:
                    </div>

                    <div class="profile-value">

                        <?php echo htmlspecialchars($user["user_id"]); ?>

                    </div>

                </div>

                <div class="profile-row">

                    <div class="profile-label">
                        Role:
                    </div>

                    <div class="profile-value">

                        <?php echo htmlspecialchars($user["role_name"]); ?>

                    </div>

                </div>

                <div class="profile-row">

                    <div class="profile-label">
                        Status:
                    </div>

                    <div class="profile-value status-active">

                        <?php echo htmlspecialchars($user["status"]); ?>

                    </div>

                </div>

                <div class="profile-row">

                    <div class="profile-label">
                        Joined:
                    </div>

                    <div class="profile-value">

                        <?php
                        echo date(
                            "d M Y, h:i A",
                            strtotime($user["created_at"])
                        );
                        ?>

                    </div>

                </div>

            </div>

            <a
                href="change_password.php"
                class="password-link"
            >
                🔑 Change Password
            </a>

        </div>

        <?php else: ?>

        <div class="empty">

            <p>
                Profile information not found.
            </p>

        </div>

        <?php endif; ?>

    </div>

</main>

</div>

</body>

</html>
