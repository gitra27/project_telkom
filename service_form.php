<?php
include 'koneksi.php';

$service = ['nama_service' => '', 'harga' => ''];

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM service WHERE id_service = $id");
    $service = $result->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_service'];
    $harga = $_POST['harga'];

    if (isset($_POST['id_service']) && $_POST['id_service'] != '') {
        $id = $_POST['id_service'];
        $conn->query("UPDATE service SET nama_service='$nama', harga='$harga' WHERE id_service=$id");
    } else {
        $conn->query("INSERT INTO service (nama_service, harga) VALUES ('$nama', '$harga')");
    }
    header("Location: service_list.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= isset($_GET['id']) ? "Edit" : "Tambah" ?> Layanan</title>
</head>
<body>
    <h1><?= isset($_GET['id']) ? "Edit" : "Tambah" ?> Layanan</h1>
    <form method="post">
        <input type="hidden" name="id_service" value="<?= $_GET['id'] ?? '' ?>">
        <label>Nama Layanan:</label><br>
        <input type="text" name="nama_service" value="<?= htmlspecialchars($service['nama_service']) ?>" required><br><br>

        <label>Harga:</label><br>
        <input type="number" step="0.01" name="harga" value="<?= htmlspecialchars($service['harga']) ?>" required><br><br>

        <button type="submit">Simpan</button>
    </form>
    <br>
    <a href="service_list.php">Kembali ke daftar</a>
</body>
</html>
