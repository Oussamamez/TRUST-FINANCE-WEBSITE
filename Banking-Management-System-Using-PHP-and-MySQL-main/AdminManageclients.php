<?php
session_start();
if(!isset($_SESSION['loginid'])){ header('location:login.php'); exit; }
$con = new mysqli('localhost','root','','websitedb');

// Pagination settings
$clients_per_page = 10; // Number of clients to display per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $clients_per_page;

// Handle freezing/banning client account
if (isset($_GET['freeze'])) {
    $id = $con->real_escape_string($_GET['freeze']); // national_identifier_number
    
    // Find the corresponding RIP in useraccount table
    $rip_query = $con->query("SELECT RIP FROM useraccount WHERE national_identifier_number = '$id' LIMIT 1");
    if ($rip_query && $rip_query->num_rows > 0) {
        $rip = $rip_query->fetch_assoc()['RIP'];
        // Update the 'Status' to 0 (Frozen/Banned) in the useraccount table
        $update = $con->query("UPDATE useraccount SET Status = 0 WHERE RIP = '$rip'");
    }
    
    if (isset($update) && $update) {
        // Redirect back to the current page after update, preserving search and pagination
        $redirect_url = 'AdminManageclients.php?' . http_build_query(array_merge($_GET, ['freeze' => null]));
        header("Location: " . $redirect_url);
        exit;
    } else {
        // Handle error if update fails or RIP not found
        // You might want to add error handling logic here
    }
}

// Handle unfreezing/unbanning client account
if (isset($_GET['unfreeze'])) {
    $id = $con->real_escape_string($_GET['unfreeze']); // national_identifier_number
    
    // Find the corresponding RIP in useraccount table
    $rip_query = $con->query("SELECT RIP FROM useraccount WHERE national_identifier_number = '$id' LIMIT 1");
    if ($rip_query && $rip_query->num_rows > 0) {
         $rip = $rip_query->fetch_assoc()['RIP'];
        // Update the 'Status' to 1 (Active) in the useraccount table
        $update = $con->query("UPDATE useraccount SET Status = 1 WHERE RIP = '$rip'");
    }
    
    if (isset($update) && $update) {
        // Redirect back to the current page after update, preserving search and pagination
        $redirect_url = 'AdminManageclients.php?' . http_build_query(array_merge($_GET, ['unfreeze' => null]));
        header("Location: " . $redirect_url);
        exit;
    } else {
        // Handle error if update fails or RIP not found
        // You might want to add error handling logic here
    }
}

// Handle approving client account
if (isset($_GET['approve'])) {
    $id = $con->real_escape_string($_GET['approve']); // national_identifier_number
    
    // Find the corresponding RIP in useraccount table
    $rip_query = $con->query("SELECT RIP FROM useraccount WHERE national_identifier_number = '$id' LIMIT 1");
    if ($rip_query && $rip_query->num_rows > 0) {
         $rip = $rip_query->fetch_assoc()['RIP'];
        // Update the 'Status' to 1 (Active) in the useraccount table
        $update = $con->query("UPDATE useraccount SET Status = 1 WHERE RIP = '$rip'");
    }
    
    if (isset($update) && $update) {
        // Redirect back to the current page after update, preserving search and pagination
        $redirect_url = 'AdminManageclients.php?' . http_build_query(array_merge($_GET, ['approve' => null]));
        header("Location: " . $redirect_url);
        exit;
    } else {
        // Handle error if update fails or RIP not found
        // You might want to add error handling logic here
    }
}

// Handle search
$search_term = $con->real_escape_string($_GET['search'] ?? '');
$search_condition = '';
$status_filter = $con->real_escape_string($_GET['status'] ?? 'all'); // Add status filter

$where_clauses = [];

if (!empty($search_term)) {
    $where_clauses[] = "(n.first_name LIKE '%$search_term%' OR n.family_name LIKE '%$search_term%' OR n.email LIKE '%$search_term%' OR n.national_identifier_number LIKE '%$search_term%')";
}

// Adjust status filter logic
if ($status_filter !== 'all') {
    $where_clauses[] = "ua.Status = '$status_filter'";
}

$sql_condition = '';
if (!empty($where_clauses)) {
    $sql_condition = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Fetch total number of clients for pagination (with search and status filter)
$total_clients_query = $con->query("SELECT COUNT(*) AS total FROM normaluser n JOIN useraccount ua ON n.national_identifier_number = ua.national_identifier_number" . $sql_condition);
$total_clients = $total_clients_query->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_clients / $clients_per_page);

// Ensure current_page is not more than total_pages (handle empty search results or deletion of last client on page)
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $clients_per_page;
}

// Fetch clients for the current page (with search and status filter and pagination)
$clients_query = $con->query("SELECT n.*, ua.Status FROM normaluser n JOIN useraccount ua ON n.national_identifier_number = ua.national_identifier_number" . $sql_condition . " ORDER BY n.national_identifier_number DESC LIMIT $clients_per_page OFFSET $offset");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRUST Finance - Manage Clients</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

        // JavaScript for toggling client details row
        function toggleClientDetails(clientId) {
            const detailsRow = document.getElementById(`details-${clientId}`);
            const mainRow = document.getElementById(`main-${clientId}`);

            if (detailsRow.classList.contains('hidden')) {
                detailsRow.classList.remove('hidden');
                mainRow.classList.add('bg-gray-100'); // Highlight the main row when details are open
            } else {
                detailsRow.classList.add('hidden');
                mainRow.classList.remove('bg-gray-100'); // Remove highlight when details are closed
            }
        }

         // Prevent row toggle when clicking delete button
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.action-button').forEach(button => { // Changed class name
                button.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            });
        });

        // Function to handle client action (Freeze, Unfreeze, Approve)
        function handleClientAction(clientId, action) {
            event.stopPropagation(); // Prevent row toggle when clicking buttons
            
            let confirmMessage = '';
            if (action === 'freeze') {
                confirmMessage = 'Are you sure you want to freeze this client?';
            } else if (action === 'unfreeze') {
                confirmMessage = 'Are you sure you want to unfreeze this client?';
            } else if (action === 'approve') {
                 confirmMessage = 'Are you sure you want to approve this client account?';
            }

            if(confirm(confirmMessage)) {
                // Construct the base URL with current filters and pagination
                const baseUrl = 'AdminManageclients.php?' + 
                                'page=<?php echo $current_page; ?>' +
                                '&search=<?php echo urlencode($search_term); ?>' +
                                '&status=<?php echo urlencode($status_filter); ?>'; // Include status filter

                window.location.href = baseUrl + '&'+ action +'=' + clientId; // Append the action
            }
        }

    </script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-maze-green-950 text-white p-4 md:p-6 flex-shrink-0 overflow-y-auto sticky top-0 h-screen">
            <div class="flex items-center mb-8">
                <a href="admin_dashboard.php" class="flex items-center cursor-pointer">
                    <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold">TRUST Finance</h1>
                </a>
            </div>
            
            <nav class="space-y-2">
                 <!-- Dashboard Section -->
                 <a href="admin_dashboard.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
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

                <a href="AdminManageclients.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
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

                <!-- Report Section -->
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
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-6xl mx-auto bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-6">Clients List</h2>

                <!-- Search Form -->
                <form method="GET" action="AdminManageclients.php" class="flex gap-4 mb-6">
                    <input type="text" name="search" placeholder="Search clients..." class="flex-1 py-2 px-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-maze-green"
                           value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="bg-maze-green-900 text-white px-6 py-2 rounded-md hover:bg-maze-green-light">Search</button>
                </form>

                <!-- Clients Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100 border-b">
                                <th class="text-left p-3">Client ID</th>
                                <th class="text-left p-3">Name</th>
                                <th class="text-left p-3">Email</th>
                                <th class="text-left p-3">Phone</th>
                                <th class="text-left p-3">Date of Birth</th>
                                <th class="text-left p-3">Account Status</th>
                                <th class="text-left p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($clients_query->num_rows > 0): ?>
                                <?php while($row = $clients_query->fetch_assoc()): ?>
                                    <tr id="main-<?php echo $row['national_identifier_number']; ?>" class="border-b hover:bg-gray-50 cursor-pointer" onclick="toggleClientDetails('<?php echo $row['national_identifier_number']; ?>')">
                                        <td class="p-3"><?php echo htmlspecialchars($row['national_identifier_number']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['family_name']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($row['date_of_birth']); ?></td>
                                        <td class="p-3">
                                            <?php
                                                $status_text = 'Unknown';
                                                $status_class = 'bg-gray-100 text-gray-800';
                                                switch ($row['Status'] ?? 1) { // Default to 1 if Status is null
                                                    case 0:
                                                        $status_text = 'Frozen';
                                                        $status_class = 'bg-red-100 text-red-800';
                                                        break;
                                                    case 1:
                                                        $status_text = 'Active';
                                                        $status_class = 'bg-green-100 text-green-800';
                                                        break;
                                                    case 2:
                                                        $status_text = 'Pending';
                                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                }
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td class="p-3">
                                              <?php if (($row['Status'] ?? 1) == 1): // Check if status is 1 (Active) ?>
                                                <button onclick="handleClientAction('<?php echo $row['national_identifier_number']; ?>', 'freeze')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm action-button">Freeze</button>
                                              <?php elseif (($row['Status'] ?? 1) == 0): // Status is 0 (Frozen) ?>
                                                <button onclick="handleClientAction('<?php echo $row['national_identifier_number']; ?>', 'unfreeze')" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm action-button">Unfreeze</button>
                                              <?php elseif (($row['Status'] ?? 1) == 2): // Status is 2 (Pending) ?>
                                                <button onclick="handleClientAction('<?php echo $row['national_identifier_number']; ?>', 'approve')" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm action-button">Approve</button>
                                              <?php endif; ?>
                                        </td>
                                    </tr>
                                    <!-- Expanded Details for Client -->
                                    <tr id="details-<?php echo $row['national_identifier_number']; ?>" class="hidden">
                                        <td colspan="7" class="p-4 bg-gray-50">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <h3 class="font-bold mb-2">Personal Information</h3>
                                                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['family_name']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['phone_number']); ?></p>
                                                    <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($row['date_of_birth']); ?></p>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold mb-2">Account Details</h3>
                                                    <p><strong>National ID:</strong> <?php echo htmlspecialchars($row['national_identifier_number']); ?></p>
                                                     <?php
                                                        // Fetch account details for the client
                                                        $account_query = $con->query("SELECT RIP, balance FROM useraccount WHERE national_identifier_number = '". $row['national_identifier_number'] ."'");
                                                        $account = $account_query->fetch_assoc();
                                                     ?>
                                                    <?php if($account): ?>
                                                        <p><strong>RIP:</strong> <?php echo htmlspecialchars($account['RIP']); ?></p>
                                                        <p><strong>Balance:</strong> $<?php echo number_format($account['balance'] ?? 0, 2); ?></p>
                                                    <?php else: ?>
                                                        <p>No account details available.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="p-3 text-center text-gray-500">No clients found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex justify-center items-center space-x-4">
                    <?php if ($current_page > 1): ?>
                        <a href="AdminManageclients.php?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search_term); ?>&status=<?php echo urlencode($status_filter); ?>" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-200">Previous</a>
                    <?php endif; ?>

                    <span class="text-gray-700">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="AdminManageclients.php?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search_term); ?>&status=<?php echo urlencode($status_filter); ?>" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-200">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>