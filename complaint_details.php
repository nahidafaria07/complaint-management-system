<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: my_complaints.php");
    exit();
}

$complaint_id = $_GET["id"];

$sql = "SELECT * FROM complaints WHERE complaint_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Complaint not found.");
}

$complaint = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Complaint Details - CMS</title>

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial,sans-serif;
    background:linear-gradient(135deg,#b8d8f5,#d6eaff);
    color:#1e293b;
    min-height:100vh;
}

.navbar{
    background:#1769aa;
    padding:18px 25px;
    color:white;
}

.navbar h2{
    margin:0 0 12px;
}

.navbar a{
    color:white;
    text-decoration:none;
    margin-right:18px;
    font-size:14px;
}

.navbar a:hover{
    text-decoration:underline;
}

.container{
    width:92%;
    max-width:900px;
    margin:30px auto;
}

.back-btn{
    display:inline-block;
    color:#1769aa;
    background:#edf6ff;
    padding:10px 15px;
    border-radius:8px;
    text-decoration:none;
    margin-bottom:18px;
    font-size:14px;
}

.card{
    background:#edf6ff;
    border-radius:15px;
    padding:28px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:15px;
    border-bottom:1px solid #c8dced;
    padding-bottom:20px;
}

.header h1{
    margin:0;
    color:#1769aa;
    font-size:25px;
}

.header p{
    margin:7px 0 0;
    color:#718096;
    font-size:13px;
}

.status{
    padding:8px 13px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
    white-space:nowrap;
}

.pending{
    background:#fff3cd;
    color:#856404;
}

.in-progress{
    background:#dbeafe;
    color:#1769aa;
}

.resolved{
    background:#dcfce7;
    color:#177245;
}

.grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
    margin-top:22px;
}

.info{
    background:white;
    padding:15px;
    border-radius:10px;
}

.info strong{
    display:block;
    color:#1769aa;
    font-size:13px;
    margin-bottom:6px;
}

.info p{
    margin:0;
    font-size:14px;
}

.section{
    background:white;
    margin-top:22px;
    padding:20px;
    border-radius:10px;
}

.section h3{
    margin:0 0 12px;
    color:#1769aa;
    font-size:17px;
}

.section p{
    margin:0;
    line-height:1.7;
    color:#4a5568;
    font-size:14px;
}

.timeline-item{
    display:flex;
    gap:13px;
    padding:12px 0;
    border-left:3px solid #b8d8f5;
    padding-left:18px;
    margin-left:7px;
}

.dot{
    width:11px;
    height:11px;
    background:#1769aa;
    border-radius:50%;
    margin-left:-25px;
    margin-top:4px;
}

.timeline-item strong{
    color:#1769aa;
    font-size:14px;
}

.timeline-item p{
    margin-top:5px;
}

@media(max-width:600px){

    .container{
        width:94%;
    }

    .card{
        padding:20px;
    }

    .header{
        flex-direction:column;
    }

    .grid{
        grid-template-columns:1fr;
    }

    .navbar a{
        display:inline-block;
        margin-bottom:8px;
    }
}

</style>

</head>

<body>

<div class="navbar">

    <h2>Complaint Management System</h2>

    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="submit_complaint.php">📝 Submit Complaint</a>
    <a href="my_complaints.php">📋 My Complaints</a>
    <a href="updates.php">🔔 Updates</a>
    <a href="student_profile.php">👤 Profile</a>
    <a href="logout.php">🚪 Logout</a>

</div>

<div class="container">

    <a href="my_complaints.php" class="back-btn">
        ← Back to My Complaints
    </a>

    <div class="card">

        <div class="header">

            <div>

                <h1>
                    <?php echo htmlspecialchars($complaint["title"]); ?>
                </h1>

                <p>
                    Complaint #<?php echo $complaint["complaint_id"]; ?>
                </p>

            </div>

            <span class="status <?php echo strtolower(str_replace(" ","-",$complaint["status"])); ?>">
                <?php echo htmlspecialchars($complaint["status"]); ?>
            </span>

        </div>

        <div class="grid">

            <div class="info">
                <strong>Category</strong>
                <p><?php echo htmlspecialchars($complaint["category"]); ?></p>
            </div>

            <div class="info">
                <strong>Department</strong>
                <p><?php echo htmlspecialchars($complaint["department"]); ?></p>
            </div>

            <div class="info">
                <strong>Priority</strong>
                <p><?php echo htmlspecialchars($complaint["priority"]); ?></p>
            </div>

            <div class="info">
                <strong>Submitted Date</strong>
                <p>
                    <?php echo date("d M Y, h:i A",strtotime($complaint["created_at"])); ?>
                </p>
            </div>

        </div>

        <div class="section">

            <h3>Complaint Description</h3>

            <p>
                <?php echo nl2br(htmlspecialchars($complaint["description"])); ?>
            </p>

        </div>

        <div class="section">

            <h3>Complaint Status</h3>

            <div class="timeline-item">

                <div class="dot"></div>

                <div>
                    <strong>Complaint Submitted</strong>
                    <p>Your complaint has been submitted successfully.</p>
                </div>

            </div>

            <?php if ($complaint["status"] == "In Progress" || $complaint["status"] == "Resolved"): ?>

                <div class="timeline-item">

                    <div class="dot"></div>

                    <div>
                        <strong>In Progress</strong>
                        <p>Your complaint is currently being reviewed.</p>
                    </div>

                </div>

            <?php endif; ?>

            <?php if ($complaint["status"] == "Resolved"): ?>

                <div class="timeline-item">

                    <div class="dot"></div>

                    <div>
                        <strong>Resolved</strong>
                        <p>Your complaint has been resolved.</p>
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>