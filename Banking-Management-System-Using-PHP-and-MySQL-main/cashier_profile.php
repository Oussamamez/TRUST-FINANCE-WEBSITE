<?php
session_start();
if(!isset($_SESSION['loginid']) || strtolower($_SESSION['type_of_user'] ?? '') !== 'cashier') {
    header('location:login.php');
    exit;
}

$con = new mysqli('localhost','root','','websitedb');

// Get cashier information from super_user table
$loginid = $_SESSION['loginid'];
$cashier = $con->query("SELECT * FROM super_user WHERE superuser_id='$loginid' AND type_of_user='Cashier'")->fetch_assoc();

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = $con->real_escape_string($_POST['email']);
    $phone = $con->real_escape_string($_POST['phone']);
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address (e.g., example@domain.com)";
    } 
    // Phone number validation (exactly 10 digits)
    elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error_message = "Phone number must be exactly 10 digits.";
    }
    else {
        // Update cashier information in super_user table
        $update = $con->query("UPDATE super_user SET email='$email', phone_number='$phone' WHERE superuser_id='$loginid'");
        
        if ($update) {
            $success_message = "Profile updated successfully!";
            // Refresh cashier data
            $cashier = $con->query("SELECT * FROM super_user WHERE superuser_id='$loginid' AND type_of_user='Cashier'")->fetch_assoc();
        } else {
            $error_message = "Failed to update profile. Please try again.";
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
                <a href="cashier_report.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Report
                </a>
                <a href="cashier_profile.php" class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
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
                <!-- Profile Header -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Manage Profile</h2>
                    <p class="text-gray-600">View and update your personal information</p>
                </div>

                <?php if($success_message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if($error_message): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
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
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($cashier['first_name'] . ' ' . $cashier['family_name']); ?></h3>
                            <div class="flex flex-wrap justify-center md:justify-start gap-2">
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs">Verified</span>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Cashier</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Information Tab -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="border-b">
                        <div class="py-4 px-6 text-center border-b-2 border-maze-green-900 font-medium text-maze-green-900">
                            Personal Information
                        </div>
                    </div>
                    <!-- Personal Information Tab Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" value="<?php echo htmlspecialchars($cashier['first_name']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" value="<?php echo htmlspecialchars($cashier['family_name']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" value="<?php echo htmlspecialchars($cashier['email']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" value="<?php echo htmlspecialchars($cashier['phone_number']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="text" value="<?php echo htmlspecialchars($cashier['date_of_birth']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Superuser ID</label>
                                    <input type="text" value="<?php echo htmlspecialchars($cashier['superuser_id']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Update Profile Information</h3>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Left Column -->
                            <div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($cashier['email']); ?>" 
                                           class="w-full p-2 border rounded-md" required
                                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                           title="Please enter a valid email address (e.g., example@domain.com)">
                                    <p class="text-sm text-gray-500 mt-1">Format: example@domain.com</p>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($cashier['phone_number']); ?>" 
                                           class="w-full p-2 border rounded-md" required
                                           pattern="\d{10}"
                                           maxlength="10"
                                           title="Please enter exactly 10 digits">
                                    <p class="text-sm text-gray-500 mt-1">Enter exactly 10 digits (e.g., 1234567890)</p>
                                </div>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-4">
                            <button type="button" onclick="window.location.reload()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">
                                Cancel
                            </button>
                            <button type="submit" name="update_profile" class="px-4 py-2 bg-maze-green-900 text-white rounded-md">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 