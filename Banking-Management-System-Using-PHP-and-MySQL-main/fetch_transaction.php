<?php
// fetch_transaction.php
header('Content-Type: application/json');

// Create database connection
$con = new mysqli('localhost','root','','websitedb');
if ($con->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$transactionCode = $data['transactionCode'] ?? '';

if ($transactionCode) {
    $stmt = $con->prepare("SELECT * FROM mono_acc_transaction WHERE transaction_id = ?");
    $stmt->bind_param('s', $transactionCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $transaction = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'clientId' => $transaction['client_id'],
            'firstName' => $transaction['first_name'],
            'lastName' => $transaction['last_name'],
            'type' => $transaction['type_of_transaction'],
            'rip' => $transaction['RIP'],
            'amount' => $transaction['amount'],
            'date' => $transaction['date']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
