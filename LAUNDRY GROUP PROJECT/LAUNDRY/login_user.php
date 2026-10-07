<?php
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $dbuser  = 'LAUNDRY';
    $dbpass  = 'GROUP5';
    $host    = 'localhost';
    $port    = '1521';
    $service = 'FREEPDB1';
    $dsn     = "//{$host}:{$port}/{$service}";

    $conn = oci_connect($dbuser, $dbpass, $dsn);
    if (!$conn) {
        $e = oci_error();
        die("❌ Connection failed: " . $e['message']);
    }

    $sql = "SELECT * FROM CUSTOMER WHERE UPPER(CUST_EMAIL) = UPPER(:email)";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':email', $email);
    oci_execute($stid);

    $row = oci_fetch_assoc($stid);

    if ($row) {
        $db_password = trim($row['PASSWORD'] ?? '');

        if ($password === $db_password) {
            $_SESSION['CUSTID']      = $row['CUSTID'];
            $_SESSION['CUST_NAME']   = $row['CUST_NAME'];
            $_SESSION['CUST_EMAIL']  = $row['CUST_EMAIL'];

            header("Location: user_dashboard.php");
            exit;
        } else {
            $message = "❌ Invalid password.";
        }
    } else {
        $message = "❌ Email not found. Try again.";
    }

    oci_free_statement($stid);
    oci_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Login - Kerei Laundry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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
            opacity: 0;
            animation: fadeIn 0.8s forwards;
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        .logo {
            width: 80px;
            margin-bottom: 20px;
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
            position: relative;
            margin-bottom: 20px;
        }
        .input-group i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
        }
        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
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
            color:rgb(224, 228, 231);
            text-decoration: none;
            font-weight: bold;
        }
        .link a:hover {
            text-decoration: underline;
        }
        .btn-back {
            display: inline-block;
            background: linear-gradient(to right, #007bff, #4caf50);
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-back:hover {
            background: linear-gradient(to right, #0056b3, #3e8e41);
        }
        @media (max-width: 500px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="Kerei Laundry Logo" class="logo" />
        <h2><i class="fas fa-sign-in-alt"></i> User Login</h2>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="text" name="email" placeholder="Email" required />
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <button type="submit">Login</button>
        </form>

        <div class="link">
            Don’t have an account? <a href="signup_user.php">Sign Up</a>
        </div>

        <div class="link">
            <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
