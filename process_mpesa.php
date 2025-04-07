<?php
session_start();
require_once 'db.php';

// Check if lease ID exists in session
if (!isset($_SESSION['lease_id']) {
    header('Location: create_lease.php');
    exit();
}

$lease_id = $_SESSION['lease_id'];
$total_amount = $_SESSION['total_amount'];

// Process M-Pesa payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    
    // In a real implementation, you would call the M-Pesa STK Push API here
    // This is a simulation
    
    try {
        // Update lease with payment reference
        $payment_reference = 'MPESA_' . time();
        $stmt = $pdo->prepare("UPDATE leases SET payment_reference = ?, payment_status = 'completed' WHERE lease_id = ?");
        $stmt->execute([$payment_reference, $lease_id]);
        
        // Send confirmation SMS
        $lease = $pdo->query("SELECT r.phone as renter_phone, o.phone as owner_phone 
                             FROM leases l 
                             JOIN users r ON l.renter_id = r.user_id 
                             JOIN cars c ON l.car_id = c.car_id 
                             JOIN users o ON c.owner_id = o.user_id 
                             WHERE l.lease_id = $lease_id")->fetch(PDO::FETCH_ASSOC);
        
        $renter_message = "Your payment of KES $total_amount for lease ID $lease_id has been received. Thank you!";
        sendSMS($lease['renter_phone'], $renter_message);
        
        $owner_message = "Payment for lease ID $lease_id has been completed. You will receive KES " . ($total_amount * 0.9) . " after commission.";
        sendSMS($lease['owner_phone'], $owner_message);
        
        // Clear session and redirect
        unset($_SESSION['lease_id']);
        unset($_SESSION['total_amount']);
        
        header('Location: lease_success.php');
        exit();
    } catch (PDOException $e) {
        $error = "Payment processing failed: " . $e->getMessage();
    }
}

function sendSMS($phone, $message) {
    // Placeholder for actual SMS integration
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO sms_logs (recipient_phone, message, status) VALUES (?, ?, 'sent')");
    $stmt->execute([$phone, $message]);
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .payment-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="payment-container">
            <h2 class="text-center mb-4">M-Pesa Payment</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <div class="alert alert-info">
                <p>Total Amount: <strong>KES <?php echo number_format($total_amount, 2); ?></strong></p>
                <p>Lease ID: <strong><?php echo $lease_id; ?></strong></p>
            </div>
            <form method="POST">
                <div class="mb-3">
                    <label for="phone" class="form-label">M-Pesa Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g., 254712345678" required>
                    <div class="form-text">Enter the phone number registered with M-Pesa</div>
                </div>
                <button type="submit" class="btn btn-success w-100">Pay via M-Pesa</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>