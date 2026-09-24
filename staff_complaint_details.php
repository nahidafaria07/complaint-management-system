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

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: staff_complaints.php");
    exit();
}

$complaint_id = (int) $_GET["id"];

$sql = "SELECT
            complaints.*,
            users.name AS student_name,
            users.email AS student_email
        FROM complaints
        INNER JOIN users
            ON complaints.user_id = users.user_id
        WHERE complaints.complaint_id = ?
        AND complaints.staff_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $complaint_id, $staff_id);
$stmt->execute();

$result = $stmt->get_result();
$complaint = $result->fetch_assoc();

if (!$complaint) {
    die("Complaint not found or not assigned to you.");
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Complaint Details</title>

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
}

.nav-actions a {
    text-decoration: none;
    color: white;
    padding: 10px 17px;
    border-radius: 8px;
    font-size: 14px;
    min-width: 95px;
    text-align: center;
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

.details-box {
    background: #edf6ff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 18px rgba(0,0,0,.07);
    max-width: 950px;
}

.details-box h1 {
    margin: 0 0 25px;
    color: #123e68;
    font-size: 25px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.info-item {
    background: #dbeeff;
    border: 1px solid #bdd9ef;
    border-radius: 10px;
    padding: 17px;
}

.info-item.full {
    grid-column: 1 / -1;
}

.info-item label {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.info-item strong {
    color: #123e68;
    font-size: 14px;
}

.description {
    line-height: 1.7;
    color: #334155;
    white-space: pre-wrap;
}

.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
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

.rejected {
    background: #f8d7da;
    color: #842029;
}

.image-box {
    margin-top: 10px;
}

.image-box img {
    max-width: 100%;
    max-height: 450px;
    border-radius: 10px;
    border: 1px solid #bdd9ef;
}

.no-image {
    color: #64748b;
    font-size: 13px;
}

.buttons {
    margin-top: 25px;
    display: flex;
    gap: 10px;
}

.back-btn,
.update-btn {
    display: inline-block;
    padding: 10px 17px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-size: 14px;
}

.back-btn {
    background: #64748b;
}

.back-btn:hover {
    background: #475569;
}

.update-btn {
    background: #1769aa;
}

.update-btn:hover {
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

    .info-grid {
        grid-template-columns: 1fr;
    }

    .info-item.full {
        grid-column: auto;
    }

    .details-box {
        padding: 20px;
    }

    .buttons {
        flex-direction: column;
    }

    .back-btn,
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

        <h2>Complaint Details</h2>

        <p>View submitted complaint application</p>

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

<div class="details-box">

    <h1>
        Complaint #<?php echo htmlspecialchars($complaint["complaint_id"]); ?>
    </h1>

    <div class="info-grid">

        <div class="info-item">

            <label>Student Name</label>

            <strong>
                <?php echo htmlspecialchars($complaint["student_name"]); ?>
            </strong>

        </div>

        <div class="info-item">

            <label>Student Email</label>

            <strong>
                <?php echo htmlspecialchars($complaint["student_email"]); ?>
            </strong>

        </div>

        <div class="info-item full">

            <label>Complaint Title</label>

            <strong>
                <?php echo htmlspecialchars($complaint["title"]); ?>
            </strong>

        </div>

        <div class="info-item">

            <label>Category</label>

            <strong>
                <?php echo htmlspecialchars($complaint["category"]); ?>
            </strong>

        </div>

        <div class="info-item">

            <label>Department</label>

            <strong>
                <?php echo htmlspecialchars($complaint["department"]); ?>
            </strong>

        </div>

        <div class="info-item">

            <label>Priority</label>

            <strong>
                <?php echo htmlspecialchars($complaint["priority"]); ?>
            </strong>

        </div>

        <div class="info-item">

            <label>Status</label>

            <?php

            $status_class = "pending";

            if ($complaint["status"] == "In Progress") {
                $status_class = "progress";
            }

            if ($complaint["status"] == "Resolved") {
                $status_class = "resolved";
            }

            if ($complaint["status"] == "Rejected") {
                $status_class = "rejected";
            }

            ?>

            <span class="status <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($complaint["status"]); ?>
            </span>

        </div>

        <div class="info-item">

            <label>Submitted At</label>

            <strong>
                <?php echo htmlspecialchars($complaint["created_at"]); ?>
            </strong>

        </div>

        <div class="info-item full">

            <label>Complaint Description</label>

            <div class="description">
                <?php echo htmlspecialchars($complaint["description"]); ?>
            </div>

        </div>

        <div class="info-item full">

            <label>Attached Image</label>

            <div class="image-box">

                <?php if (!empty($complaint["image"])): ?>

                    <img
                        src="uploads/<?php echo htmlspecialchars($complaint["image"]); ?>"
                        alt="Complaint Image"
                    >

                <?php else: ?>

                    <div class="no-image">
                        No image attached.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="buttons">

        <a
            href="staff_complaints.php"
            class="back-btn"
        >
            ← Back to Complaints
        </a>

        <a
            href="update_complaint.php?id=<?php echo $complaint["complaint_id"]; ?>"
            class="update-btn"
        >
            Update Complaint
        </a>

    </div>

</div>


</div>

</body>

</html>
