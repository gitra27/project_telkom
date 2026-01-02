<?php
include "../config.php";

// Destroy all session variables
session_destroy();

// Redirect to superadmin login page
header("Location: login_superadmin.php");
exit;
?>
