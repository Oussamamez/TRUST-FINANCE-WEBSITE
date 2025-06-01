<?php
session_start();
if(!isset($_SESSION['loginid'])){ header('location:login.php'); exit; }
$con = new mysqli('localhost','root','','websitedb');
$national_id = $_SESSION['loginid'];

// Get user's RIP
$rip = $con->query("SELECT RIP FROM useraccount WHERE national_identifier_number = '$national_id'")->fetch_assoc()['RIP'] ?? null;
if (!$rip) {
    echo "<div style='color:red;padding:2em;'>Account not found. Please contact support.</div>";
    exit;
}

// Filtering
$where = "RIP='$rip' AND type_of_transaction IN ('Deposit', 'Withdraw')";
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$type = $_GET['type'] ?? 'all';

if ($date_from) $where .= " AND date >= '$date_from'";
if ($date_to) $where .= " AND date <= '$date_to'";
if ($type && $type !== 'all') $where .= " AND LOWER(type_of_transaction) = '".strtolower($type)."'";

$transactions = $con->query("SELECT * FROM mono_acc_transaction WHERE $where ORDER BY date DESC, transaction_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Bank - Transaction</title>
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
        <div class="w-64 bg-maze-green-950 text-white p-6">
            <div class="flex items-center mb-8">
                <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                    <!-- ...icon svg... -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold">TRUST Finance</h1>
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
                <!-- Filter Section -->
                <form class="bg-white rounded-lg shadow p-6 mb-8 flex flex-col md:flex-row md:items-end gap-4" method="get">
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Date From</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-maze-green-900">
                    </div>
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Date To</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-maze-green-900">
                    </div>
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Type</label>
                        <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-maze-green-900">
                            <option value="all" <?php if($type=='all') echo 'selected'; ?>>All</option>
                            <option value="deposit" <?php if($type=='deposit') echo 'selected'; ?>>Deposit</option>
                            <option value="withdraw" <?php if($type=='withdraw') echo 'selected'; ?>>Withdraw</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="bg-maze-green-900 hover:bg-maze-green-950 text-white font-semibold px-8 py-2 rounded-md shadow transition">Filter</button>
                    </div>
                    <div>
                        <button type="button" onclick="downloadCSV()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-2 rounded-md shadow transition">Download CSV</button>
                    </div>
                </form>

                <div class="space-y-4">
                    <!-- Transaction Items -->
                    <?php if($transactions && $transactions->num_rows > 0): ?>
                        <?php while($row = $transactions->fetch_assoc()): ?>
                            <?php
                                // Determine counterparty RIP for display
                                $displayRip = $row['RIP'];
                                // Ensure amount is always float for formatting
                                $amount = floatval($row['amount']);
                            ?>
                            <div class="flex items-center bg-white border rounded-lg p-3">
                                <?php 
                                $transactionType = strtolower($row['type_of_transaction']);
                                $isWithdrawal = ($transactionType == 'withdraw');
                                $validationStatus = ($row['Validated'] ?? 0) == 1 ? 'Confirmed' : 'Pending';
                                $statusColor = ($row['Validated'] ?? 0) == 1 ? 'text-green-600' : 'text-yellow-600';
                                ?>
                                <div class="flex-grow">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['date']); ?></div>
                                    <div class="text-sm text-gray-500">ID: <?php echo htmlspecialchars($row['transaction_id']); ?></div>
                                    <div class="flex items-center space-x-2">
                                        <span>RIP: <?php echo htmlspecialchars($displayRip); ?></span>
                                        <!-- Add hidden span for transaction type -->
                                        <span class="transaction-type hidden"><?php echo htmlspecialchars($transactionType); ?></span>
                                        <!-- Update this icon based on transaction type -->
                                        <?php
                                        if($isWithdrawal): ?>
                                            <!-- Red minus icon for outgoing -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                            </svg>
                                        <?php else: ?>
                                            <!-- Green plus icon for incoming -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm <?php echo $statusColor; ?>"><?php echo $validationStatus; ?></div>
                                </div>
                                <div class="text-right font-bold <?php
                                    echo $isWithdrawal ? 'text-red-500' : 'text-green-500';
                                ?>">
                                    $ <?php echo number_format($amount,2); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="bg-white border rounded-lg p-4 text-gray-500 text-center">No transactions found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- الكود الخاص ب chatbot connection -->
    <script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="x9uv2XfitCRhlpoY7ssgb";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
<script>
    function downloadCSV() {
        console.log("Download CSV button clicked.");
        // Change selector to target the transaction item divs
        const transactionItems = document.querySelectorAll('.max-w-4xl .flex.items-center.bg-white.border.rounded-lg.p-3'); 

        if (transactionItems.length === 0) {
            console.error("No transaction items found.");
            alert("No transactions found to download.");
            return;
        }

        let csv = [];
        
        // Manually define headers based on the visible information
        csv.push('"Date","ID","RIP","Type","Status","Amount"');

        // Iterate through transaction items and extract data
        transactionItems.forEach(item => {
            try {
                const date = item.querySelector('div:first-child').innerText.trim();
                const id = item.querySelector('div:nth-child(2)').innerText.replace('ID:', '').trim();
                const rip = item.querySelector('div:nth-child(3) span:first-child').innerText.replace('RIP:', '').trim();
                const status = item.querySelector('div:nth-child(4)').innerText.trim();
                const type = item.querySelector('span.transaction-type').innerText.trim(); // Get the transaction type

                // Refined selector to specifically get the amount div
                const amountElement = item.querySelector('div.text-right.font-bold');
                let amount = '';
                if (amountElement) {
                    amount = amountElement.innerText.replace('$', '').replace(',', '').trim();
                }

                // Include the type in the CSV row
                csv.push(`"${date}","${id}","${rip}","${type}","${status}","${amount}"`);
            } catch (e) {
                console.error("Error processing transaction item:", item, e);
            }
        });

        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'transactions_report.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        console.log("CSV download initiated.");
    }
</script>
</body>
</html>
