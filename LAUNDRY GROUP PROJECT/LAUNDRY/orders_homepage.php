<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['CUSTID'])) {
    header("Location: user_login.php");
    exit;
}

$CUSTID = $_SESSION['CUSTID'];

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

// Fetch all services
$sql = "SELECT * FROM SERVICE ORDER BY SERVICEID";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

$services = [];
while ($row = oci_fetch_assoc($stid)) {
    $services[] = $row;
}

// Handle order form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $PICKUP_DATE = $_POST['pickup_date'] ?? '';
    $DELIVERY_DATE = $_POST['delivery_date'] ?? '';
    $TOTAL_PRICE = 0.00;

    $orderDetails = [];

    if (isset($_POST['service_id'])) {
        foreach ($_POST['service_id'] as $SERVICEID) {
            $weightField = 'weight_' . $SERVICEID;
            $WEIGHT = (float)($_POST[$weightField] ?? 0);

            $SERVICE_PRICE = 0;
            foreach ($services as $srv) {
                if ($srv['SERVICEID'] == $SERVICEID) {
                    $SERVICE_PRICE = (float)$srv['SERVICE_PRICE'];
                    break;
                }
            }

            $SUBTOTAL = $WEIGHT * $SERVICE_PRICE;
            $TOTAL_PRICE += $SUBTOTAL;

            $orderDetails[] = [
                'SERVICEID' => $SERVICEID,
                'WEIGHT' => $WEIGHT,
                'SUBTOTAL' => $SUBTOTAL
            ];
        }
    }

    $ORDERID = 'ORD' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $STATUS = 'PENDING';

    $sqlInsertOrder = "
        INSERT INTO LAUNDRY_ORDER (
            ORDERID,
            CUSTID,
            ORDER_DATE,
            STATUS,
            TOTAL_PRICE,
            PICKUP_DATE,
            DELIVERY_DATE
        ) VALUES (
            :ORDERID,
            :CUSTID,
            SYSDATE,
            :STATUS,
            :TOTAL_PRICE,
            TO_DATE(:PICKUP_DATE, 'YYYY-MM-DD'),
            TO_DATE(:DELIVERY_DATE, 'YYYY-MM-DD')
        )
    ";

    $stmtOrder = oci_parse($conn, $sqlInsertOrder);
    oci_bind_by_name($stmtOrder, ':ORDERID', $ORDERID);
    oci_bind_by_name($stmtOrder, ':CUSTID', $CUSTID);
    oci_bind_by_name($stmtOrder, ':STATUS', $STATUS);
    oci_bind_by_name($stmtOrder, ':TOTAL_PRICE', $TOTAL_PRICE);
    oci_bind_by_name($stmtOrder, ':PICKUP_DATE', $PICKUP_DATE);
    oci_bind_by_name($stmtOrder, ':DELIVERY_DATE', $DELIVERY_DATE);

    $resultOrder = oci_execute($stmtOrder, OCI_NO_AUTO_COMMIT);

    if ($resultOrder) {
        // Insert each service into ITEM_ID table
        $successDetails = true;

        foreach ($orderDetails as $detail) {
            $ITEMID = 'ITM' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

            $sqlInsertItem = "
                INSERT INTO ITEM (
                    ITEMID,
                    ORDERID,
                    SERVICEID,
                    WEIGHT,
                    SUBTOTAL
                ) VALUES (
                    :ITEMID,
                    :ORDERID,
                    :SERVICEID,
                    :WEIGHT,
                    :SUBTOTAL
                )
            ";

            $stmtItem = oci_parse($conn, $sqlInsertItem);
            oci_bind_by_name($stmtItem, ':ITEMID', $ITEMID);
            oci_bind_by_name($stmtItem, ':ORDERID', $ORDERID);
            oci_bind_by_name($stmtItem, ':SERVICEID', $detail['SERVICEID']);
            oci_bind_by_name($stmtItem, ':WEIGHT', $detail['WEIGHT']);
            oci_bind_by_name($stmtItem, ':SUBTOTAL', $detail['SUBTOTAL']);

            $resultItem = oci_execute($stmtItem, OCI_NO_AUTO_COMMIT);
            if (!$resultItem) {
                $successDetails = false;
                $e = oci_error($stmtItem);
                echo "<p style='color:red;'>Insert item failed: {$e['message']}</p>";
                break;
            }

            oci_free_statement($stmtItem);
        }

        if ($successDetails) {
            oci_commit($conn);
            echo "<script>
                alert('✅ Order placed! Total Price: RM " . number_format($TOTAL_PRICE, 2) . "');
                window.location='payment.php?orderid=" . $ORDERID . "';
            </script>";
            exit;
        } else {
            oci_rollback($conn);
            echo "<p style='color:red;'>❌ Transaction rolled back due to error.</p>";
        }
    } else {
        $e = oci_error($stmtOrder);
        echo "<p style='color:red;'>Order insert failed: {$e['message']}</p>";
    }
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Place Laundry Order - Kerei Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #c471ed, #f64f59);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        .container {
            background: #ffffff;
            max-width: 1000px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95);}
            to { opacity: 1; transform: scale(1);}
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #c471ed;
        }
        label {
            display: block;
            margin-top: 20px;
            font-weight: 600;
            color: #555;
        }
        input[type="date"],
        input[type="number"] {
            display: block;
            width: 100%;
            max-width: 300px;
            margin-top: 8px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th {
            background: #c471ed;
            color: #fff;
            text-align: left;
            padding: 12px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .total-box {
            margin-top: 25px;
            background: #f0d9ff;
            color: #6a1b9a;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
        }
        .btn {
            margin-top: 30px;
            background: linear-gradient(to right, #c471ed, #f64f59);
            color: #fff;
            border: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .back-btn {
            background: #6c757d;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2><i class="fas fa-tshirt"></i> Place Laundry Order</h2>
    <form id="orderForm" method="POST">
        <label>Pickup Date:</label>
        <input type="date" name="pickup_date" required />

        <label>Delivery Date:</label>
        <input type="date" name="delivery_date" required />

        <h3 style="margin-top: 40px; color: #c471ed;">Select Services & Weight (kg)</h3>
        <table>
            <thead>
                <tr>
                    <th>Select</th>
                    <th>Service</th>
                    <th>Description</th>
                    <th>Price / kg (RM)</th>
                    <th>Weight (kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $srv): ?>
                    <tr>
                        <td><input type="checkbox" name="service_id[]" value="<?= htmlspecialchars($srv['SERVICEID']) ?>"></td>
                        <td><?= htmlspecialchars($srv['SERVICE_TYPE']) ?></td>
                        <td><?= htmlspecialchars($srv['SERVICE_DESC']) ?></td>
                        <td class="price"><?= number_format($srv['SERVICE_PRICE'], 2) ?></td>
                        <td><input type="number" name="weight_<?= htmlspecialchars($srv['SERVICEID']) ?>" min="0" step="0.1" value="0"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-box">
            Estimated Total: <span id="totalDisplay">RM 0.00</span>
        </div>

        <button type="button" class="btn" onclick="submitForm()">Submit Order</button>
    </form>
    <a href="user_dashboard.php" class="btn back-btn">← Back to Dashboard</a>
</div>

<script>
function calculateTotal() {
    let total = 0;
    document.querySelectorAll("input[name='service_id[]']:checked").forEach(chk => {
        let row = chk.closest("tr");
        let price = parseFloat(row.querySelector(".price").textContent);
        let weightInput = row.querySelector("input[type='number']");
        let weight = parseFloat(weightInput.value) || 0;
        total += price * weight;
    });
    document.getElementById("totalDisplay").textContent = "RM " + total.toFixed(2);
}

document.querySelectorAll("input[type='checkbox'], input[type='number']").forEach(el => {
    el.addEventListener('change', calculateTotal);
});

function submitForm() {
    let checked = document.querySelectorAll("input[name='service_id[]']:checked");
    if (checked.length === 0) {
        alert("Please select at least one service.");
        return;
    }
    let total = document.getElementById("totalDisplay").textContent;
    if (confirm("Confirm order with " + total + "?")) {
        document.getElementById("orderForm").submit();
    }
}
</script>
</body>
</html>
