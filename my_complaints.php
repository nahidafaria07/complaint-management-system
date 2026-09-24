<?php

session_start();
include("db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$name = $_SESSION["name"];

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$priority = trim($_GET["priority"] ?? "");
$status = trim($_GET["status"] ?? "");

$sql = "SELECT * FROM complaints WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($search != "") {
    $sql .= " AND title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($category != "") {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($priority != "") {
    $sql .= " AND priority = ?";
    $params[] = $priority;
    $types .= "s";
}

if ($status != "") {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$category_stmt = $conn->prepare(
    "SELECT DISTINCT category
     FROM complaints
     WHERE user_id = ?
     AND category IS NOT NULL
     AND category != ''
     ORDER BY category"
);

$category_stmt->bind_param("i", $user_id);
$category_stmt->execute();
$category_result = $category_stmt->get_result();

$total = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Complaints - Complaint Management System</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: linear-gradient(135deg, #b8d8f5, #d6eaff);
    color: #1e293b;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 230px;
    height: 100vh;
    padding: 25px 15px;
    background: linear-gradient(180deg, #123e68, #1769aa);
    color: white;
}

.logo {
    text-align: center;
    margin-bottom: 30px;
}

.logo-icon {
    width: 48px;
    height: 48px;
    margin: auto;
    border-radius: 13px;
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
}

.logo h2 {
    margin: 9px 0 3px;
}

.logo small {
    color: #cfe7ff;
    font-size: 10px;
}

.menu-title {
    margin: 20px 10px 10px;
    color: #b9d9f5;
    font-size: 11px;
    text-transform: uppercase;
}

.menu a {
    display: block;
    padding: 12px;
    margin: 5px 0;
    border-radius: 9px;
    color: #e5f2ff;
    text-decoration: none;
    font-size: 14px;
}

.menu a:hover,
.menu a.active {
    background: #2b82c8;
    color: white;
}

.main-content {
    margin-left: 230px;
    min-height: 100vh;
}

.topbar {
    padding: 17px 30px;
    background: #eaf4ff;
    border-bottom: 1px solid #c9dff2;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.topbar h2 {
    margin: 0;
    color: #174a75;
    font-size: 19px;
}

.user {
    color: #42617a;
    font-size: 14px;
    font-weight: 600;
}

.content {
    padding: 30px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.page-header h1 {
    margin: 0 0 7px;
    color: #123e68;
    font-size: 29px;
}

.page-header p {
    margin: 0;
    color: #526d83;
    font-size: 13px;
}

.count {
    padding: 10px 15px;
    border-radius: 9px;
    background: #dceeff;
    color: #1769aa;
    font-size: 13px;
    font-weight: bold;
}

.filter-box,
.table-box {
    background: #edf6ff;
    border: 1px solid #c9dff2;
    border-radius: 14px;
    box-shadow: 0 5px 18px rgba(20,70,110,0.08);
}

.filter-box {
    padding: 22px;
    margin-bottom: 22px;
}

.filter-box h3 {
    margin: 0 0 16px;
    color: #1769aa;
    font-size: 16px;
}

.filter-form {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto auto;
    gap: 9px;
}

input,
select {
    width: 100%;
    height: 42px;
    padding: 9px 11px;
    border: 1px solid #c5d7e8;
    border-radius: 8px;
    background: white;
    color: #334155;
    outline: none;
}

input:focus,
select:focus {
    border-color: #1769aa;
}

.search-btn,
.clear-btn {
    height: 42px;
    padding: 0 16px;
    border-radius: 8px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    cursor: pointer;
}

.search-btn {
    border: none;
    background: #1769aa;
    color: white;
}

.search-btn:hover {
    background: #12598f;
}

.clear-btn {
    background: #dce8f3;
    color: #456278;
}

.table-box {
    overflow: hidden;
}

.table-title {
    padding: 17px 20px;
    border-bottom: 1px solid #c9dff2;
    color: #1769aa;
    font-size: 15px;
    font-weight: bold;
}

.table-scroll {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

th {
    padding: 13px;
    background: #dceeff;
    color: #456278;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
}

td {
    padding: 14px 13px;
    border-bottom: 1px solid #d8e7f3;
    font-size: 13px;
}

tr:hover {
    background: #f5faff;
}

.complaint-title {
    color: #1e293b;
    font-weight: bold;
    margin-bottom: 4px;
}

.category {
    color: #64748b;
    font-size: 12px;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: bold;
    white-space: nowrap;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-progress {
    background: #dbeafe;
    color: #1769aa;
}

.status-resolved {
    background: #dcfce7;
    color: #177245;
}

.priority-low {
    background: #dcfce7;
    color: #177245;
}

.priority-medium {
    background: #fff3cd;
    color: #856404;
}

.priority-high {
    background: #fee2e2;
    color: #b91c1c;
}

.view-btn {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 7px;
    background: #1769aa;
    color: white;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
}

.view-btn:hover {
    background: #12598f;
}

.empty {
    padding: 60px 20px;
    text-align: center;
    color: #64748b;
}

.empty-icon {
    font-size: 38px;
    margin-bottom: 10px;
}

.empty h3 {
    margin: 5px 0;
    color: #334155;
}

@media (max-width: 1050px) {

    .filter-form {
        grid-template-columns: 1fr 1fr 1fr;
    }

}

@media (max-width: 700px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main-content {
        margin-left: 0;
    }

    .content {
        padding: 18px;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .topbar {
        padding: 15px 18px;
    }

}

</style>

</head>

<body>

<aside class="sidebar">

    <div class="logo">

        <div class="logo-icon">✓</div>

        <h2>CMS</h2>

        <small>COMPLAINT MANAGEMENT</small>

    </div>

    <div class="menu-title">Student Menu</div>

    <nav class="menu">

        <a href="dashboard.php">🏠 Dashboard</a>

        <a href="submit_complaint.php">📝 Submit Complaint</a>

        <a href="my_complaints.php" class="active">📋 My Complaints</a>

        <a href="updates.php">🔔 Updates</a>

        <a href="student_profile.php">👤 Profile</a>

        <a href="change_password.php">🔑 Change Password</a>

        <a href="logout.php">🚪 Logout</a>

    </nav>

</aside>

<main class="main-content">

    <div class="topbar">

        <h2>📋 My Complaints</h2>

        <div class="user">
            👤 <?php echo htmlspecialchars($name); ?>
        </div>

    </div>

    <div class="content">

        <div class="page-header">

            <div>

                <h1>My Complaints</h1>

                <p>
                    View, search and track your submitted complaints.
                </p>

            </div>

            <div class="count">

                <?php echo $total; ?>

                Complaint<?php echo $total == 1 ? "" : "s"; ?>

            </div>

        </div>

        <div class="filter-box">

            <h3>🔍 Search & Filter</h3>

            <form method="GET" class="filter-form">

                <input
                    type="text"
                    name="search"
                    placeholder="Search by complaint title"
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <select name="category">

                    <option value="">All Categories</option>

                    <?php while ($cat = $category_result->fetch_assoc()): ?>

                        <option
                            value="<?php echo htmlspecialchars($cat["category"]); ?>"
                            <?php echo $category == $cat["category"] ? "selected" : ""; ?>
                        >
                            <?php echo htmlspecialchars($cat["category"]); ?>
                        </option>

                    <?php endwhile; ?>

                </select>

                <select name="priority">

                    <option value="">All Priorities</option>

                    <option value="Low" <?php echo $priority == "Low" ? "selected" : ""; ?>>
                        Low
                    </option>

                    <option value="Medium" <?php echo $priority == "Medium" ? "selected" : ""; ?>>
                        Medium
                    </option>

                    <option value="High" <?php echo $priority == "High" ? "selected" : ""; ?>>
                        High
                    </option>

                </select>

                <select name="status">

                    <option value="">All Status</option>

                    <option value="Pending" <?php echo $status == "Pending" ? "selected" : ""; ?>>
                        Pending
                    </option>

                    <option value="In Progress" <?php echo $status == "In Progress" ? "selected" : ""; ?>>
                        In Progress
                    </option>

                    <option value="Resolved" <?php echo $status == "Resolved" ? "selected" : ""; ?>>
                        Resolved
                    </option>

                </select>

                <button type="submit" class="search-btn">
                    🔍 Search
                </button>

                <a href="my_complaints.php" class="clear-btn">
                    Clear
                </a>

            </form>

        </div>

        <div class="table-box">

            <div class="table-title">
                📋 Complaint Records
            </div>

            <div class="table-scroll">

                <?php if ($result->num_rows > 0): ?>

                    <table>

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Complaint</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Staff Update</th>
                                <th>Date</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($complaint = $result->fetch_assoc()): ?>

                                <?php

                                $status_class = "status-pending";

                                if ($complaint["status"] == "In Progress") {
                                    $status_class = "status-progress";
                                }

                                if ($complaint["status"] == "Resolved") {
                                    $status_class = "status-resolved";
                                }

                                $priority_class = "priority-medium";

                                if ($complaint["priority"] == "Low") {
                                    $priority_class = "priority-low";
                                }

                                if ($complaint["priority"] == "High") {
                                    $priority_class = "priority-high";
                                }

                                $remark = trim($complaint["staff_remark"] ?? "");

                                ?>

                                <tr>

                                    <td>
                                        #<?php echo htmlspecialchars($complaint["complaint_id"]); ?>
                                    </td>

                                    <td>

                                        <div class="complaint-title">
                                            <?php echo htmlspecialchars($complaint["title"]); ?>
                                        </div>

                                        <div class="category">
                                            <?php echo htmlspecialchars($complaint["category"]); ?>
                                        </div>

                                    </td>

                                    <td>

                                        <span class="badge <?php echo $priority_class; ?>">
                                            <?php echo htmlspecialchars($complaint["priority"]); ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($complaint["status"]); ?>
                                        </span>

                                    </td>

                                    <td>

                                        <?php

                                        echo $remark != ""
                                            ? htmlspecialchars($remark)
                                            : "No update yet";

                                        ?>

                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($complaint["created_at"]); ?>
                                    </td>

                                    <td>

                                        <a
                                            href="complaint_details.php?id=<?php echo $complaint["complaint_id"]; ?>"
                                            class="view-btn"
                                        >
                                            👁 View
                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty">

                        <div class="empty-icon">📭</div>

                        <h3>No Complaints Found</h3>

                        <div>
                            No complaints match your current search or filter.
                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>

</body>

</html>