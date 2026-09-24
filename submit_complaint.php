
<?php

session_start();

include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"];

$message = "";

$category_sql = "SELECT category_name
                 FROM categories
                 WHERE status = 'Active'
                 ORDER BY category_name ASC";

$category_result = $conn->query($category_sql);

$department_sql = "SELECT department_name
                   FROM departments
                   WHERE status = 'Active'
                   ORDER BY department_name ASC";

$department_result = $conn->query($department_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"] ?? "");
    $category = $_POST["category"] ?? "";
    $department = $_POST["department"] ?? "";
    $priority = $_POST["priority"] ?? "Medium";
    $description = trim($_POST["description"] ?? "");

    $image_name = NULL;

    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] != UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["image"]["error"] == UPLOAD_ERR_OK) {

            $extension = strtolower(
                pathinfo(
                    $_FILES["image"]["name"],
                    PATHINFO_EXTENSION
                )
            );

            $allowed_extensions = [
                "jpg",
                "jpeg",
                "png",
                "webp"
            ];

            if (in_array($extension, $allowed_extensions)) {

                $image_name = uniqid("complaint_") . "." . $extension;

                $upload_dir = __DIR__ . "/uploads/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $upload_path = $upload_dir . $image_name;

                if (!move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $upload_path
                )) {

                    $message = "Image upload failed. Please check uploads folder.";
                    $image_name = NULL;
                }

            } else {

                $message = "Only JPG, JPEG, PNG or WEBP images are allowed.";
            }

        } else {

            $message = "Image upload error. Please try another image.";
        }
    }

    if ($message == "") {

        $sql = "INSERT INTO complaints
                (
                    user_id,
                    title,
                    category,
                    department,
                    priority,
                    description,
                    image
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database error: " . $conn->error);
        }

        $stmt->bind_param(
            "issssss",
            $user_id,
            $title,
            $category,
            $department,
            $priority,
            $description,
            $image_name
        );

        if ($stmt->execute()) {

            $message = "Complaint submitted successfully!";

        } else {

            $message = "Complaint could not be submitted: " . $stmt->error;
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Submit Complaint - CMS</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #eef6ff;
            color: #1e3a5f;
        }

        .navbar {
            background: #0f3d70;
            padding: 18px 30px;
            color: white;
            box-shadow: 0 3px 12px rgba(15, 61, 112, 0.20);
        }

        .navbar h2 {
            margin: 0 0 12px 0;
            font-size: 23px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-right: 22px;
            display: inline-block;
            padding: 8px 10px;
            border-radius: 7px;
            font-size: 14px;
        }

        .navbar a:hover {
            background: #2563eb;
        }

        .navbar a.active {
            background: #2563eb;
        }

        .container {
            width: 92%;
            max-width: 820px;
            margin: 35px auto;
            background: #eaf3ff;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid #b9d4f5;
            box-shadow: 0 6px 18px rgba(31, 55, 82, 0.08);
        }

        h1 {
            margin: 0 0 8px 0;
            color: #123f6d;
            font-size: 28px;
        }

        .welcome {
            color: #52708f;
            margin-bottom: 25px;
            font-size: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 7px;
            color: #244d73;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #b7cfe8;
            border-radius: 8px;
            font-size: 15px;
            background: #f7fbff;
            color: #1e3a5f;
            outline: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        small {
            display: block;
            color: #5d7893;
            margin-top: 6px;
        }

        .message {
            padding: 13px 15px;
            margin: 20px 0;
            border-radius: 8px;
            background: #dff3e6;
            color: #166534;
            border: 1px solid #b7dfc3;
        }

        .submit-btn {
            display: block;
            width: 100%;
            margin-top: 25px;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #1d4ed8;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 700px) {

            .navbar {
                padding: 15px;
            }

            .navbar a {
                margin-right: 5px;
                margin-bottom: 5px;
            }

            .container {
                width: 94%;
                padding: 22px;
                margin: 25px auto;
            }

            h1 {
                font-size: 24px;
            }
        }

    </style>

</head>

<body>

    <div class="navbar">

        <h2>CMS</h2>

        <a href="dashboard.php">🏠 Dashboard</a>

        <a href="submit_complaint.php" class="active">📝 Submit Complaint</a>

        <a href="my_complaints.php">📋 My Complaints</a>

        <a href="updates.php">🔔 Updates</a>

        <a href="student_profile.php">👤 Profile</a>

        <a href="logout.php">🚪 Logout</a>

    </div>

    <div class="container">

        <h1>Submit New Complaint</h1>

        <div class="welcome">
            Welcome, <?php echo htmlspecialchars($name); ?>
        </div>

        <?php if ($message != ""): ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <label for="title">
                Complaint Title *
            </label>

            <input
                type="text"
                id="title"
                name="title"
                placeholder="Enter complaint title"
                required
            >

            <label for="category">
                Category *
            </label>

            <select
                id="category"
                name="category"
                required
            >

                <option value="">
                    Select Category
                </option>

                <?php while ($category = $category_result->fetch_assoc()): ?>

                    <option
                        value="<?php echo htmlspecialchars($category["category_name"]); ?>"
                    >
                        <?php echo htmlspecialchars($category["category_name"]); ?>
                    </option>

                <?php endwhile; ?>

            </select>

            <label for="department">
                Department *
            </label>

            <select
                id="department"
                name="department"
                required
            >

                <option value="">
                    Select Department
                </option>

                <?php while ($department = $department_result->fetch_assoc()): ?>

                    <option
                        value="<?php echo htmlspecialchars($department["department_name"]); ?>"
                    >
                        <?php echo htmlspecialchars($department["department_name"]); ?>
                    </option>

                <?php endwhile; ?>

            </select>

            <label for="priority">
                Priority
            </label>

            <select
                id="priority"
                name="priority"
            >

                <option value="Low">
                    Low
                </option>

                <option value="Medium" selected>
                    Medium
                </option>

                <option value="High">
                    High
                </option>

            </select>

            <label for="description">
                Complaint Description *
            </label>

            <textarea
                id="description"
                name="description"
                placeholder="Write your complaint details..."
                required
            ></textarea>

            <label for="image">
                Attach Image (Optional)
            </label>

            <input
                type="file"
                id="image"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >

            <small>
                JPG, JPEG, PNG or WEBP image
            </small>

            <input
                type="submit"
                value="Submit Complaint"
                class="submit-btn"
            >

        </form>

        <a
            href="dashboard.php"
            class="back-link"
        >
            Back to Dashboard
        </a>

    </div>

</body>

</html>
