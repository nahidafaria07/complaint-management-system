
<?php

session_start();

include "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Staff") {
    header("Location: login.php");
    exit();
}

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
            users.name,
            users.email,
            users.status,
            roles.role_name,
            staff.staff_id
        FROM users
        INNER JOIN roles
            ON users.role_id = roles.role_id
        INNER JOIN staff
            ON users.user_id = staff.user_id
        WHERE users.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

$stmt->close();

if (!$staff) {
    die("Staff profile not found.");
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Staff Profile</title>

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

.message {
    max-width: 850px;
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
    max-width: 850px;
    background: #edf6ff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 5px 18px rgba(0,0,0,.07);
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-bottom: 25px;
    margin-bottom: 25px;
    border-bottom: 1px solid #c8dff2;
}

.profile-icon {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background: #1769aa;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

.profile-header h1 {
    margin: 0 0 7px;
    color: #123e68;
    font-size: 25px;
}

.profile-header p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.profile-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.info-box {
    background: #dbeeff;
    border: 1px solid #bdd9ef;
    border-radius: 12px;
    padding: 18px;
}

.info-box.full {
    grid-column: 1 / -1;
}

.info-box label {
    display: block;
    color: #64748b;
    font-size: 12px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.info-box strong {
    color: #123e68;
    font-size: 15px;
}

.edit-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.edit-value {
    flex: 1;
    color: #123e68;
    font-size: 15px;
    font-weight: bold;
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
}

.cancel-button:hover {
    background: #475569;
}

.status {
    display: inline-block;
    background: #d9f2df;
    color: #237a3b;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.password-section {
    margin-top: 25px;
    padding-top: 22px;
    border-top: 1px solid #c8dff2;
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

    .profile-info {
        grid-template-columns: 1fr;
    }

    .info-box.full {
        grid-column: auto;
    }

    .nav-actions {
        justify-content: stretch;
    }

    .nav-actions a {
        flex: 1;
    }

    .profile-header {
        align-items: flex-start;
    }

    .edit-form {
        flex-wrap: wrap;
    }

    .edit-form input {
        width: 100%;
        flex-basis: 100%;
    }

    .save-button,
    .cancel-button {
        flex: 1;
        text-align: center;
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

    <a href="staff_dashboard.php">
        Dashboard
    </a>

    <a href="staff_complaints.php">
        Complaints
    </a>

    <a href="staff_updates.php">
        Updates
    </a>

    <a href="staff_profile.php" class="active">
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
        <h2>Staff Profile</h2>
        <p>View and manage your staff account information</p>
    </div>

    <div class="nav-actions">

        <a href="staff_profile.php" class="profile-btn">
            👤 Profile
        </a>

        <a href="logout.php" class="logout-btn">
            ↪ Logout
        </a>

    </div>

</div>

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
            <h1><?php echo htmlspecialchars($staff["name"]); ?></h1>
            <p>Staff Member</p>
        </div>

    </div>

    <div class="profile-info">

        <div class="info-box">

            <label>Name</label>

            <?php if ($edit_field == "name"): ?>

                <form method="POST" class="edit-form">

                    <input type="hidden" name="field" value="name">

                    <input
                        type="text"
                        name="name"
                        value="<?php echo htmlspecialchars($staff["name"]); ?>"
                        required
                    >

                    <button type="submit" class="save-button">
                        Save
                    </button>

                    <a href="staff_profile.php" class="cancel-button">
                        Cancel
                    </a>

                </form>

            <?php else: ?>

                <div class="edit-row">

                    <div class="edit-value">
                        <?php echo htmlspecialchars($staff["name"]); ?>
                    </div>

                    <a
                        href="staff_profile.php?edit=name"
                        class="edit-button"
                    >
                        Edit
                    </a>

                </div>

            <?php endif; ?>

        </div>

        <div class="info-box">

            <label>Staff ID</label>

            <strong>
                <?php echo htmlspecialchars($staff["staff_id"]); ?>
            </strong>

        </div>

        <div class="info-box">

            <label>Email</label>

            <?php if ($edit_field == "email"): ?>

                <form method="POST" class="edit-form">

                    <input type="hidden" name="field" value="email">

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($staff["email"]); ?>"
                        required
                    >

                    <button type="submit" class="save-button">
                        Save
                    </button>

                    <a href="staff_profile.php" class="cancel-button">
                        Cancel
                    </a>

                </form>

            <?php else: ?>

                <div class="edit-row">

                    <div class="edit-value">
                        <?php echo htmlspecialchars($staff["email"]); ?>
                    </div>

                    <a
                        href="staff_profile.php?edit=email"
                        class="edit-button"
                    >
                        Edit
                    </a>

                </div>

            <?php endif; ?>

        </div>

        <div class="info-box">

            <label>Role</label>

            <strong>
                <?php echo htmlspecialchars($staff["role_name"]); ?>
            </strong>

        </div>

        <div class="info-box full">

            <label>Account Status</label>

            <span class="status">
                <?php echo htmlspecialchars($staff["status"] ?? "Active"); ?>
            </span>

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

</body>

</html>
