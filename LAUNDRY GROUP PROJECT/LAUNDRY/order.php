

<!DOCTYPE html>
<html>
<?php include 'connect.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Laundry</title>
</head>
<body>
    <h2>Place an Order</h2>
    <form method="POST">
        Name: <input type="text" name="customer" required><br><br>
        Service:
        <select name="service">
            <option value="Wash">Wash</option>
            <option value="Dry">Dry</option>
            <option value="Iron">Iron</option>
        </select><br><br>
        Date: <input type="date" name="date" required><br><br>
        <input type="submit" name="submit" value="Submit Order">
    </form>

<?php
if (isset($_POST['submit'])) {
    $name = $_POST['customer'];
    $service = $_POST['service'];
    $date = $_POST['date'];

    $sql = "INSERT INTO orders (customer, service, date) VALUES ('$name', '$service', '$date')";

    if (mysqli_query($conn, $sql)) {
        echo "<p style='color: green;'>Order placed successfully!</p>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
</body>
</html>
