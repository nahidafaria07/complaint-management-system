
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$categories = [];
$departments = [];

$category_result = $conn->query("
    SELECT category_name
    FROM categories
    WHERE status = 'Active'
    ORDER BY category_name ASC
");

if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category_name'];
    }
}

$department_result = $conn->query("
    SELECT department_name
    FROM departments
    WHERE status = 'Active'
    ORDER BY department_name ASC
");

if ($department_result) {
    while ($row = $department_result->fetch_assoc()) {
        $departments[] = $row['department_name'];
    }
}

$where = [];
$params = [];
$types = '';

if ($status !== '' && in_array($status, ['Pending', 'In Progress', 'Resolved'], true)) {
    $where[] = 'c.status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($category !== '') {
    $where[] = 'c.category = ?';
    $params[] = $category;
    $types .= 's';
}

if ($department !== '') {
    $where[] = 'c.department = ?';
    $params[] = $department;
    $types .= 's';
}

if ($priority !== '' && in_array($priority, ['Low', 'Medium', 'High'], true)) {
    $where[] = 'c.priority = ?';
    $params[] = $priority;
    $types .= 's';
}

if ($search !== '') {
    $where[] = '(c.title LIKE ? OR c.description LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';

    $like = '%' . $search . '%';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;

    $types .= 'ssss';
}

$sql = "SELECT
            c.complaint_id,
            c.title,
            c.category,
            c.department,
            c.priority,
            c.status,
            c.description,
            c.created_at,
            u.name AS student_name,
            u.email AS student_email,
            suser.name AS staff_name
        FROM complaints c
        INNER JOIN users u
            ON c.user_id = u.user_id
        LEFT JOIN staff s
            ON c.staff_id = s.staff_id
        LEFT JOIN users suser
            ON s.user_id = suser.user_id";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY c.created_at DESC';

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Database query error.');
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Complaints</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #eef6ff;
            color: #17324d;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 240px;
            height: 100vh;
            background: #0f3d70;
            padding: 25px 15px;
        }

        .sidebar h2 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
        }

        .sidebar a {
            display: block;
            text-decoration: none;
            color: white;
            padding: 13px 15px;
            margin-bottom: 8px;
            border-radius: 7px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #1f5f9f;
        }

        .main {
            margin-left: 240px;
            padding: 25px;
        }

        .topbar {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .topbar h1 {
            color: #0f3d70;
            font-size: 26px;
        }

        .topbar p {
            margin-top: 6px;
            color: #6b7c8f;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .card h2 {
            color: #0f3d70;
            margin-bottom: 18px;
        }

        .filters {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto auto;
            gap: 10px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #35536f;
            margin-bottom: 6px;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 11px;
            border: 1px solid #c8d9e8;
            border-radius: 6px;
            outline: none;
            background: white;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #1f6fb2;
        }

        .btn {
            padding: 11px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
        }

        .filter-btn {
            background: #0f5b9a;
            color: white;
        }

        .filter-btn:hover {
            background: #0b4778;
        }

        .clear-btn {
            background: #e4edf5;
            color: #24445e;
        }

        .clear-btn:hover {
            background: #d3e2ef;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th {
            background: #0f3d70;
            color: white;
            padding: 13px 10px;
            text-align: left;
            font-size: 14px;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid #e1eaf2;
            font-size: 14px;
        }

        tr:hover td {
            background: #f5faff;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .pending {
            background: #fff1c7;
            color: #8a6500;
        }

        .progress {
            background: #d9ecff;
            color: #07558f;
        }

        .resolved {
            background: #d9f5df;
            color: #187332;
        }

        .priority {
            font-weight: bold;
        }

        .high {
            color: #c62828;
        }

        .medium {
            color: #d17a00;
        }

        .low {
            color: #218838;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #71869a;
        }

        small {
            color: #71869a;
        }

        @media (max-width: 1200px) {
            .filters {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main {
                margin-left: 0;
            }

            .filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">

    <h2>Admin Panel</h2>

    <a href="admin_dashboard.php">Dashboard</a>

    <a href="admin_complaints.php" class="active">
        All Complaints
    </a>

    <a href="admin_users.php">
        Manage Users
    </a>

    <a href="admin_profile.php">
        Profile
    </a>

    <a href="logout.php">
        Logout
    </a>

</div>

<div class="main">

    <div class="topbar">

        <h1>All Complaints</h1>

        <p>
            Search and filter complaints by different criteria.
        </p>

    </div>

    <div class="card">

        <h2>Search & Filter</h2>

        <form method="GET" class="filters">

            <div class="filter-group">

                <label>Search</label>

                <input
                    type="text"
                    name="search"
                    placeholder="Title, description, student name or email"
                    value="<?php echo htmlspecialchars($search); ?>"
                >

            </div>

            <div class="filter-group">

                <label>Status</label>

                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option value="Pending"
                        <?php echo $status === 'Pending' ? 'selected' : ''; ?>>
                        Pending
                    </option>

                    <option value="In Progress"
                        <?php echo $status === 'In Progress' ? 'selected' : ''; ?>>
                        In Progress
                    </option>

                    <option value="Resolved"
                        <?php echo $status === 'Resolved' ? 'selected' : ''; ?>>
                        Resolved
                    </option>

                </select>

            </div>

            <div class="filter-group">

                <label>Category</label>

                <select name="category">

                    <option value="">
                        All Categories
                    </option>

                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?php echo htmlspecialchars($cat); ?>"
                            <?php echo $category === $cat ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($cat); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-group">

                <label>Department</label>

                <select name="department">

                    <option value="">
                        All Departments
                    </option>

                    <?php foreach ($departments as $dept): ?>

                        <option
                            value="<?php echo htmlspecialchars($dept); ?>"
                            <?php echo $department === $dept ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($dept); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-group">

                <label>Priority</label>

                <select name="priority">

                    <option value="">
                        All Priority
                    </option>

                    <option value="Low"
                        <?php echo $priority === 'Low' ? 'selected' : ''; ?>>
                        Low
                    </option>

                    <option value="Medium"
                        <?php echo $priority === 'Medium' ? 'selected' : ''; ?>>
                        Medium
                    </option>

                    <option value="High"
                        <?php echo $priority === 'High' ? 'selected' : ''; ?>>
                        High
                    </option>

                </select>

            </div>

            <button
                type="submit"
                class="btn filter-btn"
            >
                Filter
            </button>

            <a
                href="admin_complaints.php"
                class="btn clear-btn"
            >
                Clear
            </a>

        </form>

    </div>

    <div class="card">

        <h2>Complaint List</h2>

        <div class="table-container">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Student</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned Staff</th>
                        <th>Created</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $row['complaint_id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['title']); ?>
                            </td>

                            <td>

                                <?php echo htmlspecialchars($row['student_name']); ?>

                                <br>

                                <small>
                                    <?php echo htmlspecialchars($row['student_email']); ?>
                                </small>

                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['category']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['department']); ?>
                            </td>

                            <td>

                                <span class="priority
                                    <?php echo strtolower($row['priority']); ?>">

                                    <?php echo htmlspecialchars($row['priority']); ?>

                                </span>

                            </td>

                            <td>

                                <?php

                                $status_class = '';

                                if ($row['status'] === 'Pending') {
                                    $status_class = 'pending';
                                } elseif ($row['status'] === 'In Progress') {
                                    $status_class = 'progress';
                                } elseif ($row['status'] === 'Resolved') {
                                    $status_class = 'resolved';
                                }

                                ?>

                                <span class="status <?php echo $status_class; ?>">

                                    <?php echo htmlspecialchars($row['status']); ?>

                                </span>

                            </td>

                            <td>

                                <?php

                                if (!empty($row['staff_name'])) {
                                    echo htmlspecialchars($row['staff_name']);
                                } else {
                                    echo 'Not Assigned';
                                }

                                ?>

                            </td>

                            <td>

                                <?php

                                echo date(
                                    'd M Y',
                                    strtotime($row['created_at'])
                                );

                                ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="9"
                            class="no-data"
                        >
                            No complaints found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>
