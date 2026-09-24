```php
<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "Teacher") {
    header("Location: login.php");
    exit();
}

include "db.php";

$teacher_id = $_SESSION["user_id"];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: teacher_complaints.php");
    exit();
}

$complaint_id = (int)$_GET["id"];

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $new_status = trim($_POST["status"]);
    $update_message = trim($_POST["update_message"]);

    $check_sql = "SELECT status
                  FROM complaints
                  WHERE complaint_id = ?";

    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        $error = "Database error.";
    } else {

        $check_stmt->bind_param("i", $complaint_id);
        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows == 0) {

            $error = "Complaint not found.";

        } else {

            $complaint_data = $check_result->fetch_assoc();
            $old_status = $complaint_data["status"];

            $update_sql = "UPDATE complaints
                           SET status = ?
                           WHERE complaint_id = ?";

            $update_stmt = $conn->prepare($update_sql);

            if (!$update_stmt) {

                $error = "Database error.";

            } else {

                $update_stmt->bind_param(
                    "si",
                    $new_status,
                    $complaint_id
                );

                if ($update_stmt->execute()) {

                    if ($old_status != $new_status || $update_message != "") {

                        $history_sql = "INSERT INTO complaint_updates
                                        (
                                            complaint_id,
                                            teacher_id,
                                            old_status,
                                            new_status,
                                            update_message
                                        )
                                        VALUES (?, ?, ?, ?, ?)";

                        $history_stmt = $conn->prepare($history_sql);

                        if ($history_stmt) {

                            $history_stmt->bind_param(
                                "iisss",
                                $complaint_id,
                                $teacher_id,
                                $old_status,
                                $new_status,
                                $update_message
                            );

                            $history_stmt->execute();
                        }
                    }

                    $message = "Complaint updated successfully!";

                } else {

                    $error = "Failed to update complaint.";
                }
            }
        }
    }
}

$sql = "SELECT
            complaints.*,
            users.name AS student_name,
            users.email AS student_email
        FROM complaints
        INNER JOIN users
            ON complaints.user_id = users.user_id
        WHERE complaints.complaint_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    die("Complaint not found.");

}

$complaint = $result->fetch_assoc();

$history_sql = "SELECT
                    complaint_updates.*,
                    users.name AS actor_name,
                    roles.role_name AS actor_role
                FROM complaint_updates
                LEFT JOIN users
                    ON complaint_updates.teacher_id = users.user_id
                LEFT JOIN roles
                    ON users.role_id = roles.role_id
                WHERE complaint_updates.complaint_id = ?
                ORDER BY complaint_updates.updated_at DESC";

$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $complaint_id);
$history_stmt->execute();

$history_result = $history_stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Update Complaint</title>

    <link rel="stylesheet" href="style.css">

    <style>

        body {
            background: #eef6ff;
        }

        .main-content {
            background: #eef6ff;
            min-height: 100vh;
        }

        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #d6e7f8;
        }

        .content {
            background: #eef6ff;
            padding: 30px;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 16px;
            background: #dbeafe;
            color: #1e40af;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #bfdbfe;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        .details-card {
            background: #ffffff;
            border: 1px solid #c7ddf5;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 18px rgba(31, 55, 82, 0.06);
        }

        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            border-bottom: 1px solid #dbeafe;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .details-header h1 {
            margin: 0 0 7px;
            color: #1e3a8a;
            font-size: 25px;
        }

        .details-header p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .status {
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            background: #dbeafe;
            color: #1e40af;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .details-grid > div {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 15px;
        }

        .details-grid strong {
            display: block;
            color: #1e3a8a;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .details-grid p {
            margin: 0;
            color: #334155;
            font-size: 13px;
        }

        .description-box {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .description-box h3 {
            margin-top: 0;
            color: #1e3a8a;
            font-size: 16px;
        }

        .description-box p {
            color: #475569;
            line-height: 1.7;
            font-size: 14px;
            margin-bottom: 0;
        }

        .update-box {
            background: #f8fbff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 30px;
        }

        .update-box h2 {
            margin-top: 0;
            color: #1e3a8a;
            font-size: 19px;
        }

        .update-box label {
            display: block;
            margin: 15px 0 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .update-box select,
        .update-box textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 11px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            background: #ffffff;
        }

        .update-box select:focus,
        .update-box textarea:focus {
            outline: none;
            border-color: #60a5fa;
        }

        .submit-button {
            margin-top: 17px;
            border: none;
            border-radius: 8px;
            padding: 11px 18px;
            background: #2563eb;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .submit-button:hover {
            background: #1d4ed8;
        }

        .timeline {
            margin-top: 10px;
        }

        .timeline h2 {
            color: #1e3a8a;
            font-size: 19px;
            margin-bottom: 20px;
        }

        .timeline-item {
            position: relative;
            display: flex;
            gap: 15px;
            padding: 0 0 22px 5px;
            margin-bottom: 5px;
            border-left: 2px solid #bfdbfe;
            padding-left: 22px;
        }

        .timeline-dot {
            position: absolute;
            left: -7px;
            top: 2px;
            width: 11px;
            height: 11px;
            background: #2563eb;
            border-radius: 50%;
            border: 3px solid #dbeafe;
        }

        .timeline-item strong {
            color: #1e3a8a;
            font-size: 14px;
        }

        .timeline-item p {
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
            margin: 7px 0;
        }

        .timeline-item small {
            color: #64748b;
            font-size: 11px;
        }

        @media (max-width: 900px) {

            .details-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

            .details-header {
                flex-direction: column;
            }

            .content {
                padding: 18px;
            }

            .details-card {
                padding: 18px;
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

            <a href="teacher_complaints.php">
                📋 All Complaints
            </a>

           <a href="updates.php">🔔 Updates</a>

            <a href="teacher_profile.php">
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
                Update Complaint
            </h2>

            <div class="user">
                👤
                <?php echo htmlspecialchars($_SESSION["name"]); ?>
            </div>

        </div>

        <div class="content">

            <a href="teacher_complaints.php" class="back-btn">
                ← Back to Complaints
            </a>

            <?php if ($message != ""): ?>

                <div class="success">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>

            <?php if ($error != ""): ?>

                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <div class="details-card">

                <div class="details-header">

                    <div>

                        <h1>
                            <?php echo htmlspecialchars($complaint["title"]); ?>
                        </h1>

                        <p>
                            Complaint #
                            <?php echo $complaint["complaint_id"]; ?>
                        </p>

                    </div>

                    <span class="status">
                        <?php echo htmlspecialchars($complaint["status"]); ?>
                    </span>

                </div>

                <div class="details-grid">

                    <div>
                        <strong>Student</strong>
                        <p>
                            <?php echo htmlspecialchars($complaint["student_name"]); ?>
                        </p>
                    </div>

                    <div>
                        <strong>Email</strong>
                        <p>
                            <?php echo htmlspecialchars($complaint["student_email"]); ?>
                        </p>
                    </div>

                    <div>
                        <strong>Category</strong>
                        <p>
                            <?php echo htmlspecialchars($complaint["category"]); ?>
                        </p>
                    </div>

                    <div>
                        <strong>Department</strong>
                        <p>
                            <?php echo htmlspecialchars($complaint["department"]); ?>
                        </p>
                    </div>

                    <div>
                        <strong>Priority</strong>
                        <p>
                            <?php echo htmlspecialchars($complaint["priority"]); ?>
                        </p>
                    </div>

                    <div>
                        <strong>Submitted</strong>
                        <p>
                            <?php
                            echo date(
                                "d M Y, h:i A",
                                strtotime($complaint["created_at"])
                            );
                            ?>
                        </p>
                    </div>

                </div>

                <div class="description-box">

                    <h3>
                        Complaint Description
                    </h3>

                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $complaint["description"]
                            )
                        );
                        ?>
                    </p>

                </div>

                <div class="update-box">

                    <h2>
                        Update Complaint
                    </h2>

                    <form method="POST">

                        <label>
                            Change Status
                        </label>

                        <select name="status" required>

                            <option value="Pending"
                                <?php
                                if ($complaint["status"] == "Pending") {
                                    echo "selected";
                                }
                                ?>>
                                Pending
                            </option>

                            <option value="In Progress"
                                <?php
                                if ($complaint["status"] == "In Progress") {
                                    echo "selected";
                                }
                                ?>>
                                In Progress
                            </option>

                            <option value="Resolved"
                                <?php
                                if ($complaint["status"] == "Resolved") {
                                    echo "selected";
                                }
                                ?>>
                                Resolved
                            </option>

                            <option value="Rejected"
                                <?php
                                if ($complaint["status"] == "Rejected") {
                                    echo "selected";
                                }
                                ?>>
                                Rejected
                            </option>

                        </select>

                        <label>
                            Update Message
                        </label>

                        <textarea
                            name="update_message"
                            rows="5"
                            placeholder="Write an update for the student..."
                        ></textarea>

                        <button
                            type="submit"
                            class="submit-button"
                        >
                            Update Complaint
                        </button>

                    </form>

                </div>

                <div class="timeline">

                    <h2>
                        Update History
                    </h2>

                    <?php if ($history_result->num_rows > 0): ?>

                        <?php while ($history = $history_result->fetch_assoc()): ?>

                            <div class="timeline-item">

                                <div class="timeline-dot"></div>

                                <div>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $history["old_status"]
                                        );
                                        ?>

                                        →

                                        <?php
                                        echo htmlspecialchars(
                                            $history["new_status"]
                                        );
                                        ?>

                                    </strong>

                                    <?php if (!empty($history["update_message"])): ?>

                                        <p>
                                            <?php
                                            echo nl2br(
                                                htmlspecialchars(
                                                    $history["update_message"]
                                                )
                                            );
                                            ?>
                                        </p>

                                    <?php endif; ?>

                                    <small>

                                        By
                                        <?php
                                        echo htmlspecialchars(
                                            $history["actor_name"] ?? "Unknown"
                                        );
                                        ?>

                                        (<?php
                                        echo htmlspecialchars(
                                            $history["actor_role"] ?? "Unknown"
                                        );
                                        ?>)

                                        ·

                                        <?php
                                        echo date(
                                            "d M Y, h:i A",
                                            strtotime(
                                                $history["updated_at"]
                                            )
                                        );
                                        ?>

                                    </small>

                                </div>

                            </div>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <p>
                            No updates yet.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>
```
