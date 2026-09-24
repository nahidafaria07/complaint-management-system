
<?php

session_start();

include "db.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $role_id = isset($_POST["role_id"]) ? (int)$_POST["role_id"] : 0;

    if ($name == "" || $email == "" || $password == "" || $role_id == 0) {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (!in_array($role_id, [1, 2, 4])) {

        $message = "Invalid account type.";
        $message_type = "error";

    } else {

        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {

            $message = "Database error.";
            $message_type = "error";

        } else {

            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();

            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {

                $message = "An account with this email already exists.";
                $message_type = "error";

            } else {

                $role_sql = "SELECT role_id FROM roles WHERE role_id = ?";
                $role_stmt = $conn->prepare($role_sql);

                if (!$role_stmt) {

                    $message = "Database error.";
                    $message_type = "error";

                } else {

                    $role_stmt->bind_param("i", $role_id);
                    $role_stmt->execute();

                    $role_result = $role_stmt->get_result();

                    if ($role_result->num_rows == 0) {

                        $message = "Invalid account type.";
                        $message_type = "error";

                    } else {

                        $status = "Active";

                        $conn->begin_transaction();

                        try {

                            $insert_sql = "INSERT INTO users
                                           (name, email, password, role_id, status)
                                           VALUES (?, ?, ?, ?, ?)";

                            $stmt = $conn->prepare($insert_sql);

                            if (!$stmt) {
                                throw new Exception("Failed to create user account.");
                            }

                            $stmt->bind_param(
                                "sssis",
                                $name,
                                $email,
                                $password,
                                $role_id,
                                $status
                            );

                            if (!$stmt->execute()) {
                                throw new Exception("Failed to create user account.");
                            }

                            $new_user_id = $conn->insert_id;

                            $stmt->close();

                            if ($role_id == 4) {

                                $staff_sql = "INSERT INTO staff (user_id) VALUES (?)";
                                $staff_stmt = $conn->prepare($staff_sql);

                                if (!$staff_stmt) {
                                    throw new Exception("Failed to create staff record.");
                                }

                                $staff_stmt->bind_param("i", $new_user_id);

                                if (!$staff_stmt->execute()) {
                                    throw new Exception("Failed to create staff record.");
                                }

                                $staff_stmt->close();
                            }

                            $conn->commit();

                            $message = "Account created successfully. You can now login.";
                            $message_type = "success";

                        } catch (Exception $e) {

                            $conn->rollback();

                            $message = $e->getMessage();
                            $message_type = "error";
                        }
                    }

                    $role_stmt->close();
                }
            }

            $check_stmt->close();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sign Up - Complaint Management System</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #b8d8f5, #d6eaff);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.signup-container {
    width: 100%;
    max-width: 460px;
}

.signup-card {
    background: #edf6ff;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.logo {
    text-align: center;
    font-size: 30px;
    font-weight: bold;
    color: #123e68;
    margin-bottom: 8px;
}

.subtitle {
    text-align: center;
    color: #64748b;
    margin-bottom: 28px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #123e68;
    font-weight: bold;
}

.input-box {
    display: flex;
    align-items: center;
    background: white;
    border: 1px solid #b7cfe8;
    border-radius: 8px;
    overflow: hidden;
}

.input-icon {
    width: 45px;
    text-align: center;
    font-size: 19px;
}

.input-box input,
.input-box select {
    width: 100%;
    border: none;
    outline: none;
    padding: 12px 10px;
    font-size: 15px;
    background: white;
    color: #334155;
}

.input-box:focus-within {
    border-color: #1769aa;
    box-shadow: 0 0 0 2px rgba(23,105,170,0.1);
}

.signup-button {
    width: 100%;
    padding: 13px;
    margin-top: 8px;
    background: #1769aa;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

.signup-button:hover {
    background: #12598f;
}

.login-link {
    text-align: center;
    margin-top: 20px;
    color: #64748b;
}

.login-link a {
    color: #1769aa;
    text-decoration: none;
    font-weight: bold;
}

.login-link a:hover {
    text-decoration: underline;
}

.message {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
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

@media (max-width: 500px) {

    .signup-card {
        padding: 25px 20px;
    }

}

</style>

</head>

<body>

<div class="signup-container">

    <div class="signup-card">

        <div class="logo">
            CMS
        </div>

        <div class="subtitle">
            Create your account
        </div>

        <?php if ($message != ""): ?>

            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label>
                    Full Name
                </label>

                <div class="input-box">

                    <span class="input-icon">
                        👤
                    </span>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter your full name"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label>
                    Email
                </label>

                <div class="input-box">

                    <span class="input-icon">
                        📧
                    </span>

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label>
                    Password
                </label>

                <div class="input-box">

                    <span class="input-icon">
                        🔒
                    </span>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label>
                    Register As
                </label>

                <div class="input-box">

                    <span class="input-icon">
                        👥
                    </span>

                    <select name="role_id" required>

                        <option value="">
                            Select account type
                        </option>

                        <option value="1">
                            Student
                        </option>

                        <option value="2">
                            Teacher
                        </option>

                        <option value="4">
                            Staff
                        </option>

                    </select>

                </div>

            </div>

            <button
                type="submit"
                class="signup-button"
            >
                Create Account
            </button>

        </form>

        <div class="login-link">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>

    </div>

</div>

</body>

</html>
