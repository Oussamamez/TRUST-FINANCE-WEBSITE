<?php
session_start();
if(!isset($_SESSION['loginid'])){ 
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit; 
}

$con = new mysqli('localhost','root','','websitedb');
$national_id = $_SESSION['userid'] ?? $_SESSION['loginid'];

// Get user's RIP
$rip = $con->query("SELECT RIP FROM useraccount WHERE national_identifier_number = '$national_id'")->fetch_assoc()['RIP'] ?? null;

if (!$rip) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Account not found']);
    exit;
}

// Get transaction type counts for the last 30 days
$sql = "SELECT type_of_transaction, COUNT(*) as count FROM mono_acc_transaction WHERE RIP = '$rip' AND Validated = 1 AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY type_of_transaction";
$result = $con->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['type_of_transaction']] = (int)$row['count'];
}

header('Content-Type: application/json');
echo json_encode($data);
?> 