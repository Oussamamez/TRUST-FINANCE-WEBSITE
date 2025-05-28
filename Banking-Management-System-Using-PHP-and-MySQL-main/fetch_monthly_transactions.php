<?php
session_start();
// Only allow access for managers/admins
if(!isset($_SESSION['loginid']) || (strtolower($_SESSION['type_of_user'] ?? '') !== 'admin' && strtolower($_SESSION['type_of_user'] ?? '') !== 'manager')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$con = new mysqli('localhost','root','','websitedb');

// Get number of transactions per month for the last 12 months
$sql = "SELECT DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as count
        FROM mono_acc_transaction
        WHERE Validated = 1 AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC";
$result = $con->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['month']] = (int)$row['count'];
}

header('Content-Type: application/json');
echo json_encode($data);
?> 