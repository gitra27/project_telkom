<?php
include "config.php";

echo "<h3>Struktur Tabel tb_karyawan:</h3>";
$result = mysqli_query($conn, "DESCRIBE tb_karyawan");
if ($result) {
    echo "<table border='1'><tr><th>Kolom</th><th>Tipe</th><th>Null</th><th>Key</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

echo "<br><h3>Struktur Tabel tb_absensi:</h3>";
$result2 = mysqli_query($conn, "DESCRIBE tb_absensi");
if ($result2) {
    echo "<table border='1'><tr><th>Kolom</th><th>Tipe</th><th>Null</th><th>Key</th></tr>";
    while ($row = mysqli_fetch_assoc($result2)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
