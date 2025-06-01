<?php
// You can add your PHP backend logic here for handling registration
$error = "";
$success = "";
$con = new mysqli('localhost','root','','websitedb');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $con->real_escape_string($_POST['first_name']);
    $family_name = $con->real_escape_string($_POST['family_name']);
    $email = $con->real_escape_string($_POST['email']);
    $phone_number = $con->real_escape_string($_POST['phone_number']);
    $date_of_birth = $con->real_escape_string($_POST['date_of_birth']);
    $national_id = $con->real_escape_string($_POST['national_id']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Calculate age from date of birth
    $birth_date = new DateTime($date_of_birth);
    $today = new DateTime('today');
    $age = $birth_date->diff($today)->y;

    // Server-side validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address (e.g., example@domain.com).";
    } elseif (!preg_match('/^\d{10}$/', $phone_number)) {
        $error = "Phone number must be exactly 10 digits.";
    } elseif (!preg_match('/^\d{18}$/', $national_id)) {
        $error = "National ID must be exactly 18 digits.";
    } elseif ($age < 18) {
        $error = "You must be at least 18 years old to register.";
    } elseif (strlen($password) < 8 || preg_match_all('/[0-9]/', $password) < 2) {
        $error = "Password must be at least 8 characters long and contain at least 2 numbers.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    }
    else {
        // Check if National ID already exists
        $nin_check = $con->query("SELECT national_identifier_number FROM normaluser WHERE national_identifier_number = '$national_id'");
        if ($nin_check && $nin_check->num_rows > 0) {
            $error = "This National ID is already registered. Please check your National ID.";
        } else {
            // Check if email already exists
            $email_check = $con->query("SELECT email FROM normaluser WHERE email = '$email'");
            if ($email_check && $email_check->num_rows > 0) {
                $error = "This email is already registered. Please use a different email address.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Start transaction
                $con->begin_transaction();
                
                try {
                    // Insert into normaluser table
                    $sql_user = "INSERT INTO normaluser (national_identifier_number, family_name, first_name, email, phone_number, date_of_birth, uPassword, superuser_id) 
                                VALUES ('$national_id', '$family_name', '$first_name', '$email', '$phone_number', '$date_of_birth', '$hashedPassword', NULL)";

                    if (!$con->query($sql_user)) {
                        throw new Exception("Error creating user: " . $con->error);
                    }

                    // Generate unique RIP
                    $rip = 'RIP' . $national_id;
                    
                    // Insert into useraccount table with status 2 (Pending) and NULL superuserid
                    $sql_acc = "INSERT INTO useraccount (RIP, balance, national_identifier_number, Status, superuser_id) 
                               VALUES ('$rip', 0.00, '$national_id', 2, NULL)";
                    
                    if (!$con->query($sql_acc)) {
                        throw new Exception("Error creating account: " . $con->error);
                    }

                    // If everything is successful, commit the transaction
                    $con->commit();
                    
                    // Set success message in session
                    session_start();
                    $_SESSION['registration_success'] = "Registration successful! Please wait for admin validation to login.";
                    
                    // Redirect to login page
                    header("Location: login.php");
                    exit;
                    
                } catch (Exception $e) {
                    // If there's an error, rollback the transaction
                    $con->rollback();
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRUST FINANCE Sign Up</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-maze-green-950 font-[Inter] min-h-screen flex items-center justify-center">
    <div class="w-full max-w-2xl bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-8 flex flex-col justify-center">
            <div class="mb-8 flex items-center justify-center">
                <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                    </svg>    
                </div>
                <h1 class="text-2xl font-bold text-maze-green-950">TRUST FINANCE</h1>
            </div>
            <h2 class="text-2xl font-bold text-maze-green mb-6 text-center">Client Registration</h2>
            <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-5">
                <div>
                    <label for="national-id" class="block text-maze-green-950 mb-1 font-medium">National ID (NIN) *</label>
                    <input id="national-id" name="national_id" type="text" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green" required
                           pattern="\d{18}"
                           maxlength="18"
                           title="Please enter exactly 18 digits for your National ID">
                    <p class="text-sm text-gray-500 mt-1">Must be exactly 18 digits</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first-name" class="block text-maze-green-950 mb-1 font-medium">First Name *</label>
                        <input id="first-name" name="first_name" type="text" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green" required>
                    </div>
                    <div>
                        <label for="family-name" class="block text-maze-green-950 mb-1 font-medium">Family Name *</label>
                        <input id="family-name" name="family_name" type="text" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green" required>
                    </div>
                </div>
                <div>
                    <label for="email" class="block text-maze-green-950 mb-1 font-medium">Email *</label>
                    <input id="email" name="email" type="email" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                           title="Please enter a valid email address (e.g., example@domain.com)">
                    <p class="text-sm text-gray-500 mt-1">Must be a valid email address (e.g., example@domain.com)</p>
                </div>
                <div>
                    <label for="phone-number" class="block text-maze-green-950 mb-1 font-medium">Phone Number *</label>
                    <input id="phone-number" name="phone_number" type="tel" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green" value="<?php echo isset($phone_number) ? htmlspecialchars($phone_number) : ''; ?>" required
                           pattern="\d{10}"
                           maxlength="10"
                           title="Please enter exactly 10 digits.">
                    <p class="text-sm text-gray-500 mt-1">Must be exactly 10 digits (e.g., 1234567890)</p>
                </div>
                <div>
                    <label for="date-of-birth" class="block text-maze-green-950 mb-1 font-medium">Date of Birth *</label>
                    <input id="date-of-birth" name="date_of_birth" type="date" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green" value="<?php echo isset($date_of_birth) ? htmlspecialchars($date_of_birth) : ''; ?>" required>
                    <p class="text-sm text-gray-500 mt-1">You must be at least 18 years old to register.</p>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-maze-green-950 mb-1 font-medium">Password *</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green pr-10" required
                               pattern="^(?=.*\d.*\d)[A-Za-z\d]{8,}$"
                               title="Password must be at least 8 characters with at least 2 numbers.">
                        <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-4.03 9-9 9S3 17 3 12 7.03 3 12 3s9 4.03 9 9z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Must be at least 8 characters with at least 2 numbers.</p>
                </div>
                <div class="mb-4">
                    <label for="confirm-password" class="block text-maze-green-950 mb-1 font-medium">Confirm Password *</label>
                    <div class="relative">
                        <input id="confirm-password" name="confirm_password" type="password" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green pr-10" required
                               minlength="8"
                               title="Please confirm your password.">
                        <button type="button" onclick="togglePassword('confirm-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg id="eye-confirm-password" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-4.03 9-9 9S3 17 3 12 7.03 3 12 3s9 4.03 9 9z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-maze-green-950 hover:bg-maze-green-900 text-white font-semibold py-3 rounded-md transition-all duration-200">
                    Register Client
                </button>
            </form>
            <div class="flex items-center justify-center gap-2 mt-8">
                <p class="text-base text-maze-green-900">Already have an account? </p>
                <a href="login.php" class="text-base font-semibold text-maze-green underline">Sign In</a>
            </div>
        </div>
    </div>
    <script>
    function togglePassword(id, btn) {
        const input = document.getElementById(id);
        const svg = btn.querySelector('svg');
        if (input.type === 'password') {
            input.type = 'text';
            svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.97 0-9-4.03-9-9 0-1.657.402-3.22 1.125-4.575M6.7 6.7A6.978 6.978 0 003 12c0 3.866 3.134 7 7 7 1.657 0 3.22-.402 4.575-1.125M17.3 17.3A6.978 6.978 0 0021 12c0-3.866-3.134-7-7-7-1.657 0-3.22.402-4.575 1.125" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />';
        } else {
            input.type = 'password';
            svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-4.03 9-9 9S3 17 3 12 7.03 3 12 3s9 4.03 9 9z"/>';
        }
    }
    </script>
</body>
</html>