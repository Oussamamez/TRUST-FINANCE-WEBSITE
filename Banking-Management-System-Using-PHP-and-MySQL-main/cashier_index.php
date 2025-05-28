<?php
session_start();
if(!isset($_SESSION['loginid']) || strtolower($_SESSION['type_of_user'] ?? '') !== 'cashier') {
    header('location:login.php');
    exit;
}
$con = new mysqli('localhost','root','','websitedb');

// Fetch latest 5 transactions (deposits and withdrawals)
$latest_transactions = $con->query("SELECT transaction_id, type_of_transaction, amount, date FROM mono_acc_transaction WHERE type_of_transaction = 'Deposit' OR type_of_transaction = 'Withdraw' ORDER BY date DESC LIMIT 5");

// AJAX handler for fetching transaction details
if(isset($_POST['action']) && $_POST['action'] === 'fetch' && isset($_POST['tx_code'])) {
    $tx_code = $con->real_escape_string($_POST['tx_code']);
    $tx = $con->query("SELECT * FROM mono_acc_transaction WHERE transaction_id='$tx_code'")->fetch_assoc();
    if ($tx) {
        // Join with useraccount/normaluser for more info
        $rip = $tx['RIP'];
        $acc = $con->query("SELECT * FROM useraccount WHERE RIP='$rip'")->fetch_assoc();
        $client = $acc ? $con->query("SELECT * FROM normaluser WHERE national_identifier_number='".$acc['national_identifier_number']."'")->fetch_assoc() : null;
        echo json_encode([
            'success' => true,
            'transaction' => [
                'transaction_id' => $tx['transaction_id'],
                'type' => $tx['type_of_transaction'],
                'amount' => $tx['amount'],
                'date' => $tx['date'],
                'rip' => $tx['RIP'],
                'validated' => $tx['Validated'],
                'client_id' => $acc ? $acc['national_identifier_number'] : '',
                'first_name' => $client['first_name'] ?? '',
                'last_name' => $client['family_name'] ?? '',
                'email' => $client['email'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Transaction not found.']);
    }
    exit;
}

// AJAX handler for accepting transaction
if(isset($_POST['action']) && $_POST['action'] === 'accept' && isset($_POST['tx_code'])) {
    $tx_code = $con->real_escape_string($_POST['tx_code']);
    $tx = $con->query("SELECT * FROM mono_acc_transaction WHERE transaction_id='$tx_code'")->fetch_assoc();
    if ($tx && !$tx['Validated']) {
        // Update balance
        $rip = $tx['RIP'];
        $amount = floatval($tx['amount']);
        $acc = $con->query("SELECT * FROM useraccount WHERE RIP='$rip'")->fetch_assoc();
        if ($tx['type_of_transaction'] === 'Deposit') {
            $con->query("UPDATE useraccount SET balance = balance + $amount WHERE RIP='$rip'");
        } elseif ($tx['type_of_transaction'] === 'Withdraw') {
            if ($acc && $acc['balance'] >= $amount) {
                $con->query("UPDATE useraccount SET balance = balance - $amount WHERE RIP='$rip'");
            } else {
                echo json_encode(['success' => false, 'error' => 'Insufficient balance for withdrawal.']);
                exit;
            }
        }
        $con->query("UPDATE mono_acc_transaction SET Validated=1 WHERE transaction_id='$tx_code'");
        echo json_encode(['success' => true]);
    } else if ($tx && $tx['Validated']) {
        echo json_encode(['success' => false, 'error' => 'Transaction already validated.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Transaction not found.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Transaction View</title>
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
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-full md:w-64 bg-maze-green-950 text-white p-4 md:p-6">
            <div class="flex items-center mb-8">
                <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold"><a href="cashier_dashboard.php">TRUST Finance</a></h1>
            </div>
            <nav class="space-y-2">
                <a href="cashier_dashboard.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="cashier_index.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    Transaction
                </a>
                <a href="cashier_report.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Report
                </a>
                <a href="cashier_profile.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
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
            <div class="max-w-6xl mx-auto">
                <h2 class="text-xl font-bold mb-4">Approve Deposit/Withdraw</h2>
                <!-- Transaction Code Input -->
                <form id="transactionForm" class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold mb-4">Enter Transaction Code</h2>
                    <div class="mb-4">
                        <label for="transactionCode" class="block text-sm font-medium text-gray-700">Transaction Code</label>
                        <input type="text" id="transactionCode" name="transactionCode" class="w-full p-2 border rounded-md" required>
                    </div>
                    <button type="button" id="checkTransaction" class="w-full bg-maze-green-900 text-white p-2 rounded-md">Check</button>
                </form>

                <!-- Latest Deposit/Withdrawal Transactions Table -->
                <div class="bg-white shadow rounded-lg p-6 mt-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Latest Deposit/Withdrawal Transactions</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($latest_transactions && $latest_transactions->num_rows > 0): ?>
                                    <?php while($transaction = $latest_transactions->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo ($transaction['type_of_transaction'] == 'Deposit') ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo htmlspecialchars($transaction['type_of_transaction']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo ($transaction['type_of_transaction'] == 'Deposit') ? 'text-green-600' : 'text-red-600'; ?>">
                                                 $<?php echo number_format($transaction['amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($transaction['date']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No recent deposit or withdrawal transactions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md relative">
            <button id="closeModal" class="absolute top-2 right-4 text-gray-500 text-xl">&times;</button>
            <h2 class="text-xl font-bold mb-4 text-maze-green-900">Transaction Details</h2>
            <div id="transactionDetails" class="mb-4">
                <!-- Transaction details will be dynamically inserted here -->
            </div>
            <button id="acceptTransaction" class="w-full bg-maze-green-900 hover:bg-maze-green-950 text-white font-semibold py-2 rounded">Accept Transaction</button>
        </div>
    </div>

    <script>
        let currentTxCode = null;
        document.getElementById('checkTransaction').addEventListener('click', function () {
            const transactionCode = document.getElementById('transactionCode').value.trim();
            if (!transactionCode) {
                alert('Please enter a transaction code.');
                return;
            }
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=fetch&tx_code=' + encodeURIComponent(transactionCode)
            })
            .then(res => res.json())
            .then(data => {
                const modal = document.getElementById('transactionModal');
                const details = document.getElementById('transactionDetails');
                if (data.success) {
                    currentTxCode = transactionCode;
                    details.innerHTML = `
                        <div><strong>Transaction ID:</strong> ${data.transaction.transaction_id}</div>
                        <div><strong>Type:</strong> ${data.transaction.type}</div>
                        <div><strong>Amount:</strong> $${parseFloat(data.transaction.amount).toLocaleString()}</div>
                        <div><strong>Date:</strong> ${data.transaction.date}</div>
                        <div><strong>RIP:</strong> ${data.transaction.rip}</div>
                        <div><strong>Client Name:</strong> ${data.transaction.first_name} ${data.transaction.last_name}</div>
                        <div><strong>Client Email:</strong> ${data.transaction.email}</div>
                        <div><strong>Status:</strong> ${data.transaction.validated == 1 ? '<span class="text-green-600">Accepted</span>' : '<span class="text-yellow-600">Pending</span>'}</div>
                    `;
                    document.getElementById('acceptTransaction').style.display = data.transaction.validated == 1 ? 'none' : 'block';
                    modal.classList.remove('hidden');
                } else {
                    details.innerHTML = `<div class="text-red-600">${data.error}</div>`;
                    document.getElementById('acceptTransaction').style.display = 'none';
                    modal.classList.remove('hidden');
                }
            });
        });

        document.getElementById('closeModal').addEventListener('click', function () {
            document.getElementById('transactionModal').classList.add('hidden');
        });

        document.getElementById('acceptTransaction').addEventListener('click', function() {
            if (!currentTxCode) return;
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=accept&tx_code=' + encodeURIComponent(currentTxCode)
            })
            .then(res => res.json())
            .then(data => {
                const details = document.getElementById('transactionDetails');
                if (data.success) {
                    details.innerHTML += `<div class="text-green-600 mt-2">Transaction accepted!</div>`;
                    document.getElementById('acceptTransaction').style.display = 'none';
                } else {
                    details.innerHTML += `<div class="text-red-600 mt-2">${data.error}</div>`;
                }
            });
        });
    </script>
</body>
</html>

