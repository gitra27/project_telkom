<?php
// ENABLE ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// DESTROY ALL SESSION DATA
session_unset();
session_destroy();

// DELETE SESSION COOKIE IF EXISTS
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// REDIRECT TO LOGIN PAGE
header("Location: login_admin.php");
exit();
?>
