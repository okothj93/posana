<?php
session_start();
require_once 'db.php';

// Get featured cars for the homepage
$featured_cars = $pdo->query("SELECT * FROM cars WHERE available = TRUE ORDER BY RAND() LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

// Get stats for the homepage
$stats = [
    'total_cars' => $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn(),
    'available_cars' => $pdo->query("SELECT COUNT(*) FROM cars WHERE available = TRUE")->fetchColumn(),
    'total_leases' => $pdo->query("SELECT COUNT(*) FROM leases")->fetchColumn(),
    'active_leases' => $pdo->query("SELECT COUNT(*) FROM leases WHERE lease_status = 'active'")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-color);
        }
        
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('images/car-rental-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
        
        .feature-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background-color: var(--light-color);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .navbar {
            background-color: var(--dark-color);
        }
        
        .navbar-brand {
            font-weight: bold;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">CarRentalPro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cars.php">Browse Cars</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['username']; ?>
                            </button>
                            <ul class="dropdown-menu">
                                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin_dashboard.php">Admin Dashboard</a></li>
                                <?php elseif ($_SESSION['user_type'] === 'owner'): ?>
                                    <li><a class="dropdown-item" href="owner_dashboard.php">Owner Dashboard</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="renter_dashboard.php">My Account</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Find Your Perfect Rental Car</h1>
            <p class="lead mb-5">Choose from our wide selection of vehicles for your next adventure</p>
            <a href="cars.php" class="btn btn-primary btn-lg px-4 me-2">Browse Cars</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-outline-light btn-lg px-4">Register Your Car</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container mb-5">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_cars']; ?></div>
                    <div class="stat-title">Total Cars</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['available_cars']; ?></div>
                    <div class="stat-title">Available Now</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_leases']; ?></div>
                    <div class="stat-title">Total Rentals</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_leases']; ?></div>
                    <div class="stat-title">Active Rentals</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Cars -->
    <section class="container mb-5">
        <h2 class="text-center mb-4">Featured Cars</h2>
        <div class="row">
            <?php foreach ($featured_cars as $car): ?>
                <div class="col-md-3 mb-4">
                    <div class="card feature-card h-100">
                        <?php if ($car['photo_path']): ?>
                            <img src="<?php echo $car['photo_path']; ?>" class="card-img-top" alt="<?php echo $car['make'] . ' ' . $car['model']; ?>">
                        <?php else: ?>
                            <img src="images/car-placeholder.jpg" class="card-img-top" alt="Car placeholder">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $car['make'] . ' ' . $car['model']; ?></h5>
                            <p class="card-text">
                                <i class="bi bi-calendar"></i> <?php echo $car['year']; ?><br>
                                <i class="bi bi-people"></i> <?php echo $car['seating_capacity']; ?> seats<br>
                                <i class="bi bi-palette"></i> <?php echo $car['color']; ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary">KES <?php echo number_format($car['daily_rate'], 2); ?>/day</span>
                                <a href="car_details.php?id=<?php echo $car['car_id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <a href="cars.php" class="btn btn-outline-primary">View All Cars</a>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-light py-5 mb-5">
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="mb-3">
                        <i class="bi bi-search-heart" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                    </div>
                    <h4>1. Find Your Car</h4>
                    <p>Browse our selection of vehicles and choose the perfect one for your needs.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="mb-3">
                        <i class="bi bi-calendar-check" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                    </div>
                    <h4>2. Book Online</h4>
                    <p>Select your dates and complete the booking process with our secure payment system.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="mb-3">
                        <i class="bi bi-key" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                    </div>
                    <h4>3. Pick Up & Enjoy</h4>
                    <p>Collect your car at the agreed location and enjoy your journey.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="container mb-5">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3>Ready to get started?</h3>
                <p class="lead">Join thousands of satisfied customers who have used our car rental services.</p>
            </div>
            <div class="col-md-4 text-end">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cars.php" class="btn btn-primary btn-lg">Rent a Car Now</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-lg">Register Now</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>CarRentalPro</h5>
                    <p>Your trusted partner for car rental and leasing services.</p>
                    <div class="social-icons">
                        <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="cars.php" class="text-white">Browse Cars</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Services</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Car Rental</a></li>
                        <li><a href="#" class="text-white">Long Term Leasing</a></li>
                        <li><a href="#" class="text-white">Car Listing</a></li>
                        <li><a href="#" class="text-white">Fleet Management</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-geo-alt"></i> 123 Rental Street, Nairobi</li>
                        <li><i class="bi bi-telephone"></i> +254 700 123 456</li>
                        <li><i class="bi bi-envelope"></i> info@carrentalpro.com</li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 CarRentalPro. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-white">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Smooth scrolling for anchor links
            $('a[href*="#"]').on('click', function(e) {
                e.preventDefault();
                
                $('html, body').animate(
                    {
                        scrollTop: $($(this).attr('href')).offset().top,
                    },
                    500,
                    'linear'
                );
            });
        });
    </script>
</body>
</html>