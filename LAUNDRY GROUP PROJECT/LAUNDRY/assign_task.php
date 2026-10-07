<?php
session_start();

// Only admins can access
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbuser  = 'LAUNDRY';
$dbpass  = 'GROUP5';
$host    = 'localhost';
$port    = '1521';
$service = 'FREEPDB1';
$dsn     = "//{$host}:{$port}/{$service}";

$conn = oci_connect($dbuser, $dbpass, $dsn);
if (!$conn) {
    $e = oci_error();
    die("❌ Connection failed: " . htmlentities($e['message']));
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderid = $_POST['orderid'] ?? '';
    $employeeid = $_POST['employeeid'] ?? '';
    $task = $_POST['task'] ?? '';

    if (empty($orderid) || empty($employeeid) || empty($task)) {
        $error = "❌ All fields are required.";
    } else {
        $assignmentid = uniqid();
        $date = date('Y-m-d');

        $sql = "
            INSERT INTO ORDER_ASSIGNMENT (
                ASSIGNMENTID,
                ORDERID,
                EMPLOYEEID,
                TASK,
                ASSIGNED_DATE
            ) VALUES (
                :aid,
                :oid,
                :eid,
                :task,
                TO_DATE(:adate, 'YYYY-MM-DD')
            )
        ";

        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':aid', $assignmentid);
        oci_bind_by_name($stid, ':oid', $orderid);
        oci_bind_by_name($stid, ':eid', $employeeid);
        oci_bind_by_name($stid, ':task', $task);
        oci_bind_by_name($stid, ':adate', $date);

        $result = oci_execute($stid, OCI_COMMIT_ON_SUCCESS);
        if ($result) {
            $success = "✅ Task assigned successfully.";
        } else {
            $e = oci_error($stid);
            $error = "❌ Failed to assign task: " . htmlentities($e['message']);
        }

        oci_free_statement($stid);
    }
}

// Fetch orders only if status is COMPLETE
$sqlOrders = "
    SELECT ORDERID
    FROM LAUNDRY_ORDER
    WHERE STATUS = 'COMPLETED'
    ORDER BY ORDERID
";
$stid = oci_parse($conn, $sqlOrders);
oci_execute($stid);

$orders = [];
while ($row = oci_fetch_assoc($stid)) {
    $orders[] = $row['ORDERID'];
}
oci_free_statement($stid);

// Fetch all employees
$sqlEmps = "SELECT EMPLOYEEID, EMPLOYEE_NAME FROM EMPLOYEES ORDER BY EMPLOYEEID";
$stid = oci_parse($conn, $sqlEmps);
oci_execute($stid);

$employees = [];
while ($row = oci_fetch_assoc($stid)) {
    $employees[] = $row;
}
oci_free_statement($stid);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Assign Task - Kerei Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fff0f5;
            margin: 0;
            color: #333;
        }
        .container {
            width: 600px;
            max-width: 90%;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(90deg, #f8c7d8, #f9a3b4);
            padding: 20px;
            color: #fff;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .breadcrumbs {
            margin-bottom: 20px;
        }
        .breadcrumbs a {
            color: #e75480;
            text-decoration: none;
            font-weight: bold;
        }
        .breadcrumbs a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        form .input-group {
            position: relative;
            margin-bottom: 20px;
            width: 100%;
        }
        form .input-group i {
            position: absolute;
            top: 12px;
            left: 12px;
            color: #e75480;
        }
        form .input-group select,
        form .input-group textarea,
        form .input-group input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }
        textarea {
            resize: vertical;
        }
        button {
            width: 100%;
            background: linear-gradient(to right, #f8c7d8, #f9a3b4);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: linear-gradient(to right, #f797b7, #f47399);
        }
        a.back {
            display: inline-block;
            margin-top: 20px;
            background: linear-gradient(to right, #f8c7d8, #f9a3b4);
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        a.back:hover {
            background: linear-gradient(to right, #f797b7, #f47399);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>📝 Assign Task to Employee</h2>
    </div>
    <div class="content">
        <div class="breadcrumbs">
            <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a> &raquo; Assign Task
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-receipt"></i>
                <select name="orderid" required>
                    <option value="">-- Select Order (Completed) --</option>
                    <?php foreach ($orders as $oid): ?>
                        <option value="<?= htmlspecialchars($oid) ?>"><?= htmlspecialchars($oid) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <i class="fas fa-user"></i>
                <select name="employeeid" required>
                    <option value="">-- Select Employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['EMPLOYEEID']) ?>">
                            <?= htmlspecialchars($emp['EMPLOYEEID']) ?> - <?= htmlspecialchars($emp['EMPLOYEE_NAME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <i class="fas fa-tasks"></i>
                <textarea name="task" rows="4" required placeholder="Describe the task..."></textarea>
            </div>

            <button type="submit"><i class="fas fa-paper-plane"></i> Assign Task</button>
        </form>

        <a class="back" href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>
</body>
</html>
