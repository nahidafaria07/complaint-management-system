
<?php

session_start();

include("db.php");

$error = "";
$success = "";

if (isset($_GET["reset_requested"])) {
    $success = "Password reset request sent to Admin. Please wait for your new password.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* Normal Login */
    if (isset($_POST["login"])) {

        $email = trim($_POST["email"]);
        $password = trim($_POST["password"]);

        $sql = "SELECT users.*, roles.role_name
                FROM users
                JOIN roles ON users.role_id = roles.role_id
                WHERE users.email = ?
                AND users.password = ?
                AND users.status = 'Active'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role_name"];

            if ($user["role_name"] == "Student") {
                header("Location: dashboard.php");
                exit();
            }

            if ($user["role_name"] == "Teacher") {
                header("Location: teacher_dashboard.php");
                exit();
            }

            if ($user["role_name"] == "Staff") {
                header("Location: staff_dashboard.php");
                exit();
            }

            if ($user["role_name"] == "Admin") {
                header("Location: admin_dashboard.php");
                exit();
            }

            die("Invalid user role");

        } else {

            $error = "Invalid email or password";
        }

        $stmt->close();
    }


    /* Forgot Password Request */
    if (isset($_POST["forgot_password"])) {

        $email = trim($_POST["forgot_email"]);

        if ($email == "") {

            $error = "Please enter your email address.";

        } else {

            $stmt = $conn->prepare(
                "SELECT user_id, email
                 FROM users
                 WHERE email = ?
                 AND status = 'Active'"
            );

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows == 1) {

                $user = $result->fetch_assoc();

                $check = $conn->prepare(
                    "SELECT request_id
                     FROM password_reset_requests
                     WHERE user_id = ?
                     AND status = 'Pending'"
                );

                $check->bind_param("i", $user["user_id"]);
                $check->execute();

                $check_result = $check->get_result();

                if ($check_result->num_rows > 0) {

                    $error = "Your password reset request is already pending.";

                } else {

                    $insert = $conn->prepare(
                        "INSERT INTO password_reset_requests
                        (user_id, email, status)
                        VALUES (?, ?, 'Pending')"
                    );

                    $insert->bind_param(
                        "is",
                        $user["user_id"],
                        $user["email"]
                    );

                    if ($insert->execute()) {

                        header("Location: login.php?reset_requested=1");
                        exit();

                    } else {

                        $error = "Something went wrong. Please try again.";
                    }

                    $insert->close();
                }

                $check->close();

            } else {

                $error = "No active account found with this email.";
            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Complaint Management System</title>

<script src="https://accounts.google.com/gsi/client" async defer></script>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    min-height: 100vh;
    font-family: Arial, Helvetica, sans-serif;
    background: #2f5f9f;
    overflow-y: auto;
    overflow-x: hidden;
    position: relative;
    padding: 35px 15px;
}

body::before,
body::after {
    content: "";
    position: fixed;
    border-radius: 50%;
    filter: blur(5px);
    opacity: 0.25;
    pointer-events: none;
    animation: float 8s ease-in-out infinite alternate;
}

body::before {
    width: 280px;
    height: 280px;
    background: #6fa3df;
    top: -100px;
    left: -80px;
}

body::after {
    width: 350px;
    height: 350px;
    background: #193e73;
    right: -120px;
    bottom: -120px;
    animation-delay: 2s;
}

@keyframes float {

    from {
        transform: translate(0, 0);
    }

    to {
        transform: translate(30px, 25px);
    }

}

.page {
    width: 100%;
    min-height: calc(100vh - 70px);
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.brand {
    text-align: center;
    color: white;
    margin-bottom: 25px;
}

.logo {
    width: 62px;
    height: 62px;
    border-radius: 18px;
    background: white;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 30px rgba(0,0,0,0.18);
}

.logo-icon {
    width: 30px;
    height: 34px;
    border: 4px solid #2f5f9f;
    border-radius: 8px;
    position: relative;
}

.logo-icon::after {
    content: "";
    width: 15px;
    height: 8px;
    border-left: 4px solid #2f5f9f;
    border-bottom: 4px solid #2f5f9f;
    transform: rotate(-45deg);
    position: absolute;
    left: 5px;
    top: 8px;
}

.brand h1 {
    font-size: 25px;
    font-weight: 700;
    letter-spacing: 0.2px;
}

.brand p {
    margin-top: 7px;
    font-size: 13px;
    color: #e7eff9;
}

.login-card {
    width: 100%;
    max-width: 430px;
    background: white;
    border-radius: 20px;
    padding: 30px 34px 28px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.22);
}

.welcome {
    text-align: center;
    margin-bottom: 22px;
}

.welcome-icon {
    width: 52px;
    height: 52px;
    margin: 0 auto 10px;
    border-radius: 50%;
    background: #edf4fc;
    color: #2f5f9f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.welcome h2 {
    color: #1f2937;
    font-size: 22px;
    margin-bottom: 6px;
}

.welcome p {
    color: #7b8794;
    font-size: 13px;
}

.error {
    background: #fff0f0;
    color: #c0392b;
    border: 1px solid #f5c6c6;
    padding: 10px 12px;
    border-radius: 9px;
    font-size: 13px;
    margin-bottom: 15px;
    text-align: center;
}

.success {
    background: #e9f8ef;
    color: #16834b;
    border: 1px solid #bde8cd;
    padding: 10px 12px;
    border-radius: 9px;
    font-size: 13px;
    margin-bottom: 15px;
    text-align: center;
}

.input-group {
    margin-bottom: 16px;
}

.input-group label {
    display: block;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 7px;
}

.input-box {
    position: relative;
}

.input-box .icon {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #8b98a8;
    font-size: 16px;
}

.input-box input {
    width: 100%;
    height: 46px;
    border: 1px solid #d7dee8;
    border-radius: 10px;
    padding: 0 42px;
    outline: none;
    font-size: 14px;
    color: #263238;
    transition: 0.2s;
}

.input-box input:focus {
    border-color: #477bb8;
    box-shadow: 0 0 0 3px rgba(71,123,184,0.12);
}

.eye {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #7d8997;
    font-size: 17px;
}

.login-btn {
    width: 100%;
    height: 46px;
    border: none;
    border-radius: 10px;
    background: #2f5f9f;
    color: white;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 3px;
    transition: 0.2s;
}

.login-btn:hover {
    background: #254f87;
}

.forgot {
    text-align: right;
    margin-top: 9px;
    margin-bottom: 3px;
}

.forgot a {
    color: #2f5f9f;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.forgot a:hover {
    text-decoration: underline;
}

.divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 21px 0;
    color: #9aa5b1;
    font-size: 12px;
}

.divider::before,
.divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #e3e7ec;
}

.google-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.google-button {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.google-button .g_id_signin {
    display: flex !important;
    justify-content: center !important;
    width: auto !important;
    margin: 0 auto !important;
}

.g_id_signin {
    margin: 0 auto !important;
}

.signup {
    text-align: center;
    margin-top: 21px;
    font-size: 13px;
    color: #7b8794;
}

.signup a {
    color: #2f5f9f;
    font-weight: 700;
    text-decoration: none;
}

.signup a:hover {
    text-decoration: underline;
}

.footer {
    text-align: center;
    color: #dce8f5;
    font-size: 11px;
    margin-top: 20px;
    padding-bottom: 10px;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    justify-content: center;
    align-items: center;
    padding: 20px;
    z-index: 100;
}

.modal-box {
    width: 100%;
    max-width: 390px;
    background: white;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}

.modal-box h2 {
    color: #2f5f9f;
    font-size: 21px;
    margin-bottom: 7px;
}

.modal-box p {
    color: #7b8794;
    font-size: 13px;
    line-height: 1.5;
    margin-bottom: 20px;
}

.modal-buttons {
    display: flex;
    gap: 10px;
    margin-top: 18px;
}

.modal-buttons button {
    flex: 1;
    height: 42px;
    border-radius: 9px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
}

.cancel-btn {
    border: 1px solid #d7dee8;
    background: white;
    color: #555;
}

.request-btn {
    border: none;
    background: #2f5f9f;
    color: white;
}

.request-btn:hover {
    background: #254f87;
}

@media (max-width: 500px) {

    body {
        padding: 25px 12px;
    }

    .login-card {
        padding: 26px 22px 25px;
    }

    .brand h1 {
        font-size: 21px;
    }

}

</style>

</head>

<body>

<div class="page">

    <div class="brand">

        <div class="logo">
            <div class="logo-icon"></div>
        </div>

        <h1>Complaint Management System</h1>

        <p>Making complaints easier to submit, track and resolve.</p>

    </div>


    <div class="login-card">

        <div class="welcome">

            <div class="welcome-icon">👋</div>

            <h2>Welcome Back</h2>

            <p>Sign in to continue to your account</p>

        </div>


        <?php if (!empty($error)) { ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>


        <?php if (!empty($success)) { ?>

            <div class="success">
                <?php echo htmlspecialchars($success); ?>
            </div>

        <?php } ?>


        <form method="POST" action="login.php">

            <div class="input-group">

                <label>Email Address</label>

                <div class="input-box">

                    <span class="icon">✉</span>

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >

                </div>

            </div>


            <div class="input-group">

                <label>Password</label>

                <div class="input-box">

                    <span class="icon">🔒</span>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Enter your password"
                        required
                    >

                    <span
                        class="eye"
                        onclick="togglePassword()"
                    >
                        👁
                    </span>

                </div>

            </div>


            <div class="forgot">

                <a href="#" onclick="openForgot(); return false;">
                    Forgot Password?
                </a>

            </div>


            <button
                type="submit"
                name="login"
                class="login-btn"
            >
                SIGN IN
            </button>

        </form>


        <div class="divider">
            OR
        </div>


        <div class="google-wrapper">

            <div
                id="g_id_onload"
                data-client_id="174434152570-o4hv0rio594k4jh5fab9i3nd61oabn7v.apps.googleusercontent.com"
                data-callback="handleCredentialResponse"
                data-auto_prompt="false">
            </div>

            <div class="google-button">

                <div
                    class="g_id_signin"
                    data-type="standard"
                    data-size="large"
                    data-theme="outline"
                    data-text="continue_with"
                    data-shape="rectangular"
                    data-logo_alignment="left">
                </div>

            </div>

        </div>


        <div class="signup">

            Don't have an account?
            <a href="signup.php">Sign Up</a>

        </div>

    </div>


    <div class="footer">
        © 2026 Complaint Management System
    </div>

</div>


<!-- Forgot Password Modal -->

<div class="modal" id="forgotModal">

    <div class="modal-box">

        <h2>Forgot Password?</h2>

        <p>
            Enter your registered email address.
            Your request will be sent to the Admin.
        </p>


        <form method="POST" action="login.php">

            <div class="input-group">

                <label>Email Address</label>

                <div class="input-box">

                    <span class="icon">✉</span>

                    <input
                        type="email"
                        name="forgot_email"
                        placeholder="Enter your registered email"
                        required
                    >

                </div>

            </div>


            <div class="modal-buttons">

                <button
                    type="button"
                    class="cancel-btn"
                    onclick="closeForgot()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="forgot_password"
                    class="request-btn"
                >
                    Send Request
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function togglePassword() {

    const password = document.getElementById("password");

    if (password.type === "password") {

        password.type = "text";

    } else {

        password.type = "password";

    }

}


function openForgot() {

    document.getElementById("forgotModal").style.display = "flex";

}


function closeForgot() {

    document.getElementById("forgotModal").style.display = "none";

}


window.onclick = function(event) {

    const modal = document.getElementById("forgotModal");

    if (event.target === modal) {

        modal.style.display = "none";

    }

}


function handleCredentialResponse(response) {

    const form = document.createElement("form");

    form.method = "POST";
    form.action = "google_login.php";

    const input = document.createElement("input");

    input.type = "hidden";
    input.name = "credential";
    input.value = response.credential;

    form.appendChild(input);

    document.body.appendChild(form);

    form.submit();

}

</script>

</body>

</html>
