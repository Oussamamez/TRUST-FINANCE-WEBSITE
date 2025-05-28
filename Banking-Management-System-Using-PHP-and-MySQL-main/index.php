<?php 
session_start();

if (isset($_SESSION['loginid'])) {
    // Redirect based on user type
    switch($_SESSION['type_of_user']) {
        case 'client':
            header("Location: clientDashboard.php");
            break;
        case 'admin':
            header("Location: manager_home.php");
            break;
        case 'cashier':
            header("Location: cashier_dashboard.php");
            break;
        default:
            header("Location: login.php");
    }
} else {
    header("Location: login.php");
}
exit();
?>
