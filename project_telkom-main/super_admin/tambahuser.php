<?php
include "../config.php";

if (isset($_POST['save'])) {

    $nama   = $_POST['nama'];
    $nik    = $_POST['nik'];
    $asal   = $_POST['asal_sekolah'];
    $lantai = $_POST['lantai'];
    $start  = $_POST['start_date'];
    $end    = $_POST['end_date'];

    // Hash password agar aman
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Query insert user baru
    $query = mysqli_query($conn, "
        INSERT INTO tb_karyawan(nama, nik, password, asal_sekolah, lantai, start_date, end_date)
        VALUES ('$nama', '$nik', '$password_hash', '$asal', '$lantai', '$start', '$end')
    ");

    if ($query) {
        echo "<script>
                alert('User berhasil ditambahkan!');
                window.location='data_user.php';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan user!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Tambah User</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'>

    <style>
        body { background: #f4f6f9; }
        .container-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 40px;
            max-width: 500px;
        }
    </style>
</head>

<body>

<div class="container d-flex justify-content-center">
    <div class="container-box shadow">

        <h3 class="mb-4">Tambah User Baru</h3>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Nama</label>
                <input type="text" name="nama" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">NIK</label>
                <input type="text" name="nik" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Asal Sekolah</label>
                <input type="text" name="asal_sekolah" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Lantai</label>
                <select name="lantai" class="form-select" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Tanggal Mulai PKL</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tanggal Berakhir PKL</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password Akun</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" name="save" class="btn btn-primary w-100">Simpan User</button>

        </form>

    </div>
</div>

</body>
</html>
