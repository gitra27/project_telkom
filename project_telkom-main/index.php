<?php
include 'config.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Jika belum login, redirect ke halaman demo
header("Location: demo.html");
exit;
?>
