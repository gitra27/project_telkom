<?php
include "config.php";

// Hapus admin
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM tb_admin WHERE id_admin=$id");
    echo "<script>alert('Admin dihapus!'); window.location='dashboard_superadmin.php?page=admin';</script>";
}

// Jika tombol update ditekan
if (isset($_POST['update'])) {
    $id   = $_POST['id_admin'];
    $nama = $_POST['nama_admin'];
    $nik  = $_POST['nik_admin'];
    $lantai = $_POST['lantai'];

    // Jika password diisi → update password
    if (!empty($_POST['password_admin'])) {
        $pw = password_hash($_POST['password_admin'], PASSWORD_DEFAULT);
        $query = "
            UPDATE tb_admin SET
            nama_admin='$nama',
            nik_admin='$nik',
            lantai='$lantai',
            password_admin='$pw'
            WHERE id_admin='$id'
        ";
    } else {
        $query = "
            UPDATE tb_admin SET
            nama_admin='$nama',
            nik_admin='$nik',
            lantai='$lantai'
            WHERE id_admin='$id'
        ";
    }

    mysqli_query($conn, $query);
    echo "<script>alert('Data admin diperbarui!'); window.location='dashboard_superadmin.php?page=admin';</script>";
}

$admins = mysqli_query($conn, "SELECT * FROM tb_admin ORDER BY lantai ASC");
?>

<h3>Data Admin</h3>

<a href="tambah_admin.php">
    <button style='margin-bottom:15px;padding:10px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;'>
        + Tambah Admin
    </button>
</a>

<table border="1" width="100%" cellpadding="8" cellspacing="0">
<tr bgcolor="#eee">
    <th>Nama</th>
    <th>NIK</th>
    <th>Lantai</th>
    <th>Aksi</th>
</tr>

<?php while ($row = mysqli_fetch_assoc($admins)): ?>

<tr>
    <td><?= $row['nama_admin'] ?></td>
    <td><?= $row['nik_admin'] ?></td>
    <td><?= $row['lantai'] ?></td>
    <td>
        <a href="dashboard_superadmin.php?page=admin&edit=<?= $row['id_admin'] ?>">Edit</a> | 
        <a href="?page=admin&delete=<?= $row['id_admin'] ?>" onclick="return confirm('Hapus admin ini?')">Hapus</a>
    </td>
</tr>

<?php endwhile; ?>
</table>

<?php
// Jika tombol edit ditekan → tampilkan form edit
if (isset($_GET['edit'])):
    $id = $_GET['edit'];
    $e = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_admin WHERE id_admin=$id"));
?>

<h3>Edit Admin</h3>

<form method="POST">

    <input type="hidden" name="id_admin" value="<?= $e['id_admin'] ?>">

    <label>Nama Admin</label>
    <input type="text" name="nama_admin" value="<?= $e['nama_admin'] ?>" required>

    <label>NIK Admin</label>
    <input type="text" name="nik_admin" value="<?= $e['nik_admin'] ?>" required>

    <label>Lantai</label>
    <select name="lantai" required>
        <option <?=($e['lantai']=="1"?"selected":"")?>>1</option>
        <option <?=($e['lantai']=="2"?"selected":"")?>>2</option>
        <option <?=($e['lantai']=="3"?"selected":"")?>>3</option>
        <option <?=($e['lantai']=="5"?"selected":"")?>>5</option>
        <option <?=($e['lantai']=="6"?"selected":"")?>>6</option>
    </select>

    <label>Password Baru (opsional)</label>
    <input type="password" name="password_admin" placeholder="Kosongkan jika tidak diganti">

    <button type="submit" name="update">UPDATE</button>

</form>

<?php endif; ?>
