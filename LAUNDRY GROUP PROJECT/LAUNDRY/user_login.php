<?php
// signup_user.php

// Database credentials
$host = "localhost";
$user = "root";
$password = "";
$dbname = "laundry_system"; // change to your DB name

// Create DB connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form data is sent
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data safely
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<p style='color:red; text-align:center;'>Email already registered! <a href='signup_user.html'>Try again</a></p>";
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            echo "<p style='color:green; text-align:center;'>Sign up successful! <a href='login_user.php'>Login here</a></p>";
        } else {
            echo "<p style='color:red; text-align:center;'>Error: " . $stmt->error . "</p>";
        }
    }

    $stmt->close();
}

$conn->close();
?>
