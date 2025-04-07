<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $user_type = $_POST['user_type'];
    $full_name = $_POST['full_name'];
    
    // Additional fields based on user type
    $id_number = $_POST['id_number'] ?? null;
    $residence = $_POST['residence'] ?? null;
    $workplace = $_POST['workplace'] ?? null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, phone, user_type, full_name, id_number, residence, workplace) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $phone, $user_type, $full_name, $id_number, $residence, $workplace]);
        
        // Handle photo upload if exists
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $user_id = $pdo->lastInsertId();
            $photo_name = $user_id . '_' . basename($_FILES['photo']['name']);
            $target_path = 'uploads/photos/' . $photo_name;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $stmt = $pdo->prepare("UPDATE users SET photo_path = ? WHERE user_id = ?");
                $stmt->execute([$target_path, $user_id]);
            }
        }
        
        header('Location: login.php');
        exit();
    } catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">Register for Car Rental System</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" id="registrationForm">
                <div class="mb-3">
                    <label class="form-label">I am a:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="user_type" id="renterType" value="renter" checked>
                        <label class="form-check-label" for="renterType">Renter</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="user_type" id="ownerType" value="owner">
                        <label class="form-check-label" for="ownerType">Car Owner</label>
                    </div>
                </div>
                
                <div id="commonFields" class="form-section active">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo (Passport Size)</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                    </div>
                    <button type="button" class="btn btn-primary next-btn">Next</button>
                </div>
                
                <div id="renterFields" class="form-section">
                    <h4>Renter Information</h4>
                    <div class="mb-3">
                        <label for="id_number" class="form-label">ID/Passport Number</label>
                        <input type="text" class="form-control" id="id_number" name="id_number">
                    </div>
                    <div class="mb-3">
                        <label for="residence" class="form-label">Residence</label>
                        <input type="text" class="form-control" id="residence" name="residence">
                    </div>
                    <div class="mb-3">
                        <label for="workplace" class="form-label">Place of Work</label>
                        <input type="text" class="form-control" id="workplace" name="workplace">
                    </div>
                    <button type="button" class="btn btn-secondary prev-btn">Previous</button>
                    <button type="submit" class="btn btn-success">Register</button>
                </div>
                
                <div id="ownerFields" class="form-section">
                    <h4>Car Owner Information</h4>
                    <div class="mb-3">
                        <label for="owner_id_number" class="form-label">ID/Passport Number</label>
                        <input type="text" class="form-control" id="owner_id_number" name="id_number">
                    </div>
                    <button type="button" class="btn btn-secondary prev-btn">Previous</button>
                    <button type="submit" class="btn btn-success">Register</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle user type change
            $('input[name="user_type"]').change(function() {
                $('#commonFields').addClass('active');
                $('#renterFields, #ownerFields').removeClass('active');
            });
            
            // Next button click
            $('.next-btn').click(function() {
                $('#commonFields').removeClass('active');
                const userType = $('input[name="user_type"]:checked').val();
                if (userType === 'renter') {
                    $('#renterFields').addClass('active');
                } else {
                    $('#ownerFields').addClass('active');
                }
            });
            
            // Previous button click
            $('.prev-btn').click(function() {
                $('#renterFields, #ownerFields').removeClass('active');
                $('#commonFields').addClass('active');
            });
        });
    </script>
</body>
</html>