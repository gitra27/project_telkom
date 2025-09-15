<?php 
include 'config.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="style.css" />
  <title>Login Absen</title>
</head>
<body class="d-flex align-items-center" style="min-height:100vh;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-none rounded-4 border-0 p-4 animate-fade">
        <h3 class="text-center mb-4">Login Absen</h3>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $pass  = $_POST['password'];
            
            // Debug: cek apakah data POST diterima
            if (empty($email) || empty($pass)) {
                echo "<div class='alert alert-warning'>Email dan password harus diisi!</div>";
            } else {
                $res = $conn->query("SELECT * FROM tb_users WHERE email='$email'");
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    if (password_verify($pass, $row['password'])) {
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['role']    = $row['role'];
                        $_SESSION['nama']    = $row['nama'];
                        
                        // Pastikan tidak ada output sebelum header
                        ob_clean();
                        header("Location: index.php");
                        exit();
                    } else {
                        echo "<div class='alert alert-danger'>Password salah!</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>Email tidak ditemukan!</div>";
                }
            }
        }
        ?>
        <form method="post" action="">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Masukkan email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
          </div>
          <button class="btn btn-primary w-100 py-2">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
