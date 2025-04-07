<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a car owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_id = $_SESSION['user_id'];
    $registration_number = $_POST['registration_number'];
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $color = $_POST['color'];
    $seating_capacity = $_POST['seating_capacity'];
    $daily_rate = $_POST['daily_rate'];
    $commission_rate = $_POST['commission_rate'];
    $description = $_POST['description'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO cars (owner_id, registration_number, make, model, year, color, seating_capacity, daily_rate, commission_rate, description) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$owner_id, $registration_number, $make, $model, $year, $color, $seating_capacity, $daily_rate, $commission_rate, $description]);
        
        // Handle car photo upload
        if (isset($_FILES['car_photo']) && $_FILES['car_photo']['error'] === UPLOAD_ERR_OK) {
            $car_id = $pdo->lastInsertId();
            $photo_name = 'car_' . $car_id . '_' . basename($_FILES['car_photo']['name']);
            $target_path = 'uploads/cars/' . $photo_name;
            
            if (move_uploaded_file($_FILES['car_photo']['tmp_name'], $target_path)) {
                $stmt = $pdo->prepare("UPDATE cars SET photo_path = ? WHERE car_id = ?");
                $stmt->execute([$target_path, $car_id]);
            }
        }
        
        // Send SMS notification to owner
        $owner_phone = $pdo->query("SELECT phone FROM users WHERE user_id = $owner_id")->fetchColumn();
        $message = "Your car $registration_number has been successfully registered in our system.";
        sendSMS($owner_phone, $message);
        
        $success = "Car registered successfully!";
    } catch (PDOException $e) {
        $error = "Car registration failed: " . $e->getMessage();
    }
}

function sendSMS($phone, $message) {
    // This is a placeholder for actual SMS gateway integration
    // In a real implementation, you would use an API like Africa's Talking, Twilio, etc.
    $sms_log = [
        'recipient_phone' => $phone,
        'message' => $message,
        'status' => 'sent'
    ];
    
    // Log the SMS in database
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO sms_logs (recipient_phone, message, status) VALUES (?, ?, ?)");
    $stmt->execute([$sms_log['recipient_phone'], $sms_log['message'], $sms_log['status']]);
    
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Car</title>
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
            <h2 class="text-center mb-4">Register Your Car</h2>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="registration_number" class="form-label">Registration Number</label>
                            <input type="text" class="form-control" id="registration_number" name="registration_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="make" class="form-label">Make</label>
                            <input type="text" class="form-control" id="make" name="make" required>
                        </div>
                        <div class="mb-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model" required>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control" id="year" name="year" min="1900" max="<?php echo date('Y'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color" required>
                        </div>
                        <div class="mb-3">
                            <label for="seating_capacity" class="form-label">Seating Capacity</label>
                            <input type="number" class="form-control" id="seating_capacity" name="seating_capacity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="daily_rate" class="form-label">Daily Rate (KES)</label>
                            <input type="number" class="form-control" id="daily_rate" name="daily_rate" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                            <input type="number" class="form-control" id="commission_rate" name="commission_rate" min="0" max="100" step="0.01" value="10" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="car_photo" class="form-label">Car Photo</label>
                    <input type="file" class="form-control" id="car_photo" name="car_photo" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary w-100">Register Car</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>