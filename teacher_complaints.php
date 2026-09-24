
<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "Teacher") {
    header("Location: login.php");
    exit();
}

include "db.php";

$teacher_user_id = $_SESSION["user_id"];

$sql = "SELECT complaints.*
        FROM complaints
        ORDER BY complaints.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>All Complaints</title>

    <link rel="stylesheet" href="style.css">

    <style>

        body {
            background: #eef6ff;
        }

        .main-content {
            background: #eef6ff;
            min-height: 100vh;
        }

        .content {
            padding: 30px;
            background: #eef6ff;
        }

        .complaint-box {
            background: #ffffff;
            border: 1px solid #c7ddf5;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 6px 18px rgba(31, 55, 82, 0.06);
        }

        .page-title {
            color: #1e3a8a;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #dbeafe;
            color: #1e3a8a;
            padding: 13px;
            text-align: left;
            font-size: 12px;
            white-space: nowrap;
        }

        td {
            padding: 13px;
            border-bottom: 1px solid #dbeafe;
            color: #475569;
            font-size: 13px;
        }

        tr:hover {
            background: #f8fbff;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: #dbeafe;
            color: #1e40af;
            font-size: 11px;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .view-btn,
        .update-btn {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
        }

        .view-btn {
            background: #dbeafe;
            color: #1e40af;
        }

        .view-btn:hover {
            background: #bfdbfe;
        }

        .update-btn {
            background: #2563eb;
            color: #ffffff;
        }

        .update-btn:hover {
            background: #1d4ed8;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #64748b;
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

            <a href="teacher_complaints.php" class="active">
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
                All Complaints
            </h2>

            <div class="user">
                👤
                <?php echo htmlspecialchars($_SESSION["name"]); ?>
            </div>

        </div>

        <div class="content">

            <div class="complaint-box">

                <h2 class="page-title">
                    All Complaints
                </h2>

                <?php if ($result->num_rows > 0): ?>

                    <div class="table-container">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        Title
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Department
                                    </th>

                                    <th>
                                        Priority
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Submitted
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($complaint = $result->fetch_assoc()): ?>

                                    <tr>

                                        <td>
                                            #
                                            <?php
                                            echo (int)$complaint["complaint_id"];
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

                                            <span class="status">

                                                <?php
                                                echo htmlspecialchars(
                                                    $complaint["status"]
                                                );
                                                ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?php
                                            echo date(
                                                "d M Y, h:i A",
                                                strtotime(
                                                    $complaint["created_at"]
                                                )
                                            );
                                            ?>

                                        </td>

                                        <td>

                                            <div class="action-buttons">

                                                <a
                                                    href="teacher_complaint_details.php?id=<?php echo (int)$complaint["complaint_id"]; ?>"
                                                    class="view-btn"
                                                >
                                                    View
                                                </a>

                                                <a
                                                    href="update_complaint.php?id=<?php echo (int)$complaint["complaint_id"]; ?>"
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
                        No complaints found.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

</body>

</html>
