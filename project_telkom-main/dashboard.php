<?php
include "config.php";
// session_start() sudah dipanggil di config.php, jadi tidak perlu dipanggil lagi
if(!isset($_SESSION['nik'])){ header("Location: login.php"); exit; }

// Fungsi esc() untuk escape HTML
function esc($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$nik = $_SESSION['nik'];
$today = date('Y-m-d');

// fetch user
$uq = mysqli_query($conn, "SELECT * FROM tb_karyawan WHERE nik='".esc($nik)."' LIMIT 1");
$user = $uq && mysqli_num_rows($uq) ? mysqli_fetch_assoc($uq) : null;
$lantai = $user['lantai'] ?? '';
$nama = $user['nama'] ?? '';

// Messages
$msg = '';
$err = '';

// --- Handle POST actions (submit forms in same file) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // anti-csrf minimal: ensure nik posted or session matches
    $post_nik = $_POST['nik'] ?? $nik;
    if ($post_nik !== $nik) {
        $err = "User mismatch.";
    } else {
        $action = $_POST['action'] ?? '';

        // sanitize common fields
        $catatan = isset($_POST['catatan']) ? mysqli_real_escape_string($conn, $_POST['catatan']) : '';

        // get client-submitted lat/lon (must exist)
        $lat = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
        $lon = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';

        // helper upload
        $uploaded_foto = '';
        if (isset($_FILES['foto']) && isset($_FILES['foto']['name']) && $_FILES['foto']['name'] !== '') {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fn = 'foto_'.$nik.'_'.time().'.'.$ext;
            $target = __DIR__ . '/uploads/foto/' . $fn;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) $uploaded_foto = 'uploads/foto/'.$fn;
        }

        $uploaded_file = '';
        if (isset($_FILES['file_upload']) && isset($_FILES['file_upload']['name']) && $_FILES['file_upload']['name'] !== '') {
            $ext2 = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
            $fn2 = 'file_'.$nik.'_'.time().'.'.$ext2;
            $target2 = __DIR__ . '/uploads/file/' . $fn2;
            if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target2)) $uploaded_file = 'uploads/file/'.$fn2;
        }

        // build maps_url if lat/lon present & valid-ish
        $maps_url = '';
        if ($lat !== '' && $lon !== '') {
            $maps_url = "https://www.google.com/maps?q=" . urlencode($lat . "," . $lon);
        }

        // FETCH existing record for today
        $r = mysqli_query($conn, "SELECT * FROM tb_absensi WHERE nik='".esc($nik)."' AND tanggal='$today' LIMIT 1");
        $exists = ($r && mysqli_num_rows($r) > 0);
        $row = $exists ? mysqli_fetch_assoc($r) : null;

        // ACTIONS
        if ($action === 'masuk') {
            $time_now = date('H:i:s');
            $current_hour = date('H');
            
            // Validasi jam kerja realistis Indonesia (08:00 - 17:00)
            if ($current_hour < 8 || $current_hour >= 17) {
                $err = "Absen masuk hanya bisa dilakukan pada jam kerja (08:00 - 17:00 WIB).";
            } else {
                // location required
                if ($lat === '' || $lon === '') { 
                    $err = "Lokasi wajib untuk absen masuk."; 
                } else {
                    // Status berdasarkan jam masuk
                    if ($current_hour == 8 && date('i') <= 15) {
                        $status = 'Hadir'; // Tepat waktu (08:00-08:15)
                    } elseif ($current_hour == 8 && date('i') > 15) {
                        $status = 'Telat'; // Terlambat (08:16-08:59)
                    } else {
                        $status = 'Hadir'; // Masuk setelah jam 9 (dianggap hadir)
                    }
                    
                    if ($exists) {
                        // update if jam_masuk empty
                        if (empty($row['jam_masuk'])) {
                            $sql = "UPDATE tb_absensi SET jam_masuk='$time_now', status='".esc($status)."', foto='$uploaded_foto', file_upload='$uploaded_file', catatan='$catatan', maps_url='$maps_url' WHERE id_absen='".$row['id_absen']."'";
                            mysqli_query($conn, $sql);
                            $msg = "Absen masuk tercatat. Status: $status";
                        } else {
                            // Notifikasi jika sudah absen masuk
                            $err = "Anda sudah melakukan absen masuk hari ini pada pukul " . $row['jam_masuk'] . ". Tidak bisa absen masuk lagi.";
                        }
                    } else {
                        // insert new record
                        $sql = "INSERT INTO tb_absensi (nik, tanggal, jam_masuk, status, foto, file_upload, catatan, maps_url) VALUES ('".esc($nik)."','$today','$time_now','".esc($status)."','$uploaded_foto','$uploaded_file','$catatan','$maps_url')";
                        mysqli_query($conn, $sql);
                        $msg = "Absen masuk tercatat. Status: $status";
                    }
                }
            }
        }

        elseif ($action === 'pulang') {
            $time_now = date('H:i:s');
            $current_hour = date('H');
            $current_minute = date('i');
            
            if (!$exists) $err = "Belum ada record absen masuk hari ini.";
            else {
                if (!empty($row['jam_pulang'])) $err = "Absen pulang sudah tercatat.";
                else {
                    // Validasi jam kerja realistis Indonesia untuk pulang (16:30 - 18:00)
                    if ($current_hour < 16 || ($current_hour == 16 && $current_minute < 30)) {
                        $err = "Absen pulang hanya bisa dilakukan mulai pukul 16:30 WIB.";
                    } elseif ($current_hour >= 18) {
                        $err = "Absen pulang hanya bisa dilakukan hingga pukul 18:00 WIB.";
                    } else {
                        // location required
                        if ($lat === '' || $lon === '') { 
                            $err = "Lokasi wajib untuk absen pulang."; 
                        } else {
                            // Catatan opsional
                            $keterangan = $_POST['keterangan_pulang'] ?? '';
                            // Cek apakah status 'Selesai' ada di enum, jika tidak gunakan 'Hadir'
                            $check_enum = mysqli_query($conn, "SHOW COLUMNS FROM tb_absensi WHERE Field = 'status' AND Type LIKE '%Selesai%'");
                            $has_selesai = mysqli_num_rows($check_enum) > 0;
                            $new_status = $has_selesai ? 'Selesai' : 'Hadir';
                            $sql = "UPDATE tb_absensi SET jam_pulang='$time_now', status='$new_status', maps_url='$maps_url', catatan=COALESCE('$keterangan', catatan) WHERE id_absen='".$row['id_absen']."'";
                            mysqli_query($conn, $sql);
                            $msg = "Absen pulang tercatat. Terima kasih.";
                        }
                        $new_status = $has_selesai ? 'Selesai' : 'Hadir';
                        $sql = "UPDATE tb_absensi SET jam_pulang='$time_now', status='$new_status', maps_url='$maps_url', catatan=COALESCE('$keterangan', catatan) WHERE id_absen='".$row['id_absen']."'";
                        mysqli_query($conn, $sql);
                        $msg = "Absen pulang tercatat. Terima kasih.";
                    }
                }
            }
        }

        elseif ($action === 'izin' || $action === 'sakit') {
            // izin/sakit: wajib upload file (surat) — enforce
            if ($uploaded_file === '') {
                $err = "Untuk Izin / Sakit wajib upload file (surat).";
            } else {
                $status = ($action === 'izin') ? 'Izin' : 'Sakit';
                if ($exists) {
                    mysqli_query($conn, "UPDATE tb_absensi SET jam_masuk=NULL, jam_pulang=NULL, status='".esc($status)."', file_upload='$uploaded_file', catatan='$catatan' WHERE id_absen='".$row['id_absen']."'");
                } else {
                    mysqli_query($conn, "INSERT INTO tb_absensi (nik,tanggal,status,file_upload,catatan) VALUES ('".esc($nik)."','$today','".esc($status)."','$uploaded_file','$catatan')");
                }
                $msg = "Status $status tercatat untuk hari ini.";
            }
        }

        elseif ($action === 'save_note') {
            // save or update note
            if ($exists) {
                mysqli_query($conn, "UPDATE tb_absensi SET catatan='$catatan' WHERE id_absen='".$row['id_absen']."'");
            } else {
                mysqli_query($conn, "INSERT INTO tb_absensi (nik,tanggal,status,catatan) VALUES ('".esc($nik)."','$today','Belum Absen','$catatan')");
            }
            $msg = "Catatan disimpan.";
        }

        elseif ($action === 'upload_profile') {
            // Handle profile photo upload
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_photo'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                // Validate file type
                if (!in_array($file['type'], $allowed_types)) {
                    $err = "Hanya file JPEG, PNG, atau GIF yang diperbolehkan.";
                }
                // Validate file size
                elseif ($file['size'] > $max_size) {
                    $err = "Ukuran file maksimal 2MB.";
                }
                else {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = __DIR__ . '/uploads/profile/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $nik . '_' . time() . '.' . $ext;
                    $target_path = $upload_dir . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Update database with new photo path
                        $photo_path = 'uploads/profile/' . $filename;
                        $update_sql = "UPDATE tb_karyawan SET photo_path = '".esc($photo_path)."' WHERE nik = '".esc($nik)."'";
                        
                        if (mysqli_query($conn, $update_sql)) {
                            // Update user data for display
                            $user['photo_path'] = $photo_path;
                            $msg = "Foto profil berhasil diperbarui!";
                            
                            // Refresh user data
                            $uq = mysqli_query($conn, "SELECT * FROM tb_karyawan WHERE nik='".esc($nik)."' LIMIT 1");
                            $user = $uq && mysqli_num_rows($uq) ? mysqli_fetch_assoc($uq) : null;
                            
                            // Add JavaScript to update avatar without page reload
                            echo "<script>updateProfileAvatar('".esc($photo_path)."'); document.getElementById('photoPreview').innerHTML = '';</script>";
                        } else {
                            $err = "Gagal menyimpan foto ke database.";
                            // Remove uploaded file if database update failed
                            unlink($target_path);
                        }
                    } else {
                        $err = "Gagal mengupload file.";
                    }
                }
            } else {
                $err = "Tidak ada file yang diupload atau terjadi kesalahan.";
            }
        }

        // refresh $absenHariIni after processing
        $cekAbsen = mysqli_query($conn, "SELECT * FROM tb_absensi WHERE nik='".esc($nik)."' AND tanggal='$today' LIMIT 1");
        $absenHariIni = $cekAbsen && mysqli_num_rows($cekAbsen) ? mysqli_fetch_assoc($cekAbsen) : null;
    }
} // end POST

// If not POST, just load current day's record for display:
if (!isset($absenHariIni)) {
    $cekAbsen = mysqli_query($conn, "SELECT * FROM tb_absensi WHERE nik='".esc($nik)."' AND tanggal='$today' LIMIT 1");
    $absenHariIni = $cekAbsen && mysqli_num_rows($cekAbsen) ? mysqli_fetch_assoc($cekAbsen) : null;
}

// --- Small stats for user's floor (useful for admin per-lantai) ---
$total_floor = 0; $floor_hadir = 0; $floor_sakit = 0; $floor_izin = 0;
if ($lantai) {
    $r1 = mysqli_query($conn, "SELECT COUNT(*) as c FROM tb_karyawan WHERE lantai='".esc($lantai)."' AND account_active=1");
    $total_floor = $r1 ? intval(mysqli_fetch_assoc($r1)['c']) : 0;
    $r2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM tb_absensi a JOIN tb_karyawan k ON a.nik=k.nik WHERE a.tanggal='$today' AND k.lantai='".esc($lantai)."' AND a.status IN ('Hadir','Telat','Selesai')");
    $floor_hadir = $r2 ? intval(mysqli_fetch_assoc($r2)['c']) : 0;
    $r3 = mysqli_query($conn, "SELECT COUNT(*) as c FROM tb_absensi a JOIN tb_karyawan k ON a.nik=k.nik WHERE a.tanggal='$today' AND k.lantai='".esc($lantai)."' AND a.status='Sakit'");
    $floor_sakit = $r3 ? intval(mysqli_fetch_assoc($r3)['c']) : 0;
    $r4 = mysqli_query($conn, "SELECT COUNT(*) as c FROM tb_absensi a JOIN tb_karyawan k ON a.nik=k.nik WHERE a.tanggal='$today' AND k.lantai='".esc($lantai)."' AND a.status='Izin'");
    $floor_izin = $r4 ? intval(mysqli_fetch_assoc($r4)['c']) : 0;
}

// --- HISTORY (last 30 days) ---
$history = [];
$hq = mysqli_query($conn, "SELECT * FROM tb_absensi WHERE nik='".esc($nik)."' ORDER BY tanggal DESC, jam_masuk DESC LIMIT 30");
if ($hq) while($r = mysqli_fetch_assoc($hq)) $history[] = $r;
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Dashboard Presensi - <?= esc($nama) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{
  --telkom-primary:#e31937;
  --telkom-secondary:#003d7a;
  --telkom-accent:#ff6b35;
  --telkom-light:#f8f9fa;
  --telkom-dark:#2c3e50;
  --telkom-gray:#6c757d;
  --telkom-success:#28a745;
  --telkom-warning:#ffc107;
  --telkom-danger:#dc3545;
  --gradient-telkom:linear-gradient(135deg, var(--telkom-primary) 0%, var(--telkom-secondary) 100%);
  --gradient-card:linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
  --shadow-sm:0 2px 4px rgba(0,0,0,0.08);
  --shadow-md:0 4px 12px rgba(0,0,0,0.12);
  --shadow-lg:0 8px 24px rgba(0,0,0,0.16);
}

body{
  background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  min-height:100vh;
  margin:0;
  padding:20px;
  position:relative;
}

body::before{
  content:'';
  position:fixed;
  top:0;
  left:0;
  right:0;
  bottom:0;
  background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23e31937" opacity="0.03"/><circle cx="75" cy="75" r="1" fill="%23003d7a" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
  pointer-events:none;
  z-index:0;
}

.header{
  background:var(--gradient-card);
  border-bottom:3px solid var(--telkom-primary);
  padding:20px 30px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  border-radius:16px;
  box-shadow:var(--shadow-md);
  position:relative;
  z-index:1;
  backdrop-filter:blur(10px);
  border:1px solid rgba(255,255,255,0.2);
}

.brand{
  display:flex;
  align-items:center;
  gap:16px;
}

.brand img{
  height:50px;
  border-radius:8px;
  box-shadow:var(--shadow-sm);
}

.telkom{
  color:var(--telkom-primary);
  font-weight:700;
}

.card-profile{
  background:var(--gradient-card);
  border-radius:16px;
  padding:30px;
  box-shadow:var(--shadow-md);
  border:1px solid rgba(255,255,255,0.2);
  position:relative;
  z-index:1;
  transition:transform 0.3s ease, box-shadow 0.3s ease;
}

.card-profile:hover{
  transform:translateY(-2px);
  box-shadow:var(--shadow-lg);
}

.avatar{
  width:100px;
  height:100px;
  border-radius:50%;
  object-fit:cover;
  border:4px solid var(--telkom-primary);
  box-shadow:var(--shadow-md);
}

.stat-card{
  background:var(--gradient-card);
  border-radius:16px;
  padding:25px;
  box-shadow:var(--shadow-md);
  border:1px solid rgba(255,255,255,0.2);
  position:relative;
  z-index:1;
  transition:all 0.3s ease;
  border-left:4px solid var(--telkom-primary);
}

.stat-card:hover{
  transform:translateY(-2px);
  box-shadow:var(--shadow-lg);
  border-left-color:var(--telkom-accent);
}

.stat-card h4{
  color:var(--telkom-secondary);
  font-weight:600;
  margin-bottom:15px;
}

.stat-card .badge{
  background:var(--gradient-telkom);
  color:white;
  padding:8px 16px;
  border-radius:20px;
  font-weight:500;
  font-size:14px;
}

.small-muted{
  font-size:13px;
  color:var(--telkom-gray);
  background:rgba(255,255,255,0.8);
  padding:8px 12px;
  border-radius:8px;
  border-left:3px solid var(--telkom-warning);
}

.upload-preview{
  max-width:120px;
  max-height:120px;
  object-fit:cover;
  border-radius:12px;
  box-shadow:var(--shadow-md);
  border:2px solid var(--telkom-primary);
}

.table-scroll{
  max-height:420px;
  overflow:auto;
  border-radius:12px;
  box-shadow:var(--shadow-sm);
}

.badge-telkom{
  background:var(--gradient-telkom);
  color:#fff;
  padding:6px 14px;
  border-radius:20px;
  font-weight:500;
  font-size:12px;
  box-shadow:var(--shadow-sm);
}

.btn-telkom{
  background:var(--gradient-telkom);
  color:white;
  border:none;
  padding:12px 24px;
  border-radius:8px;
  font-weight:600;
  transition:all 0.3s ease;
  box-shadow:var(--shadow-sm);
  position:relative;
  overflow:hidden;
}

.btn-telkom::before{
  content:'';
  position:absolute;
  top:0;
  left:-100%;
  width:100%;
  height:100%;
  background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition:left 0.5s ease;
}

.btn-telkom:hover{
  transform:translateY(-2px);
  box-shadow:var(--shadow-md);
  color:white;
}

.btn-telkom:hover::before{
  left:100%;
}

.form-control{
  border:2px solid #e9ecef;
  border-radius:8px;
  padding:12px 16px;
  transition:all 0.3s ease;
  background:rgba(255,255,255,0.9);
}

.form-control:focus{
  border-color:var(--telkom-primary);
  box-shadow:0 0 0 0.2rem rgba(227,25,55,0.25);
  background:white;
}

.alert{
  border-radius:12px;
  border:none;
  padding:16px 20px;
  box-shadow:var(--shadow-sm);
  position:relative;
  z-index:1;
}

.alert-success{
  background:linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  color:#155724;
  border-left:4px solid var(--telkom-success);
}

.alert-danger{
  background:linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  color:#721c24;
  border-left:4px solid var(--telkom-danger);
}

.container{
  max-width:1200px;
  margin:0 auto;
  position:relative;
  z-index:1;
}

.row{
  margin-bottom:30px;
}

h3, h4, h5{
  color:var(--telkom-secondary);
  font-weight:700;
}

@keyframes fadeInUp{
  from{
    opacity:0;
    transform:translateY(20px);
  }
  to{
    opacity:1;
    transform:translateY(0);
  }
}

.card-profile, .stat-card{
  animation:fadeInUp 0.6s ease-out;
}

.stat-card:nth-child(2){
  animation-delay:0.1s;
}

.stat-card:nth-child(3){
  animation-delay:0.2s;
}

hr{
  border:0;
  height:2px;
  background:var(--gradient-telkom);
  border-radius:2px;
  margin:30px 0;
}

.btn-outline-primary, .btn-outline-warning{
  border-width:2px;
  font-weight:600;
  transition:all 0.3s ease;
}

.btn-outline-primary:hover{
  background:var(--telkom-primary);
  border-color:var(--telkom-primary);
  transform:translateY(-2px);
  box-shadow:var(--shadow-sm);
}

.btn-outline-warning:hover{
  background:var(--telkom-warning);
  border-color:var(--telkom-warning);
  transform:translateY(-2px);
  box-shadow:var(--shadow-sm);
}

/* Custom styles for 7 hari terakhir */
.list-group-item {
  border-left: none !important;
  border-right: none !important;
  transition: all 0.3s ease;
}

.list-group-item:hover {
  background-color: rgba(227, 25, 55, 0.05);
  transform: translateX(5px);
}

.list-group-item .border-start {
  transition: all 0.3s ease;
}

.list-group-item:hover .border-start {
  border-color: var(--telkom-primary) !important;
}

</style>
</head>

<body>

<!-- HEADER -->
<div class="header mb-4">
  <div class="brand">
    <img src="telkom2.png" alt="Telkom" onerror="this.style.display='none'">
    <div>
      <div style="font-weight:700;color:var(--telkom-secondary);font-size:18px;">
        <i class="fas fa-clock"></i> Sistem Presensi Magang 
        <span class="badge-telkom ms-2">Dashboard User</span>
      </div>
      <div class="small-muted mt-1">
        <i class="fas fa-user"></i> Halo, <?= esc($nama); ?> (<?= esc($nik); ?>) — 
        <i class="fas fa-school"></i> <?= esc($user['asal_sekolah'] ?? '') ?>
      </div>
    </div>
  </div>
  <div style="display:flex;gap:12px;align-items:center">
    <div id="datetime" class="small-muted text-end" style="background:rgba(255,255,255,0.8);padding:8px 12px;border-radius:8px;border-left:3px solid var(--telkom-primary);">
      <i class="fas fa-calendar-alt"></i> <span id="date"></span><br>
      <i class="fas fa-clock"></i> <span id="time"></span>
    </div>
    <a href="riwayat_absen.php" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-history"></i> Riwayat
    </a>
    <a href="logout.php" class="btn btn-outline-danger btn-sm">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<div class="container py-3">
  <div class="row g-3">
    <!-- left -->
    <div class="col-lg-4">
      <div class="card p-3 mb-3 card-profile">
        <div class="d-flex gap-3 align-items-center">
          <img src="<?= !empty($user['photo_path']) && file_exists($user['photo_path']) ? esc($user['photo_path']) : 'uploads/profile/default_avatar.png' ?>" alt="avatar" class="avatar" id="profileAvatar">
          <div>
            <h5 class="mb-0 telkom"><?= esc($user['nama'] ?? $nama) ?></h5>
            <div class="small-muted"><?= esc($user['divisi'] ?? '') ?></div>
            <div class="mt-2 small-muted">NIK: <?= esc($nik) ?></div>
            <div class="small-muted">Lantai: <?= esc($lantai ?: '-') ?></div>
          </div>
        </div>

        <hr>

        <div class="small-muted">Masa PKL</div>
        <div><strong><?= esc($user['start_date'] ?? '-') ?> — <?= esc($user['end_date'] ?? '-') ?></strong></div>
        <div class="mt-1"><?= ($user['account_active'] ? "<span class='badge bg-success'>Aktif</span>" : "<span class='badge bg-secondary'>Nonaktif</span>") ?></div>

        <hr>

        <!-- quick upload foto profil -->
        <form method="POST" enctype="multipart/form-data" class="mb-2">
          <input type="hidden" name="action" value="upload_profile">
          <label class="form-label small-muted">Ubah Foto Profil</label>
          <input type="file" name="profile_photo" accept="image/*" class="form-control form-control-sm mb-2" onchange="previewProfilePhoto(this)">
          <button type="submit" class="btn btn-telkom btn-sm w-100">
            <i class="fas fa-upload me-2"></i>Upload Foto
          </button>
          <div id="photoPreview" class="mt-2 text-center"></div>
        </form>

      </div>

      <!-- summary lantai -->
      <div class="card p-3 mb-3">
        <h6 class="mb-2">Ringkasan Lantai <?= esc($lantai ?: '-') ?></h6>
        <div class="d-flex justify-content-between">
          <div class="small-muted">Total Peserta</div><div><strong><?= $total_floor ?></strong></div>
        </div>
        <div class="d-flex justify-content-between">
          <div class="small-muted">Hadir Hari Ini</div><div><strong><?= $floor_hadir ?></strong></div>
        </div>
        <div class="d-flex justify-content-between">
          <div class="small-muted">Sakit</div><div><strong><?= $floor_sakit ?></strong></div>
        </div>
        <div class="d-flex justify-content-between">
          <div class="small-muted">Izin</div><div><strong><?= $floor_izin ?></strong></div>
        </div>
      </div>

      <!-- 7 hari terakhir -->
      <div class="card p-3">
        <h6 class="mb-3">7 Hari Terakhir</h6>
        <div class="list-group list-group-flush">
          <?php
          $sevenQ = mysqli_query($conn, "SELECT tanggal,status,jam_masuk,jam_pulang FROM tb_absensi WHERE nik='".esc($nik)."' ORDER BY tanggal DESC LIMIT 7");
          if ($sevenQ && mysqli_num_rows($sevenQ) > 0):
            while($s = mysqli_fetch_assoc($sevenQ)):
              $status_color = ($s['status'] == 'Hadir') ? 'text-success' : 
                            ($s['status'] == 'Telat' ? 'text-warning' : 
                            ($s['status'] == 'Izin' ? 'text-primary' : 
                            ($s['status'] == 'Sakit' ? 'text-warning' : 'text-secondary')));
          ?>
            <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-start border-0 ps-3">
              <div class="d-flex align-items-center">
                <div class="border-start border-3 border-warning me-3" style="height: 40px;"></div>
                <div>
                  <div class="fw-bold"><?= date("d M", strtotime($s['tanggal'])); ?></div>
                  <small class="<?= $status_color ?>"><?= $s['status'] ?: 'Belum Absen' ?></small>
                </div>
              </div>
              <div class="text-end">
                <small class="text-muted"><?= ($s['jam_masuk'] ?: '-') ?></small>
              </div>
            </div>
          <?php endwhile; else: ?>
            <div class="list-group-item text-center text-muted py-3">
              <i class="fas fa-calendar-times me-2"></i>Belum ada riwayat absensi
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- right -->
    <div class="col-lg-8">
      <div class="card p-3 mb-3">
        <h5>Status Absensi Hari Ini</h5>

        <?php if ($err): ?>
          <div class="alert alert-danger" id="errorMessage"><?= esc($err) ?></div>
        <?php endif; ?>
        <?php if ($msg): ?>
          <div class="alert alert-success" id="successMessage"><?= esc($msg) ?></div>
        <?php endif; ?>

        <?php if (!$absenHariIni): ?>
          <!-- not yet -->
          <div class="alert alert-secondary">Belum melakukan absen hari ini.</div>

          <!-- Absen form -->
          <form method="POST" enctype="multipart/form-data" id="formAbsen">
            <input type="hidden" name="nik" value="<?= esc($nik) ?>">
            <input type="hidden" name="action" id="actionField" value="masuk">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="mb-2">
              <label class="form-label">Status Kehadiran</label>
              <select name="status_select" id="statusSelect" class="form-select" required>
                <option value="Hadir">Hadir</option>
                <option value="Izin">Izin</option>
                <option value="Sakit">Sakit</option>
              </select>
            </div>

            <div class="mb-2">
              <label class="form-label">Catatan (opsional)</label>
              <textarea name="catatan" class="form-control" rows="2"></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label">Foto Selfie (wajib)</label>
              <input type="file" name="foto" accept="image/*" required class="form-control">
            </div>

            <div class="mb-2">
              <label class="form-label">Upload Surat / File (jika Izin/Sakit wajib)</label>
              <input type="file" name="file_upload" class="form-control">
            </div>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-telkom" onclick="submitAbsen('masuk')">Kirim Absen Masuk</button>
              <button type="button" class="btn btn-outline-primary" onclick="submitAbsen('izin')">Kirim Izin</button>
              <button type="button" class="btn btn-outline-warning" onclick="submitAbsen('sakit')">Kirim Sakit</button>
            </div>
            <div class="small-muted mt-2">Catatan: Absen masuk hanya bisa dilakukan jam 08.00-08.15</div>
          </form>

        <?php else: ?>
          <!-- already have record -->
          <div class="row">
            <div class="col-md-6">
              <p><strong>Status:</strong> 
                <?php
                  $status = $absenHariIni['status'] ?? 'Belum Absen';
                  if ($status == 'Hadir') {
    $cls = 'badge bg-success';
} elseif ($status == 'Telat') {
    $cls = 'badge bg-warning text-dark';
} elseif ($status == 'Izin') {
    $cls = 'badge bg-primary';
} elseif ($status == 'Sakit') {
    $cls = 'badge bg-warning text-dark';
} elseif ($status == 'Selesai') {
    $cls = 'badge bg-info';
} else {
    $cls = 'badge bg-secondary';
}

// Jika status Selesai tapi tidak tersimpan di database, tampilkan sebagai Hadir
if ($status == 'Belum Absen' && !empty($absenHariIni['jam_pulang'])) {
    $status = 'Selesai';
    $cls = 'badge bg-info';
}
                  echo "<span class='$cls'>".$status."</span>";
                ?>
              </p>
              <p><strong>Jam Masuk:</strong> <?= esc($absenHariIni['jam_masuk'] ?? '-') ?></p>
              <p><strong>Jam Pulang:</strong> <?= esc($absenHariIni['jam_pulang'] ?? '-') ?></p>
            </div>
            <div class="col-md-6">
              <p><strong>Lokasi Masuk:</strong> 
                <?php if (!empty($absenHariIni['maps_url'])): ?>
                  <a href="<?= esc($absenHariIni['maps_url']) ?>" target="_blank" class="btn btn-sm btn-danger">📍 Lihat Lokasi</a>
                <?php else: ?> - <?php endif; ?>
              </p>

              <?php if (!empty($absenHariIni['foto'])): ?>
                <p><strong>Foto:</strong><br><img src="<?= esc($absenHariIni['foto']) ?>" style="max-width:220px;border-radius:8px"></p>
              <?php endif; ?>

              <?php if (!empty($absenHariIni['file_upload'])): ?>
                <p><strong>File:</strong><br><a href="<?= esc($absenHariIni['file_upload']) ?>" target="_blank">📎 Lihat / Download</a></p>
              <?php endif; ?>
            </div>
          </div>

          <hr>

          <?php if (empty($absenHariIni['jam_pulang']) && $status !== 'Izin' && $status !== 'Sakit'): ?>
            <form method="POST" id="formPulang">
              <input type="hidden" name="nik" value="<?= esc($nik) ?>">
              <input type="hidden" name="action" value="pulang">
              <input type="hidden" name="latitude" id="latitude_pulang">
              <input type="hidden" name="longitude" id="longitude_pulang">
              
              <div class="mb-3">
                <label class="form-label">Catatan/Alasan (jika pulang setelah 16:30)</label>
                <textarea name="keterangan_pulang" class="form-control" rows="2" placeholder="Contoh: Lembur mengerjakan data pegawai, rapat tambahan, dll."></textarea>
              </div>
              
              <button type="button" class="btn btn-telkom" onclick="submitPulang()">Kirim Absen Pulang</button>
            </form>
            <div class="small-muted mt-2">Absen pulang hanya bisa dilakukan mulai pukul 16.30. Jika lewat dari jam 16.30, wajib mengisi catatan/alasan.</div>
          <?php elseif ($status === 'Izin' || $status === 'Sakit'): ?>
            <div class="alert alert-info">Status <?= htmlspecialchars($status) ?> - Tidak perlu absen pulang.</div>
          <?php else: ?>
            <div class="alert alert-success">Terima kasih, kamu sudah absen pulang hari ini.</div>
          <?php endif; ?>

          <hr>
          <!-- edit catatan -->
          <form method="POST">
            <input type="hidden" name="nik" value="<?= esc($nik) ?>">
            <input type="hidden" name="action" value="save_note">
            <label class="form-label">Ubah / Tambah Catatan</label>
            <textarea name="catatan" class="form-control mb-2" rows="3"><?= esc($absenHariIni['catatan'] ?? '') ?></textarea>
            <button class="btn btn-outline-primary">Simpan Catatan</button>
          </form>

        <?php endif; ?>

      </div>

      <!-- History table -->
      <div class="card p-3">
        <h6>Riwayat 30 Hari</h6>
        <div class="table-responsive table-scroll">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Tanggal</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Lokasi</th><th>Foto</th><th>File</th><th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($history)): ?>
                <tr><td colspan="8" class="text-center small-muted">Belum ada riwayat.</td></tr>
              <?php else: foreach($history as $h): ?>
                <tr>
                  <td><?= esc($h['tanggal']) ?></td>
                  <td>
                    <?php
                      $s = $h['status'];
                      $cls = ($s=='Hadir'?'badge bg-success':($s=='Telat'?'badge bg-warning text-dark':($s=='Izin'?'badge bg-primary':($s=='Sakit'?'badge bg-warning text-dark':'badge bg-secondary'))));
                      echo "<span class='$cls'>".esc($s)."</span>";
                    ?>
                  </td>
                  <td><?= esc($h['jam_masuk'] ?: '-') ?></td>
                  <td><?= esc($h['jam_pulang'] ?: '-') ?></td>
                  <td>
                    <?php if (!empty($h['maps_url'])): ?>
                      <a href="<?= esc($h['maps_url']) ?>" class="btn btn-sm btn-danger" target="_blank">📍 Lihat</a>
                    <?php else: echo '-'; endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($h['foto']) && file_exists($h['foto'])): ?>
                      <img src="<?= esc($h['foto']) ?>" style="width:60px;border-radius:6px">
                    <?php else: echo '-'; endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($h['file_upload']) && file_exists($h['file_upload'])): ?>
                      <a href="<?= esc($h['file_upload']) ?>" target="_blank">📎</a>
                    <?php else: echo '-'; endif; ?>
                  </td>
                  <td><?= esc($h['catatan'] ?: '-') ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
// show live datetime
function updateDateTime(){
  const dateEl = document.getElementById('date');
  const timeEl = document.getElementById('time');
  const now = new Date();
  const opt = { weekday:'long', day:'2-digit', month:'long', year:'numeric' };
  const timeOpt = { hour:'2-digit', minute:'2-digit', second:'2-digit' };
  
  if (dateEl) dateEl.innerHTML = now.toLocaleDateString('id-ID', opt);
  if (timeEl) timeEl.innerHTML = now.toLocaleTimeString('id-ID', timeOpt);
}
setInterval(updateDateTime,1000);
updateDateTime();

// auto-hide success message
function autoHideSuccessMessage() {
  const successMsg = document.getElementById('successMessage');
  if (successMsg) {
    setTimeout(() => {
      successMsg.style.transition = 'opacity 0.5s';
      successMsg.style.opacity = '0';
      setTimeout(() => {
        successMsg.remove();
        // refresh page to reset form after absen pulang
        if (successMsg.textContent.includes('pulang tercatat')) {
          setTimeout(() => {
            window.location.reload();
          }, 500);
        }
      }, 500);
    }, 3000);
  }
}

// auto-hide error message
function autoHideErrorMessage() {
  const errorMsg = document.getElementById('errorMessage');
  if (errorMsg) {
    setTimeout(() => {
      errorMsg.style.transition = 'opacity 0.5s';
      errorMsg.style.opacity = '0';
      setTimeout(() => {
        errorMsg.remove();
      }, 500);
    }, 3000);
  }
}

// run auto-hide when page loads
document.addEventListener('DOMContentLoaded', function() {
  autoHideSuccessMessage();
  autoHideErrorMessage();
});

// geolocation helpers
function setLatLonInputs(lat, lon){
  const latEl = document.getElementById('latitude');
  const lonEl = document.getElementById('longitude');
  const latP = document.getElementById('latitude_pulang');
  const lonP = document.getElementById('longitude_pulang');
  if(latEl) latEl.value = lat;
  if(lonEl) lonEl.value = lon;
  if(latP) latP.value = lat;
  if(lonP) lonP.value = lon;
}

function requestLocation(callback){
  if (!navigator.geolocation) {
    alert('Geolocation tidak didukung browser ini.');
    callback(false);
    return;
  }
  navigator.geolocation.getCurrentPosition(
    function(pos){
      setLatLonInputs(pos.coords.latitude, pos.coords.longitude);
      callback(true);
    },
    function(err){
      alert('Gagal mendapatkan lokasi. Pastikan GPS aktif dan izinkan akses lokasi.');
      callback(false);
    },
    {timeout:15000, enableHighAccuracy:true}
  );
}

function submitAbsen(action){
  requestLocation(function(ok){
    if (!ok) return;
    // set action field
    document.getElementById('actionField').value = action;
    // if action requires file (izin/sakit) we keep same form; server checks file presence
    // set statusSelect if needed
    if (action==='izin' || action==='sakit') document.getElementById('statusSelect').value = (action==='izin'?'Izin':'Sakit');
    // finally submit
    document.getElementById('formAbsen').submit();
  });
}

function submitPulang(){
  requestLocation(function(ok){
    if (!ok) return;
    document.getElementById('formPulang').submit();
  });
}

// preview profile photo
function previewProfilePhoto(input) {
  const preview = document.getElementById('photoPreview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.innerHTML = '<img src="' + e.target.result + '" class="upload-preview" alt="Preview">';
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    preview.innerHTML = '';
  }
}

// update profile avatar after successful upload
function updateProfileAvatar(newPhotoPath) {
  const avatar = document.getElementById('profileAvatar');
  if (avatar && newPhotoPath) {
    avatar.src = newPhotoPath + '?t=' + new Date().getTime(); // Add timestamp to prevent caching
  }
}

</script>

</body>
</html>