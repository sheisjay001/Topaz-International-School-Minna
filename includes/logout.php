<?php
session_start();

$redirect_url = '../login.php'; // Default fallback

// Check for explicit type parameter
if (isset($_GET['type'])) {
    if ($_GET['type'] === 'student') {
        $redirect_url = '../student_login.php';
    } elseif ($_GET['type'] === 'staff' || $_GET['type'] === 'admin') {
        $redirect_url = '../login.php';
    }
} elseif (isset($_SESSION['role'])) {
    // Fallback to session role if type not provided
    if ($_SESSION['role'] === 'student') {
        $redirect_url = '../student_login.php';
    } elseif ($_SESSION['role'] === 'staff' || $_SESSION['role'] === 'admin') {
        $redirect_url = '../login.php';
    }
}

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to the appropriate login page
header("Location: " . $redirect_url);
exit();
?>
