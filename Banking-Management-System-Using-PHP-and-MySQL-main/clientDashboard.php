<?php
session_start();
if(!isset($_SESSION['loginid'])){ header('location:login.php'); exit; }
$national_id = $_SESSION['loginid'];
$con = new mysqli('localhost','root','','websitedb');

// Fetch user info (join normaluser for name)
$user = $con->query("SELECT * FROM normaluser WHERE national_identifier_number='$national_id'")->fetch_assoc();

if (!$user) {
    echo "<div style='color:red;padding:2em;'>User not found. Please contact support or log in again.</div>";
    exit;
}

$clientName = trim(($user['first_name'] ?? '') . ' ' . ($user['family_name'] ?? ''));
if ($clientName === '') $clientName = $user['email']; // fallback to email if name is empty

// Fetch RIP from useraccount
$rip = $con->query("SELECT RIP FROM useraccount WHERE national_identifier_number = '$national_id'")->fetch_assoc()['RIP'] ?? null;

if (!$rip) {
    echo "<div style='color:red;padding:2em;'>Account not found. Please contact support.</div>";
    exit;
}

// Fetch up-to-date balance from useraccount
$balance = $con->query("SELECT balance FROM useraccount WHERE RIP = '$rip'")->fetch_assoc()['balance'] ?? 0.00;

$_SESSION['rip'] = $rip; // Store RIP in session

// Fetch latest transfers (last 5)
$transfers = $con->query("SELECT * FROM transfer WHERE from_RIP='$rip' OR to_RIP='$rip' ORDER BY transaction_id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Bank - Client Dashboard</title>
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
    <div class="flex flex-col md:flex-row min-h-screen">
        <!-- Sidebar -->
        <div class="w-full md:w-64 bg-maze-green-950 text-white p-4 md:p-6">
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
        <div class="flex-1 p-4 md:p-8">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h2 class="text-2xl font-bold mb-4 md:mb-0" id="greeting">Hi, <?php echo htmlspecialchars($clientName); ?></h2>
                <span class="text-gray-500">Today, <?php echo date('Y-m-d'); ?></span>
            </div>
            <!-- Account Section -->
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-4">Account Section</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                    <!-- Balance Box -->
                    <div class="bg-maze-green-950 text-white p-4 rounded-lg shadow-lg flex flex-col justify-between" style="min-height:auto; height:auto;">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm">Balance</span>
                            <div class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold <?php echo $balance < 0 ? 'text-red-500' : 'text-white'; ?>">
                            $<?php echo number_format($balance, 2); ?>
                        </h3>
                    </div>
                    <!-- Balance Chart -->
                    <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col justify-between" style="min-height:auto; height:auto;">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Balance History</h4>
                            <span class="text-sm text-gray-500">Last 30 Days</span>
                        </div>
                        <div class="relative" style="height: 250px; min-width: 300px;">
                            <canvas id="balanceChart"></canvas>
                        </div>
                    </div>
                    <!-- Transaction Types Doughnut Chart -->
                    <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col justify-between" style="min-height:auto; height:auto;">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Transaction Types Breakdown</h4>
                            <span class="text-sm text-gray-500">Last 30 Days</span>
                        </div>
                        <div class="relative flex flex-col items-center" style="height: 250px; min-width: 300px;">
                            <canvas id="txTypeChart" width="250" height="250"></canvas>
                            <div id="txTypeCenter" class="absolute top-1/2 left-1/2 text-center" style="transform: translate(-50%, -50%);"></div>
                        </div>
                        <div id="txTypeLegend" class="mt-4 flex flex-wrap gap-4 justify-center"></div>
                    </div>
                </div>
            </div>
            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Deposit/Withdraw Section -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Deposit / Withdraw</h3>
                    <div class="bg-white p-6 rounded-lg" id="bank-actions">
                        <form method="POST" action="client_transaction_action.php" onsubmit="return validateAmount(this);">
                            <div class="mb-4">
                                <label class="block text-sm mb-2">Amount In $</label>
                                <input type="number" name="amount" class="w-full p-2 border rounded-md" min="0.01" step="0.01" required oninput="this.value = this.value.replace(/[^0-9.]/g, '')" onkeypress="return event.charCode >= 48 && event.charCode <= 57 || event.charCode === 46">
                            </div>
                            <div class="flex gap-2 mb-4">
                                <button name="action" value="deposit" class="flex-1 bg-green-600 hover:bg-green-700 text-white p-2 rounded-md">Deposit</button>
                                <button name="action" value="withdraw" class="flex-1 bg-red-600 hover:bg-red-700 text-white p-2 rounded-md">Withdraw</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Transaction Popup Modal (JS only, not functional in PHP) -->
                <div id="transaction-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
                    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md relative">
                        <button onclick="closeTransactionModal()" class="absolute top-2 right-4 text-gray-500 text-xl">&times;</button>
                        <h2 class="text-xl font-bold mb-4 text-maze-green-900">Transaction Created</h2>
                        <div class="mb-4">
                            <div class="mb-2"><strong>Transaction Code:</strong> <span id="modal-tx-code"></span></div>
                            <div class="mb-4 text-gray-700">Please give this code to the cashier to deposit or withdraw your money. The transaction will be completed within 24 hours. Once you've received the funds, please confirm below.</div>
                        </div>
                        <button id="confirm-received-btn" class="w-full bg-maze-green-900 hover:bg-maze-green-950 text-white font-semibold py-2 rounded">Confirm Received</button>
                    </div>
                </div>
                <!-- Latest Transfers -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Latest Transfers</h3>
                    <div class="space-y-4">
                        <?php if($transfers && $transfers->num_rows > 0): ?>
                            <?php while($tx = $transfers->fetch_assoc()): ?>
                                <div class="bg-white p-4 rounded-lg flex justify-between items-center">
                                    <div>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($tx['transaction_id']); ?></p>
                                        <div class="flex items-center space-x-2">
                                            <span>
                                                <?php
                                                    if ($tx['from_RIP'] == $rip) {
                                                        echo "To: " . htmlspecialchars($tx['to_RIP']);
                                                    } else {
                                                        echo "From: " . htmlspecialchars($tx['from_RIP']);
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="font-bold">$<?php echo number_format($tx['amount'], 2); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="bg-white p-4 rounded-lg text-gray-500">No recent transfers.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Send Money -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-bold mb-4">Send Money</h3>
                    <div class="bg-white p-6 rounded-lg">
                        <form method="POST" action="client_transaction_action.php" onsubmit="return validateTransferForm(this);">
                            <div class="mb-4">
                                <label class="block text-sm mb-2">Recipient RIP</label>
                                <input type="text" name="rip" placeholder="RIP123456789" pattern="RIP\d+" title="RIP must start with 'RIP' followed by numbers" class="w-full p-2 border rounded-md" required>
                                <p class="text-xs text-gray-500 mt-1">Format: RIP followed by numbers (e.g., RIP123456789)</p>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm mb-2">Amount In $</label>
                                <input type="number" name="amount" min="0.01" step="0.01" class="w-full p-2 border rounded-md" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm mb-2">Reason</label>
                                <textarea name="transfer_reason" class="w-full p-2 border rounded-md" rows="3"></textarea>
                            </div>
                            <input type="hidden" name="action" value="transfer">
                            <button type="submit" class="w-full bg-maze-green-950 text-white p-2 rounded-md">
                                Send Money
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- JS for modal (optional, not functional in PHP) -->
    <script>
        function closeTransactionModal() {
            document.getElementById('transaction-modal').classList.add('hidden');
        }
        document.getElementById('confirm-received-btn') && (document.getElementById('confirm-received-btn').onclick = function() {
            alert('Thank you for confirming!');
            closeTransactionModal();
        });
    </script>
    <script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="x9uv2XfitCRhlpoY7ssgb";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('fetch_balance_history.php')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('balanceChart').getContext('2d');
            
            // Format dates to be more readable
            const formattedDates = data.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: formattedDates,
                    datasets: [{
                        label: 'Account Balance',
                        data: data.map(item => item.balance),
                        borderColor: '#0F3E2A',
                        backgroundColor: 'rgba(15, 62, 42, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                        pointBackgroundColor: '#0F3E2A',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 62, 42, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#0F3E2A',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                },
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 0,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        })
        .catch(error => console.error('Error loading balance history:', error));
});
</script>
<script>
// Transaction Types Doughnut Chart
fetch('fetch_transaction_types.php')
    .then(response => response.json())
    .then(data => {
        const ctx = document.getElementById('txTypeChart').getContext('2d');
        const labels = Object.keys(data);
        const values = Object.values(data);
        const total = values.reduce((a, b) => a + b, 0);
        const colors = [
            '#0F3E2A', // Deposit
            '#DC2626', // Withdraw
            '#2563EB', // Transfer
            '#059669', // Loan Repayment
            '#F59E42'  // Loan Deposit
        ];
        // Center text
        const center = document.getElementById('txTypeCenter');
        center.innerHTML = `<div class='text-2xl font-bold text-gray-800'>${total}</div><div class='text-xs text-gray-500'>Total Transactions</div>`;
        // Chart
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed} transaction${context.parsed === 1 ? '' : 's'}`;
                            }
                        }
                    }
                }
            }
        });
        // Modern legend
        const legend = document.getElementById('txTypeLegend');
        legend.innerHTML = labels.map((label, i) =>
            `<span class="flex items-center gap-2"><span style="display:inline-block;width:16px;height:16px;background:${colors[i]};border-radius:50%"></span><span class='text-sm text-gray-700'>${label}</span></span>`
        ).join('');
    })
    .catch(error => console.error('Error loading transaction types:', error));
</script>
<script>
function validateAmount(form) {
    const amount = form.amount.value;
    if (!amount || isNaN(amount) || amount <= 0) {
        alert('Please enter a valid positive number for the amount.');
        return false;
    }
    return true;
}
</script>
<script>
function validateTransferForm(form) {
    const rip = form.rip.value.trim();
    const amount = parseFloat(form.amount.value);
    
    // Validate RIP format
    if (!rip.match(/^RIP\d+$/)) {
        alert('Invalid RIP format. RIP must start with "RIP" followed by numbers (e.g., RIP123456789)');
        return false;
    }
    
    // Validate amount
    if (isNaN(amount) || amount <= 0) {
        alert('Please enter a valid amount greater than 0');
        return false;
    }
    
    return true;
}
</script>
</body>
</html>