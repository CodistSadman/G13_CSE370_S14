<?php include("../config/db.php"); ?>

<h2>Login Page</h2>

<form method="POST">
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>

    <button type="submit" name="login">Login</button>
</form>

<?php

session_start();

if (isset($_POST['login'])) {

    include("../config/db.php");

    $email = $_POST['email'];
    $password = $_POST['password'];

    // database থেকে user খুঁজবে
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    $user = mysqli_fetch_assoc($result);

    if ($user) {

        // password match check
        if (password_verify($password, $user['password'])) {

            // login success → session create
            $_SESSION['user'] = $user['ssn'];

            echo "✅ Login Successful!";

            // dashboard এ পাঠাবে
            header("Location: ../dashboard/home.php");
            exit();

        } else {
            echo "❌ Wrong Password";
        }

    } else {
        echo "❌ User not found";
    }
}
?>