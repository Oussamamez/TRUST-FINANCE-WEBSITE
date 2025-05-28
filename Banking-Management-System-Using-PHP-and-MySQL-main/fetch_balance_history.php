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

// Get all validated transactions for the last 30 days and before
$all_transactions = $con->query("
    SELECT date, type_of_transaction, amount
    FROM mono_acc_transaction
    WHERE RIP = '$rip' AND Validated = 1
    ORDER BY date ASC
");

$today = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Calculate the balance 30 days ago
$balance_30_days_ago = 0;
$daily_changes = [];

while ($row = $all_transactions->fetch_assoc()) {
    $tx_date = substr($row['date'], 0, 10);
    $change = 0;
    if (strtolower($row['type_of_transaction']) === 'deposit') {
        $change = floatval($row['amount']);
    } elseif (strtolower($row['type_of_transaction']) === 'withdraw') {
        $change = -floatval($row['amount']);
    }
    if ($tx_date < $start_date) {
        $balance_30_days_ago += $change;
    } else {
        if (!isset($daily_changes[$tx_date])) $daily_changes[$tx_date] = 0;
        $daily_changes[$tx_date] += $change;
    }
}

// Get the initial balance (current balance)
$current_balance = $con->query("SELECT balance FROM useraccount WHERE RIP = '$rip'")->fetch_assoc()['balance'] ?? 0;

// Build the balance history
$balance_history = [];
$running_balance = $balance_30_days_ago;
$date_cursor = $start_date;

while ($date_cursor <= $today) {
    if (isset($daily_changes[$date_cursor])) {
        $running_balance += $daily_changes[$date_cursor];
    }
    $balance_history[] = [
        'date' => $date_cursor,
        'balance' => round($running_balance, 2)
    ];
    $date_cursor = date('Y-m-d', strtotime($date_cursor . ' +1 day'));
}

// Adjust the last point to match the current balance (in case of rounding or missing data)
if (count($balance_history) > 0) {
    $balance_history[count($balance_history)-1]['balance'] = round($current_balance, 2);
}

header('Content-Type: application/json');
echo json_encode($balance_history);
?> 