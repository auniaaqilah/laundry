<?php
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $password     = trim($_POST['password'] ?? '');

    if (!$name || !$email || !$phone_number || !$address || !$password) {
        $message = "❌ All fields are required.";
    } else {
        $custid = "CUST" . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $dbuser  = 'LAUNDRY';
        $dbpass  = 'GROUP5';
        $host    = 'localhost';
        $port    = '1521';
        $service = 'FREEPDB1';
        $dsn     = "//{$host}:{$port}/{$service}";

        $conn = oci_connect($dbuser, $dbpass, $dsn);
        if (!$conn) {
            $e = oci_error();
            die("Connection failed: " . $e['message']);
        }

        // Check if email already exists
        $sqlCheck = "SELECT CUSTID FROM CUSTOMER WHERE UPPER(CUST_EMAIL) = UPPER(:email)";
        $stidCheck = oci_parse($conn, $sqlCheck);
        oci_bind_by_name($stidCheck, ':email', $email);
        oci_execute($stidCheck);

        if (oci_fetch_assoc($stidCheck)) {
            $message = "❌ Email already registered.";
        } else {
            $sql = "INSERT INTO CUSTOMER 
                    (CUSTID, CUST_NAME, CUST_EMAIL, CUST_PHONE, CUST_ADDRESS, PASSWORD)
                    VALUES (:id, :name, :email, :phone, :address, :password)";

            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':id', $custid);
            oci_bind_by_name($stid, ':name', $name);
            oci_bind_by_name($stid, ':email', $email);
            oci_bind_by_name($stid, ':phone', $phone_number);
            oci_bind_by_name($stid, ':address', $address);
            oci_bind_by_name($stid, ':password', $password);

            if (oci_execute($stid, OCI_COMMIT_ON_SUCCESS)) {
                $message = "✅ Sign up successful! <a href='login_user.php'>Login here</a>";
            } else {
                $e = oci_error($stid);
                $message = "❌ Error inserting data: " . $e['message'];
            }

            oci_free_statement($stid);
        }

        oci_free_statement($stidCheck);
        oci_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Sign Up - Kerei Laundry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            background: linear-gradient(to right, #007bff, #4caf50);
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            text-align: center;
        }
        h2 {
            color: #007bff;
            margin-bottom: 20px;
        }
        .message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        form {
            margin-top: 20px;
            text-align: left;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background: linear-gradient(to right, #007bff, #4caf50);
            border: none;
            color: #fff;
            padding: 12px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: linear-gradient(to right, #0056b3, #3e8e41);
        }
        .link {
            margin-top: 20px;
            font-size: 14px;
        }
        .link a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📝 Sign Up</h2>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="input-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" required>
            </div>
            <div class="input-group">
                <label>Address</label>
                <textarea name="address" rows="3" required></textarea>
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Sign Up</button>
        </form>

        <div class="link">
            <a href="login_user.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
