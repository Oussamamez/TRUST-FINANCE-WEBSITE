<?php
session_start();
if(!isset($_SESSION['loginid']) || (strtolower($_SESSION['type_of_user'] ?? '') !== 'admin')) {
    header('location:login.php');
    exit;
}
$con = new mysqli('localhost','root','','websitedb');
$admin_id = $_SESSION['loginid'];
$success = $error = "";

// Fetch admin info
$admin = $con->query("SELECT * FROM super_user WHERE superuser_id='$admin_id'")->fetch_assoc();

// Handle profile update (only for updatable fields)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $con->real_escape_string($_POST['email']);
    $phone = $con->real_escape_string($_POST['phone_number']);

    // Server-side validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address (e.g., example@domain.com).";
    } elseif (!preg_match('/^\\d{10}$/', $phone)) { // Check for exactly 10 digits
        $error = "Phone number must be exactly 10 digits.";
    } else {
        $update = $con->query("UPDATE super_user SET email='$email', phone_number='$phone' WHERE superuser_id='$admin_id'");
        if ($update) {
            $success = "Profile updated successfully!";
            // Re-fetch admin data to display updated info
            $admin = $con->query("SELECT * FROM super_user WHERE superuser_id='$admin_id'")->fetch_assoc();
        } else {
            $error = "Profile update failed: " . $con->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Bank - Manage Profile</title>
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
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-full md:w-64 bg-maze-green-950 text-white p-4 md:p-6">
            <div class="flex items-center mb-8">
                <a href="manager_home.php" class="flex items-center cursor-pointer">
                    <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
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
                <a href="admin_profile.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
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
        <div class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <!-- Profile Header -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                    <div class="flex items-center gap-4">
                        <h2 class="text-2xl font-bold mb-4 md:mb-0">Manage Profile</h2>
                    </div>
                     <span class="text-gray-500">Today, <?php echo date('Y-m-d'); ?></span>
                </div>

                 <?php if($success): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Summary Card -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex flex-col md:flex-row items-center md:items-start">
                        <div class="w-24 h-24 bg-gray-200 rounded-full mb-4 md:mb-0 md:mr-6 flex items-center justify-center overflow-hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['family_name']); ?></h3>
                            <div class="flex flex-wrap justify-center md:justify-start gap-2">
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs">Verified</span>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs"><?php echo htmlspecialchars($admin['type_of_user']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Display (Read-only) -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="border-b mb-6">
                        <div class="py-4 px-6 text-center border-b-2 border-maze-green-900 font-medium text-maze-green-900">
                            Personal Information
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($admin['first_name']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($admin['family_name']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($admin['email']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                            </div>
                        </div>
                        <div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" value="<?php echo htmlspecialchars($admin['phone_number']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                            </div>
                             <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                <input type="date" value="<?php echo htmlspecialchars($admin['date_of_birth']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Admin ID</label>
                                <input type="text" value="<?php echo htmlspecialchars($admin['superuser_id']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Profile Information Form -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Update Profile Information</h3>
                    <form method="POST" action="">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" class="w-full p-2 border rounded-md" required
                                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,}"
                                           title="Please enter a valid email address (e.g., example@domain.com).">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($admin['phone_number']); ?>" class="w-full p-2 border rounded-md" required
                                           pattern="\\d{10}"
                                           maxlength="10"
                                           title="Please enter exactly 10 digits.">
                                </div>
                            </div>
                             <div>
                                <!-- Empty column to maintain grid layout -->
                             </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-4">
                            <a href="admin_dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Cancel</a>
                            <button type="submit" class="px-4 py-2 bg-maze-green-900 text-white rounded-md hover:bg-maze-green-light">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>