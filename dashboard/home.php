<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<h1>🎉 Welcome to Dashboard</h1>

<a href="../auth/logout.php">Logout</a>