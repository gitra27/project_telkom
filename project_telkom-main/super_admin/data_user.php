<?php
include "../config.php";

// Hapus user
if(isset($_GET['hapus'])){
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM tb_karyawan WHERE id='$id'");
    header("Location: data_user.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data User</title>
</head>
<body>

<h2>Data User</h2>

<a href="tambahuser.php">+ Tambah User</a>
<br><br>

<table border="1" cellpadding="8">
    <tr>
        <th>No</th>
        <th>NIK</th>
        <th>Nama</th>
        <th>Role</th>
        <th>Aksi</th>
    </tr>

    <?php
    $no = 1;
    $data = mysqli_query($conn, "SELECT * FROM tb_karyawan ORDER BY id DESC");
    while($d = mysqli_fetch_array($data)){
    ?>
    <tr>
        <td><?= $no++; ?></td>
        <td><?= $d['nik']; ?></td>
        <td><?= $d['nama']; ?></td>
        <td><?= $d['role']; ?></td>
        <td>
            <a href="edit_user.php?id=<?= $d['id']; ?>">Edit</a> |
            <a href="data_user.php?hapus=<?= $d['id']; ?>" onclick="return confirm('Hapus user?')">Hapus</a>
        </td>
    </tr>
    <?php } ?>
</table>

</body>
</html>
