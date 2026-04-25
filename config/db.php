<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "NUTRIPHASE";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database connection failed");
}

?>