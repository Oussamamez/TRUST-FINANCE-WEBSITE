<?php
session_start();
if(!isset($_SESSION['loginid']) || (strtolower($_SESSION['type_of_user'] ?? '') !== 'admin')) {
    header('location:login.php');
    exit;
}
$con = new mysqli('localhost','root','','websitedb');

// Handle search and sort for Active Loans
$search_term = $con->real_escape_string($_GET['search'] ?? '');
$sort_by = $con->real_escape_string($_GET['sort'] ?? 'newest');

$active_loans_condition = 'WHERE l.loan_status = \'Approved\'';
if (!empty($search_term)) {
     $active_loans_condition .= " AND (n.first_name LIKE '%$search_term%' OR n.family_name LIKE '%$search_term%' OR l.loan_id LIKE '%$search_term%')";
}

$sql_order = 'ORDER BY l.loan_date DESC'; // Default sort
if ($sort_by === 'oldest') {
    $sql_order = 'ORDER BY l.loan_date ASC';
} elseif ($sort_by === 'amount-high') {
    $sql_order = 'ORDER BY l.amount DESC';
} elseif ($sort_by === 'amount-low') {
    $sql_order = 'ORDER BY l.amount ASC';
}

// Fetch active loans based on filters
$active_loans = $con->query("SELECT l.*, n.first_name, n.family_name, n.email, n.phone_number, ua.balance 
    FROM loan l 
    JOIN useraccount ua ON l.RIP = ua.RIP 
    JOIN normaluser n ON ua.national_identifier_number = n.national_identifier_number 
    $active_loans_condition
    $sql_order");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Bank - Active Loans</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Updated configuration to include Tailwind colors
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

        // JavaScript for toggling loan details
        function toggleLoanDetails(loanId) {
            const detailsRow = document.getElementById(`details-${loanId}`);
            const mainRow = document.getElementById(`main-${loanId}`);
            
            if (detailsRow.classList.contains('hidden')) {
                detailsRow.classList.remove('hidden');
                mainRow.classList.add('bg-gray-100'); // Highlight the main row when details are open
            } else {
                detailsRow.classList.add('hidden');
                mainRow.classList.remove('bg-gray-100'); // Remove highlight when details are closed
            }
        }

        // Trigger form submission on search input keyup after a delay or on change
        const searchInput = document.querySelector('input[name="search"]');
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500); // Submit 500ms after user stops typing
        });

    </script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-full md:w-64 bg-maze-green-950 text-white p-4 md:p-6 min-h-screen">
            <div class="flex items-center mb-8">
                <a href="manager_home.php" class="flex items-center cursor-pointer">
                    <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold">TRUST Finance</h1>
                </a>
            </div>
                        
            <nav class="space-y-2">
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

                <a href="activeloanpages.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
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

                <!-- Report Section -->
                <a href="admin_report.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
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
        <div class="flex-1 p-8">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Active Loans</h2>

                 <!-- Filter Options -->
                <form method="GET" action="activeloanpages.php" class="flex flex-wrap gap-3 mb-6">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="search"
                            placeholder="Search by name or ID..." 
                            class="w-full py-2 px-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-maze-green"
                            value="<?php echo htmlspecialchars($search_term); ?>"
                        >
                    </div>
                    <select name="sort" onchange="this.form.submit()" class="border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-maze-green">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="amount-high" <?php echo $sort_by === 'amount-high' ? 'selected' : ''; ?>>Amount (High to Low)</option>
                        <option value="amount-low" <?php echo $sort_by === 'amount-low' ? 'selected' : ''; ?>>Amount (Low to High)</option>
                    </select>
                     <?php /* Add a hidden submit button or rely on JS change */ ?>
                     <input type="submit" class="hidden"/>
                </form>

                 <!-- Active Loans Table -->
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100 border-b">
                                    <th class="text-left p-3">Loan ID</th>
                                <th class="text-left p-3">Name</th>
                                    <th class="text-left p-3">Amount</th>
                                    <th class="text-left p-3">Start Date</th>
                                    <th class="text-left p-3">Tax</th>
                                    <th class="text-left p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($active_loans->num_rows > 0): ?>
                                    <?php while($loan = $active_loans->fetch_assoc()): ?>
                                    <tr id="main-active-<?php echo $loan['loan_id']; ?>" class="border-b hover:bg-gray-50 cursor-pointer" onclick="toggleLoanDetails('active-<?php echo $loan['loan_id']; ?>')">
                                        <td class="p-3"><?php echo htmlspecialchars($loan['loan_id']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['family_name']); ?></td>
                                        <td class="p-3">$<?php echo number_format($loan['amount'], 2); ?></td>
                                        <td class="p-3"><?php echo date('M d, Y', strtotime($loan['loan_date'])); ?></td>
                                        <td class="p-3">$<?php echo number_format($loan['loan_tax'], 2); ?></td>
                                        <td class="p-3">
                                            <span id="status-active-<?php echo $loan['loan_id']; ?>" class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($loan['loan_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                     <!-- Expanded Details for Active Loan -->
                                    <tr id="details-active-<?php echo $loan['loan_id']; ?>" class="hidden">
                                        <td colspan="6" class="p-4 bg-gray-50">
                                            <div class="grid grid-cols-2 gap-8">
                                                <div>
                                                    <h3 class="font-bold mb-3">Loan Details</h3>
                                                    <div class="grid grid-cols-2 gap-y-2">
                                                        <p class="text-gray-600">Approved Amount:</p>
                                                        <p>$<?php echo number_format($loan['amount'], 2); ?></p>
                                                        <p class="text-gray-600">Term:</p>
                                                        <p><?php echo htmlspecialchars($loan['loan_term'] ?? 'N/A'); ?> months</p>
                                                        <p class="text-gray-600">Monthly Payment:</p>
                                                        <p>$<?php
                                                            $monthly_payment = 'N/A';
                                                            if (($loan['amount'] ?? 0) > 0 && ($loan['loan_term'] ?? 0) > 0) {
                                                                $monthly_payment = number_format(($loan['amount'] / $loan['loan_term']), 2);
                                                            }
                                                            echo $monthly_payment;
                                                        ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <h3 class="font-bold mb-3">Information</h3>
                                                    <div class="grid grid-cols-2 gap-y-2">
                                                        <p class="text-gray-600">Rip:</p>
                                                        <p><?php echo htmlspecialchars($loan['RIP']); ?></p>
                                                        <p class="text-gray-600">Name:</p>
                                                        <p><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['family_name']); ?></p>
                                                        <p class="text-gray-600">Email:</p>
                                                        <p><?php echo htmlspecialchars($loan['email']); ?></p>
                                                        <p class="text-gray-600">Phone:</p>
                                                        <p><?php echo htmlspecialchars($loan['phone_number']); ?></p>
                                                        <p class="text-gray-600">Current Balance:</p>
                                                        <p>$<?php echo number_format($loan['balance'], 2); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php /* Add Approved By info if available */ ?>
                                             <?php if ($loan['loan_status'] === 'Approved' && isset($loan['approved_by'])): ?>
                                                <div class="flex items-center mt-4">
                                                    <div class="border-l pl-6 ml-6">
                                                        <h3 class="font-bold mb-2">Approved By</h3>
                                                        <p class="text-gray-800"><?php echo htmlspecialchars($loan['approved_by']); ?></p>
                                                         <?php /* Role and Date/Time not available in current DB schema */ ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-3 text-center text-gray-500">No active loans</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                 </div>
            </div>
        </div>
    </div>
</body>
</html> 