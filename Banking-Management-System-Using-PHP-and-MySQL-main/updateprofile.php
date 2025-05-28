<?php
session_start();
if(!isset($_SESSION['loginid'])){ 
    header('location:login.php'); 
    exit; 
}

$con = new mysqli('localhost','root','','websitedb');
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

$national_id = $_SESSION['loginid'];
$success = '';
$error = '';

// Fetch current user data
$user = $con->query("SELECT * FROM normaluser WHERE national_identifier_number='$national_id'")->fetch_assoc();

if (!$user) {
    $error = "User not found. Please contact support.";
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $con->real_escape_string($_POST["email"]);
        $phone_number = $con->real_escape_string($_POST["phone_number"]);

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address (e.g., example@domain.com)";
        } 
        // Phone number validation
        elseif (!preg_match('/^\d{10}$/', $phone_number)) {
            $error = "Phone number must be exactly 10 digits";
        }
        else {
            $sql = "UPDATE normaluser SET 
                    email = '$email',
                    phone_number = '$phone_number'
                    WHERE national_identifier_number = '$national_id'";

            if ($con->query($sql)) {
                $success = "Profile updated successfully!";
                // Refresh user data
                $user = $con->query("SELECT * FROM normaluser WHERE national_identifier_number='$national_id'")->fetch_assoc();
            } else {
                $error = "Profile update failed: " . $con->error;
            }
        }
    }
}

// Fetch user info
$user = $con->query("SELECT * FROM normaluser WHERE national_identifier_number='$national_id'")->fetch_assoc();

if (!$user) {
    echo "<div style='color:red;padding:2em;'>User not found. Please contact support or log in again.</div>";
    exit;
}

$clientName = trim(($user['first_name'] ?? '') . ' ' . ($user['family_name'] ?? ''));
if ($clientName === '') $clientName = $user['email']; // fallback to email if name is empty

// Check if user is verified (has an active account)
$isVerified = $con->query("SELECT Status FROM useraccount WHERE national_identifier_number = '$national_id'")->fetch_assoc()['Status'] ?? 0;
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
        <div class="w-64 bg-maze-green-950 text-white p-6">
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
                <a href="clientdashboard.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="client_transactions.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Transaction
                </a>
                <a href="clientloans.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Loans
                </a>
                <div class="flex items-center p-3 bg-maze-green-900 rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Profile
                </div>
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
                <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                    <div class="flex items-center gap-4">
                        <h2 class="text-2xl font-bold mb-4 md:mb-0" id="greeting">Hi, <?php echo htmlspecialchars($clientName); ?></h2>
                        <?php if ($isVerified): ?>
                        <div class="flex items-center gap-2 bg-green-100 text-green-800 px-3 py-1 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium">Verified Client</span>
                        </div>
                        <?php endif; ?>
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
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['family_name']); ?></h3>
                            <div class="flex flex-wrap justify-center md:justify-start gap-2">
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs">Verified</span>
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
                                    <input type="text" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Family Name</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['family_name']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" value="<?php echo htmlspecialchars($user['phone_number']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">National ID</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['national_identifier_number']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Update Profile Information</h3>
                    <form method="POST" action="">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Left Column -->
                            <div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Family Name</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['family_name']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           class="w-full p-2 border rounded-md" required 
                                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                           title="Please enter a valid email address (e.g., example@domain.com)">
                                    <p class="text-sm text-gray-500 mt-1">Format: example@domain.com</p>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" 
                                           class="w-full p-2 border rounded-md" required 
                                           pattern="\d{10}"
                                           maxlength="10"
                                           title="Please enter exactly 10 digits">
                                    <p class="text-sm text-gray-500 mt-1">Enter exactly 10 digits (e.g., 1234567890)</p>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" class="w-full p-2 border rounded-md bg-gray-50" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-4">
                            <a href="clientdashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-maze-green-900 text-white rounded-md">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="x9uv2XfitCRhlpoY7ssgb";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
</body>
</html> 