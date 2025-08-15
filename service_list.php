<?php
include 'koneksi.php';

$services = $conn->query("SELECT * FROM service");

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $conn->query("DELETE FROM service WHERE id_service = $id");
    header("Location: service_list.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Layanan Bengkel</title>
</head>
<body>
    <h1>Daftar Layanan</h1>
    <a href="service_form.php">Tambah Layanan</a>
    <table border="1" cellpadding="10">
        <tr>
            <th>Nama Layanan</th>
            <th>Harga</th>
            <th>Aksi</th>
        </tr>
        <?php while($row = $services->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['nama_service']) ?></td>
            <td><?= htmlspecialchars($row['harga']) ?></td>
            <td>
                <a href="service_form.php?id=<?= $row['id_service'] ?>">Edit</a> |
                <a href="service_list.php?hapus=<?= $row['id_service'] ?>" onclick="return confirm('Yakin hapus?')">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
