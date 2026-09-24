<?php

session_start();

include("db.php");
require_once("vendor/autoload.php");

$client = new Google_Client([
    "client_id" => "174434152570-o4hv0rio594k4jh5fab9i3nd61oabn7v.apps.googleusercontent.com"
]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request");
}

$credential = $_POST["credential"] ?? "";

if (empty($credential)) {
    die("Google login failed");
}

$payload = $client->verifyIdToken($credential);

if (!$payload) {
    die("Invalid Google token");
}

$email = $payload["email"] ?? "";
$name = $payload["name"] ?? "Google User";

if (empty($email)) {
    die("Google account email not found");
}

$sql = "SELECT users.*, roles.role_name
        FROM users
        JOIN roles ON users.role_id = roles.role_id
        WHERE users.email = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 1) {

    $user = $result->fetch_assoc();

    if ($user["status"] !== "Active") {
        die("Your account is inactive.");
    }

    $_SESSION["user_id"] = $user["user_id"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["role"] = $user["role_name"];

} else {

    $role_id = 1;
    $password = bin2hex(random_bytes(16));

    $insert = "INSERT INTO users (name, email, password, role_id, status)
               VALUES (?, ?, ?, ?, 'Active')";

    $stmt2 = $conn->prepare($insert);
    $stmt2->bind_param("sssi", $name, $email, $password, $role_id);

    if (!$stmt2->execute()) {
        die("Could not create Google account.");
    }

    $user_id = $stmt2->insert_id;

    $_SESSION["user_id"] = $user_id;
    $_SESSION["name"] = $name;
    $_SESSION["role"] = "Student";
}

header("Location: dashboard.php");
exit();

?>