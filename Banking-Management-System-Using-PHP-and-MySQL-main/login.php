<?php
session_start();
$error = '';
$con = new mysqli('localhost','root','','websitedb');

// Check if the account was frozen and redirected from clientloans.php
if (isset($_GET['frozen']) && $_GET['frozen'] == 'true') {
    $error = "Your account has been permanently frozen due to an overdue loan.";
    // Exit after displaying error for redirected frozen accounts
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $con->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Server-side email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check admin/cashier (super_user)
        $res = $con->query("SELECT * FROM super_user WHERE email='$email' LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['suPassword'])) {
                // Check the user's status (for super_user - assuming Status column exists here too)
                 // Although the request was for client accounts, keeping this check here for completeness if needed later
                 if (isset($user['Status']) && $user['Status'] == 0) {
                     $error = "This superuser account is frozen."; // Or a different message
                     exit();
                 } else {
                    $_SESSION['loginid'] = $user['superuser_id'];
                    $_SESSION['type_of_user'] = strtolower($user['type_of_user']);
                    if ($_SESSION['type_of_user'] === 'admin') {
                        header('Location: admin_profile.php');
                        exit;
                    } elseif ($_SESSION['type_of_user'] === 'cashier') {
                        header('Location: cashier_index.php');
                        exit;
                    }
                 }
            } else {
                $error = 'Invalid password.';
            }
        } else {
            // Check client (normaluser)
            $res2 = $con->query("SELECT n.*, ua.Status FROM normaluser n JOIN useraccount ua ON n.national_identifier_number = ua.national_identifier_number WHERE n.email='$email' LIMIT 1");

            if ($res2 && $res2->num_rows > 0) {
                $client = $res2->fetch_assoc();

                if (!isset($client['uPassword'])) {
                    $error = 'Password field missing for this user. Please check your database.';
                } elseif (empty($client['uPassword'])) {
                    $error = 'No password set for this user. Please reset your password.';
                } elseif (password_verify($password, $client['uPassword'])) {
                    // Check the account status from the useraccount table (now included in the $client result)
                    $account_status = $client['Status'] ?? 1; // Default to 1 (active) if status is not found

                    if ($account_status == 0) {
                        // Account is frozen (Status is 0)
                        $error = "Your account has been frozen. Please contact support.";
                        // Prevent login by not proceeding to set session variables or redirect
                    } elseif ($account_status == 2) {
                        // Account is pending validation (Status is 2)
                        $error = "Your account is pending admin validation. Please wait for approval.";
                    } else {
                        // Account is active (Status is 1), proceed with login
                        $_SESSION['loginid'] = $client['national_identifier_number'];
                        $_SESSION['type_of_user'] = 'client';
                        // Clear chat history and remove chatbase script before redirecting
                        echo "<script>
                            // Remove existing chatbase script
                            const existingScript = document.getElementById('x9uv2XfitCRhlpoY7ssgb');
                            if (existingScript) {
                                existingScript.remove();
                            }
                            // Clear chatbase if it exists
                            if (window.chatbase) {
                                window.chatbase('reset');
                                // Remove chatbase from window object
                                delete window.chatbase;
                            }
                            // Clear any stored chat history
                            localStorage.removeItem('chatbase_history');
                            sessionStorage.removeItem('chatbase_history');
                        </script>";
                        header('Location: clientdashboard.php');
                        exit;
                    }
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'User not found.';
            }
        }
    }
}

// Check if redirected from signup with pending validation message
if (isset($_GET['pending_validation']) && $_GET['pending_validation'] == 'true') {
    $error = "Registration successful! Your account is pending admin validation.";
}

$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Bank - Login</title>
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
<body class="bg-maze-green-950 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
        <div class="mb-8 flex items-center justify-center">
            <div class="w-10 h-10 flex items-center justify-center bg-white rounded-full mr-3 shadow">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-maze-green-900">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-maze-green-950">TRUST BANK</h1>
        </div>
        <h2 class="text-xl font-bold text-maze-green mb-6 text-center">Sign In</h2>
        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-5">
            <div>
                <label for="email" class="block text-maze-green-950 mb-1 font-medium">Email</label>
                <input id="email" name="email" type="email" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green" required
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       title="Please enter a valid email address (e.g., example@domain.com)">
            </div>
            <div>
                <label for="password" class="block text-maze-green-950 mb-1 font-medium">Password</label>
                <div class="relative">
                    <input id="password" name="password" type="password" class="w-full p-3 border border-maze-green-900 rounded-md bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maze-green pr-10" required>
                    <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">
                         <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="w-full bg-maze-green-950 hover:bg-maze-green-900 text-white font-semibold py-3 rounded-md transition-all duration-200">Login</button>
        </form>
        <div class="flex items-center justify-center gap-2 mt-8">
            <p class="text-base text-maze-green-900">Don't have an account( Client ) ?</p>
            <a href="signup.php" class="text-base font-semibold text-maze-green underline">Sign Up</a>
        </div>
    </div>
    <script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.97 0-9-4.03-9-9 0-1.657.402-3.22 1.125-4.575M6.7 6.7A6.978 6.978 0 003 12c0 3.866 3.134 7 7 7 1.657 0 3.22-.402 4.575-1.125M17.3 17.3A6.978 6.978 0 0021 12c0-3.866-3.134-7-7-7-1.657 0-3.22.402-4.575 1.125" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />'; // Closed eye icon
        } else {
            passwordInput.type = 'password';
            eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />'; // Open eye icon
        }
    }
    </script>
</body>
</html>
