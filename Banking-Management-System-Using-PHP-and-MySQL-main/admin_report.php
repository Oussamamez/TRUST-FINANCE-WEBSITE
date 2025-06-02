<?php
session_start();
if(!isset($_SESSION['loginid']) || strtolower($_SESSION['type_of_user'] ?? '') !== 'admin') { // Modified session check
    header('location:login.php');
    exit;
}
$con = new mysqli('localhost','root','','websitedb');

// Handle report sending to admin
if(isset($_POST['send_report'])) {
    $from_date = $con->real_escape_string($_POST['from_date']);
    $to_date = $con->real_escape_string($_POST['to_date']);
    
    // Get transactions for the date range
    $transactions = $con->query("SELECT t.*, n.first_name, n.family_name 
        FROM transfer t 
        JOIN useraccount ua ON t.from_RIP = ua.RIP 
        JOIN normaluser n ON ua.national_identifier_number = n.national_identifier_number 
        WHERE t.date BETWEEN '$from_date' AND '$to_date'
        ORDER BY t.date DESC");
    
    // Send email to admin (you'll need to configure email settings)
    $to = "admin@trustfinance.com";
    $subject = "Admin Report - " . date('Y-m-d'); // Modified subject
    $message = "Admin Report from $from_date to $to_date\n\n"; // Modified message
    
    while($row = $transactions->fetch_assoc()) {
        $message .= "Date: " . $row['date'] . "\n";
        $message .= "Type: " . ($row['amount'] > 0 ? "Deposit" : "Withdraw") . "\n";
        $message .= "Amount: $" . abs($row['amount']) . "\n";
        $message .= "Client: " . $row['first_name'] . " " . $row['family_name'] . "\n";
        $message .= "Status: Completed\n\n";
    }
    
    mail($to, $subject, $message);
    $success_message = "Report sent successfully!"; // Modified success message
}

// Get transactions for display
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

$transactions = $con->query("(
    SELECT 
        t.transaction_id, 
        t.type_of_transaction AS type, 
        t.amount, 
        t.date, 
        t.RIP, 
        n.first_name, 
        n.family_name
    FROM mono_acc_transaction t
    JOIN useraccount ua ON t.RIP = ua.RIP
    JOIN normaluser n ON ua.national_identifier_number = n.national_identifier_number
    WHERE DATE(t.date) BETWEEN '$from_date' AND '$to_date'
)
UNION ALL
(
    SELECT 
        tf.transaction_id, 
        'Transfer' AS type,
        tf.amount, 
        tf.date, 
        tf.from_RIP AS RIP, 
        n.first_name, 
        n.family_name
    FROM transfer tf
    JOIN useraccount ua ON tf.from_RIP = ua.RIP
    JOIN normaluser n ON ua.national_identifier_number = n.national_identifier_number
    WHERE DATE(tf.date) BETWEEN '$from_date' AND '$to_date'
)
ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Finance Bank - Admin Report</title> <!-- Modified title -->
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
  </script>
</head>
<body class="bg-gray-100">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <div class="w-full md:w-64 bg-maze-green-950 text-white p-4 md:p-6">
      <div class="flex items-center mb-8">
        <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
          </svg>
        </div>
        <h1 class="text-xl font-bold"><a href="admin_dashboard.php">TRUST Finance</a></h1>
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

        <!-- Report Section -->
         <a href="admin_report.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
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
      <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow border border-maze-green/20">
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-2xl font-bold text-maze-green">📆 Admin Report</h1> <!-- Modified heading -->
          <div class="flex gap-2">
            <button onclick="downloadCSV()" class="bg-white text-maze-green border border-maze-green px-6 py-3 text-lg rounded hover:bg-maze-green light hover:text-white font-semibold">⬇️ CSV</button>
          </div>
        </div>
        <?php if(isset($success_message)): ?>
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
          </div>
        <?php endif; ?>
        <!-- Filters -->
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
          <div>
            <label class="block text-sm font-medium text-maze-green">From</label>
            <input type="date" name="from_date" value="<?php echo $from_date; ?>" class="w-full px-3 py-2 border border-maze-green rounded text-maze-green bg-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-maze-green">To</label>
            <input type="date" name="to_date" value="<?php echo $to_date; ?>" class="w-full px-3 py-2 border border-maze-green rounded text-maze-green bg-white" />
          </div>
          <div class="flex items-end">
            <button type="submit" class="w-full bg-maze-green text-white px-6 py-3 text-lg rounded hover:bg-maze-green-light font-semibold">🔍 Filter</button>
          </div>
        </form>
        <!-- Report Table -->
        <div class="overflow-x-auto">
          <table id="reportTable" class="min-w-full border border-maze-green">
            <thead class="bg-maze-green text-white">
              <tr>
                <th class="text-left px-4 py-2">Date</th>
                <th class="text-left px-4 py-2">Type</th>
                <th class="text-left px-4 py-2">Amount</th>
                <th class="text-left px-4 py-2">Client Name</th>
                <th class="text-left px-4 py-2">Status</th>
              </tr>
            </thead>
            <tbody class="text-maze-green bg-white">
              <?php if($transactions->num_rows > 0): ?>
                <?php while($row = $transactions->fetch_assoc()): ?>
                  <tr class="border-t border-maze-green/30">
                    <td class="px-4 py-2"><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                    <td class="px-4 py-2 <?php echo $row['amount'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                      <?php echo htmlspecialchars($row['type']); ?>
                    </td>
                    <td class="px-4 py-2 <?php echo $row['amount'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                      <?php echo $row['amount'] > 0 ? '+' : '-'; ?> $<?php echo number_format(abs($row['amount']), 2); ?>
                    </td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['family_name']); ?></td>
                    <td class="px-4 py-2 text-green-700">Completed</td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="px-4 py-2 text-center">No transactions found for the selected date range</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script>
    function downloadCSV() {
      const table = document.getElementById("reportTable");
      let csv = [];
      for (let row of table.rows) {
        let cols = Array.from(row.cells).map(cell => `"${cell.innerText.trim()}"`);
        csv.push(cols.join(","));
      }
      const blob = new Blob([csv.join("\n")], { type: 'text/csv' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'cashier_report.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  </script>
</body>
</html> 