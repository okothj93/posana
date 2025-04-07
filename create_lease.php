<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) {
    header('Location: login.php');
    exit();
}

// Get available cars
$cars = $pdo->query("SELECT * FROM cars WHERE available = TRUE")->fetchAll(PDO::FETCH_ASSOC);

// Get renters
$renters = $pdo->query("SELECT * FROM users WHERE user_type = 'renter'")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = $_POST['car_id'];
    $renter_id = $_POST['renter_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $payment_method = $_POST['payment_method'];
    
    // Calculate total amount
    $car = $pdo->query("SELECT daily_rate, commission_rate FROM cars WHERE car_id = $car_id")->fetch(PDO::FETCH_ASSOC);
    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    $total_amount = $days * $car['daily_rate'];
    $commission_amount = $total_amount * ($car['commission_rate'] / 100);
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Create lease
        $stmt = $pdo->prepare("INSERT INTO leases (car_id, renter_id, start_date, end_date, total_amount, commission_amount, payment_method, payment_status, lease_status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'active')");
        $stmt->execute([$car_id, $renter_id, $start_date, $end_date, $total_amount, $commission_amount, $payment_method]);
        
        // Mark car as unavailable
        $pdo->exec("UPDATE cars SET available = FALSE WHERE car_id = $car_id");
        
        // Commit transaction
        $pdo->commit();
        
        // Get contact information
        $renter = $pdo->query("SELECT full_name, phone FROM users WHERE user_id = $renter_id")->fetch(PDO::FETCH_ASSOC);
        $owner = $pdo->query("SELECT u.phone FROM users u JOIN cars c ON u.user_id = c.owner_id WHERE c.car_id = $car_id")->fetch(PDO::FETCH_ASSOC);
        
        // Send notifications
        $renter_message = "Hello {$renter['full_name']}, your lease for car ID $car_id has been created from $start_date to $end_date. Total amount: KES $total_amount.";
        sendSMS($renter['phone'], $renter_message);
        
        $owner_message = "Your car ID $car_id has been leased to {$renter['full_name']} from $start_date to $end_date. You will earn KES " . ($total_amount - $commission_amount) . " after commission.";
        sendSMS($owner['phone'], $owner_message);
        
        // Redirect to payment processing based on method
        $_SESSION['lease_id'] = $pdo->lastInsertId();
        $_SESSION['total_amount'] = $total_amount;
        
        switch ($payment_method) {
            case 'mpesa':
                header('Location: process_mpesa.php');
                break;
            case 'card':
                header('Location: process_card.php');
                break;
            case 'paypal':
                header('Location: process_paypal.php');
                break;
        }
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Lease creation failed: " . $e->getMessage();
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
    <title>Create Lease</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 800px;
            margin: 30px auto;
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
        <div class="form-container">
            <h2 class="text-center mb-4">Create New Lease</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" id="leaseForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="car_id" class="form-label">Select Car</label>
                            <select class="form-select" id="car_id" name="car_id" required>
                                <option value="">-- Select Car --</option>
                                <?php foreach ($cars as $car): ?>
                                    <option value="<?php echo $car['car_id']; ?>">
                                        <?php echo $car['make'] . ' ' . $car['model'] . ' (' . $car['registration_number'] . ') - KES ' . $car['daily_rate'] . '/day'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="renter_id" class="form-label">Select Renter</label>
                            <select class="form-select" id="renter_id" name="renter_id" required>
                                <option value="">-- Select Renter --</option>
                                <?php foreach ($renters as $renter): ?>
                                    <option value="<?php echo $renter['user_id']; ?>">
                                        <?php echo $renter['full_name'] . ' (' . $renter['phone'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">-- Select Payment Method --</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3" id="amountDisplay">
                    <h5>Lease Summary</h5>
                    <p>Days: <span id="days">0</span></p>
                    <p>Daily Rate: <span id="dailyRate">KES 0.00</span></p>
                    <p>Total Amount: <span id="totalAmount">KES 0.00</span></p>
                    <p>Commission (<span id="commissionRate">0</span>%): <span id="commissionAmount">KES 0.00</span></p>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create Lease</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Calculate lease amount when dates or car changes
            function calculateLease() {
                const carId = $('#car_id').val();
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                
                if (carId && startDate && endDate) {
                    const start = new Date(startDate);
                    const end = new Date(endDate);
                    
                    if (start >= end) {
                        alert('End date must be after start date');
                        return;
                    }
                    
                    // Calculate days
                    const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                    $('#days').text(days);
                    
                    // Get car details via AJAX
                    $.get('get_car_details.php', { car_id: carId }, function(data) {
                        const dailyRate = parseFloat(data.daily_rate);
                        const commissionRate = parseFloat(data.commission_rate);
                        const totalAmount = days * dailyRate;
                        const commissionAmount = totalAmount * (commissionRate / 100);
                        
                        $('#dailyRate').text('KES ' + dailyRate.toFixed(2));
                        $('#commissionRate').text(commissionRate.toFixed(2));
                        $('#totalAmount').text('KES ' + totalAmount.toFixed(2));
                        $('#commissionAmount').text('KES ' + commissionAmount.toFixed(2));
                    }, 'json');
                }
            }
            
            $('#car_id, #start_date, #end_date').change(calculateLease);
        });
    </script>
</body>
</html>