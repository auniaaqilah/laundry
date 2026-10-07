<?php
// Optional: session_start() if you want to protect this page for logged in users
session_start();

// Example: block access if user not logged in
if (!isset($_SESSION['CUSTID'])) {
    header("Location: login_user.html");
    exit;
}

// Connect to Oracle
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

// Fetch services
$sql = "SELECT * FROM SERVICE ORDER BY SERVICEID";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

$services = [];
while ($row = oci_fetch_assoc($stid)) {
    $services[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Services - Kerei Laundry</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f1f1f1;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    h2 {
      text-align: center;
      color: #007bff;
      margin-bottom: 30px;
    }
    .search-box {
      text-align: center;
      margin-bottom: 30px;
    }
    .search-box input {
      padding: 10px 15px;
      width: 60%;
      max-width: 400px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }
    .card {
      background: #ffffff;
      border-radius: 8px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s;
      opacity: 0;
      animation: fadeIn 0.8s forwards;
    }
    .card:hover {
      transform: scale(1.05);
    }
    @keyframes fadeIn {
      to {
        opacity: 1;
      }
    }
    .card i {
      font-size: 50px;
      color: #007bff;
      margin-bottom: 10px;
    }
    .card h3 {
      color: #007bff;
      margin: 10px 0;
    }
    .card p {
      color: #555;
      font-size: 14px;
      margin: 8px 0;
    }
    .price {
      font-size: 18px;
      color: #28a745;
      font-weight: bold;
      margin: 10px 0;
    }
    .btn-order {
      display: inline-block;
      background: #007bff;
      color: #fff;
      padding: 10px 15px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
    }
    .btn-order:hover {
      background: #0056b3;
    }
    .btn-back {
      display: inline-block;
      margin-top: 30px;
      background: #6c757d;
      color: #fff;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
    }
    .btn-back:hover {
      background: #5a6268;
    }
    .no-services {
      text-align: center;
      color: #dc3545;
      font-weight: bold;
      font-size: 18px;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Available Laundry Services</h2>

    <div class="search-box">
      <input type="text" id="serviceSearch" placeholder="Search services...">
    </div>

    <?php if (count($services) > 0): ?>
      <div class="cards" id="serviceCards">
        <?php foreach ($services as $service): ?>
          <div class="card">
            <i class="fas fa-tshirt"></i>
            <h3><?= htmlspecialchars($service['SERVICE_TYPE']) ?></h3>
            <p><?= htmlspecialchars($service['SERVICE_DESC']) ?></p>
            <p class="price">RM <?= number_format($service['SERVICE_PRICE'], 2) ?></p>
            <a href="orders_homepage.php?serviceid=<?= urlencode($service['SERVICEID']) ?>" class="btn-order">Order Now</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="no-services">No services available at the moment.</p>
    <?php endif; ?>

    <a href="user_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  </div>

  <script>
    document.getElementById('serviceSearch').addEventListener('input', function () {
      let search = this.value.toLowerCase();
      document.querySelectorAll('#serviceCards .card').forEach(card => {
        let text = card.textContent.toLowerCase();
        card.style.display = text.includes(search) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
