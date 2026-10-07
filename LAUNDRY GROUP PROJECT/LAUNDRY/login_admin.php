<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $login_error = "❌ Email and password are required.";
    } else {
        // DB connection details
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

        $sql = "
            SELECT EMPLOYEEID, EMPLOYEE_NAME, PASSWORD, ROLE
            FROM EMPLOYEES
            WHERE EMPLOYEE_CONTACT = :email
        ";

        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':email', $email);
        oci_execute($stid);

        $row = oci_fetch_assoc($stid);

        if ($row) {
            $db_hash = $row['PASSWORD'];
            $role = strtoupper(trim($row['ROLE']));

            if (password_verify($password, $db_hash)) {
                if ($role === 'ADMIN') {
                    $_SESSION['admin_id']   = $row['EMPLOYEEID'];
                    $_SESSION['admin_name'] = $row['EMPLOYEE_NAME'];
                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    $login_error = "❌ Access denied. You are not an admin.";
                }
            } else {
                $login_error = "❌ Incorrect password.";
            }
        } else {
            $login_error = "❌ User not found.";
        }

        oci_free_statement($stid);
        oci_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Login - Kerei Laundry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #007acc, #4caf50);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
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
            color: #007acc;
            margin-bottom: 20px;
        }
        .form-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
            width: 100%;
            max-width: 300px;
        }
        .input-group i {
            position: absolute;
            top: 12px;
            left: 10px;
            color: #007acc;
        }
        .input-group input {
            width: 100%;
            max-width: 300px;
            box-sizing: border-box;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: linear-gradient(to right, #007acc, #4caf50);
            border: none;
            color: #fff;
            font-weight: bold;
            padding: 12px;
            width: 100%;
            max-width: 300px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 16px;
        }
        button:hover {
            background: linear-gradient(to right, #005fa3, #3e8e41);
        }
        .error {
            background: #ffdddd;
            color: #a94442;
            border: 1px solid #ebccd1;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        .forgot {
            margin-top: 15px;
        }
        .forgot a {
            color: #007acc;
            text-decoration: none;
        }
        .forgot a:hover {
            text-decoration: underline;
        }
        .link {
            margin-top: 20px;
        }
        .btn-back {
            display: inline-block;
            background: linear-gradient(to right, #007acc, #4caf50);
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            border: none;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: background 0.3s, transform 0.2s;
        }
        .btn-back:hover {
            background: linear-gradient(to right, #005fa3, #3e8e41);
            transform: scale(1.05);
        }
        @media (max-width: 500px) {
            .container {
                width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <img src="logo.png" alt="Kerei Laundry Logo" class="logo" />
    <h2>🔐 Admin Login</h2>

    <?php if (!empty($login_error)): ?>
        <div class="error"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-content">
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="text" name="email" placeholder="Email / Contact" required>
        </div>
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit">Login</button>
    </form>

    <div class="link">
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>
</body>
</html>
