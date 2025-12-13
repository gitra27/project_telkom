<?php
// dashboard.php - User Dashboard (full)
// Requirements: config.php must call session_start() and set $conn (mysqli)

include "config.php";

// Protect
if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit();
}

$nik  = $_SESSION['nik'];
$nama = $_SESSION['nama'] ?? 'User';

// helpers
function esc($v){ global $conn; return htmlspecialchars(mysqli_real_escape_string($conn, trim($v ?? ''))); }
function try_mkdir($dir){ if(!is_dir($dir)) @mkdir($dir,0777,true); }

// ensure upload folders
try_mkdir(__DIR__ . '/uploads');
try_mkdir(__DIR__ . '/uploads/foto');
try_mkdir(__DIR__ . '/uploads/file');

// today vars
$today = date('Y-m-d');
$time_now = date('H:i:s');
$jam_now = date('H:i');

// fetch user
$uq = mysqli_query($conn, "SELECT * FROM tb_karyawan WHERE nik='".esc($nik)."' LIMIT 1");
$user = $uq && mysqli_num_rows($uq) ? mysqli_fetch_assoc($uq) : null;
$lantai = $user['lantai'] ?? '';

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
            // location required
            if ($lat === '' || $lon === '') { $err = "Lokasi wajib untuk absen masuk."; }
            else {
                // determine status: <=09:00 => Hadir, >09:00 => Telat
                $jam_limit = strtotime(date('Y-m-d').' 09:00:00');
                $is_late = time() > $jam_limit;
                $status = $is_late ? 'Telat' : 'Hadir';
                if ($exists) {
                    // update if jam_masuk empty
                    if (empty($row['jam_masuk'])) {
                        $sql = "UPDATE tb_absensi SET jam_masuk='$time_now', status='".esc($status)."', catatan='".mysqli_real_escape_string($conn,$catatan)."', foto='".mysqli_real_escape_string($conn,$uploaded_foto)."', file_upload='".mysqli_real_escape_string($conn,$uploaded_file)."', latitude='".esc($lat)."', longitude='".esc($lon)."', maps_url='".mysqli_real_escape_string($conn,$maps_url)."' WHERE id_absen='".$row['id_absen']."'";
                        mysqli_query($conn, $sql);
                        $msg = "Absen masuk tercatat. Status: $status";
                    } else {
                        $err = "Absen masuk sudah tercatat hari ini.";
                    }
                } else {
                    $sql = "INSERT INTO tb_absensi (nik,tanggal,jam_masuk,status,catatan,foto,file_upload,latitude,longitude,maps_url) VALUES ('".esc($nik)."','$today','$time_now','".esc($status)."','".mysqli_real_escape_string($conn,$catatan)."','".mysqli_real_escape_string($conn,$uploaded_foto)."','".mysqli_real_escape_string($conn,$uploaded_file)."','".esc($lat)."','".esc($lon)."','".mysqli_real_escape_string($conn,$maps_url)."')";
                    mysqli_query($conn, $sql);
                    $msg = "Absen masuk tercatat. Status: $status";
                }
            }
        }

        elseif ($action === 'pulang') {
            if (!$exists) $err = "Belum ada record absen masuk hari ini.";
            else {
                if (!empty($row['jam_pulang'])) $err = "Absen pulang sudah tercatat.";
                else {
                    if ($lat === '' || $lon === '') { $err = "Lokasi wajib untuk absen pulang."; }
                    else {
                        $sql = "UPDATE tb_absensi SET jam_pulang='$time_now', status='Selesai', latitude_pulang='".esc($lat)."', longitude_pulang='".esc($lon)."', maps_url_pulang='".mysqli_real_escape_string($conn,$maps_url)."' WHERE id_absen='".$row['id_absen']."'";
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
                    mysqli_query($conn, "UPDATE tb_absensi SET jam_masuk=NULL, jam_pulang=NULL, status='".esc($status)."', catatan='".mysqli_real_escape_string($conn,$catatan)."', file_upload='".mysqli_real_escape_string($conn,$uploaded_file)."' WHERE id_absen='".$row['id_absen']."'");
                } else {
                    mysqli_query($conn, "INSERT INTO tb_absensi (nik,tanggal,status,catatan,file_upload) VALUES ('".esc($nik)."','$today','".esc($status)."','".mysqli_real_escape_string($conn,$catatan)."','".mysqli_real_escape_string($conn,$uploaded_file)."')");
                }
                $msg = "Status $status tercatat untuk hari ini.";
            }
        }

        elseif ($action === 'save_note') {
            // save or update note
            if ($exists) {
                mysqli_query($conn, "UPDATE tb_absensi SET catatan='".mysqli_real_escape_string($conn,$catatan)."' WHERE id_absen='".$row['id_absen']."'");
            } else {
                mysqli_query($conn, "INSERT INTO tb_absensi (nik,tanggal,status,catatan) VALUES ('".esc($nik)."','$today','Belum Absen','".mysqli_real_escape_string($conn,$catatan)."')");
            }
            $msg = "Catatan disimpan.";
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
<style>
:root{--telkom:#ff0033}
body{background:#f4f6f9;font-family:Inter,Arial,Helvetica,sans-serif}
.header{background:#fff;border-bottom:1px solid #e6e9ee;padding:12px 20px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:12px}
.brand img{height:40px}
.telkom{color:var(--telkom)}
.card-profile{border-radius:12px}
.avatar{width:80px;height:80px;border-radius:50%;object-fit:cover}
.stat-card{border-radius:10px}
.small-muted{font-size:13px;color:#6b7280}
.upload-preview{max-width:120px;max-height:120px;object-fit:cover;border-radius:8px}
.table-scroll{max-height:420px;overflow:auto}
.badge-telkom{background:var(--telkom);color:#fff}
</style>
</head>
<body>

<!-- HEADER -->
<div class="header mb-3">
  <div class="brand">
    <img src="telkom2.png" alt="Telkom" onerror="this.style.display='none'">
    <div>
      <div style="font-weight:700;color:#111">Sistem Presensi Magang <span class="small-muted">- Dashboard User</span></div>
      <div class="small-muted">Halo, <?= esc($nama); ?> (<?= esc($nik); ?>) — <?= esc($user['asal_sekolah'] ?? '') ?></div>
    </div>
  </div>

  <div style="display:flex;gap:12px;align-items:center">
    <div id="datetime" class="small-muted text-end"></div>
    <a href="riwayat_absen.php" class="btn btn-outline-secondary btn-sm">Riwayat</a>
    <a href="logout.php" class="btn btn-outline-dark btn-sm">Logout</a>
  </div>
</div>

<div class="container py-3">
  <div class="row g-3">
    <!-- left -->
    <div class="col-lg-4">
      <div class="card p-3 mb-3 card-profile">
        <div class="d-flex gap-3 align-items-center">
          <img src="<?= esc($user['photo_path'] ?? 'https://via.placeholder.com/80') ?>" alt="avatar" class="avatar">
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
          <input type="file" name="profile_photo" accept="image/*" class="form-control form-control-sm mb-2">
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
        <h6>7 Hari Terakhir</h6>
        <ul class="list-group list-group-flush">
          <?php
          $sevenQ = mysqli_query($conn, "SELECT tanggal,status,jam_masuk,jam_pulang FROM tb_absensi WHERE nik='".esc($nik)."' ORDER BY tanggal DESC LIMIT 7");
          if ($sevenQ && mysqli_num_rows($sevenQ) > 0):
            while($s = mysqli_fetch_assoc($sevenQ)):
          ?>
            <li class="list-group-item d-flex justify-content-between">
              <div>
                <strong><?= date("d M", strtotime($s['tanggal'])); ?></strong><br>
                <small class="small-muted"><?= $s['status'] ?: 'Belum Absen' ?></small>
              </div>
              <div class="small-muted"><?= ($s['jam_masuk'] ?: '-') ?></div>
            </li>
          <?php endwhile; else: ?>
            <li class="list-group-item">Belum ada data.</li>
          <?php endif; ?>
        </ul>
      </div>

    </div>

    <!-- right -->
    <div class="col-lg-8">
      <div class="card p-3 mb-3">
        <h5>Status Absensi Hari Ini</h5>

        <?php if ($err): ?>
          <div class="alert alert-danger"><?= esc($err) ?></div>
        <?php endif; ?>
        <?php if ($msg): ?>
          <div class="alert alert-success"><?= esc($msg) ?></div>
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
              <button type="button" class="btn btn-outline-secondary" onclick="submitAbsen('izin')">Kirim Izin</button>
              <button type="button" class="btn btn-outline-warning" onclick="submitAbsen('sakit')">Kirim Sakit</button>
            </div>
            <div class="small-muted mt-2">Catatan: Absen masuk: jam 08.00–09.00 (lebih dari 09.00 = Telat)</div>
          </form>

        <?php else: ?>
          <!-- already have record -->
          <div class="row">
            <div class="col-md-6">
              <p><strong>Status:</strong> <?= esc($absenHariIni['status'] ?? 'Belum Absen') ?></p>
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

          <?php if (empty($absenHariIni['jam_pulang'])): ?>
            <form method="POST" id="formPulang">
              <input type="hidden" name="nik" value="<?= esc($nik) ?>">
              <input type="hidden" name="action" value="pulang">
              <input type="hidden" name="latitude" id="latitude_pulang">
              <input type="hidden" name="longitude" id="longitude_pulang">
              <button type="button" class="btn btn-telkom" onclick="submitPulang()">Kirim Absen Pulang</button>
            </form>
            <div class="small-muted mt-2">Absen pulang akan menambahkan jam pulang pada record hari ini.</div>
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
  const el = document.getElementById('datetime');
  const now = new Date();
  const opt = { weekday:'long', day:'2-digit', month:'long', year:'numeric' };
  if (el) el.innerHTML = now.toLocaleDateString('id-ID', opt) + '<br><small>' + now.toLocaleTimeString('id-ID') + '</small>';
}
setInterval(updateDateTime,1000);
updateDateTime();

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

// request location and callback
function requestLocation(cb){
  if (!navigator.geolocation) { alert('Browser tidak mendukung Geolocation.'); cb(false); return; }
  navigator.geolocation.getCurrentPosition(function(pos){
    setLatLonInputs(pos.coords.latitude, pos.coords.longitude);
    cb(true);
  }, function(err){
    alert('Gagal mendapatkan lokasi. Pastikan izin lokasi diizinkan.');
    cb(false);
  }, { enableHighAccuracy: true, timeout: 10000 });
}

// submit absen (masuk/izin/sakit)
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

// submit pulang
function submitPulang(){
  requestLocation(function(ok){
    if (!ok) return;
    document.getElementById('formPulang').submit();
  });
}
</script>

</body>
</html>
