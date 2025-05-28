<?php
session_start();
if(!isset($_SESSION['loginid']) || $_SESSION['type_of_user'] !== 'admin'){ 
    header('location:login.php'); 
    exit; 
}

$con = new mysqli('localhost','root','','websitedb');
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Handle new user creation (client, admin, or cashier)
$create_error = $create_success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug information
    error_log("Form submitted: " . print_r($_POST, true));
    
    // Validate required fields
    $required_fields = ['account_type', 'first_name', 'last_name', 'email', 'date_of_birth', 'phone_number', 'password', 'confirm_password'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $create_error = "Please fill in all required fields: " . implode(", ", $missing_fields);
    } else {
        $account_type = $_POST['account_type'];
        $first_name = $con->real_escape_string($_POST['first_name']);
        $family_name = $con->real_escape_string($_POST['last_name']);
        $email = $con->real_escape_string($_POST['email']);
        $date_of_birth = $con->real_escape_string($_POST['date_of_birth']);
        $phone_number = $con->real_escape_string($_POST['phone_number']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $admin_id = $_SESSION['loginid'];

        // Get bank name for staff
        $bank_query = $con->query("SELECT bank_name FROM super_user WHERE superuser_id = '$admin_id'");
        $bank_row = $bank_query->fetch_assoc();
        $bank_name = $bank_row['bank_name'];

        // Calculate age from date of birth
        $birth_date = new DateTime($date_of_birth);
        $today = new DateTime('today');
        $age = $birth_date->diff($today)->y;

        if ($password !== $confirm_password) {
            $create_error = "Passwords do not match!";
        }
        elseif ($age < 18) {
            $create_error = "User must be at least 18 years old.";
        }
        elseif (strlen($password) < 8) {
            $create_error = "Password must be at least 8 characters long.";
        }
        elseif (preg_match_all('/[0-9]/', $password) < 2) {
            $create_error = "Password must contain at least 2 numbers.";
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $create_error = "Invalid email format.";
        }
        elseif (!preg_match('/^\d{10}$/', $phone_number)) {
            $create_error = "Phone number must be exactly 10 digits.";
        }
        else {
            // Check if email already exists
            $email_check = $con->query("SELECT email FROM normaluser WHERE email = '$email' UNION SELECT email FROM super_user WHERE email = '$email'");
            if ($email_check && $email_check->num_rows > 0) {
                $create_error = "This email is already registered. Please use a different email address.";
            }
            else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                if ($account_type === 'client') {
                    error_log("Processing client creation...");
                    if (!isset($_POST['national_id']) || empty($_POST['national_id'])) {
                        $create_error = "National ID is required for client accounts.";
                    } else {
                        $national_id = $con->real_escape_string($_POST['national_id']);
                        if (!preg_match('/^\d{18}$/', $national_id)) {
                            $create_error = "National ID must be exactly 18 numeric characters.";
                        } else {
                            // Check if national ID already exists
                            $nin_check = $con->query("SELECT national_identifier_number FROM normaluser WHERE national_identifier_number = '$national_id'");
                            if ($nin_check && $nin_check->num_rows > 0) {
                                $create_error = "This National ID is already registered.";
                            } else {
                                try {
                                    // Start transaction
                                    $con->begin_transaction();
                                    error_log("Starting transaction for client creation...");
                                    
                                    // Insert into normaluser table
                                    $insert_user_sql = "INSERT INTO normaluser (national_identifier_number, family_name, first_name, email, phone_number, date_of_birth, uPassword, superuser_id) 
                                                      VALUES ('$national_id', '$family_name', '$first_name', '$email', '$phone_number', '$date_of_birth', '$hashed_password', '$admin_id')";
                                    error_log("Executing SQL: " . $insert_user_sql);
                                    
                                    $insert_user = $con->query($insert_user_sql);
                                    
                                    if (!$insert_user) {
                                        throw new Exception("Error creating client: " . $con->error);
                                    }
                                    
                                    // Generate unique RIP
                                    $rip = 'RIP' . $national_id;
                                    
                                    // Insert into useraccount table
                                    $sql_acc = "INSERT INTO useraccount (RIP, balance, national_identifier_number, Status, superuser_id)
                                                VALUES ('$rip', 0.00, '$national_id', 1, '$admin_id')";
                                    error_log("Executing SQL: " . $sql_acc);
                                    
                                    if (!$con->query($sql_acc)) {
                                        throw new Exception("Error creating account: " . $con->error);
                                    }
                                    
                                    // If everything is successful, commit the transaction
                                    $con->commit();
                                    error_log("Transaction committed successfully");
                                    $create_success = "Client created successfully! Account number: " . $rip;
                                    
                                } catch (Exception $e) {
                                    // If there's an error, rollback the transaction
                                    $con->rollback();
                                    error_log("Error in client creation: " . $e->getMessage());
                                    $create_error = $e->getMessage();
                                }
                            }
                        }
                    }
                } else {
                    // Handle staff creation (Admin/Cashier)
                    if (!isset($_POST['superuser_type']) || empty($_POST['superuser_type'])) {
                        $create_error = "Please select staff type (Admin or Cashier).";
                    } else {
                        $superuser_type = $_POST['superuser_type'];
                        
                        // Generate unique superuser_id
                        $superuser_id = uniqid('SU', true);
                        
                        // Insert into super_user table
                        $insert_staff = $con->query("INSERT INTO super_user (superuser_id, family_name, first_name, phone_number, date_of_birth, type_of_user, suPassword, email, bank_name) 
                                                   VALUES ('$superuser_id', '$family_name', '$first_name', '$phone_number', '$date_of_birth', '$superuser_type', '$hashed_password', '$email', '$bank_name')");
                        
                        if ($insert_staff) {
                            $create_success = ucfirst($superuser_type) . " created successfully! Staff ID: " . $superuser_id;
                        } else {
                            $create_error = "Error creating " . $superuser_type . ": " . $con->error;
                        }
                    }
                }
            }
        }
    }
    
    // Debug information
    if ($create_error) {
        error_log("Error: " . $create_error);
    }
    if ($create_success) {
        error_log("Success: " . $create_success);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Bank - Create User</title>
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

        // Show/hide fields based on account type
        function toggleAccountFields() {
            const accountType = document.querySelector('input[name="account_type"]:checked').value;
            const clientFields = document.getElementById('client-fields');
            const superuserFields = document.getElementById('superuser-fields');
            
            if (accountType === 'client') {
                clientFields.classList.remove('hidden');
                superuserFields.classList.add('hidden');
                // Make national_id required for client
                document.getElementById('national-id').required = true;
                // Remove required from superuser_type
                document.querySelectorAll('input[name="superuser_type"]').forEach(radio => {
                    radio.required = false;
                });
            } else {
                clientFields.classList.add('hidden');
                superuserFields.classList.remove('hidden');
                // Remove required from national_id
                document.getElementById('national-id').required = false;
                // Make superuser_type required
                document.querySelectorAll('input[name="superuser_type"]').forEach(radio => {
                    radio.required = true;
                });
            }
        }

        // Toggle password visibility
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>`;
            } else {
                passwordInput.type = 'password';
                icon.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>`;
            }
        }

        // Validate age on date input
        function validateAge(input) {
            const birthDate = new Date(input.value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (age < 18) {
                input.setCustomValidity('User must be at least 18 years old');
            } else {
                input.setCustomValidity('');
            }
        }

        // Call toggleAccountFields on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleAccountFields();
        });
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-maze-green-950 text-white p-6 fixed h-screen overflow-y-auto">
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
                <a href="manager_home.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'manager_home.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 0 1 8 0zM3 20a6 6 0 0 1 12 0v1H3v-1z"></path>
                    </svg>
                    Create User
                </a>

                <a href="AdminManageclients.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'AdminManageclients.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 1 0 0 5.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197M13 7a4 4 0 1 0-8 0 4 4 0 0 0 8 0z"></path>
                    </svg>
                    Clients
                </a>

                <a href="validate_clients.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'validate_clients.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Validate Clients
                </a>

                <!-- Loan Management Section -->
                <div class="pt-4 pb-2">
                    <p class="text-gray-400 text-xs uppercase font-semibold pl-3">Loan Management</p>
                </div>

                <a href="activeloanpage.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'activeloanpage.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path>
                    </svg>
                    Loan Requests
                </a>

                <a href="activeloanpages.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'activeloanpages.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Active Loans
                </a>

                <!-- Profile Section -->
                <div class="pt-4 pb-2">
                    <p class="text-gray-400 text-xs uppercase font-semibold pl-3">Profile</p>
                </div>
                <a href="admin_profile.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_profile.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Profile
                </a>

                <!-- Report Section -->
                <a href="admin_report.php" class="flex items-center p-3 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_report.php' ? 'bg-maze-green-900' : 'hover:bg-maze-green-900'; ?> rounded-md cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path>
                    </svg>
                    Report
                </a>

                <div class="pt-4">
                    <a href="logout.php" class="flex items-center p-3 hover:bg-maze-green-900 rounded-md cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"></path>
                        </svg>
                        Logout
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-64 p-8">
            <div class="max-w-xl mx-auto bg-maze-green-950 p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-6 text-center text-white">Create New User Account</h2>
                <?php if ($create_error): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md"><?php echo $create_error; ?></div>
                <?php elseif ($create_success): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md"><?php echo $create_success; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-6 bg-gray-800 p-4 rounded-lg">
                        <label class="block text-white mb-4 font-bold text-lg">Select Account Type *</label>
                        <div class="flex gap-6">
                            <label class="flex items-center cursor-pointer bg-gray-700 p-4 rounded-lg flex-1 hover:bg-gray-600 transition-colors">
                                <input type="radio" name="account_type" value="client" class="form-radio text-maze-green-900" required checked onchange="toggleAccountFields()">
                                <div class="ml-3">
                                    <span class="text-white font-medium block">Client</span>
                                    <span class="text-gray-400 text-sm">Create a new client account</span>
                                </div>
                            </label>
                            <label class="flex items-center cursor-pointer bg-gray-700 p-4 rounded-lg flex-1 hover:bg-gray-600 transition-colors">
                                <input type="radio" name="account_type" value="superuser" class="form-radio text-maze-green-900" required onchange="toggleAccountFields()">
                                <div class="ml-3">
                                    <span class="text-white font-medium block">Staff</span>
                                    <span class="text-gray-400 text-sm">Create admin or cashier account</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first-name" class="block text-sm font-medium text-white mb-1">First Name</label>
                            <input id="first-name" name="first_name" type="text" required class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green">
                        </div>
                        <div>
                            <label for="last-name" class="block text-sm font-medium text-white mb-1">Family Name</label>
                            <input id="last-name" name="last_name" type="text" required class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="email" class="block text-sm font-medium text-white mb-1">Email *</label>
                        <input id="email" name="email" type="email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green">
                        <p class="text-sm text-gray-400 mt-1">Must be a valid email address (e.g., example@domain.com)</p>
                    </div>

                    <div class="mt-4">
                        <label for="phone" class="block text-sm font-medium text-white mb-1">Phone Number (10 digits)</label>
                        <input id="phone" name="phone_number" type="tel" required pattern="\d{10}" maxlength="10" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green">
                        <p class="text-sm text-gray-400 mt-1">Must be exactly 10 digits</p>
                    </div>

                    <div class="mt-4">
                        <label for="date-of-birth" class="block text-sm font-medium text-white mb-1">Date of Birth *</label>
                        <input id="date-of-birth" name="date_of_birth" type="date" required 
                               class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green"
                               onchange="validateAge(this)">
                        <p class="text-sm text-gray-400 mt-1">User must be at least 18 years old.</p>
                    </div>

                    <!-- Client-specific Fields -->
                    <div id="client-fields">
                        <div class="mt-4">
                            <label for="national-id" class="block text-sm font-medium text-white mb-1">National ID (18 digits) *</label>
                            <input id="national-id" name="national_id" type="text" pattern="\d{18}" maxlength="18" required 
                                   class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green">
                            <p class="text-sm text-gray-400 mt-1">Must be exactly 18 digits</p>
                        </div>
                    </div>

                    <!-- Superuser-specific Fields -->
                    <div id="superuser-fields" class="hidden">
                        <div class="mt-4">
                            <label class="block text-white mb-2 font-bold text-lg">Staff Type *</label>
                            <div class="flex gap-6">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="superuser_type" value="admin" class="form-radio text-maze-green-900" required>
                                    <span class="ml-2 text-white font-medium">Admin</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="superuser_type" value="cashier" class="form-radio text-maze-green-900" required>
                                    <span class="ml-2 text-white font-medium">Cashier</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Password Fields -->
                    <div class="mt-4">
                        <label for="password" class="block text-sm font-medium text-white mb-1">Password *</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required 
                                   class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green"
                                   pattern="^(?=.*\d.*\d)[A-Za-z\d]{8,}$"
                                   title="Password must be at least 8 characters with at least 2 numbers">
                            <button type="button" onclick="togglePasswordVisibility('password', 'password-icon')" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <span id="password-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </span>
                            </button>
                        </div>
                        <p class="text-sm text-gray-400 mt-1">Must be at least 8 characters with at least 2 numbers</p>
                    </div>

                    <div class="mt-4">
                        <label for="confirm-password" class="block text-sm font-medium text-white mb-1">Confirm Password *</label>
                        <div class="relative">
                            <input id="confirm-password" name="confirm_password" type="password" required 
                                   class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green">
                            <button type="button" onclick="togglePasswordVisibility('confirm-password', 'confirm-password-icon')" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <span id="confirm-password-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="w-full bg-maze-green-900 hover:bg-maze-green-950 text-white font-bold py-3 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-maze-green focus:ring-opacity-50 transition duration-150">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
