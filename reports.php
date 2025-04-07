<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize filters
$owner_id = $_GET['owner_id'] ?? '';
$car_reg = $_GET['car_reg'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query based on filters
$query = "SELECT l.lease_id, l.start_date, l.end_date, l.total_amount, l.commission_amount, l.payment_method, 
          c.registration_number, c.make, c.model, 
          o.full_name as owner_name, r.full_name as renter_name
          FROM leases l
          JOIN cars c ON l.car_id = c.car_id
          JOIN users o ON c.owner_id = o.user_id
          JOIN users r ON l.renter_id = r.user_id
          WHERE 1=1";
          
$params = [];

if (!empty($owner_id)) {
    $query .= " AND c.owner_id = ?";
    $params[] = $owner_id;
}

if (!empty($car_reg)) {
    $query .= " AND c.registration_number LIKE ?";
    $params[] = "%$car_reg%";
}

if (!empty($start_date)) {
    $query .= " AND l.start_date >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $query .= " AND l.end_date <= ?";
    $params[] = $end_date;
}

$query .= " ORDER BY l.start_date DESC";

// Get leases
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get car owners for filter dropdown
$owners = $pdo->query("SELECT user_id, full_name FROM users WHERE user_type = 'owner'")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
$total_commission = 0;
foreach ($leases as $lease) {
    $total_amount += $lease['total_amount'];
    $total_commission += $lease['commission_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
        }
        .summary-card {
            background-color: #f0f8ff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h2 class="mb-4">Lease Reports</h2>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="owner_id" class="form-label">Car Owner</label>
                            <select class="form-select" id="owner_id" name="owner_id">
                                <option value="">All Owners</option>
                                <?php foreach ($owners as $owner): ?>
                                    <option value="<?php echo $owner['user_id']; ?>" <?php echo $owner['user_id'] == $owner_id ? 'selected' : ''; ?>>
                                        <?php echo $owner['full_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="car_reg" class="form-label">Car Registration</label>
                            <input type="text" class="form-control" id="car_reg" name="car_reg" value="<?php echo htmlspecialchars($car_reg); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="reports.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary -->
        <div class="row summary-card">
            <div class="col-md-6">
                <h5>Total Leases: <?php echo count($leases); ?></h5>
                <h5>Total Amount: KES <?php echo number_format($total_amount, 2); ?></h5>
            </div>
            <div class="col-md-6">
                <h5>Total Commission: KES <?php echo number_format($total_commission, 2); ?></h5>
                <div class="mt-2">
                    <a href="export_reports.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">Export to Excel</a>
                </div>
            </div>
        </div>
        
        <!-- Leases Table -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Lease ID</th>
                        <th>Car Details</th>
                        <th>Owner</th>
                        <th>Renter</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leases as $lease): ?>
                        <tr>
                            <td><?php echo $lease['lease_id']; ?></td>
                            <td><?php echo $lease['make'] . ' ' . $lease['model'] . ' (' . $lease['registration_number'] . ')'; ?></td>
                            <td><?php echo $lease['owner_name']; ?></td>
                            <td><?php echo $lease['renter_name']; ?></td>
                            <td>
                                <?php echo date('M j, Y', strtotime($lease['start_date'])); ?><br>
                                to<br>
                                <?php echo date('M j, Y', strtotime($lease['end_date'])); ?>
                            </td>
                            <td>KES <?php echo number_format($lease['total_amount'], 2); ?></td>
                            <td>KES <?php echo number_format($lease['commission_amount'], 2); ?></td>
                            <td><?php echo ucfirst($lease['payment_method']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>