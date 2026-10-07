<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['CUSTID'])) {
    header("Location: user_login.php");
    exit;
}

$CUSTID = $_SESSION['CUSTID'];
$ORDERID = $_GET['orderid'] ?? '';

if (!$ORDERID) {
    die("❌ Order ID missing.");
}

// DB connection
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

// Fetch order info
$sql = "SELECT STATUS, TOTAL_PRICE FROM LAUNDRY_ORDER WHERE ORDERID = :ORDERID";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':ORDERID', $ORDERID);
oci_execute($stid);
$order = oci_fetch_assoc($stid);

if (!$order) {
    die("❌ Order not found.");
}

if ($order['STATUS'] === 'COMPLETED') {
    die("✅ This order has already been paid.");
}

$TOTAL_PRICE = $order['TOTAL_PRICE'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $PAYMENT_METHOD = $_POST['payment_method'] ?? '';
    $PAYMENT_DATE = date('Y-m-d');
    $PAYMENT_STATUS = 'COMPLETED';
    $PAYMENTID = 'PAY' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

    $sqlInsert = "
        INSERT INTO PAYMENTS (
            PAYMENTID,
            ORDERID,
            PAYMENT_DATE,
            PAYMENT_METHOD,
            AMOUNT_PAID,
            PAYMENT_STATUS
        ) VALUES (
            :PAYMENTID,
            :ORDERID,
            TO_DATE(:PAYMENT_DATE, 'YYYY-MM-DD'),
            :PAYMENT_METHOD,
            :AMOUNT_PAID,
            :PAYMENT_STATUS
        )
    ";

    $stmt = oci_parse($conn, $sqlInsert);
    oci_bind_by_name($stmt, ':PAYMENTID', $PAYMENTID);
    oci_bind_by_name($stmt, ':ORDERID', $ORDERID);
    oci_bind_by_name($stmt, ':PAYMENT_DATE', $PAYMENT_DATE);
    oci_bind_by_name($stmt, ':PAYMENT_METHOD', $PAYMENT_METHOD);
    oci_bind_by_name($stmt, ':AMOUNT_PAID', $TOTAL_PRICE);
    oci_bind_by_name($stmt, ':PAYMENT_STATUS', $PAYMENT_STATUS);

    $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

    if ($result) {
        // Handle specific payment methods
        if ($PAYMENT_METHOD === 'CARD') {
            $CARD_HOLDER_NAME = $_POST['card_holder_name'] ?? '';
            $CARD_NUMBER = $_POST['card_number'] ?? '';

            $sqlCard = "
                INSERT INTO CARD (
                    PAYMENTID,
                    CARD_HOLDER_NAME,
                    CARD_NUMBER
                ) VALUES (
                    :PAYMENTID,
                    :CARD_HOLDER_NAME,
                    :CARD_NUMBER
                )
            ";
            $stmtCard = oci_parse($conn, $sqlCard);
            oci_bind_by_name($stmtCard, ':PAYMENTID', $PAYMENTID);
            oci_bind_by_name($stmtCard, ':CARD_HOLDER_NAME', $CARD_HOLDER_NAME);
            oci_bind_by_name($stmtCard, ':CARD_NUMBER', $CARD_NUMBER);
            oci_execute($stmtCard, OCI_COMMIT_ON_SUCCESS);

        } elseif ($PAYMENT_METHOD === 'CASH') {
            $CASH_RECEIVED = (float)($_POST['cash_received'] ?? 0);
            if ($CASH_RECEIVED < $TOTAL_PRICE) {
                echo "<p style='color:red;'>❌ Cash received is less than total. Please re-enter.</p>";
                exit;
            }

            $CHANGE_RETURNED = $CASH_RECEIVED - $TOTAL_PRICE;

            $sqlCash = "
                INSERT INTO CASH (
                    PAYMENTID,
                    CASH_RECEIVED,
                    CHANGE_RETURNED
                ) VALUES (
                    :PAYMENTID,
                    :CASH_RECEIVED,
                    :CHANGE_RETURNED
                )
            ";
            $stmtCash = oci_parse($conn, $sqlCash);
            oci_bind_by_name($stmtCash, ':PAYMENTID', $PAYMENTID);
            oci_bind_by_name($stmtCash, ':CASH_RECEIVED', $CASH_RECEIVED);
            oci_bind_by_name($stmtCash, ':CHANGE_RETURNED', $CHANGE_RETURNED);
            oci_execute($stmtCash, OCI_COMMIT_ON_SUCCESS);

        } elseif ($PAYMENT_METHOD === 'EWALLET') {
            $EWALLET_NAME = $_POST['ewallet_name'] ?? '';
            $TRANSACTION_REF = $_POST['transaction_ref'] ?? '';

            $sqlEwallet = "
                INSERT INTO EWALLET (
                    PAYMENTID,
                    EWALLET_NAME,
                    TRANSACTION_REF
                ) VALUES (
                    :PAYMENTID,
                    :EWALLET_NAME,
                    :TRANSACTION_REF
                )
            ";
            $stmtEwallet = oci_parse($conn, $sqlEwallet);
            oci_bind_by_name($stmtEwallet, ':PAYMENTID', $PAYMENTID);
            oci_bind_by_name($stmtEwallet, ':EWALLET_NAME', $EWALLET_NAME);
            oci_bind_by_name($stmtEwallet, ':TRANSACTION_REF', $TRANSACTION_REF);
            oci_execute($stmtEwallet, OCI_COMMIT_ON_SUCCESS);
        }

        // Update order status
        $sqlUpdateOrder = "
            UPDATE LAUNDRY_ORDER
            SET STATUS = 'COMPLETED'
            WHERE ORDERID = :ORDERID
        ";
        $stmtUpdate = oci_parse($conn, $sqlUpdateOrder);
        oci_bind_by_name($stmtUpdate, ':ORDERID', $ORDERID);
        oci_execute($stmtUpdate, OCI_COMMIT_ON_SUCCESS);

        echo "<script>alert('✅ Payment successful!'); window.location='payment_receipt.php?paymentid={$PAYMENTID}';</script>";
        exit;
    } else {
        $e = oci_error($stmt);
        echo "<p style='color:red;'>❌ Payment failed: {$e['message']}</p>";
    }
}

oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Payment - Kerei Laundry</title>
    <style>
        body {
            margin: 0;
            background: linear-gradient(135deg, #fbc2eb, #a6c1ee);
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: start;
            min-height: 100vh;
            padding: 50px;
        }
        .container {
            background: #fff;
            max-width: 500px;
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
            color: #7b4397;
            margin-bottom: 30px;
        }
        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: 600;
            color: #555;
        }
        input, select {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: border 0.3s;
        }
        input:focus, select:focus {
            border-color: #7b4397;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 30px;
            background: linear-gradient(to right, #a18cd1, #fbc2eb);
            color: #fff;
            border: none;
            font-size: 16px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
        }
        button:hover {
            opacity: 0.9;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #7b4397;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>💳 Payment for Order <?= htmlspecialchars($ORDERID) ?></h2>
    <p style="text-align:center; font-weight:bold;">Total Amount: RM <?= number_format($TOTAL_PRICE, 2) ?></p>

    <form method="POST">
        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" id="payment_method" required>
            <option value="">-- Select Method --</option>
            <option value="CASH">Cash</option>
            <option value="CARD">Card</option>
            <option value="EWALLET">E-Wallet</option>
        </select>

        <div id="card_fields" class="hidden">
            <label>Card Holder Name:</label>
            <input type="text" name="card_holder_name">
            <label>Card Number:</label>
            <input type="text" name="card_number">
        </div>

        <div id="cash_fields" class="hidden">
            <label>Cash Received (RM):</label>
            <input type="number" name="cash_received" step="0.01" min="<?= $TOTAL_PRICE ?>">
        </div>

        <div id="ewallet_fields" class="hidden">
            <label>E-Wallet Name:</label>
            <input type="text" name="ewallet_name">
            <label>Transaction Ref:</label>
            <input type="text" name="transaction_ref">
        </div>

        <button type="submit"><i class="fas fa-check-circle"></i> Confirm Payment</button>
    </form>

    <a href="orders_homepage.php" class="back-link">← Back to Orders</a>
</div>

<script>
document.getElementById('payment_method').addEventListener('change', function() {
    document.getElementById('card_fields').classList.add('hidden');
    document.getElementById('cash_fields').classList.add('hidden');
    document.getElementById('ewallet_fields').classList.add('hidden');

    if (this.value === 'CARD') {
        document.getElementById('card_fields').classList.remove('hidden');
    } else if (this.value === 'CASH') {
        document.getElementById('cash_fields').classList.remove('hidden');
    } else if (this.value === 'EWALLET') {
        document.getElementById('ewallet_fields').classList.remove('hidden');
    }
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</body>
</html>
