<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
    <style>
        body {
            background: #f2f2f2;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            width: 350px;
        }
        h2 { text-align: center; color: #333333; margin-bottom: 25px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; color: #555555; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 10px; margin-bottom: 20px;
            border: 1px solid #cccccc; border-radius: 5px; box-sizing: border-box;
        }
        input[type="submit"] {
            width: 100%; background-color: #007bff; color: white;
            padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;
        }
        input[type="submit"]:hover { background-color: #0056b3; }
        .register-link { text-align: center; margin-top: 15px; }
        .register-link a { color: #007bff; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
        .alert { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

        <?php
        if (isset($_GET['pesan']) && $_GET['pesan'] == 'berhasil_daftar') {
            echo '<div class="alert">Daftar berhasil ditambahkan!</div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="alert" style="background:#f8d7da;color:#721c24;">'.htmlspecialchars($_GET['error']).'</div>';
        }
        ?>
        <h2>Login Admin</h2>
        <form method="POST" action="proses_login_admin.php">
            <label for="email">Email:</label>
            <input type="text" name="email" required>

            <label for="password">Password:</label>
            <input type="password" name="password" required>

            <input type="submit" value="Login">
        </form>
        <div class="register-link">
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
        </div>
    </div>
</body>
</html>
