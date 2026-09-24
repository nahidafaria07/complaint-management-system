
<?php

session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

$gmail_username = "faria.nahidasultana@gmail.com";
$gmail_app_password = "YOUR_NEW_GMAIL_APP_PASSWORD";

function smtpCommand($socket, $command, $expected)
{
    fwrite($socket, $command . "\r\n");

    $response = '';

    while ($line = fgets($socket, 515)) {
        $response .= $line;

        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    if (substr($response, 0, 3) != $expected) {
        return $response;
    }

    return true;
}

function sendResetEmail($to, $name, $new_password)
{
    global $gmail_username, $gmail_app_password;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false
        ]
    ]);

    $socket = stream_socket_client(
        "ssl://smtp.gmail.com:465",
        $errno,
        $errstr,
        30,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        return "Connection failed: " . $errstr;
    }

    fgets($socket, 515);

    $result = smtpCommand($socket, "EHLO localhost", "250");

    if ($result !== true) {
        fclose($socket);
        return "EHLO failed: " . $result;
    }

    $result = smtpCommand($socket, "AUTH LOGIN", "334");

    if ($result !== true) {
        fclose($socket);
        return "AUTH LOGIN failed: " . $result;
    }

    $result = smtpCommand($socket, base64_encode($gmail_username), "334");

    if ($result !== true) {
        fclose($socket);
        return "Gmail username authentication failed: " . $result;
    }

    $result = smtpCommand($socket, base64_encode($gmail_app_password), "235");

    if ($result !== true) {
        fclose($socket);
        return "Gmail App Password authentication failed: " . $result;
    }

    $result = smtpCommand(
        $socket,
        "MAIL FROM:<" . $gmail_username . ">",
        "250"
    );

    if ($result !== true) {
        fclose($socket);
        return "MAIL FROM failed: " . $result;
    }

    $result = smtpCommand(
        $socket,
        "RCPT TO:<" . $to . ">",
        "250"
    );

    if ($result !== true) {
        fclose($socket);
        return "RCPT TO failed: " . $result;
    }

    $result = smtpCommand($socket, "DATA", "354");

    if ($result !== true) {
        fclose($socket);
        return "DATA command failed: " . $result;
    }

    $subject = "Password Reset - Complaint Management System";

    $body = "Hello " . $name . ",\r\n\r\n";
    $body .= "Your password has been reset successfully by the administrator.\r\n\r\n";
    $body .= "Your new password is: " . $new_password . "\r\n\r\n";
    $body .= "Please use this password to log in to the Complaint Management System.\r\n\r\n";
    $body .= "Regards,\r\n";
    $body .= "Complaint Management System";

    $headers = "From: Complaint Management System <" . $gmail_username . ">\r\n";
    $headers .= "To: " . $to . "\r\n";
    $headers .= "Subject: " . $subject . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "\r\n";

    fwrite($socket, $headers . $body . "\r\n.\r\n");

    $response = '';

    while ($line = fgets($socket, 515)) {
        $response .= $line;

        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    if (substr($response, 0, 3) != "250") {
        fclose($socket);
        return "Email sending failed: " . $response;
    }

    smtpCommand($socket, "QUIT", "221");

    fclose($socket);

    return true;
}

if (isset($_GET['updated'])) {
    $message = 'User status updated successfully.';
}

if (isset($_GET['created'])) {
    $message = 'User created successfully.';
}

if (isset($_GET['deleted'])) {
    $message = 'User deleted permanently.';
}

if (isset($_GET['delete_error'])) {
    $error = 'This user has existing complaint records and cannot be permanently deleted.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role_id = (int)$_POST['role_id'];

    if ($name === '' || $email === '' || $password === '') {

        $error = 'Please fill in all fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 4) {

        $error = 'Password must be at least 4 characters.';

    } elseif (!in_array($role_id, [1, 2, 3, 4])) {

        $error = 'Invalid role selected.';

    } else {

        $role_check = $conn->prepare(
            "SELECT role_id, role_name FROM roles WHERE role_id = ?"
        );

        $role_check->bind_param('i', $role_id);
        $role_check->execute();

        $role_result = $role_check->get_result();

        if ($role_result->num_rows !== 1) {

            $error = 'Selected role does not exist.';

        } else {

            $role_data = $role_result->fetch_assoc();

            $email_check = $conn->prepare(
                "SELECT user_id FROM users WHERE email = ?"
            );

            $email_check->bind_param('s', $email);
            $email_check->execute();

            $email_result = $email_check->get_result();

            if ($email_result->num_rows > 0) {

                $error = 'This email is already registered.';

            } else {

                $conn->begin_transaction();

                try {

                    $insert = $conn->prepare(
                        "INSERT INTO users (role_id, name, email, password, status)
                         VALUES (?, ?, ?, ?, 'Active')"
                    );

                    if (!$insert) {
                        throw new Exception('User creation failed.');
                    }

                    $insert->bind_param(
                        'isss',
                        $role_id,
                        $name,
                        $email,
                        $password
                    );

                    if (!$insert->execute()) {
                        throw new Exception('User creation failed.');
                    }

                    $new_user_id = $conn->insert_id;

                    $insert->close();

                    if ($role_id == 4) {

                        $staff_insert = $conn->prepare(
                            "INSERT INTO staff (user_id) VALUES (?)"
                        );

                        if (!$staff_insert) {
                            throw new Exception('Staff record creation failed.');
                        }

                        $staff_insert->bind_param('i', $new_user_id);

                        if (!$staff_insert->execute()) {
                            throw new Exception('Staff record creation failed.');
                        }

                        $staff_insert->close();
                    }

                    $conn->commit();

                    $message = $role_data['role_name'] . ' account created successfully.';

                } catch (Exception $e) {

                    $conn->rollback();
                    $error = $e->getMessage();
                }
            }

            $email_check->close();
        }

        $role_check->close();
    }
}

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {

    $user_id = (int)$_GET['toggle'];

    if ($user_id != (int)$_SESSION['user_id']) {

        $stmt = $conn->prepare(
            "UPDATE users
             SET status = CASE
                 WHEN status = 'Active' THEN 'Inactive'
                 ELSE 'Active'
             END
             WHERE user_id = ?"
        );

        if ($stmt) {

            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->close();
        }

        header('Location: admin_users.php?updated=1');
        exit();
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {

    $delete_user_id = (int)$_GET['delete'];

    if ($delete_user_id == (int)$_SESSION['user_id']) {

        $error = 'You cannot delete your own admin account.';

    } else {

        $check_complaints = $conn->prepare(
            "SELECT complaint_id
             FROM complaints
             WHERE user_id = ?
             LIMIT 1"
        );

        $check_complaints->bind_param('i', $delete_user_id);
        $check_complaints->execute();

        $complaint_result = $check_complaints->get_result();

        if ($complaint_result->num_rows > 0) {

            $check_complaints->close();

            header('Location: admin_users.php?delete_error=1');
            exit();

        } else {

            $check_complaints->close();

            $conn->begin_transaction();

            try {

                $delete_reset = $conn->prepare(
                    "DELETE FROM password_reset_requests
                     WHERE user_id = ?"
                );

                if ($delete_reset) {

                    $delete_reset->bind_param('i', $delete_user_id);
                    $delete_reset->execute();
                    $delete_reset->close();
                }

                $delete_staff = $conn->prepare(
                    "DELETE FROM staff
                     WHERE user_id = ?"
                );

                if ($delete_staff) {

                    $delete_staff->bind_param('i', $delete_user_id);
                    $delete_staff->execute();
                    $delete_staff->close();
                }

                $delete_user = $conn->prepare(
                    "DELETE FROM users
                     WHERE user_id = ?
                     AND user_id != ?"
                );

                if (!$delete_user) {
                    throw new Exception('User deletion failed.');
                }

                $delete_user->bind_param(
                    'ii',
                    $delete_user_id,
                    $_SESSION['user_id']
                );

                if (!$delete_user->execute()) {
                    throw new Exception('User deletion failed.');
                }

                if ($delete_user->affected_rows === 0) {
                    throw new Exception('User could not be deleted.');
                }

                $delete_user->close();

                $conn->commit();

                header('Location: admin_users.php?deleted=1');
                exit();

            } catch (Exception $e) {

                $conn->rollback();

                $error = $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {

    $request_id = (int)$_POST['request_id'];
    $user_id = (int)$_POST['user_id'];
    $new_password = trim($_POST['new_password']);

    if ($user_id == (int)$_SESSION['user_id']) {

        $error = 'You cannot reset your own password from this section.';

    } elseif ($new_password === '') {

        $error = 'Please enter a new password.';

    } elseif (strlen($new_password) < 4) {

        $error = 'Password must be at least 4 characters.';

    } else {

        $check = $conn->prepare(
            "SELECT request_id, email,
                    (SELECT name
                     FROM users
                     WHERE users.user_id = password_reset_requests.user_id) AS name
             FROM password_reset_requests
             WHERE request_id = ?
             AND user_id = ?
             AND status = 'Pending'"
        );

        $check->bind_param('ii', $request_id, $user_id);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows == 1) {

            $reset_data = $check_result->fetch_assoc();

            $email = $reset_data['email'];
            $name = $reset_data['name'];

            $update_user = $conn->prepare(
                "UPDATE users SET password = ? WHERE user_id = ?"
            );

            $update_user->bind_param(
                'si',
                $new_password,
                $user_id
            );

            if ($update_user->execute()) {

                $update_request = $conn->prepare(
                    "UPDATE password_reset_requests
                     SET status = 'Completed'
                     WHERE request_id = ?"
                );

                $update_request->bind_param(
                    'i',
                    $request_id
                );

                $update_request->execute();
                $update_request->close();

                $email_result = sendResetEmail(
                    $email,
                    $name,
                    $new_password
                );

                if ($email_result === true) {

                    $message = 'Password reset successfully. A notification has been sent to the user email.';

                } else {

                    $message = 'Password reset successfully, but email could not be sent. Error: ' . $email_result;
                }

            } else {

                $error = 'Password reset failed. Please try again.';
            }

            $update_user->close();

        } else {

            $error = 'This reset request is no longer pending.';
        }

        $check->close();
    }
}

$sql = "SELECT u.user_id,u.name,u.email,u.status,u.created_at,r.role_name
        FROM users u
        INNER JOIN roles r ON u.role_id = r.role_id
        ORDER BY u.user_id DESC";

$result = $conn->query($sql);

$reset_sql = "SELECT pr.request_id,pr.user_id,pr.email,pr.status,pr.requested_at,u.name
              FROM password_reset_requests pr
              INNER JOIN users u ON pr.user_id = u.user_id
              WHERE pr.status = 'Pending'
              ORDER BY pr.requested_at DESC";

$reset_result = $conn->query($reset_sql);

$roles_result = $conn->query(
    "SELECT role_id, role_name
     FROM roles
     WHERE role_id IN (1,2,3,4)
     ORDER BY role_id"
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Users</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #eef7ff;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 240px;
    height: 100vh;
    background: #0878bd;
    color: white;
    padding-top: 25px;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 14px 25px;
    font-size: 15px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #075f96;
}

.main {
    margin-left: 240px;
    padding: 25px;
}

.topbar {
    background: white;
    padding: 18px 22px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.topbar h1 {
    margin: 0;
    color: #075f96;
    font-size: 24px;
}

.admin-name {
    color: #555;
    font-weight: bold;
}

.message {
    background: #d1fae5;
    color: #065f46;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.card h2 {
    margin-top: 0;
    color: #075f96;
}

.create-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 7px;
    color: #444;
    font-weight: bold;
}

.form-group input,
.form-group select {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}

.create-button {
    grid-column: span 2;
    width: 180px;
    border: none;
    background: #0878bd;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.create-button:hover {
    background: #075f96;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th {
    background: #0878bd;
    color: white;
    padding: 12px;
    text-align: left;
}

td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

tr:hover {
    background: #f5fbff;
}

input[type="text"] {
    padding: 9px;
    border: 1px solid #ccc;
    border-radius: 6px;
    width: 180px;
}

button {
    border: none;
    background: #0878bd;
    color: white;
    padding: 9px 15px;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background: #075f96;
}

.reset-form {
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-active {
    color: #15803d;
    font-weight: bold;
}

.status-inactive {
    color: #dc2626;
    font-weight: bold;
}

.action-btn {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    font-size: 13px;
    margin-right: 5px;
    margin-bottom: 4px;
}

.activate {
    background: #16a34a;
}

.deactivate {
    background: #dc2626;
}

.delete-btn {
    background: #991b1b;
}

.delete-btn:hover {
    background: #7f1d1d;
}

.protected {
    color: #777;
    font-size: 13px;
}

.warning-text {
    color: #64748b;
    font-size: 14px;
    margin-top: 0;
}

@media (max-width: 768px) {

    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .sidebar a {
        display: inline-block;
    }

    .main {
        margin-left: 0;
        padding: 15px;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .create-form {
        grid-template-columns: 1fr;
    }

    .create-button {
        grid-column: span 1;
        width: 100%;
    }

    .reset-form {
        flex-direction: column;
        align-items: flex-start;
    }
}

</style>

</head>

<body>

<div class="sidebar">

    <h2>Admin Panel</h2>

    <a href="admin_dashboard.php">
        Dashboard
    </a>

    <a href="admin_complaints.php">
        All Complaints
    </a>

    <a href="admin_users.php" class="active">
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

    <h1>
        Manage Users
    </h1>

    <div class="admin-name">
        <?php echo htmlspecialchars($_SESSION['name']); ?>
    </div>

</div>

<?php if ($message !== ''): ?>

<div class="message">
    <?php echo htmlspecialchars($message); ?>
</div>

<?php endif; ?>

<?php if ($error !== ''): ?>

<div class="error">
    <?php echo htmlspecialchars($error); ?>
</div>

<?php endif; ?>

<div class="card">

    <h2>
        Create User Account
    </h2>

    <form method="POST" class="create-form">

        <div class="form-group">

            <label>
                Name
            </label>

            <input
                type="text"
                name="name"
                placeholder="Enter name"
                required
            >

        </div>

        <div class="form-group">

            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                placeholder="Enter email"
                required
            >

        </div>

        <div class="form-group">

            <label>
                Password
            </label>

            <input
                type="text"
                name="password"
                placeholder="Enter password"
                required
            >

        </div>

        <div class="form-group">

            <label>
                Role
            </label>

            <select name="role_id" required>

                <option value="">
                    Select Role
                </option>

                <?php if ($roles_result): ?>

                    <?php while ($role = $roles_result->fetch_assoc()): ?>

                        <option value="<?php echo (int)$role['role_id']; ?>">

                            <?php echo htmlspecialchars($role['role_name']); ?>

                        </option>

                    <?php endwhile; ?>

                <?php endif; ?>

            </select>

        </div>

        <button
            type="submit"
            name="create_user"
            class="create-button"
        >
            Create User
        </button>

    </form>

</div>

<div class="card">

    <h2>
        Password Reset Requests
    </h2>

    <p>
        Users who forgot their password can request a password reset.
    </p>

    <div class="table-container">

        <table>

            <thead>

                <tr>
                    <th>Request ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Requested At</th>
                    <th>Reset Password</th>
                </tr>

            </thead>

            <tbody>

                <?php if ($reset_result && $reset_result->num_rows > 0): ?>

                    <?php while ($request = $reset_result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo (int)$request['request_id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($request['name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($request['email']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($request['requested_at']); ?>
                            </td>

                            <td>

                                <form
                                    method="POST"
                                    class="reset-form"
                                    onsubmit="return confirm('Are you sure you want to reset this password?');"
                                >

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?php echo (int)$request['request_id']; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?php echo (int)$request['user_id']; ?>"
                                    >

                                    <input
                                        type="text"
                                        name="new_password"
                                        placeholder="New password"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        name="reset_password"
                                    >
                                        Reset
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="5"
                            style="text-align:center;"
                        >
                            No pending password reset requests.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<div class="card">

    <h2>
        User Management
    </h2>

    <p class="warning-text">
        Deactivated users remain in the system. Permanent deletion removes the account completely.
    </p>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($user = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo (int)$user['user_id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user['role_name']); ?>
                            </td>

                            <td>

                                <?php if ($user['status'] === 'Active'): ?>

                                    <span class="status-active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status-inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php echo htmlspecialchars($user['created_at']); ?>
                            </td>

                            <td>

                                <?php if ((int)$user['user_id'] === (int)$_SESSION['user_id']): ?>

                                    <span class="protected">
                                        Protected
                                    </span>

                                <?php else: ?>

                                    <?php if ($user['status'] === 'Active'): ?>

                                        <a
                                            href="admin_users.php?toggle=<?php echo (int)$user['user_id']; ?>"
                                            class="action-btn deactivate"
                                            onclick="return confirm('Are you sure you want to deactivate this user?');"
                                        >
                                            Deactivate
                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="admin_users.php?toggle=<?php echo (int)$user['user_id']; ?>"
                                            class="action-btn activate"
                                            onclick="return confirm('Are you sure you want to activate this user?');"
                                        >
                                            Activate
                                        </a>

                                    <?php endif; ?>

                                    <a
                                        href="admin_users.php?delete=<?php echo (int)$user['user_id']; ?>"
                                        class="action-btn delete-btn"
                                        onclick="return confirm('WARNING: This will permanently delete this user account. This action cannot be undone. Are you sure?');"
                                    >
                                        Delete Permanently
                                    </a>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            style="text-align:center;"
                        >
                            No users found.
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
