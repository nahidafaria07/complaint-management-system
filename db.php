<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "complaint_management";

$conn = @new mysqli($host, $username, $password, $database);

if ($conn->connect_errno) {
    die("Database connection failed. Error: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>

