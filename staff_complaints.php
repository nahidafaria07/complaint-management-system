<?php

session_start();

include "db.php";

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] != "Staff"
) {
    header("Location: login.php");
    exit();
}

$staff_user_id = $_SESSION["user_id"];

$sql = "SELECT staff_id
        FROM staff
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_user_id);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

if (!$staff) {
    die("Staff profile not found.");
}

$staff_id = $staff["staff_id"];

$sql = "SELECT complaints.*
        FROM complaints
        WHERE complaints.staff_id = ?
        ORDER BY complaints.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$complaints = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Staff Complaints</title>

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

.complaint-box {
    background: #edf6ff;
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 5px 18px rgba(0,0,0,.07);
}

.complaint-box h1 {
    margin: 0;
    color: #123e68;
    font-size: 25px;
}

.complaint-box p {
    margin-top: 7px;
    margin-bottom: 22px;
    color: #64748b;
    font-size: 14px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

th {
    background: #1769aa;
    color: white;
    padding: 13px;
    text-align: left;
    font-size: 13px;
    white-space: nowrap;
}

td {
    padding: 13px;
    border-bottom: 1px solid #dbe8f3;
    font-size: 13px;
    vertical-align: middle;
}

tr:last-child td {
    border-bottom: none;
}

tr:hover td {
    background: #f5faff;
}

.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}

.pending {
    background: #fff3cd;
    color: #856404;
}

.progress {
    background: #cfe8ff;
    color: #1769aa;
}

.resolved {
    background: #d9f2df;
    color: #237a3b;
}

.action-buttons {
    display: flex;
    gap: 7px;
    align-items: center;
}

.view-btn,
.update-btn {
    display: inline-block;
    color: white;
    text-decoration: none;
    padding: 7px 11px;
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
}

.view-btn {
    background: #2b82c8;
}

.view-btn:hover {
    background: #1769aa;
}

.update-btn {
    background: #1769aa;
}

.update-btn:hover {
    background: #12588f;
}

.empty {
    text-align: center;
    padding: 45px 20px;
    color: #64748b;
    background: white;
    border-radius: 10px;
    font-size: 14px;
}

@media (max-width: 900px) {

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

    .main {
        padding: 15px;
    }

    .navbar {
        padding: 18px;
    }

    .nav-actions {
        width: 100%;
    }

    .nav-actions a {
        flex: 1;
    }

    .complaint-box {
        padding: 18px;
    }

    .complaint-box h1 {
        font-size: 21px;
    }

    .action-buttons {
        flex-direction: column;
        align-items: stretch;
    }

    .view-btn,
    .update-btn {
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

    <a href="staff_complaints.php" class="active">
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

        <h2>Staff Complaints</h2>

        <p>View and manage complaints assigned to you</p>

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

<div class="complaint-box">

    <h1>Assigned Complaints</h1>

    <p>
        These are the complaints currently assigned to you.
    </p>

    <?php if ($complaints->num_rows > 0): ?>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Complaint</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php while ($complaint = $complaints->fetch_assoc()): ?>

                    <tr>

                        <td>
                            #<?php
                            echo htmlspecialchars(
                                $complaint["complaint_id"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $complaint["title"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $complaint["category"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $complaint["department"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $complaint["priority"]
                            );
                            ?>
                        </td>

                        <td>

                            <?php

                            $status_class = "pending";

                            if (
                                $complaint["status"] == "In Progress"
                            ) {
                                $status_class = "progress";
                            }

                            if (
                                $complaint["status"] == "Resolved"
                            ) {
                                $status_class = "resolved";
                            }

                            ?>

                            <span class="status <?php echo $status_class; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $complaint["status"]
                                );
                                ?>

                            </span>

                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $complaint["created_at"]
                            );
                            ?>
                        </td>

                        <td>

                            <div class="action-buttons">

                                <a
                                    href="staff_complaint_details.php?id=<?php echo $complaint["complaint_id"]; ?>"
                                    class="view-btn"
                                >
                                    View
                                </a>

                                <a
                                    href="update_complaint.php?id=<?php echo $complaint["complaint_id"]; ?>"
                                    class="update-btn"
                                >
                                    Update
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="empty">

            No complaints are currently assigned to you.

        </div>

    <?php endif; ?>

</div>

</div>

</body>

</html>
