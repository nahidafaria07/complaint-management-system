
<?php

session_start();

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"];

if ($role != "Teacher" && $role != "Staff") {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    if ($role == "Teacher") {
        header("Location: teacher_complaints.php");
    } else {
        header("Location: staff_complaints.php");
    }

    exit();
}

$complaint_id = (int)$_GET["id"];

$message = "";
$error = "";

$complaint = null;

if ($role == "Staff") {

    $staff_sql = "SELECT staff_id
                  FROM staff
                  WHERE user_id = ?";

    $staff_stmt = $conn->prepare($staff_sql);

    if (!$staff_stmt) {
        die("Database error.");
    }

    $staff_stmt->bind_param("i", $user_id);
    $staff_stmt->execute();

    $staff_result = $staff_stmt->get_result();

    if ($staff_result->num_rows == 0) {
        die("Staff information not found.");
    }

    $staff = $staff_result->fetch_assoc();
    $staff_id = $staff["staff_id"];

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

} else {

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
}

if (!$stmt) {
    die("Database error.");
}

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    die("Complaint not found or you do not have permission to update it.");

}

$complaint = $result->fetch_assoc();

$old_status = $complaint["status"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $new_status = trim($_POST["status"]);

    if ($role == "Teacher") {

        $update_message = trim($_POST["update_message"]);

        if ($update_message == "") {

            $error = "Please write an update message.";

        } else {

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

                    if (
                        $old_status != $new_status ||
                        $update_message != ""
                    ) {

                        $history_sql = "INSERT INTO complaint_updates
                                        (
                                            complaint_id,
                                            teacher_id,
                                            old_status,
                                            new_status,
                                            update_message,
                                            updated_at
                                        )
                                        VALUES (?, ?, ?, ?, ?, NOW())";

                        $history_stmt = $conn->prepare($history_sql);

                        if ($history_stmt) {

                            $history_stmt->bind_param(
                                "iisss",
                                $complaint_id,
                                $user_id,
                                $old_status,
                                $new_status,
                                $update_message
                            );

                            $history_stmt->execute();
                        }
                    }

                    $message = "Complaint updated successfully.";

                    $complaint["status"] = $new_status;

                } else {

                    $error = "Failed to update complaint.";

                }
            }
        }

    } else {

        $staff_remark = trim($_POST["staff_remark"]);

        if ($staff_remark == "") {

            $error = "Please write an update message.";

        } else {

            $conn->begin_transaction();

            try {

                $update_sql = "UPDATE complaints
                               SET status = ?,
                                   staff_remark = ?
                               WHERE complaint_id = ?
                               AND staff_id = ?";

                $update_stmt = $conn->prepare($update_sql);

                if (!$update_stmt) {
                    throw new Exception("Database error.");
                }

                $update_stmt->bind_param(
                    "ssii",
                    $new_status,
                    $staff_remark,
                    $complaint_id,
                    $staff_id
                );

                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update complaint.");
                }

                $history_sql = "INSERT INTO complaint_updates
                                (
                                    complaint_id,
                                    teacher_id,
                                    old_status,
                                    new_status,
                                    update_message,
                                    updated_at
                                )
                                VALUES (?, ?, ?, ?, ?, NOW())";

                $history_stmt = $conn->prepare($history_sql);

                if (!$history_stmt) {
                    throw new Exception("Failed to save update history.");
                }

                $history_stmt->bind_param(
                    "iisss",
                    $complaint_id,
                    $user_id,
                    $old_status,
                    $new_status,
                    $staff_remark
                );

                if (!$history_stmt->execute()) {
                    throw new Exception("Failed to save update history.");
                }

                $conn->commit();

                $message = "Complaint updated successfully.";

                $complaint["status"] = $new_status;
                $complaint["staff_remark"] = $staff_remark;

            } catch (Exception $e) {

                $conn->rollback();

                $error = "Update failed: " . $e->getMessage();

            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Update Complaint</title>

    <link rel="stylesheet" href="style.css">

    <style>

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #eef6ff;
            margin: 0;
        }

        .dashboard-container {
            min-height: 100vh;
        }

        .main-content {
            background: #eef6ff;
            min-height: 100vh;
        }

        .content {
            padding: 30px;
            background: #eef6ff;
        }

        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #dbeafe;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 16px;
            background: #dbeafe;
            color: #1e40af;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #bfdbfe;
        }

        .details-card {
            background: #ffffff;
            border: 1px solid #c7ddf5;
            border-radius: 16px;
            padding: 28px;
            max-width: 1100px;
            box-shadow: 0 6px 18px rgba(31, 55, 82, 0.06);
        }

        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 20px;
            margin-bottom: 25px;
            border-bottom: 1px solid #dbeafe;
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
            background: #dbeafe;
            color: #1e40af;
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-box {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 15px;
        }

        .info-box strong {
            display: block;
            color: #1e3a8a;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .info-box p {
            margin: 0;
            color: #475569;
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
            font-size: 17px;
        }

        .description-box p {
            color: #475569;
            line-height: 1.7;
            font-size: 14px;
        }

        .update-box {
            background: #f8fbff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 22px;
        }

        .update-box h2 {
            margin-top: 0;
            color: #1e3a8a;
            font-size: 19px;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        select,
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            background: #ffffff;
        }

        textarea {
            resize: vertical;
        }

        select:focus,
        textarea:focus {
            outline: none;
            border-color: #60a5fa;
        }

        .submit-button {
            margin-top: 17px;
            padding: 11px 18px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .submit-button:hover {
            background: #1d4ed8;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            max-width: 1100px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            max-width: 1100px;
        }

        @media (max-width: 850px) {

            .details-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 18px;
            }

            .details-card {
                padding: 18px;
            }

            .details-header {
                flex-direction: column;
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

            <?php if ($role == "Teacher"): ?>

                <a href="teacher_dashboard.php">
                    🏠 Dashboard
                </a>

                <a href="teacher_complaints.php" class="active">
                    📋 All Complaints
                </a>

                <a href="teacher_updates.php">
                    🔔 Updates
                </a>

                <a href="teacher_profile.php">
                    👤 Profile
                </a>

            <?php else: ?>

                <a href="staff_dashboard.php">
                    🏠 Dashboard
                </a>

                <a href="staff_complaints.php" class="active">
                    📋 Complaints
                </a>

                <a href="staff_updates.php">
                    🔔 Updates
                </a>

                <a href="staff_profile.php">
                    👤 Profile
                </a>

            <?php endif; ?>

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

            <?php if ($role == "Teacher"): ?>

                <a
                    href="teacher_complaints.php"
                    class="back-btn"
                >
                    ← Back to Complaints
                </a>

            <?php else: ?>

                <a
                    href="staff_complaints.php"
                    class="back-btn"
                >
                    ← Back to Complaints
                </a>

            <?php endif; ?>

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
                            <?php
                            echo htmlspecialchars(
                                $complaint["title"]
                            );
                            ?>
                        </h1>

                        <p>
                            Complaint #
                            <?php
                            echo (int)$complaint["complaint_id"];
                            ?>
                        </p>

                    </div>

                    <span class="status">

                        <?php
                        echo htmlspecialchars(
                            $complaint["status"]
                        );
                        ?>

                    </span>

                </div>

                <div class="details-grid">

                    <?php if (isset($complaint["student_name"])): ?>

                        <div class="info-box">

                            <strong>
                                Student
                            </strong>

                            <p>
                                <?php
                                echo htmlspecialchars(
                                    $complaint["student_name"]
                                );
                                ?>
                            </p>

                        </div>

                    <?php endif; ?>

                    <?php if (isset($complaint["student_email"])): ?>

                        <div class="info-box">

                            <strong>
                                Email
                            </strong>

                            <p>
                                <?php
                                echo htmlspecialchars(
                                    $complaint["student_email"]
                                );
                                ?>
                            </p>

                        </div>

                    <?php endif; ?>

                    <div class="info-box">

                        <strong>
                            Category
                        </strong>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $complaint["category"]
                            );
                            ?>
                        </p>

                    </div>

                    <div class="info-box">

                        <strong>
                            Department
                        </strong>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $complaint["department"]
                            );
                            ?>
                        </p>

                    </div>

                    <div class="info-box">

                        <strong>
                            Priority
                        </strong>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $complaint["priority"]
                            );
                            ?>
                        </p>

                    </div>

                    <div class="info-box">

                        <strong>
                            Submitted
                        </strong>

                        <p>
                            <?php
                            echo date(
                                "d M Y, h:i A",
                                strtotime(
                                    $complaint["created_at"]
                                )
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

                        <label for="status">
                            Change Status
                        </label>

                        <select
                            name="status"
                            id="status"
                            required
                        >

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

                        <?php if ($role == "Teacher"): ?>

                            <label for="update_message">
                                Update Message
                            </label>

                            <textarea
                                name="update_message"
                                id="update_message"
                                rows="5"
                                placeholder="Write an update for the student..."
                                required
                            ></textarea>

                            <button
                                type="submit"
                                class="submit-button"
                            >
                                Update Complaint
                            </button>

                        <?php else: ?>

                            <label for="staff_remark">
                                Staff Remark / Update Message
                            </label>

                            <textarea
                                name="staff_remark"
                                id="staff_remark"
                                rows="5"
                                placeholder="Write complaint update here..."
                                required
                            ><?php
                            echo htmlspecialchars(
                                isset($complaint["staff_remark"])
                                    ? $complaint["staff_remark"]
                                    : ""
                            );
                            ?></textarea>

                            <button
                                type="submit"
                                class="submit-button"
                            >
                                Save Update
                            </button>

                        <?php endif; ?>

                    </form>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>
