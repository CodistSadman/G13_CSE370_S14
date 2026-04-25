<?php include("../config/db.php"); ?>

<h2>User Registration</h2>

<form method="POST">
    SSN: <input type="text" name="ssn" required><br><br>
    Name: <input type="text" name="name" required><br><br>
    Email: <input type="email" name="email" required><br><br>
    Age: <input type="number" name="age"><br><br>
    Gender: <input type="text" name="gender"><br><br>
    Height: <input type="text" name="height"><br><br>
    Weight: <input type="text" name="weight"><br><br>
    Password: <input type="password" name="password" required><br><br>

    <button type="submit" name="register">Register</button>
</form>

<?php

if (isset($_POST['register'])) {

    include("../config/db.php");

    $ssn = $_POST['ssn'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $query = "INSERT INTO users 
    (ssn, name, email, password, age, gender, height, weight)
    VALUES 
    ('$ssn', '$name', '$email', '$password', '$age', '$gender', '$height', '$weight')";

    $result = mysqli_query($conn, $query);

    if ($result) {
        echo "✅ Registration Successful!";
    } else {
        echo "❌ Error: " . mysqli_error($conn);
    }
}
?>