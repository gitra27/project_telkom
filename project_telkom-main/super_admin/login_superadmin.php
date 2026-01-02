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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Superadmin - Sistem Absensi Telkom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --telkom-primary: #e31937;
            --telkom-secondary: #ffffff;
            --telkom-accent: #ff6b35;
            --telkom-dark: #8b0000;
            --telkom-light: #ffebee;
            --gradient-telkom: linear-gradient(135deg, var(--telkom-primary) 0%, var(--telkom-dark) 100%);
            --gradient-reverse: linear-gradient(135deg, var(--telkom-secondary) 0%, #f5f5f5 100%);
            --shadow-lg: 0 10px 30px rgba(227, 25, 55, 0.2);
            --shadow-xl: 0 20px 40px rgba(227, 25, 55, 0.3);
        }

        body {
            background: linear-gradient(135deg, #ffffff 0%, #f8f8f8 50%, #ffebee 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23e31937" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="%23e31937" opacity="0.03"/><rect x="45" y="45" width="10" height="10" fill="%23e31937" opacity="0.02"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            pointer-events: none;
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--telkom-primary);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            position: relative;
            width: 100%;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-telkom);
            border-radius: 20px 20px 0 0;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: var(--gradient-telkom);
            padding: 30px 25px;
            text-align: center;
            position: relative;
        }

        .login-logo {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .login-logo i {
            font-size: 26px;
            padding: 10px;
            background: rgba(255,255,255,0.25);
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.4);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .login-subtitle {
            color: rgba(255,255,255,0.95);
            font-size: 14px;
            margin: 0;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .login-body {
            padding: 15px 30px;
            background: white;
        }

        .form-group {
            margin-bottom: 10px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control-custom {
            background: #fafafa;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            padding: 16px 18px 16px 18px;
            font-size: 15px;
            transition: all 0.3s ease;
            height: 54px;
            color: #333;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: 1;
            text-align: left;
        }

        .form-control-custom:focus {
            border-color: var(--telkom-primary);
            box-shadow: 0 0 0 0.25rem rgba(227, 25, 55, 0.15);
            background: white;
            color: var(--telkom-dark);
            outline: none;
        }

        .btn-login {
            background: var(--gradient-telkom);
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-sizing: border-box;
            box-shadow: 0 4px 15px rgba(227, 25, 55, 0.2);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(227, 25, 55, 0.4);
            background: linear-gradient(135deg, var(--telkom-dark) 0%, var(--telkom-primary) 100%);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 14px 18px;
            margin-bottom: 22px;
            animation: shake 0.6s ease-in-out;
            background: #fff5f5;
            color: var(--telkom-primary);
            font-weight: 600;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 14px;
            box-sizing: border-box;
            border: 1px solid #ffebee;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-12px); }
            75% { transform: translateX(12px); }
        }

        .login-footer {
            text-align: center;
            padding: 15px 20px;
            color: #777;
            font-size: 13px;
            border-top: 2px solid #ffebee;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-sizing: border-box;
        }

        .login-footer i {
            color: var(--telkom-primary);
            margin: 0 8px;
            font-size: 13px;
        }

        .login-footer span {
            color: var(--telkom-primary);
            font-weight: 700;
        }

        @media (max-width: 480px) {
            .login-card {
                margin: 10px;
                border-radius: 15px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 35px 20px 25px;
            }

            .login-logo {
                font-size: 22px;
            }

            .login-logo i {
                font-size: 26px;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-crown"></i>
                    <span>SUPERADMIN</span>
                </div>
                <p class="login-subtitle">Panel Super Administrator</p>
            </div>

            <div class="login-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" class="form-control-custom" placeholder="Masukkan Username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" class="form-control-custom" placeholder="Masukkan Password" required>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        LOGIN
                    </button>
                </form>
            </div>

            <div class="login-footer">
                <i class="fas fa-shield-alt"></i>
                <span> 2026 Sistem Absensi Telkom</span>
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>