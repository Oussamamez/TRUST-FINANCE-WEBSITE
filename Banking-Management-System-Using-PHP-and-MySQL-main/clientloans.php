<?php
session_start();
if(!isset($_SESSION['loginid'])){ 
    header('location:login.php'); 
    exit; 
}

$con = new mysqli('localhost','root','','websitedb');
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Ensure created_at column exists
$con->query("ALTER TABLE loan ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

$national_id = $_SESSION['loginid'];
$error = '';
$success = '';

// Get user's RIP (account number)
$user_account = $con->query("SELECT RIP FROM useraccount WHERE national_identifier_number = '$national_id'")->fetch_assoc();
$rip = $user_account ? $user_account['RIP'] : null;

if (!$rip) {
    $error = "No account found. Please contact support.";
} else {
    // Fetch user's active loan
    $active_loan = $con->query("SELECT * FROM loan WHERE RIP = '$rip' AND loan_status = 'Approved' ORDER BY loan_date DESC LIMIT 1")->fetch_assoc();
    
    // --- Add logic to check for overdue loans and freeze account ---
    if ($active_loan && $active_loan['loan_status'] == 'Approved' && $active_loan['loan_term'] > 0) {
        $loan_end_date = date('Y-m-d', strtotime($active_loan['loan_date'] . ' + ' . $active_loan['loan_term'] . ' months'));
        $current_date = date('Y-m-d');

        if (strtotime($current_date) > strtotime($loan_end_date)) {
            // Loan is overdue and still approved, freeze the account
            $update_status_sql = "UPDATE useraccount SET Status = 0 WHERE national_identifier_number = '$national_id'";
            if ($con->query($update_status_sql)) {
                // Account frozen successfully. You might want to log out the user here.
                // For now, we'll just set an error message.
                $error = "Your account has been permanently frozen due to an overdue loan.";
                 // Redirect to login page after freezing
                 header('location:login.php?frozen=true');
                 exit();
            }
        }
    }
    // --- End overdue loan check logic ---

    // Fetch loan payment history
    $payment_history = $con->query("SELECT * FROM mono_acc_transaction WHERE RIP = '$rip' AND type_of_transaction = 'Loan Payment' ORDER BY date DESC");
    
    // Fetch all loan requests for this client
    $all_loans = $con->query("SELECT * FROM loan WHERE RIP = '$rip' ORDER BY loan_date DESC");
    
    // Handle new loan request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_loan'])) {
        $amount = intval($_POST['amount']); // Convert to integer
        $loan_date = date('Y-m-d');
        
        // Validate amount
        if ($amount < 50 || $amount > 50000) {
            $error = "Loan amount must be between $50 and $50,000.";
        } else {
            // Check if user has any unpaid loans
            $unpaid_loan = $con->query("SELECT * FROM loan WHERE RIP = '$rip' AND loan_status = 'Approved'")->fetch_assoc();
            if ($unpaid_loan) {
                $error = "You must pay off your current loan before applying for a new one.";
            } else {
                // Check if user already has a pending loan
                $pending_loan = $con->query("SELECT * FROM loan WHERE RIP = '$rip' AND loan_status = 'Pending'")->fetch_assoc();
                if ($pending_loan) {
                    $error = "You already have a pending loan request. Please wait for it to be processed before applying for another loan.";
                } else {
                    // ============================================
                    // OPTION 1: TWO MONTH CHECK
                    // To enable: Remove the /* and */ below
                    // To disable: Add /* and */ back
                    // ============================================
                    
                 /*   $two_months_ago = date('Y-m-d', strtotime('-2 months'));
                    $recent_loan = $con->query("SELECT * FROM loan WHERE RIP = '$rip' AND loan_date >= '$two_months_ago'")->fetch_assoc();
                    
                    if ($recent_loan) {
                        $error = "You can only apply for a new loan after 2 months from your last loan request.";
                    } else {*/
                        // ============================================
                        // OPTION 2: 10-MINUTE DELAY CHECK
                        // To enable: Remove the /* and */ below
                        // To disable: Add /* and */ back
                        // ============================================
                        
                        $ten_minutes_ago = date('Y-m-d H:i:s', strtotime('-10 minutes'));
                        $recent_request = $con->query("SELECT * FROM loan WHERE RIP = '$rip' AND created_at >= '$ten_minutes_ago'")->fetch_assoc();
                        
                        if ($recent_request) {
                            $error = "You can only apply for a new loan after 10 minutes from your last loan request.";
                        } else {
                        

                        // ============================================
                        // CHECK IF LAST LOAN WAS PAID (ALWAYS ACTIVE)
                        // ============================================
                        $last_loan = $con->query("SELECT * FROM loan WHERE RIP = '$rip' ORDER BY loan_date DESC LIMIT 1")->fetch_assoc();
                        if ($last_loan && $last_loan['loan_status'] !== 'Completed') {
                            $error = "You must pay off your last loan before applying for a new one.";
                        } else {
                            // Calculate loan term based on amount
                            $term = 0;
                            if ($amount >= 50 && $amount <= 499) {
                                $term = 3;
                            } elseif ($amount >= 500 && $amount <= 1999) {
                                $term = 6;
                            } elseif ($amount >= 2000 && $amount <= 9999) {
                                $term = 12;
                            } elseif ($amount >= 10000) {
                                $term = 24;
                            }

                            // Generate unique loan ID
                            $loan_id = 'LOAN-' . date('Y') . sprintf('%04d', rand(1, 9999));
                            
                            // Calculate loan tax (5% of loan amount)
                            $loan_tax = $amount * 0.05;
                            
                            // Get current timestamp
                            $current_timestamp = date('Y-m-d H:i:s');
                            
                            // Insert the loan request
                            $insert_sql = "INSERT INTO loan (loan_id, amount, loan_status, loan_date, loan_tax, RIP, loan_term, created_at) 
                                        VALUES ('$loan_id', '$amount', 'Pending', '$loan_date', '$loan_tax', '$rip', '$term', '$current_timestamp')"; 
                            
                            if ($con->query($insert_sql)) {
                                $success = "Loan request submitted successfully!";
                                // Refresh the page to show updated data
                                header('Location: clientloans.php?success=1');
                                exit();
                            } else {
                                $error = "Error submitting loan request: " . $con->error;
                            }
                        }
                    }
                }
            }
        }
    }

    // Handle loan payment
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
        $payment_amount = $con->real_escape_string($_POST['payment_amount']);
        $transaction_id = 'PAY-' . date('Y') . sprintf('%04d', rand(1, 9999));
        $payment_date = date('Y-m-d');
        
        // Get the active loan ID to associate the payment
        $active_loan_id = $active_loan ? $active_loan['loan_id'] : null;

        if ($active_loan_id) {
            // Check if user has sufficient balance
            $user_balance_query = $con->query("SELECT balance FROM useraccount WHERE RIP = '$rip'");
            $user_balance = $user_balance_query->fetch_assoc()['balance'];

            if ($user_balance < $payment_amount) {
                $error = "Insufficient balance. You need $" . number_format($payment_amount, 2) . " but your current balance is $" . number_format($user_balance, 2);
            } else {
                // Insert the payment transaction, linking it to the active loan
                $insert_sql = "INSERT INTO mono_acc_transaction (transaction_id, type_of_transaction, amount, date, tax, Validated, RIP, loan_id) 
                              VALUES ('$transaction_id', 'Loan Payment', '$payment_amount', '$payment_date', 0, 1, '$rip', '$active_loan_id')";
                
                if ($con->query($insert_sql)) {
                    // Update user's balance by subtracting the payment amount
                    $update_balance = $con->query("UPDATE useraccount SET balance = balance - $payment_amount WHERE RIP = '$rip'");
                    
                    if ($update_balance) {
                        $success = "Payment processed successfully!";
                        
                        // --- Recalculate and update remaining balance and loan status ---
                        // Fetch total payments for this active loan AFTER the new payment is inserted
                        $total_paid_query = $con->query("SELECT SUM(amount) AS total_paid FROM mono_acc_transaction WHERE RIP = '$rip' AND loan_id = '$active_loan_id' AND type_of_transaction = 'Loan Payment' AND Validated = 1");
                        $total_paid_result = $total_paid_query->fetch_assoc();
                        $total_paid = $total_paid_result['total_paid'] ?? 0;

                        // --- Fetch the original loan amount from the loan table explicitly ---
                        $original_loan_amount_query = $con->query("SELECT amount FROM loan WHERE loan_id = '$active_loan_id' LIMIT 1");
                        $original_loan_amount_data = $original_loan_amount_query->fetch_assoc();
                        $original_loan_amount = $original_loan_amount_data['amount'] ?? 0;

                        // Calculate remaining balance correctly using original amount
                        $remaining_balance = $original_loan_amount - $total_paid;

                        // Update loan status if fully paid (remaining balance <= 0)
                        if ($remaining_balance <= 0) {
                            $current_timestamp = date('Y-m-d H:i:s');
                            $update_loan_status_sql = "UPDATE loan SET loan_status = 'Completed', created_at = '$current_timestamp' WHERE loan_id = '$active_loan_id'";
                            $con->query($update_loan_status_sql);
                            $success .= " Loan is now fully paid!";
                            // Redirect to refresh the page and clear the active loan display
                            header('Location: clientloans.php?payment_success=true');
                            exit();
                        }
                        
                        // Redirect to refresh the page and display the updated balance/history
                        header('Location: clientloans.php?payment_success=true');
                        exit();
                    } else {
                        $error = "Error updating balance: " . $con->error;
                    }
                } else {
                    $error = "Error processing payment: " . $con->error;
                }
            }
        } else {
            $error = "No active loan found to make a payment.";
        }
    }

    // --- Add logic to recalculate active loan balance and status on page load ---
    // After fetching the active loan (if any), calculate its current remaining balance
    if ($active_loan) {
        $active_loan_id = $active_loan['loan_id'];
        $total_paid_query = $con->query("SELECT SUM(amount) AS total_paid FROM mono_acc_transaction WHERE RIP = '$rip' AND loan_id = '$active_loan_id' AND type_of_transaction = 'Loan Payment' AND Validated = 1");
        $total_paid_result = $total_paid_query->fetch_assoc();
        $total_paid = $total_paid_result['total_paid'] ?? 0;

        // Fetch the original loan amount from the loan table if not already available (should be)
        // We need the original amount to calculate remaining balance correctly on page load
        if (!isset($active_loan['original_amount'])) {
             $original_loan_amount_query = $con->query("SELECT amount FROM loan WHERE loan_id = '$active_loan_id' LIMIT 1");
             $original_loan_amount_data = $original_loan_amount_query->fetch_assoc();
             $active_loan['original_amount'] = $original_loan_amount_data['amount'] ?? 0;
        }

        // Calculate remaining balance and update active_loan variable for display
        $original_loan_amount = $active_loan['original_amount']; 
        $active_loan['amount'] = $original_loan_amount - $total_paid; // This will now store the remaining balance for display
        
        // If the calculated remaining balance is <= 0, update the loan status in DB and nullify active_loan
        if ($active_loan['amount'] <= 0 && $active_loan['loan_status'] != 'Completed') {
             $update_loan_status_sql = "UPDATE loan SET loan_status = 'Completed' WHERE loan_id = '$active_loan_id'";
             $con->query($update_loan_status_sql);
             $active_loan = null;
        }

        // --- Calculate remaining loan term for display ---
        if ($active_loan) { // Re-check if active_loan is still set after status update check
            $loan_start_date = new DateTime($active_loan['loan_date']);
            $current_date = new DateTime();
            $interval = $loan_start_date->diff($current_date);
            $elapsed_months = $interval->y * 12 + $interval->m;

            $total_loan_term = $active_loan['loan_term'];
            $remaining_term = max(0, $total_loan_term - $elapsed_months);

            // Store remaining term in the active_loan array for display
            $active_loan['remaining_term'] = $remaining_term;
        }
         // Note: Monthly payment and end date display will now use the updated active_loan array
         // If active_loan becomes null, they will show defaults from the ternary operators
    }
    // --- End recalculation logic ---
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Bank - Manage Loans</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'maze-green': {
                            900: '#0F3E2A',
                            950: '#0A2B1E'
                        }
                    }
                }
            }
        }

        // Show modal
        function showModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        }

        // Close modal
        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }

        // Update term display
        function updateTerm(amount) {
            const termDisplay = document.querySelector('input[name="term_display"]');
            amount = parseInt(amount);
            
            if (isNaN(amount)) {
                termDisplay.value = 'Term will be determined by amount';
                return;
            }

            let term = '';
            if (amount >= 50 && amount <= 499) {
                term = '3 months';
            } else if (amount >= 500 && amount <= 1999) {
                term = '6 months';
            } else if (amount >= 2000 && amount <= 9999) {
                term = '12 months';
            } else if (amount >= 10000 && amount <= 50000) {
                term = '24 months';
            } else if (amount > 50000) {
                term = 'Maximum loan amount is $50,000';
            } else {
                term = 'Amount outside valid range';
            }
            
            termDisplay.value = term;
        }

        // Validate loan amount
        function validateLoanAmount() {
            const amountInput = document.querySelector('input[name="amount"]');
            const amount = parseInt(amountInput.value);
            
            if (isNaN(amount) || amount < 50 || amount > 50000) {
                alert('Please enter a valid loan amount between $50 and $50,000');
                return false;
            }
            
            if (amount % 1 !== 0) {
                alert('Please enter a whole number amount');
                return false;
            }
            
            return true;
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listeners to buttons
            document.querySelector('button[onclick="showModal(\'requestLoanModal\')"]').addEventListener('click', function(e) {
                e.preventDefault();
                showModal('requestLoanModal');
            });

            document.querySelector('button[onclick="showModal(\'viewLoanModal\')"]').addEventListener('click', function(e) {
                e.preventDefault();
                showModal('viewLoanModal');
            });
        });
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-maze-green-950 text-white p-6">
            <div class="flex items-center mb-8">
                <a href="clientdashboard.php" class="flex items-center cursor-pointer">
                    <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold">TRUST Finance</h1>
                </a>
            </div>

            <nav class="space-y-2">
                <a href="clientdashboard.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'clientdashboard.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="client_transactions.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'client_transactions.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    Transaction
                </a>
                <a href="clientloans.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'clientloans.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Loans
                </a>
                <a href="updateprofile.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'updateprofile.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Profile
                </a>
                <a href="logout.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6 bg-white">
            <div class="max-w-4xl mx-auto">
                <?php if($success): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Loan Header -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Manage Loans</h2>
                    <p class="text-gray-600">Request new loans or manage your existing loans</p>
                </div>

                <!-- Loan Summary Card -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-green-50 p-4 rounded-md">
                            <h3 class="text-sm font-medium text-gray-500">Current Loan Balance</h3>
                            <p class="text-2xl font-bold text-gray-800">
                                <!-- Display the calculated remaining balance -->
                                $<?php echo $active_loan ? number_format($active_loan['amount'], 2) : '0.00'; ?>
                            </p>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-md">
                            <h3 class="text-sm font-medium text-gray-500">Monthly Payment</h3>
                            <p class="text-2xl font-bold text-gray-800">
                                <!-- Calculate monthly payment based on the original loan amount and term -->
                                $<?php echo ($active_loan && $active_loan['loan_term'] > 0 && isset($active_loan['original_amount'])) ? number_format($active_loan['original_amount'] / $active_loan['loan_term'], 2) : '0.00'; ?>
                            </p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-md">
                            <h3 class="text-sm font-medium text-gray-500">Loan Term Remaining</h3>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo ($active_loan && isset($active_loan['remaining_term'])) ? $active_loan['remaining_term'] . ' months' : 'No active loan'; ?>
                            </p>
                            <?php if ($active_loan && isset($active_loan['remaining_term']) && $active_loan['remaining_term'] > 0):
                                // Calculate end date based on the loan_date and original loan_term
                                ?>
                                <span class="text-sm text-gray-500">
                                    End date: <?php echo date('M d, Y', strtotime($active_loan['loan_date'] . ' + ' . $active_loan['loan_term'] . ' months')); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Reminder Section -->
                <?php if ($active_loan && $active_loan['loan_status'] == 'Approved'): 
                    // Calculate next payment date
                    $last_payment = $con->query("SELECT MAX(date) as last_payment FROM mono_acc_transaction WHERE RIP = '$rip' AND loan_id = '{$active_loan['loan_id']}' AND type_of_transaction = 'Loan Payment'")->fetch_assoc()['last_payment'];
                    if ($last_payment) {
                        $next_payment = date('Y-m-d', strtotime($last_payment . ' +1 month'));
                    } else {
                        $next_payment = date('Y-m-d', strtotime($active_loan['loan_date'] . ' +1 month'));
                    }
                    
                    // Calculate days until next payment
                    $today = new DateTime();
                    $payment_date = new DateTime($next_payment);
                    $days_until_payment = $today->diff($payment_date)->days;
                    
                    // Determine if payment is due in 3 days
                    $is_due_soon = $days_until_payment <= 3 && $days_until_payment > 0;
                    
                    // Set appropriate background and text colors
                    $bg_color = $is_due_soon ? 'bg-red-50' : 'bg-yellow-50';
                    $border_color = $is_due_soon ? 'border-red-400' : 'border-yellow-400';
                    $text_color = $is_due_soon ? 'text-red-800' : 'text-yellow-800';
                    $icon_color = $is_due_soon ? 'text-red-400' : 'text-yellow-400';
                ?>
                <div class="<?php echo $bg_color; ?> border-l-4 <?php echo $border_color; ?> p-4 mb-6 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 <?php echo $icon_color; ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium <?php echo $text_color; ?>">
                                <?php echo $is_due_soon ? 'Payment Due Soon!' : 'Payment Reminder'; ?>
                            </h3>
                            <div class="mt-2 text-sm <?php echo $text_color; ?>">
                                <p>Your next monthly payment of $<?php echo number_format($active_loan['original_amount'] / $active_loan['loan_term'], 2); ?> is due by 
                                <?php echo date('F d, Y', strtotime($next_payment)); ?>.</p>
                                <?php if ($is_due_soon): ?>
                                    <p class="mt-1 font-semibold">Only <?php echo $days_until_payment; ?> day<?php echo $days_until_payment != 1 ? 's' : ''; ?> remaining!</p>
                                <?php endif; ?>
                                <p class="mt-1">Please ensure you have sufficient funds in your account for the automatic payment.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- My Loan Requests Table -->
                <div class="bg-white shadow rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">My Loan Requests</h3>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100 border-b">
                                <th class="text-left p-3">Loan ID</th>
                                <th class="text-left p-3">Amount</th>
                                <th class="text-left p-3">Date</th>
                                <th class="text-left p-3">Status</th>
                                <th class="text-left p-3">Term</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($all_loans && $all_loans->num_rows > 0): ?>
                                <?php while($loan = $all_loans->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3"><?php echo htmlspecialchars($loan['loan_id']); ?></td>
                                    <td class="p-3">$<?php echo number_format($loan['amount'], 2); ?></td>
                                    <td class="p-3"><?php echo date('M d, Y', strtotime($loan['loan_date'])); ?></td>
                                    <td class="p-3">
                                        <?php
                                            $status = $loan['loan_status'];
                                            $color = $status == 'Approved' ? 'text-green-600' : ($status == 'Rejected' ? 'text-red-600' : 'text-yellow-600');
                                        ?>
                                        <span class="font-semibold <?php echo $color; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td class="p-3"><?php echo htmlspecialchars($loan['loan_term']); ?> months</td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="p-3 text-center text-gray-500">No loan requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div class="flex w-full gap-8 my-8">
                    <button type="button" onclick="showModal('requestLoanModal')" class="flex-1 bg-maze-green-900 hover:bg-maze-green-950 text-white font-semibold text-xl px-0 py-5 rounded-xl shadow transition duration-150 ease-in-out flex items-center justify-center gap-3 min-h-[56px]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Request New Loan
                    </button>
                    <button type="button" onclick="showModal('viewLoanModal')" class="flex-1 bg-blue-700 hover:bg-blue-800 text-white font-semibold text-xl px-0 py-5 rounded-xl shadow transition duration-150 ease-in-out flex items-center justify-center gap-3 min-h-[56px]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" /></svg>
                        View Loan Details
                    </button>
                </div>

                <!-- Request New Loan Modal -->
                <div id="requestLoanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                    <div class="bg-white p-6 rounded-lg w-full max-w-md relative">
                        <button type="button" onclick="closeModal('requestLoanModal')" class="absolute top-2 right-4 text-gray-500 text-xl">&times;</button>
                        <h2 class="text-xl font-semibold mb-4">Request New Loan</h2>
                        <form method="POST" onsubmit="return validateLoanAmount()">
                            <div class="mb-4">
                                <label class="block mb-1">Loan Amount ($)</label>
                                <input type="number" name="amount" min="50" max="50000" step="1" required
                                       class="w-full border border-gray-300 p-2 rounded mb-3" 
                                       placeholder="Enter amount (min: $50, max: $50,000)"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateTerm(this.value);">
                            </div>

                            <div class="mb-4">
                                <label class="block mb-1">Term</label>
                                <input type="text" name="term_display" readonly
                                       class="w-full border border-gray-300 p-2 rounded mb-4 bg-gray-50" 
                                       placeholder="Term will be determined by amount">
                            </div>

                            <button type="submit" name="request_loan" 
                                    class="w-full bg-maze-green-900 text-white px-4 py-2 rounded hover:bg-maze-green-950">
                                Submit Request
                            </button>
                        </form>
                    </div>
                </div>

                <!-- View Loan Details Modal -->
                <div id="viewLoanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                    <div class="bg-white p-6 rounded-lg w-full max-w-md relative">
                        <button onclick="closeModal('viewLoanModal')" class="absolute top-2 right-4 text-gray-500 text-xl">&times;</button>
                        <h2 class="text-xl font-semibold mb-4">Loan Details</h2>
                        <?php if ($active_loan): ?>
                            <p><strong>Amount:</strong> $<?php echo number_format($active_loan['amount'], 2); ?></p>
                            <p><strong>Monthly Payment:</strong> $<?php echo ($active_loan && $active_loan['loan_term'] > 0 && isset($active_loan['original_amount'])) ? number_format($active_loan['original_amount'] / $active_loan['loan_term'], 2) : 'N/A'; ?></p>
                            <p><strong>Remaining Term:</strong> <?php echo ($active_loan && isset($active_loan['remaining_term'])) ? $active_loan['remaining_term'] . ' months' : 'N/A'; ?></p>
                            <p><strong>End Date:</strong> <?php echo ($active_loan && $active_loan['loan_term'] > 0) ? date('M d, Y', strtotime($active_loan['loan_date'] . ' + ' . $active_loan['loan_term'] . ' months')) : 'N/A'; ?></p>
                            <p><strong>Tax:</strong> $<?php echo number_format($active_loan['loan_tax'], 2); ?></p>
                        <?php else: ?>
                            <p class="text-gray-500">No active loan found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Loan History -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Loan Payment History</h3>
                    <div class="space-y-4">
                        <?php if ($payment_history && $payment_history->num_rows > 0): ?>
                            <?php while($payment = $payment_history->fetch_assoc()): ?>
                                <div class="flex items-center bg-white border rounded-lg p-3">
                                    <div class="w-6 mr-4">
                                        <?php if ($payment['type_of_transaction'] === 'Loan Payment'): ?>
                                            <!-- Red minus icon for loan payments -->
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 text-red-500">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7l-7 7-7-7" />
                                            </svg>
                                        <?php else: ?>
                                            <!-- Green plus icon for loan disbursement -->
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 text-green-500">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7l-7-7-7 7" />
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($payment['date']); ?></div>
                                        <div class="flex items-center space-x-2">
                                            <span><?php echo htmlspecialchars($payment['type_of_transaction']); ?></span>
                                            <?php if ($payment['type_of_transaction'] === 'Loan Payment'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-right font-bold <?php echo $payment['type_of_transaction'] === 'Loan Payment' ? 'text-red-500' : 'text-green-500'; ?>">
                                        <?php echo $payment['type_of_transaction'] === 'Loan Payment' ? '-' : '+'; ?> $<?php echo number_format($payment['amount'], 2); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-center">No payment history found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Loan Repayment Options -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Loan Repayment Options</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-800 mb-2">Early Repayment</h4>
                            <p class="text-sm text-gray-600 mb-2">Pay off your loan early with no penalties.</p>
                            <?php if ($active_loan): ?>
                                <p class="font-medium mb-2">Payoff amount: 
                                    <span class="text-gray-800">$<?php echo number_format($active_loan['amount'], 2); ?></span>
                                </p>
                                <form method="POST">
                                    <input type="hidden" name="payment_amount" value="<?php echo $active_loan['amount']; ?>">
                                    <button type="submit" name="make_payment" 
                                            class="bg-blue-600 text-white py-2 px-4 rounded-md w-full">
                                        Pay in Full
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-gray-500">No active loan to pay off.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="x9uv2XfitCRhlpoY7ssgb";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
</body>
</html> 