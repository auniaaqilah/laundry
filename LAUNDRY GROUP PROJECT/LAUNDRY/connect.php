<?php
$host = "localhost"; 
$user = "root";      
$pass = "";          
$dbname = "LAUNDRY";


$conn = new mysqli($host, $user, $pass, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✅ Connected to MySQL database successfully!";
?>

