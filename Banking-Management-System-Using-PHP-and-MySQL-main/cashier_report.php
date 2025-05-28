<?php
session_start();
if(!isset($_SESSION['loginid'])){ header('location:login.php'); exit; }
$con = new mysqli('localhost','root','','websitedb');

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
  <title>Finance Bank - Cashier Report</title>
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
        <h1 class="text-xl font-bold"><a href="cashier_dashboard.php">TRUST Finance</a></h1>
      </div>
      <nav class="space-y-2">
        <a href="cashier_dashboard.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
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
        <a href="cashier_report.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
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
    <div class="flex-1 p-8 overflow-auto">
      <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow border border-maze-green/20">
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-2xl font-bold text-maze-green">📆 Cashier Report</h1>
          <div class="flex gap-2">
            <button onclick="downloadCSV()" class="bg-white text-maze-green border border-maze-green px-6 py-3 text-lg rounded hover:bg-maze-green light hover:text-white font-semibold">⬇️ CSV</button>
          </div>
        </div>
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