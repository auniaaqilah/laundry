<?php
// Oracle connection
$dbuser  = "LAUNDRY";
$dbpass  = "GROUP5";
$host    = "localhost";
$port    = "1521";
$service = "FREEPDB1";
$dsn     = "//{$host}:{$port}/{$service}";

$conn = oci_connect($dbuser, $dbpass, $dsn);
if (!$conn) {
    die("❌ DB Connection failed: " . oci_error()['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['employeeid'];
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $role = 'ADMIN';
    $schedule = $_POST['schedule'];
    $plain_password = $_POST['password'];
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT); // Hash here

    $sql = "INSERT INTO EMPLOYEES (EMPLOYEEID, EMPLOYEE_NAME, EMPLOYEE_CONTACT, ROLE, WORK_SCHEDULE, PASSWORD)
            VALUES (:eid, :name, :contact, :role, :schedule, :pass)";

    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':eid', $emp_id);
    oci_bind_by_name($stid, ':name', $name);
    oci_bind_by_name($stid, ':contact', $contact);
    oci_bind_by_name($stid, ':role', $role);
    oci_bind_by_name($stid, ':schedule', $schedule);
    oci_bind_by_name($stid, ':pass', $hashed_password);

    if (oci_execute($stid)) {
        echo "✅ Admin registered with hashed password!";
    } else {
        echo "❌ Failed: " . oci_error($stid)['message'];
    }

    oci_free_statement($stid);
    oci_close($conn);
}
?>

<!-- HTML Form -->
<form method="POST">
    <h2>Register Admin</h2>
    <input type="text" name="employeeid" placeholder="Employee ID" required><br>
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="text" name="contact" placeholder="Contact / Email" required><br>
    <input type="text" name="schedule" placeholder="Schedule" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>
