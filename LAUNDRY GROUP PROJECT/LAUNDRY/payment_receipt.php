<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['CUSTID'])) {
    header("Location: user_login.php");
    exit;
}

$PAYMENTID = $_GET['paymentid'] ?? '';

if (!$PAYMENTID) {
    die("❌ Payment ID missing.");
}

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

// Fetch payment record
$sql = "
    SELECT 
        P.PAYMENTID,
        P.ORDERID,
        TO_CHAR(P.PAYMENT_DATE, 'YYYY-MM-DD') AS PAYMENT_DATE,
        P.PAYMENT_METHOD,
        P.AMOUNT_PAID,
        P.PAYMENT_STATUS
    FROM PAYMENTS P
    WHERE P.PAYMENTID = :PAYMENTID
";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':PAYMENTID', $PAYMENTID);
oci_execute($stid);

$payment = oci_fetch_assoc($stid);

if (!$payment) {
    die("❌ Payment not found.");
}

// Fetch the total price of the order (needed for change calculation)
$totalPrice = 0;
$sqlOrder = "SELECT TOTAL_PRICE FROM LAUNDRY_ORDER WHERE ORDERID = :ORDERID";
$stidOrder = oci_parse($conn, $sqlOrder);
oci_bind_by_name($stidOrder, ':ORDERID', $payment['ORDERID']);
oci_execute($stidOrder);
$order = oci_fetch_assoc($stidOrder);
if ($order) {
    $totalPrice = floatval($order['TOTAL_PRICE']);
}

// Fetch method-specific details
$details = [];

switch ($payment['PAYMENT_METHOD']) {
    case 'CARD':
        $sqlDetail = "SELECT CARD_HOLDER_NAME, CARD_NUMBER FROM CARD WHERE PAYMENTID = :PAYMENTID";
        break;
    case 'CASH':
        $sqlDetail = "SELECT CASH_RECEIVED FROM CASH WHERE PAYMENTID = :PAYMENTID";
        break;
    case 'EWALLET':
        $sqlDetail = "SELECT EWALLET_NAME, TRANSACTION_REF FROM EWALLET WHERE PAYMENTID = :PAYMENTID";
        break;
    default:
        $sqlDetail = null;
}

if ($sqlDetail) {
    $stmtDetail = oci_parse($conn, $sqlDetail);
    oci_bind_by_name($stmtDetail, ':PAYMENTID', $PAYMENTID);
    oci_execute($stmtDetail);
    $details = oci_fetch_assoc($stmtDetail) ?: [];
}

oci_free_statement($stid);
oci_free_statement($stidOrder);
oci_close($conn);

// Compute CHANGE_RETURNED for CASH
$changeReturned = null;
if ($payment['PAYMENT_METHOD'] === 'CASH') {
    $cashReceived = floatval($details['CASH_RECEIVED'] ?? 0);
    $changeReturned = $cashReceived - $totalPrice;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Payment Receipt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            margin: 0;
            background: linear-gradient(135deg, #c471ed, #f64f59);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: #fff;
            max-width: 700px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95);}
            to { opacity: 1; transform: scale(1);}
        }
        h2 {
            text-align: center;
            color: #c471ed;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 16px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
        }
        th {
            background: linear-gradient(to right, #c471ed, #f64f59);
            color: #fff;
            text-align: left;
            font-weight: 600;
        }
        td {
            background: #f9f9f9;
        }
        a.btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 20px;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(to right, #00c6ff, #0072ff);
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        a.btn:hover {
            opacity: 0.9;
        }
        .amount {
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🧾 Payment Receipt</h2>
    <table>
        <tr>
            <th>Payment ID</th>
            <td><?= htmlspecialchars($payment['PAYMENTID']) ?></td>
        </tr>
        <tr>
            <th>Order ID</th>
            <td><?= htmlspecialchars($payment['ORDERID']) ?></td>
        </tr>
        <tr>
            <th>Payment Date</th>
            <td><?= htmlspecialchars($payment['PAYMENT_DATE']) ?></td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td><?= htmlspecialchars($payment['PAYMENT_METHOD']) ?></td>
        </tr>
        <tr>
            <th>Amount Paid</th>
            <td class="amount">RM <?= number_format($payment['AMOUNT_PAID'], 2) ?></td>
        </tr>
        <tr>
            <th>Payment Status</th>
            <td><?= htmlspecialchars($payment['PAYMENT_STATUS']) ?></td>
        </tr>

        <?php if ($payment['PAYMENT_METHOD'] === 'CARD'): ?>
            <tr>
                <th>Card Holder Name</th>
                <td><?= htmlspecialchars($details['CARD_HOLDER_NAME'] ?? 'Not Found') ?></td>
            </tr>
            <tr>
                <th>Card Number</th>
                <td><?= htmlspecialchars($details['CARD_NUMBER'] ?? 'Not Found') ?></td>
            </tr>
        <?php elseif ($payment['PAYMENT_METHOD'] === 'CASH'): ?>
            <tr>
                <th>Cash Received</th>
                <td>RM <?= number_format($details['CASH_RECEIVED'] ?? 0, 2) ?></td>
            </tr>
            <tr>
                <th>Change Returned</th>
                <td>RM <?= number_format($changeReturned ?? 0, 2) ?></td>
            </tr>
        <?php elseif ($payment['PAYMENT_METHOD'] === 'EWALLET'): ?>
            <tr>
                <th>E-Wallet Name</th>
                <td><?= htmlspecialchars($details['EWALLET_NAME'] ?? 'Not Found') ?></td>
            </tr>
            <tr>
                <th>Transaction Ref</th>
                <td><?= htmlspecialchars($details['TRANSACTION_REF'] ?? 'Not Found') ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <a href="user_dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
