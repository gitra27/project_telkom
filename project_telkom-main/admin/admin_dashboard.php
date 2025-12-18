<?php
session_start();

/* ===============================
   KONEKSI DATABASE
   =============================== */
$conn = mysqli_connect("localhost","root","","db_karyawan2");
if(!$conn){
    die("koneksi database gagal");
}

/* ===============================
   PROTEKSI ADMIN
   =============================== */
if(!isset($_SESSION['admin'])){
    header("location: login_admin.php");
    exit;
}

/* ===============================
   DATA ADMIN (SESSION)
   =============================== */
$nama_admin   = $_SESSION['admin']['nama_admin'];
$lantai_admin = $_SESSION['admin']['lantai'];

/* ===============================
   TAMBAH USER PER LANTAI
   =============================== */
if(isset($_POST['tambah_user'])){
    $nik_user  = $_POST['nik_user'];
    $nama_user = $_POST['nama_user'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    mysqli_query($conn,"
        INSERT INTO tb_user (nik, nama, lantai, password, created_at)
        VALUES (
            '$nik_user',
            '$nama_user',
            '$lantai_admin',
            '$password',
            NOW()
        )
    ");
}

/* ===============================
   FILTER BULAN & TAHUN
   =============================== */
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

/* ===============================
   SUMMARY ABSENSI
   =============================== */
$q_summary = mysqli_query($conn,"
    SELECT 
        COUNT(*) as total,
        SUM(status='hadir') as hadir,
        SUM(status='izin') as izin,
        SUM(status='alpha') as alpha
    FROM tb_absensi
    WHERE lantai='$lantai_admin'
    AND MONTH(tanggal)='$bulan'
    AND YEAR(tanggal)='$tahun'
");
$summary = mysqli_fetch_assoc($q_summary);

/* ===============================
   DATA USER PER LANTAI
   =============================== */
$q_user = mysqli_query($conn,"
    SELECT * FROM tb_user
    WHERE lantai='$lantai_admin'
    ORDER BY nama ASC
");

/* ===============================
   RIWAYAT ABSENSI
   =============================== */
$q_absen = mysqli_query($conn,"
    SELECT * FROM tb_absensi
    WHERE lantai='$lantai_admin'
    ORDER BY tanggal DESC, jam_masuk DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>admin dashboard absensi</title>

<style>
*{box-sizing:border-box}
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f2f2f2;
}

/* ===== TOPBAR ===== */
.topbar{
    background:#b30000;
    color:white;
    padding:16px 25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.topbar h1{
    margin:0;
    font-size:20px;
}

/* ===== CONTAINER ===== */
.container{
    padding:25px;
}

/* ===== SUMMARY ===== */
.summary{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
    margin-bottom:25px;
}
.box{
    background:white;
    padding:20px;
    border-left:6px solid #b30000;
    box-shadow:0 4px 10px rgba(0,0,0,.1);
}
.box h2{
    margin:0;
    font-size:28px;
    color:#b30000;
}
.box p{
    margin-top:5px;
    color:#555;
}

/* ===== CARD ===== */
.card{
    background:white;
    padding:20px;
    margin-bottom:25px;
    box-shadow:0 4px 10px rgba(0,0,0,.1);
}
.card h3{
    margin-top:0;
    color:#b30000;
}

/* ===== FORM ===== */
.filter{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
input,select,button{
    padding:8px 10px;
    border-radius:4px;
    border:1px solid #ccc;
}
.btn{
    background:#b30000;
    color:white;
    border:none;
    cursor:pointer;
}
.btn:hover{
    background:#800000;
}

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:collapse;
    font-size:14px;
}
th{
    background:black;
    color:white;
    padding:10px;
}
td{
    padding:8px;
    border-bottom:1px solid #ddd;
}
tr:hover{
    background:#f9f9f9;
}

/* ===== STATUS ===== */
.badge{
    padding:4px 8px;
    color:white;
    border-radius:4px;
    font-size:12px;
}
.hadir{background:#28a745}
.izin{background:#ffc107;color:black}
.alpha{background:#dc3545}
</style>
</head>

<body>

<div class="topbar">
    <h1>admin dashboard absensi</h1>
    <span><?= $nama_admin ?> | lantai <?= $lantai_admin ?></span>
</div>

<div class="container">

<!-- FILTER -->
<div class="card">
<form class="filter" method="GET">
    <select name="bulan">
        <?php for($i=1;$i<=12;$i++): ?>
        <option value="<?= $i ?>" <?= ($bulan==$i)?'selected':'' ?>>
            bulan <?= $i ?>
        </option>
        <?php endfor; ?>
    </select>

    <select name="tahun">
        <?php for($t=date('Y')-1;$t<=date('Y');$t++): ?>
        <option value="<?= $t ?>" <?= ($tahun==$t)?'selected':'' ?>>
            <?= $t ?>
        </option>
        <?php endfor; ?>
    </select>

    <button class="btn">filter</button>
</form>
</div>

<!-- SUMMARY -->
<div class="summary">
    <div class="box"><h2><?= $summary['total'] ?></h2><p>total absensi</p></div>
    <div class="box"><h2><?= $summary['hadir'] ?></h2><p>hadir</p></div>
    <div class="box"><h2><?= $summary['izin'] ?></h2><p>izin</p></div>
    <div class="box"><h2><?= $summary['alpha'] ?></h2><p>alpha</p></div>
</div>

<!-- TAMBAH USER -->
<div class="card">
<h3>tambah user lantai <?= $lantai_admin ?></h3>
<form method="POST" class="filter">
    <input type="text" name="nik_user" placeholder="nik user" required>
    <input type="text" name="nama_user" placeholder="nama user" required>
    <input type="password" name="password" placeholder="password awal" required>
    <button class="btn" name="tambah_user">tambah</button>
</form>
</div>

<!-- DATA USER -->
<div class="card">
<h3>data user lantai <?= $lantai_admin ?></h3>
<table>
<tr>
    <th>nik</th>
    <th>nama</th>
    <th>lantai</th>
    <th>dibuat</th>
</tr>
<?php while($u=mysqli_fetch_assoc($q_user)): ?>
<tr>
    <td><?= $u['nik'] ?></td>
    <td><?= $u['nama'] ?></td>
    <td><?= $u['lantai'] ?></td>
    <td><?= $u['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- RIWAYAT ABSENSI -->
<div class="card">
<h3>riwayat absensi lantai <?= $lantai_admin ?></h3>
<table>
<tr>
    <th>nik</th>
    <th>nama</th>
    <th>tanggal</th>
    <th>masuk</th>
    <th>pulang</th>
    <th>status</th>
    <th>catatan</th>
</tr>
<?php while($a=mysqli_fetch_assoc($q_absen)): ?>
<tr>
    <td><?= $a['nik'] ?></td>
    <td><?= $a['nama'] ?></td>
    <td><?= $a['tanggal'] ?></td>
    <td><?= $a['jam_masuk'] ?></td>
    <td><?= $a['jam_pulang'] ?></td>
    <td><span class="badge <?= $a['status'] ?>"><?= $a['status'] ?></span></td>
    <td><?= $a['catatan'] ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>
