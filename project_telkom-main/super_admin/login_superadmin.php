<?php
session_start();

// Hardcode Superadmin (tidak masuk database)
$superadmin_user = "superadmin";
$superadmin_pass = "telkom123"; // Boleh diganti

if (isset($_POST['login'])) {
    $u = trim($_POST['username']);
    $p = trim($_POST['password']);

    if ($u === $superadmin_user && $p === $superadmin_pass) {
        $_SESSION['superadmin'] = true;
        header("Location: dashboard_superadmin.php");
        exit();
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: Inter, Arial;
        }
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .btn-telkom {
            background: #ff0033;
            color: white;
            font-weight: 600;
        }
        .btn-telkom:hover {
            background: #e6002d;
            color: white;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center vh-100">

<div class="card p-4" style="width:350px;">
    <h4 class="text-center mb-3" style="color:#ff0033;font-weight:700">
        Superadmin Login
    </h4>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger py-2"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" class="form-control mb-2" required>

        <label>Password</label>
        <input type="password" name="password" class="form-control mb-3" required>

        <button class="btn btn-telkom w-100" name="login">Masuk</button>
    </form>
</div>

</body>
</html>