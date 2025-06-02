<?php
session_start();
if(!isset($_SESSION['loginid']) || strtolower($_SESSION['type_of_user'] ?? '') !== 'admin') {
    header('location:login.php');
    exit;
}

$con = new mysqli('localhost','root','','websitedb');

// 1. Financial Overview - Daily/Weekly/Monthly deposits vs withdrawals
$deposits_withdrawals = $con->query("
    SELECT 
        DATE(date) as day,
        SUM(CASE WHEN type_of_transaction = 'Deposit' THEN amount ELSE 0 END) as deposits,
        SUM(CASE WHEN type_of_transaction = 'Withdraw' THEN amount ELSE 0 END) as withdrawals
    FROM mono_acc_transaction 
    WHERE Validated = 1
    GROUP BY DATE(date)
    ORDER BY day DESC
    LIMIT 30
");

// 2. Loan Status Distribution
$loan_status = $con->query("
    SELECT 
        loan_status,
        COUNT(*) as count
    FROM loan 
    GROUP BY loan_status
");

// 3. User Type Distribution
$user_types = $con->query("
    SELECT 
        type_of_user,
        COUNT(*) as count
    FROM super_user 
    GROUP BY type_of_user
");

// 4. Transaction Volume by Time of Day
$hourly_volume = $con->query("
    SELECT 
        HOUR(date) as hour,
        COUNT(*) as transaction_count
    FROM mono_acc_transaction 
    WHERE Validated = 1
    GROUP BY HOUR(date)
    ORDER BY hour
");

// 5. Pending Transactions Queue
$pending_queue = $con->query("
    SELECT 
        CASE
            WHEN type_of_transaction = 'Withdrawal' THEN 'Withdraw'
            ELSE type_of_transaction
        END as transaction_type,
        COUNT(*) as count
    FROM mono_acc_transaction 
    WHERE Validated = 0
    GROUP BY transaction_type
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'maze-green': {
                            DEFAULT: '#0F3E2A',
                            light: '#1C5E40',
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
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-full md:w-64 bg-maze-green-950 text-white p-4 md:p-6">
            <div class="flex items-center mb-8">
                <a href="admin_dashboard.php" class="flex items-center cursor-pointer">
                    <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold">TRUST Finance</h1>
                </a>
            </div>
            
            <nav class="space-y-2">
                <!-- Dashboard Section -->
                <a href="admin_dashboard.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Dashboard
                </a>

                <!-- Client Management -->
                <a href="manager_home.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 0 1 8 0zM3 20a6 6 0 0 1 12 0v1H3v-1z"></path>
                    </svg>
                    Create User
                </a>

                <a href="AdminManageclients.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 1 0 0 5.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197M13 7a4 4 0 1 0-8 0 4 4 0 0 0 8 0z"></path>
                    </svg>
                    Clients
                </a>

                <a href="validate_clients.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Validate Clients
                </a>

                <!-- Loan Management Section -->
                <div class="pt-4 pb-2">
                    <p class="text-gray-400 text-xs uppercase font-semibold pl-3">Loan Management</p>
                </div>

                <a href="activeloanpage.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path>
                    </svg>
                    Loan Requests
                </a>

                <a href="activeloanpages.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Active Loans
                </a>

                <!-- Profile Section -->
                <div class="pt-4 pb-2">
                    <p class="text-gray-400 text-xs uppercase font-semibold pl-3">Profile</p>
                </div>

                <a href="admin_profile.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Profile
                </a>

                <a href="admin_report.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path>
                    </svg>
                    Report
                </a>

                <div class="pt-4">
                    <a href="logout.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-auto">
            <div class="max-w-6xl mx-auto">
                <!-- Financial Overview Section -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-maze-green mb-4">Financial Overview</h2>
                    <div class="bg-white shadow rounded-lg p-4">
                        <h3 class="text-base font-medium text-gray-900 mb-3">Daily Deposits vs Withdrawals</h3>
                        <div style="height: 200px;">
                            <canvas id="depositsWithdrawalsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Loan Analysis Section -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-maze-green mb-4">Loan Analysis</h2>
                    <div class="bg-white shadow rounded-lg p-4">
                        <h3 class="text-base font-medium text-gray-900 mb-3">Loan Status Distribution (All Time)</h3>
                        <div style="height: 200px;">
                            <canvas id="loanStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- User Analysis Section -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-maze-green mb-4">User Analysis</h2>
                    <div class="bg-white shadow rounded-lg p-4">
                        <h3 class="text-base font-medium text-gray-900 mb-3">User Type Distribution</h3>
                        <div style="height: 200px;">
                            <canvas id="userTypesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Transaction Analysis Section -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-maze-green mb-4">Transaction Analysis</h2>
                    <div class="bg-white shadow rounded-lg p-4">
                        <h3 class="text-base font-medium text-gray-900 mb-3">Pending Transactions Queue</h3>
                        <div style="height: 200px;">
                            <canvas id="pendingQueueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare data for charts
        const depositsWithdrawalsData = {
            labels: [],
            deposits: [],
            withdrawals: []
        };

        const loanStatusData = {
            labels: [],
            data: [],
            colors: ['#10B981', '#F59E0B', '#EF4444']
        };

        const userTypesData = {
            labels: [],
            data: [],
            colors: ['#4F46E5', '#10B981', '#F59E0B']
        };

        const pendingQueueData = {
            labels: [],
            data: [],
            colors: ['#4F46E5', '#10B981', '#EF4444', '#F59E0B']
        };

        // Process PHP data for charts
        <?php
        // Deposits vs Withdrawals
        while($row = $deposits_withdrawals->fetch_assoc()) {
            echo "depositsWithdrawalsData.labels.push('" . $row['day'] . "');";
            echo "depositsWithdrawalsData.deposits.push(" . $row['deposits'] . ");";
            echo "depositsWithdrawalsData.withdrawals.push(" . $row['withdrawals'] . ");";
        }

        // Loan Status
        while($row = $loan_status->fetch_assoc()) {
            echo "loanStatusData.labels.push('" . $row['loan_status'] . "');";
            echo "loanStatusData.data.push(" . $row['count'] . ");";
        }

        // User Types
        while($row = $user_types->fetch_assoc()) {
            echo "userTypesData.labels.push('" . $row['type_of_user'] . "');";
            echo "userTypesData.data.push(" . $row['count'] . ");";
        }

        // Pending Queue
        while($row = $pending_queue->fetch_assoc()) {
            echo "pendingQueueData.labels.push('" . $row['transaction_type'] . "');";
            echo "pendingQueueData.data.push(" . $row['count'] . ");";
        }
        ?>

        // Create charts with fixed height
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        };

        // 1. Deposits vs Withdrawals Chart
        new Chart(document.getElementById('depositsWithdrawalsChart'), {
            type: 'line',
            data: {
                labels: depositsWithdrawalsData.labels,
                datasets: [
                    {
                        label: 'Deposits',
                        data: depositsWithdrawalsData.deposits,
                        borderColor: '#10B981',
                        tension: 0.1
                    },
                    {
                        label: 'Withdrawals',
                        data: depositsWithdrawalsData.withdrawals,
                        borderColor: '#EF4444',
                        tension: 0.1
                    }
                ]
            },
            options: chartOptions
        });

        // 2. Loan Status Chart
        new Chart(document.getElementById('loanStatusChart'), {
            type: 'pie',
            data: {
                labels: loanStatusData.labels,
                datasets: [{
                    data: loanStatusData.data,
                    backgroundColor: loanStatusData.colors
                }]
            },
            options: chartOptions
        });

        // 3. User Types Chart
        new Chart(document.getElementById('userTypesChart'), {
            type: 'pie',
            data: {
                labels: userTypesData.labels,
                datasets: [{
                    data: userTypesData.data,
                    backgroundColor: userTypesData.colors
                }]
            },
            options: chartOptions
        });

        // 4. Pending Queue Chart
        new Chart(document.getElementById('pendingQueueChart'), {
            type: 'bar',
            data: {
                labels: pendingQueueData.labels,
                datasets: [{
                    data: pendingQueueData.data,
                    backgroundColor: pendingQueueData.colors
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 