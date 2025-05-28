<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'transfer_errors.log');

// Function to log errors and redirect
function handleError($message, $redirect = true) {
    error_log($message);
    if ($redirect) {
        echo "<script>alert('$message'); window.location.href='clientdashboard.php';</script>";
        exit;
    }
}

session_start();
if (!isset($_SESSION['loginid'])) {
    handleError("No login session found");
}

$con = new mysqli('localhost', 'root', '', 'websitedb');
if ($con->connect_error) {
    handleError("Database connection failed: " . $con->connect_error);
}

// Log all POST data for debugging
error_log("POST data received: " . print_r($_POST, true));

// Ensure 'rip' is set in the session
if (!isset($_SESSION['rip'])) {
    $user = $con->query("SELECT u.RIP FROM useraccount u JOIN normaluser n ON u.national_identifier_number = n.national_identifier_number WHERE n.national_identifier_number='" . $_SESSION['loginid'] . "'")->fetch_assoc();
    if ($user) {
        $_SESSION['rip'] = $user['RIP'];
        error_log("RIP set in session: " . $user['RIP']);
    } else {
        handleError("Failed to fetch RIP for user: " . $_SESSION['loginid']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing POST request");
    error_log("Action: " . ($_POST['action'] ?? 'not set'));
    
    $action = $_POST['action'] ?? '';
    
    // Validate amount is numeric and positive
    if (!isset($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
        handleError("Please enter a valid positive number for the amount.");
    }
    
    $amount = floatval($_POST['amount']); // Ensure amount is a number
    $rip = $_SESSION['rip']; // Get RIP from session
    $transactionId = 'TXN-' . time() . '-' . rand(1000, 9999); // Generate unique transaction ID with timestamp

    error_log("Processing transaction - Amount: $amount, RIP: $rip, Action: $action");

    // Validate RIP exists in useraccount table (for sender)
    $senderRipExists = $con->query("SELECT RIP FROM useraccount WHERE RIP = '$rip'")->num_rows > 0;
    if (!$senderRipExists) {
        handleError("Invalid sender RIP: $rip");
    }

    if ($action === 'deposit') {
        $result = $con->query("INSERT INTO mono_acc_transaction (transaction_id, type_of_transaction, amount, date, RIP) VALUES ('$transactionId', 'Deposit', '$amount', NOW(), '$rip')");
        if (!$result) {
            error_log("Deposit failed: " . $con->error);
            echo "<script>alert('Deposit failed: " . $con->error . "'); window.location.href='clientdashboard.php';</script>";
            exit;
        }
        echo "<script>alert('Deposit successful. Transaction ID: $transactionId'); window.location.href='clientdashboard.php';</script>";
    } elseif ($action === 'withdraw') {
        // Check if user has sufficient balance
        $current_balance = $con->query("SELECT balance FROM useraccount WHERE RIP = '$rip'")->fetch_assoc()['balance'] ?? 0;
        
        if ($amount > $current_balance) {
            error_log("Insufficient balance for withdrawal. Balance: $current_balance, Amount: $amount");
            echo "<script>alert('Insufficient balance. Your current balance is $" . number_format($current_balance, 2) . "'); window.location.href='clientdashboard.php';</script>";
            exit;
        }
        
        $result = $con->query("INSERT INTO mono_acc_transaction (transaction_id, type_of_transaction, amount, date, RIP) VALUES ('$transactionId', 'Withdraw', '$amount', NOW(), '$rip')");
        if (!$result) {
            error_log("Withdrawal failed: " . $con->error);
            echo "<script>alert('Withdrawal failed: " . $con->error . "'); window.location.href='clientdashboard.php';</script>";
            exit;
        }
        echo "<script>alert('Withdrawal successful. Transaction ID: $transactionId'); window.location.href='clientdashboard.php';</script>";
    } elseif ($action === 'transfer') {
        error_log("Processing transfer action");
        $recipient_rip = $con->real_escape_string(trim($_POST['rip']));
        error_log("Recipient RIP: " . $recipient_rip);

        // Get transfer reason
        $transfer_reason = $con->real_escape_string(trim($_POST['transfer_reason'] ?? ''));
        error_log("Transfer Reason: " . $transfer_reason);

        // Validate RIP format
        if (!preg_match('/^RIP\d+$/', $recipient_rip)) {
            handleError("Invalid RIP format. RIP should start with \"RIP\" followed by numbers (e.g., RIP2345678899)");
        }

        // Check if recipient exists and is not the same as sender
        $recipientCheckQuery = "SELECT RIP, balance FROM useraccount WHERE RIP = '$recipient_rip'";
        error_log("Checking recipient: " . $recipientCheckQuery);
        $recipientResult = $con->query($recipientCheckQuery);
        
        if (!$recipientResult) {
            handleError("Recipient check query failed: " . $con->error);
        }
        
        if ($recipientResult->num_rows === 0) {
            handleError("Recipient not found: $recipient_rip");
        }

        if ($recipient_rip === $rip) {
            handleError("Cannot transfer to your own account.");
        }

        // Check sender balance
        $sender_account = $con->query("SELECT balance FROM useraccount WHERE RIP = '$rip'")->fetch_assoc();
        if (!$sender_account) {
            handleError("Failed to fetch sender account: " . $con->error);
        }
        
        $sender_balance = $sender_account['balance'] ?? 0.00;
        error_log("Sender balance: $sender_balance, Transfer amount: $amount");

        if ($sender_balance < $amount) {
            handleError("Insufficient funds. Your current balance is $" . number_format($sender_balance, 2));
        }

        // Start transaction
        $con->begin_transaction();
        try {
            error_log("Starting transfer transaction");
            
            // Update sender's balance
            $updateSender = $con->query("UPDATE useraccount SET balance = balance - $amount WHERE RIP = '$rip'");
            if (!$updateSender || $con->affected_rows === 0) {
                throw new Exception("Failed to update sender's balance: " . $con->error);
            }
            error_log("Sender balance updated");

            // Update recipient's balance
            $updateRecipient = $con->query("UPDATE useraccount SET balance = balance + $amount WHERE RIP = '$recipient_rip'");
            if (!$updateRecipient || $con->affected_rows === 0) {
                throw new Exception("Failed to update recipient's balance: " . $con->error);
            }
            error_log("Recipient balance updated");

            // Generate unique transaction IDs for each part
            $transferId = 'TXN-' . time() . '-T-' . rand(1000, 9999);
            $senderTxId = 'TXN-' . time() . '-S-' . rand(1000, 9999);
            $recipientTxId = 'TXN-' . time() . '-R-' . rand(1000, 9999);

            // Log the transfer
            $current_date = date('Y-m-d'); // Format date as YYYY-MM-DD
            $insertTransfer = $con->query("INSERT INTO transfer (transaction_id, amount, date, transfer_reason, tax, Validated, from_RIP, to_RIP) 
                                         VALUES ('$transferId', $amount, '$current_date', '$transfer_reason', 0, 1, '$rip', '$recipient_rip')");
            if (!$insertTransfer) {
                error_log("Transfer insert failed: " . $con->error);
                throw new Exception("Failed to log transfer: " . $con->error);
            }
            error_log("Transfer logged successfully");

            // Log sender transaction
            $insertSenderTx = $con->query("INSERT INTO mono_acc_transaction (transaction_id, type_of_transaction, amount, date, tax, Validated, RIP) 
                                         VALUES ('$senderTxId', 'Transfer (Sent)', $amount, '$current_date', 0, 1, '$rip')");
            if (!$insertSenderTx) {
                error_log("Sender transaction insert failed: " . $con->error);
                throw new Exception("Failed to log sender transaction: " . $con->error);
            }
            error_log("Sender transaction logged successfully");

            // Log recipient transaction
            $insertRecipientTx = $con->query("INSERT INTO mono_acc_transaction (transaction_id, type_of_transaction, amount, date, tax, Validated, RIP) 
                                            VALUES ('$recipientTxId', 'Transfer (Received)', $amount, '$current_date', 0, 1, '$recipient_rip')");
            if (!$insertRecipientTx) {
                error_log("Recipient transaction insert failed: " . $con->error);
                throw new Exception("Failed to log recipient transaction: " . $con->error);
            }
            error_log("Recipient transaction logged successfully");

            $con->commit();
            error_log("Transfer completed successfully - Transfer ID: $transferId");
            echo "<script>alert('Transfer completed successfully!'); window.location.href='clientdashboard.php';</script>";

        } catch (Exception $e) {
            $con->rollback();
            error_log("Transfer failed: " . $e->getMessage());
            echo "<script>alert('Transfer failed: " . $e->getMessage() . "'); window.location.href='clientdashboard.php';</script>";
        }
    } else {
        error_log("Unknown action: $action");
        echo "<script>alert('Unknown action specified.'); window.location.href='clientdashboard.php';</script>";
    }
}

$con->close();

// Remove the undefined array key warning by checking if the key exists
if (isset($_SESSION['transaction_success'])) {
    error_log('Transaction Success: ' . $_SESSION['transaction_success']);
}
error_log('Transaction Error: ' . ($_SESSION['transaction_error'] ?? 'None'));
?>