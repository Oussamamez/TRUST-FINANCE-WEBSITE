<?php
session_start();
if(!isset($_SESSION['loginid']) || strtolower($_SESSION['type_of_user'] ?? '') !== 'cashier') {
    header('location:login.php');
    exit;
}

$con = new mysqli('localhost','root','','websitedb');

// Get current date
$current_date = date('Y-m-d');

// Fetch cashier's name
$cashier_name = '';
if (isset($_SESSION['loginid'])) {
    $loginid = $con->real_escape_string($_SESSION['loginid']);
    $name_query = $con->query("SELECT first_name FROM super_user WHERE superuser_id = '$loginid'");
    if ($name_query && $name_query->num_rows > 0) {
        $cashier_name = $name_query->fetch_assoc()['first_name'];
    }
}

// Fetch statistics
$total_deposits_today = $con->query("SELECT SUM(amount) AS total_deposits FROM mono_acc_transaction WHERE DATE(date) = '$current_date' AND type_of_transaction = 'Deposit' AND Validated = 1")->fetch_assoc()['total_deposits'] ?? 0;
$total_withdrawals_today = $con->query("SELECT SUM(amount) AS total_withdrawals FROM mono_acc_transaction WHERE DATE(date) = '$current_date' AND type_of_transaction = 'Withdraw' AND Validated = 1")->fetch_assoc()['total_withdrawals'] ?? 0;
$pending_transactions_count = $con->query("SELECT COUNT(*) AS pending_count FROM mono_acc_transaction WHERE Validated = 0")->fetch_assoc()['pending_count'] ?? 0;

// Assuming 'loans' table exists with 'status' column and 'Active' status
$active_loans_count = $con->query("SELECT COUNT(*) AS active_count FROM loan WHERE loan_status = 'Approved'")->fetch_assoc()['active_count'] ?? 0;

// Fetch transaction type distribution
$transaction_types = $con->query("SELECT 
    type_of_transaction,
    COUNT(*) as count
FROM (
    SELECT type_of_transaction FROM mono_acc_transaction
    UNION ALL
    SELECT 'Transfer' as type_of_transaction FROM transfer
) as combined_transactions
GROUP BY type_of_transaction
");

$transaction_type_data = [];
$transaction_type_labels = [];
$transaction_type_colors = [
    'Deposit' => 'rgb(34, 197, 94)',    // Green
    'Withdraw' => 'rgb(239, 68, 68)',   // Red
    'Transfer' => 'rgb(59, 130, 246)'   // Blue
];

while($row = $transaction_types->fetch_assoc()) {
    $transaction_type_data[] = $row['count'];
    $transaction_type_labels[] = $row['type_of_transaction'];
}

// Fetch latest 5 transactions (deposits/withdrawals and transfers)
// Combining transactions from mono_acc_transaction and transfer tables
$latest_transactions = $con->query("(
    SELECT transaction_id, RIP, type_of_transaction, amount, date FROM mono_acc_transaction
)
UNION ALL
(
    SELECT transaction_id, from_RIP AS RIP, 'Transfer' AS type_of_transaction, amount, date FROM transfer
)
ORDER BY date DESC
LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="cashier_dashboard.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="cashier_index.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
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
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Cashier Dashboard</h1>
                <p class="text-gray-600">Welcome back<?php echo $cashier_name ? ', ' . htmlspecialchars($cashier_name) : ''; ?>! Today is <?php echo date('l, F j, Y'); ?></p>

                <!-- Stats Boxes -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 mt-6">
                    <!-- Total Deposits Today -->
                    <div class="bg-green-100 p-4 rounded-lg shadow">
                        <div class="text-sm font-medium text-green-700">Total Deposits Today</div>
                        <div class="text-2xl font-bold text-green-900">$<?php echo number_format($total_deposits_today, 2); ?></div>
                    </div>
                    <!-- Total Withdrawals Today -->
                    <div class="bg-red-100 p-4 rounded-lg shadow">
                        <div class="text-sm font-medium text-red-700">Total Withdrawals Today</div>
                        <div class="text-2xl font-bold text-red-900">$<?php echo number_format($total_withdrawals_today, 2); ?></div>
                    </div>
                    <!-- Pending Transactions -->
                    <div class="bg-yellow-100 p-4 rounded-lg shadow">
                        <div class="text-sm font-medium text-yellow-700">Pending Transactions</div>
                        <div class="text-2xl font-bold text-yellow-900"><?php echo $pending_transactions_count; ?></div>
                    </div>
                    <!-- Active Loans -->
                     <div class="bg-blue-100 p-4 rounded-lg shadow">
                        <div class="text-sm font-medium text-blue-700">Active Loans</div>
                        <div class="text-2xl font-bold text-blue-900"><?php echo $active_loans_count; ?></div>
                    </div>
                </div>

                <!-- Transaction Type Distribution Chart -->
                <div class="bg-white shadow rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Transaction Type Distribution</h2>
                    <div class="w-full max-w-md mx-auto">
                        <canvas id="transactionTypeChart"></canvas>
                    </div>
                </div>

                <!-- Latest Transactions -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Latest Transactions</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RIP</th>
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($transaction['RIP']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo ($transaction['type_of_transaction'] == 'Deposit' || ($transaction['type_of_transaction'] == 'Transfer' && $transaction['amount'] > 0)) ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo htmlspecialchars($transaction['type_of_transaction']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo ($transaction['type_of_transaction'] == 'Deposit' || ($transaction['type_of_transaction'] == 'Transfer' && $transaction['amount'] > 0)) ? 'text-green-600' : 'text-red-600'; ?>">
                                                 $<?php echo number_format(abs($transaction['amount']), 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($transaction['date']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No recent transactions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Transaction Type Distribution Chart
        const transactionTypeCtx = document.getElementById('transactionTypeChart').getContext('2d');
        new Chart(transactionTypeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($transaction_type_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($transaction_type_data); ?>,
                    backgroundColor: <?php echo json_encode(array_values($transaction_type_colors)); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Transaction Types Distribution'
                    }
                }
            }
        });
    </script>
</body>
</html> 