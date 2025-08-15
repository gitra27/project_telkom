<?php
session_start();

$role = $_SESSION['role'] ?? null;

$_SESSION = [];
session_unset();
session_destroy();

if ($role === 'admin') {
    header("Location: login_admin.php?pesan=logout");
} else {
    header("Location: index.php?pesan=logout");
}
exit();
