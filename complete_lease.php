<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['lease_id'])) {
    $lease_id = $_GET['lease_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get car ID from lease
        $car_id = $pdo->query("SELECT car_id FROM leases WHERE lease_id = $lease_id")->fetchColumn();
        
        // Update lease status
        $pdo->exec("UPDATE leases SET lease_status = 'completed' WHERE lease_id = $lease_id");
        
        // Mark car as available
        $pdo->exec("UPDATE cars SET available = TRUE WHERE car_id = $car_id");
        
        // Commit transaction
        $pdo->commit();
        
        // Get contact information for notifications
        $lease_info = $pdo->query("SELECT r.full_name as renter_name, r.phone as renter_phone, 
                                  o.phone as owner_phone, c.registration_number
                                  FROM leases l
                                  JOIN users r ON l.renter_id = r.user_id
                                  JOIN cars c ON l.car_id = c.car_id
                                  JOIN users o ON c.owner_id = o.user_id
                                  WHERE l.lease_id = $lease_id")->fetch(PDO::FETCH_ASSOC);
        
        // Send notifications
        $renter_message = "Hello {$lease_info['renter_name']}, the lease for car {$lease_info['registration_number']} has been completed. Thank you for using our service!";
        sendSMS($lease_info['renter_phone'], $renter_message);
        
        $owner_message = "Your car {$lease_info['registration_number']} has been returned and is now available for new leases.";
        sendSMS($lease_info['owner_phone'], $owner_message);
        
        $success = "Lease completed successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Lease completion failed: " . $e->getMessage();
    }
}

function sendSMS($phone, $message) {
    // Placeholder for actual SMS integration
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO sms_logs (recipient_phone, message, status) VALUES (?, ?, 'sent')");
    $stmt->execute([$phone, $message]);
    return true;
}

// Get active leases for display
$leases = $pdo->query("SELECT l.lease_id, l.start_date, l.end_date, l.total_amount, 
                      c.registration_number, u.full_name as renter_name
                      FROM leases l
                      JOIN cars c ON l.car_id = c.car_id
                      JOIN users u ON l.renter_id = u.user_id
                      WHERE l.lease_status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Lease</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h2 class="mb-4">Complete Lease</h2>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Lease ID</th>
                        <th>Car Registration</th>
                        <th>Renter Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leases as $lease): ?>
                        <tr>
                            <td><?php echo $lease['lease_id']; ?></td>
                            <td><?php echo $lease['registration_number']; ?></td>
                            <td><?php echo $lease['renter_name']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($lease['start_date'])); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($lease['end_date'])); ?></td>
                            <td>KES <?php echo number_format($lease['total_amount'], 2); ?></td>
                            <td>
                                <a href="complete_lease.php?lease_id=<?php echo $lease['lease_id']; ?>" class="btn btn-success btn-sm">Complete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>