<?php
session_start();
if(!isset($_SESSION['loginid'])){ header('location:login.php'); exit; }
$con = new mysqli('localhost','root','','websitedb');

// Handle loan approval/rejection
if(isset($_POST['action']) && isset($_POST['loan_id'])) {
    $loan_id = $con->real_escape_string($_POST['loan_id']);
    $action = $_POST['action'];
    
    if($action === 'approve') {
        // --- Fetch loan amount and calculate term before approving ---
        $loan_details_query = $con->query("SELECT amount, RIP FROM loan WHERE loan_id = '$loan_id' LIMIT 1");
        if ($loan_details_query && $loan_details_query->num_rows > 0) {
            $loan_details = $loan_details_query->fetch_assoc();
            $amount = $loan_details['amount'];
            $rip = $loan_details['RIP'];

            // Calculate loan term based on amount (same logic as clientloans.php)
            $term = 0; // Initialize term
            if ($amount >= 50 && $amount <= 499) {
                $term = 3;
            } elseif ($amount >= 500 && $amount <= 1999) {
                $term = 6;
            } elseif ($amount >= 2000 && $amount <= 9999) {
                $term = 12;
            } elseif ($amount >= 10000 && $amount <= 50000) { // Include max limit
                $term = 24;
            }
            // Loans over 50000 should ideally not be approvable or handled differently,
            // but for now, they won't get a term based on the client-side logic.

            // Update loan status AND set the calculated term
            $update_loan_status = $con->query("UPDATE loan SET loan_status = 'Approved', loan_term = '$term' WHERE loan_id = '$loan_id'");

            // If approved, add loan amount to user's balance
            if ($update_loan_status) {
                 $con->query("UPDATE useraccount SET balance = balance + $amount WHERE RIP = '$rip'");
            }
        }
        // --- End fetch and calculate ---
    } elseif($action === 'reject') {
        $con->query("UPDATE loan SET loan_status = 'Rejected' WHERE loan_id = '$loan_id'");
    }
     // Redirect back to refresh the page after action
     header('location: activeloanpage.php');
     exit();
}

// Fetch counts for summary
$pending_count = $con->query("SELECT COUNT(*) AS count FROM loan WHERE loan_status = 'Pending'")->fetch_assoc()['count'] ?? 0;
$approved_count = $con->query("SELECT COUNT(*) AS count FROM loan WHERE loan_status = 'Approved'")->fetch_assoc()['count'] ?? 0;
$rejected_count = $con->query("SELECT COUNT(*) AS count FROM loan WHERE loan_status = 'Rejected'")->fetch_assoc()['count'] ?? 0;

// Fetch pending loan requests
$search_term = $con->real_escape_string($_GET['search'] ?? '');
$status_filter = $con->real_escape_string($_GET['status'] ?? 'all');
$sort_by = $con->real_escape_string($_GET['sort'] ?? 'newest');

$sql_condition = ''; // Start with no condition to show all loans

if (!empty($search_term)) {
    $sql_condition .= " WHERE (n.first_name LIKE '%$search_term%' OR n.family_name LIKE '%$search_term%' OR l.loan_id LIKE '%$search_term%')";
}

if ($status_filter !== 'all') {
    // If a specific status is filtered, add the condition
    $sql_condition = empty($sql_condition) ? " WHERE l.loan_status = '$status_filter'" : $sql_condition . " AND l.loan_status = '$status_filter'";
}

$sql_order = 'ORDER BY l.loan_date DESC'; // Default sort
if ($sort_by === 'oldest') {
    $sql_order = 'ORDER BY l.loan_date ASC';
} elseif ($sort_by === 'amount-high') {
    $sql_order = 'ORDER BY l.amount DESC';
} elseif ($sort_by === 'amount-low') {
    $sql_order = 'ORDER BY l.amount ASC';
}

// Fetch all loan requests based on filters
$pending_loans = $con->query("SELECT l.*, n.first_name, n.family_name, n.email, n.phone_number, ua.balance 
    FROM loan l 
    JOIN useraccount ua ON l.RIP = ua.RIP 
    JOIN normaluser n ON ua.national_identifier_number = n.national_identifier_number 
    $sql_condition
    $sql_order");

// Fetch active loans based on filters
// The active loans table should only show 'Approved' loans, regardless of status filter, 
// but can be affected by search and sort.
$active_loans_condition = 'WHERE l.loan_status = \'Approved\'';
if (!empty($search_term)) {
     $active_loans_condition .= " AND (n.first_name LIKE '%$search_term%' OR n.family_name LIKE '%$search_term%' OR l.loan_id LIKE '%$search_term%')";
}
$active_loans = $con->query("SELECT l.*, n.first_name, n.family_name, n.email, n.phone_number, ua.balance 
    FROM loan l 
    JOIN useraccount ua ON l.RIP = ua.RIP 
    JOIN normaluser n ON ua.national_identifier_number = n.national_identifier_number 
    $active_loans_condition
    $sql_order");

// Removed unused transfer logic

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Bank - Loan Requests</title>
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

        // JavaScript for toggling loan request details
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

        // Function to handle loan approval or rejection
        function handleLoanAction(loanId, action) {
            event.stopPropagation(); // Prevent row toggle when clicking buttons
            
            if(confirm(`Are you sure you want to ${action} this loan request?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="loan_id" value="${loanId}">
                    <input type="hidden" name="action" value="${action}">
                `;
                document.body.appendChild(form);
                form.submit();
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
        <!-- Sidebar with Loan Management section -->
        <div class="w-64 bg-maze-green-950 text-white p-6 fixed h-screen overflow-y-auto">
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

                <a href="activeloanpage.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
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
        <div class="flex-1 p-8 ml-64">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Loan Requests</h2>
                    <div class="flex gap-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Pending</span>
                            <span class="text-sm text-gray-500"><?php echo $pending_count; ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Approved</span>
                             <span class="text-sm text-gray-500"><?php echo $approved_count; ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">Rejected</span>
                             <span class="text-sm text-gray-500"><?php echo $rejected_count; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Filter Options -->
                <form method="GET" action="activeloanpage.php" class="flex flex-wrap gap-3 mb-6">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="search"
                            placeholder="Search by name or ID..." 
                            class="w-full py-2 px-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-maze-green"
                            value="<?php echo htmlspecialchars($search_term); ?>"
                        >
                    </div>
                    <select name="status" onchange="this.form.submit()" class="border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-maze-green">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <select name="sort" onchange="this.form.submit()" class="border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-maze-green">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="amount-high" <?php echo $sort_by === 'amount-high' ? 'selected' : ''; ?>>Amount (High to Low)</option>
                        <option value="amount-low" <?php echo $sort_by === 'amount-low' ? 'selected' : ''; ?>>Amount (Low to High)</option>
                    </select>
                     <?php /* Add a hidden submit button or rely on JS change */ ?>
                     <input type="submit" class="hidden"/>
                </form>

                <!-- Loan Requests Table -->
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100 border-b">
                            <th class="text-left p-3">Request ID</th>
                            <th class="text-left p-3">Name</th>
                                    <th class="text-left p-3">Amount</th>
                            <th class="text-left p-3">Date</th>
                            <th class="text-left p-3">Status</th>
                                    <th class="text-left p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($pending_loans->num_rows > 0): ?>
                                    <?php while($loan = $pending_loans->fetch_assoc()): ?>
                                <tr id="main-<?php echo $loan['loan_id']; ?>" class="border-b hover:bg-gray-50 cursor-pointer" onclick="toggleLoanDetails('<?php echo $loan['loan_id']; ?>')">
                                        <td class="p-3"><?php echo htmlspecialchars($loan['loan_id']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['family_name']); ?></td>
                                        <td class="p-3">$<?php echo number_format($loan['amount'], 2); ?></td>
                                        <td class="p-3"><?php echo date('M d, Y', strtotime($loan['loan_date'])); ?></td>
                                        <td class="p-3">
                                        <?php
                                            $status = $loan['loan_status'];
                                            $status_class = '';
                                            if ($status === 'Approved') {
                                                $status_class = 'bg-green-100 text-green-800';
                                            } elseif ($status === 'Rejected') {
                                                $status_class = 'bg-red-100 text-red-800';
                                            } else {
                                                $status_class = 'bg-yellow-100 text-yellow-800';
                                            }
                                        ?>
                                        <span id="status-<?php echo $loan['loan_id']; ?>" class="px-2 py-1 rounded-full text-xs <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($loan['loan_status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3" id="actions-<?php echo $loan['loan_id']; ?>">
                                         <?php if ($loan['loan_status'] === 'Pending'): ?>
                                            <div class="flex gap-2">
                                                <button onclick="handleLoanAction('<?php echo $loan['loan_id']; ?>', 'approve')" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">Approve</button>
                                                <button onclick="handleLoanAction('<?php echo $loan['loan_id']; ?>', 'reject')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Reject</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm <?php echo $loan['loan_status'] === 'Approved' ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo $loan['loan_status'] === 'Approved' ? 'Approved on ' . date('M d, Y', strtotime($loan['loan_date'])) : 'Rejected on ' . date('M d, Y', strtotime($loan['loan_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <!-- Expanded Details for Loan Request -->
                                <tr id="details-<?php echo $loan['loan_id']; ?>" class="hidden">
                                    <td colspan="6" class="p-4 bg-gray-50">
                                        <div class="grid grid-cols-2 gap-8">
                                            <div>
                                                <h3 class="font-bold mb-3">Loan Details</h3>
                                                <div class="grid grid-cols-2 gap-y-2">
                                                    <p class="text-gray-600">Requested Amount:</p>
                                                    <p>$<?php echo number_format($loan['amount'], 2); ?></p>
                                                    <p class="text-gray-600">Term:</p>
                                                    <p><?php echo htmlspecialchars($loan['loan_term'] ?? 'N/A'); ?> months</p>
                                                    <p class="text-gray-600">Monthly Payment:</p>
                                                     <p>N/A</p> <?php /* Monthly payment calculation not available in DB */ ?>
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
                                        
                                        <div class="mt-6 flex justify-end gap-3">
                                             <?php if ($loan['loan_status'] === 'Pending'): ?>
                                                <button onclick="handleLoanAction('<?php echo $loan['loan_id']; ?>', 'reject')" class="bg-white border border-red-500 text-red-500 hover:bg-red-50 px-4 py-2 rounded">Reject Request</button>
                                                <button onclick="handleLoanAction('<?php echo $loan['loan_id']; ?>', 'approve')" class="bg-maze-green text-white px-4 py-2 rounded hover:bg-maze-green-light">Approve Request</button>
                                            <?php endif; ?>
                                        </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="p-3 text-center text-gray-500">No pending loan requests</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
            </div>
        </div>
    </div>
</body>
</html>