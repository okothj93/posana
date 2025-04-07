<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get filters from query string
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

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="leases_report_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, [
    'Lease ID', 
    'Car Registration', 
    'Make', 
    'Model', 
    'Owner Name', 
    'Renter Name', 
    'Start Date', 
    'End Date', 
    'Total Amount (KES)', 
    'Commission (KES)', 
    'Payment Method'
]);

// Write data rows
foreach ($leases as $lease) {
    fputcsv($output, [
        $lease['lease_id'],
        $lease['registration_number'],
        $lease['make'],
        $lease['model'],
        $lease['owner_name'],
        $lease['renter_name'],
        $lease['start_date'],
        $lease['end_date'],
        $lease['total_amount'],
        $lease['commission_amount'],
        $lease['payment_method']
    ]);
}

fclose($output);
exit();