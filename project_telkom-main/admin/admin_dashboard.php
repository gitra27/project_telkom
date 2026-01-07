<?php
// ENABLE ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 0);

// START SESSION SECURELY
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// INCLUDE CONFIGURATION
require_once '../config.php';

// VALIDATE SESSION
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header('Location: login_admin.php');
    exit;
}

// GET ADMIN DATA WITH VALIDATION
$nama_admin = $_SESSION['admin']['nama_admin'] ?? '';
$lantai_admin = $_SESSION['admin']['lantai'] ?? '';

// GENERATE CSRF TOKEN SECURELY
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// INITIALIZE MESSAGE VARIABLES
$success = '';
$error = '';

// DEBUG LOGGING
error_log("ADMIN SESSION: " . print_r($_SESSION['admin'], true));
error_log("LANTAI ADMIN: " . $lantai_admin);

// HELPER FUNCTIONS
/**
 * Execute query with error handling
 * @param string $sql
 * @param string $operation
 * @return bool
 */
function executeQuery($sql, $operation = 'query') {
    global $conn;
    
    try {
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            error_log("DATABASE ERROR: $operation failed - " . mysqli_error($conn));
            return false;
        }
        
        error_log("DATABASE SUCCESS: $operation executed successfully");
        return $result;
    } catch (Exception $e) {
        error_log("DATABASE EXCEPTION: $operation failed - " . $e->getMessage());
        return false;
    }
}

/**
 * Get table columns with error handling
 * @param string $table
 * @return array
 */
function getTableColumns($table) {
    global $conn;
    
    try {
        $result = mysqli_query($conn, "SHOW COLUMNS FROM $table");
        if (!$result) {
            error_log("COLUMNS ERROR: Failed to get columns from $table");
            return [];
        }
        
        $columns = [];
        while ($col = mysqli_fetch_assoc($result)) {
            $columns[] = $col['Field'];
        }
        
        error_log("COLUMNS SUCCESS: Found " . count($columns) . " columns in $table");
        return $columns;
    } catch (Exception $e) {
        error_log("COLUMNS EXCEPTION: Failed to get columns from $table - " . $e->getMessage());
        return [];
    }
}

// HANDLE POST ACTIONS WITH VALIDATION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_post = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (empty($csrf_post) || !hash_equals($_SESSION['csrf_token'], $csrf_post)) {
        $error = 'Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.';
        $action = '';
    }
    
    switch ($action) {
        case 'tambah_user':
            // GET AND SANITIZE INPUT
            $nik_user    = mysqli_real_escape_string($conn, $_POST['nik_user'] ?? '');
            $nama_user   = mysqli_real_escape_string($conn, $_POST['nama_user'] ?? '');
            $plain_pass  = $_POST['password'] ?? '';
            $password    = password_hash($plain_pass, PASSWORD_DEFAULT);
            $divisi      = mysqli_real_escape_string($conn, $_POST['divisi'] ?? '');
            $start_date  = mysqli_real_escape_string($conn, $_POST['start_date'] ?? '');
            $end_date    = mysqli_real_escape_string($conn, $_POST['end_date'] ?? '');

            if ($nik_user === '' || $nama_user === '' || $plain_pass === '') {
                $error = 'NIK, Nama, dan Password wajib diisi.';
                break;
            }
            
            // VALIDATE NIK DUPLICATE
            $cek_nik = executeQuery("SELECT nik FROM tb_karyawan WHERE nik='$nik_user'", 'cek_nik');
            if ($cek_nik && mysqli_num_rows($cek_nik) > 0) {
                $error = "NIK sudah terdaftar!";
            } else {
                $tb_cols = getTableColumns('tb_karyawan');
                $insert_fields = ['nik', 'nama', 'password'];
                $insert_values = [
                    "'$nik_user'",
                    "'$nama_user'",
                    "'" . mysqli_real_escape_string($conn, $password) . "'"
                ];
                
                if (in_array('lantai', $tb_cols, true)) {
                    $insert_fields[] = 'lantai';
                    $insert_values[] = "'" . mysqli_real_escape_string($conn, $lantai_admin) . "'";
                }
                if (in_array('divisi', $tb_cols, true)) {
                    $insert_fields[] = 'divisi';
                    $insert_values[] = "'$divisi'";
                } elseif (in_array('departemen', $tb_cols, true)) {
                    $insert_fields[] = 'departemen';
                    $insert_values[] = "'$divisi'";
                }
                if (in_array('start_date', $tb_cols, true)) {
                    $insert_fields[] = 'start_date';
                    $insert_values[] = $start_date !== '' ? "'$start_date'" : 'NULL';
                }
                if (in_array('end_date', $tb_cols, true)) {
                    $insert_fields[] = 'end_date';
                    $insert_values[] = $end_date !== '' ? "'$end_date'" : 'NULL';
                }
                if (in_array('account_active', $tb_cols, true)) {
                    $insert_fields[] = 'account_active';
                    $insert_values[] = '1';
                } elseif (in_array('status', $tb_cols, true)) {
                    $insert_fields[] = 'status';
                    $insert_values[] = "'aktif'";
                }
                if (in_array('created_at', $tb_cols, true)) {
                    $insert_fields[] = 'created_at';
                    $insert_values[] = 'NOW()';
                }
                
                $fields_sql = implode(', ', array_map(static fn($f) => "`$f`", $insert_fields));
                $values_sql = implode(', ', $insert_values);
                $insert = executeQuery("INSERT INTO tb_karyawan ($fields_sql) VALUES ($values_sql)", 'tambah_user');
                
                if ($insert) {
                    $success = "User berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan user: " . mysqli_error($conn);
                }
            }
            break;
            
        case 'edit_user':
            // EDIT USER PROCESS (SIMPLIFIED)
            error_log("EDIT USER: POST data received: " . print_r($_POST, true));
            
            $edit_id      = mysqli_real_escape_string($conn, $_POST['edit_id'] ?? '');
            $edit_nama    = mysqli_real_escape_string($conn, $_POST['edit_nama'] ?? '');
            $edit_divisi  = mysqli_real_escape_string($conn, $_POST['edit_divisi'] ?? '');
            $edit_start   = mysqli_real_escape_string($conn, $_POST['edit_start_date'] ?? '');
            $edit_end     = mysqli_real_escape_string($conn, $_POST['edit_end_date'] ?? '');
            $edit_status  = mysqli_real_escape_string($conn, $_POST['edit_status'] ?? '');
            $reset_pass   = isset($_POST['reset_password']) ? '1' : '0';
            $new_password = $_POST['new_password'] ?? '';
            
            if ($edit_id === '' || $edit_nama === '') {
                $error = 'ID dan Nama wajib diisi.';
                error_log("EDIT USER ERROR: Missing required fields");
                break;
            }
            
            // Simple update query dengan pengecekan kolom
            $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM tb_karyawan");
            
            // Cek apakah query berhasil
            if (!$check_columns) {
                error_log("COLUMNS QUERY FAILED: " . mysqli_error($conn));
                $error = "Gagal memeriksa struktur tabel!";
                break;
            }
            
            $columns = [];
            while ($col = mysqli_fetch_assoc($check_columns)) {
                $columns[] = $col['Field'];
                error_log("FOUND COLUMN: " . $col['Field']);
            }
            error_log("FOUND COLUMN: " . $col['Field']);
            
            // Cek apakah ada kolom yang dibutuhkan
            if (empty($columns)) {
                error_log("NO COLUMNS FOUND");
                $error = "Struktur tabel tidak valid!";
                break;
            }
            
            // Build update query berdasarkan kolom yang ada
            $update_parts = ["nama='$edit_nama'"];
            
            // Cek kolom divisi/departemen
            if (in_array('divisi', $columns)) {
                $update_parts[] = "divisi='$edit_divisi'";
            } elseif (in_array('departemen', $columns)) {
                $update_parts[] = "departemen='$edit_divisi'";
            } else {
                // Jika tidak ada keduanya, gunakan yang pertama (divisi)
                $update_parts[] = "divisi='$edit_divisi'";
                error_log("WARNING: Neither divisi nor departemen column found, using divisi as fallback");
            }
            
            // Tambahkan kolom lain jika ada
            if (in_array('start_date', $columns)) {
                $update_parts[] = "start_date='$edit_start'";
            }
            if (in_array('end_date', $columns)) {
                $update_parts[] = "end_date='$edit_end'";
            }
            
            // Cek kolom status
            if (in_array('account_active', $columns)) {
                $update_parts[] = "account_active='$edit_status'";
            } elseif (in_array('status', $columns)) {
                $status_text = $edit_status === '1' ? 'aktif' : 'nonaktif';
                $update_parts[] = "status='$status_text'";
            }
            
            // Tambahkan password reset jika ada
            if ($reset_pass === '1' && $new_password !== '') {
                $hashed_pass = password_hash($new_password, PASSWORD_DEFAULT);
                $update_parts[] = "password='$hashed_pass'";
            }
            
            $update_query = "UPDATE tb_karyawan SET " . implode(', ', $update_parts) . " WHERE id='$edit_id'";
            
            error_log("EDIT USER QUERY: $update_query");
            
            $update = mysqli_query($conn, $update_query);
            
            if ($update) {
                $success = "Data user berhasil diperbarui!";
                error_log("EDIT USER SUCCESS: User $edit_id updated");
            } else {
                $error = "Gagal update user: " . mysqli_error($conn);
                error_log("EDIT USER ERROR: " . mysqli_error($conn));
            }
            break;
            
        case 'delete_user':
            // DELETE USER PROCESS
            $delete_id = mysqli_real_escape_string($conn, $_POST['delete_id'] ?? '');
            
            if ($delete_id === '') {
                $error = 'ID user wajib diisi.';
                break;
            }
            
            // Check if user exists
            $cek_user = mysqli_query($conn, "SELECT nama FROM tb_karyawan WHERE id='$delete_id'");
            if (mysqli_num_rows($cek_user) === 0) {
                $error = 'User tidak ditemukan!';
            } else {
                $delete = mysqli_query($conn, "DELETE FROM tb_karyawan WHERE id='$delete_id'");
                if ($delete) {
                    $success = "User berhasil dihapus!";
                } else {
                    $error = "Gagal hapus user: " . mysqli_error($conn);
                }
            }
            break;
            
        case 'export_users':
            // EXPORT USERS TO CSV
            try {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="data_users_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                
                // CSV Header
                fputcsv($output, ['NIK', 'Nama', 'Divisi', 'Start Date', 'End Date', 'Status', 'Lantai']);
                
                // Get all users data using helper function
                $export_query = executeQuery("SELECT * FROM tb_karyawan WHERE lantai='$lantai_admin' ORDER BY nama ASC", 'export_users');
                
                if ($export_query && mysqli_num_rows($export_query) > 0) {
                    while ($user = mysqli_fetch_assoc($export_query)) {
                        $status = 'Aktif';
                        if (array_key_exists('account_active', $user)) {
                            $status = (int)$user['account_active'] === 1 ? 'Aktif' : 'Nonaktif';
                        } elseif (array_key_exists('status', $user)) {
                            $status = strtolower((string)$user['status']) === 'aktif' ? 'Aktif' : 'Nonaktif';
                        }
                        
                        $divisi = $user['divisi'] ?? $user['departemen'] ?? '-';
                        
                        fputcsv($output, [
                            $user['nik'],
                            $user['nama'],
                            $divisi,
                            $user['start_date'] ?? '-',
                            $user['end_date'] ?? '-',
                            $status,
                            $user['lantai'] ?? '-'
                        ]);
                    }
                }
                
                fclose($output);
                exit;
            } catch (Exception $e) {
                error_log("EXPORT USERS ERROR: " . $e->getMessage());
                $error = "Gagal export data users!";
            }
            break;
            
        case 'export_attendance':
            // EXPORT ATTENDANCE TO CSV
            try {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="riwayat_absensi_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                
                // CSV Header
                fputcsv($output, ['Tanggal', 'NIK', 'Nama', 'Divisi', 'Jam Masuk', 'Jam Pulang', 'Status', 'Catatan']);
                
                // Get attendance data
                $bulan_export = $_POST['bulan_export'] ?? date('m');
                $tahun_export = $_POST['tahun_export'] ?? date('Y');
                
                $attendance_query = executeQuery("
                    SELECT a.*, k.nama, k.divisi, k.departemen 
                    FROM tb_absensi a 
                    JOIN tb_karyawan k ON a.nik = k.nik 
                    WHERE k.lantai='$lantai_admin' 
                    AND MONTH(a.tanggal)='$bulan_export' 
                    AND YEAR(a.tanggal)='$tahun_export' 
                    ORDER BY a.tanggal DESC, k.nama ASC
                ", 'export_attendance');
                
                if ($attendance_query && mysqli_num_rows($attendance_query) > 0) {
                    while ($absen = mysqli_fetch_assoc($attendance_query)) {
                        $divisi = $absen['divisi'] ?? $absen['departemen'] ?? '-';
                        
                        fputcsv($output, [
                            $absen['tanggal'],
                            $absen['nik'],
                            $absen['nama'],
                            $divisi,
                            $absen['jam_masuk'] ?? '-',
                            $absen['jam_pulang'] ?? '-',
                            $absen['status'] ?? '-',
                            $absen['catatan'] ?? '-'
                        ]);
                    }
                }
                
                fclose($output);
                exit;
            } catch (Exception $e) {
                error_log("EXPORT ATTENDANCE ERROR: " . $e->getMessage());
                $error = "Gagal export data absensi!";
            }
            break;
            
        default:
            // UNKNOWN ACTION
            $error = "Aksi tidak dikenal!";
            break;
    }
}

// GET DATE PARAMETERS
$today = date('Y-m-d');
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// CHECK AND NOTIFY USERS WITH EXPIRED INTERNSHIP (SIMPLIFIED)
$expired_users = [];
$expiring_soon = [];

// Simple query for expired users
$check_expired = mysqli_query($conn, "
    SELECT id, nik, nama, end_date 
    FROM tb_karyawan 
    WHERE lantai='$lantai_admin' 
    AND end_date < '$today'
    ORDER BY end_date DESC
");

if ($check_expired) {
    while ($user = mysqli_fetch_assoc($check_expired)) {
        $expired_users[] = $user;
    }
}

// Simple query for expiring users (1-2 days)
$next_two_days = date('Y-m-d', strtotime('+2 days'));
$check_expiring = executeQuery("
    SELECT id, nik, nama, end_date 
    FROM tb_karyawan 
    WHERE end_date BETWEEN '$today' AND '$next_two_days'
    ORDER BY end_date ASC
", 'check_expiring');

if ($check_expiring) {
    while ($user = mysqli_fetch_assoc($check_expiring)) {
        $expiring_soon[] = $user;
    }
}

// GET STATISTICS
$totalHadir = 0;
$totalIzin = 0;
$totalSakit = 0;
$totalUser = 0;

try {
    // Get attendance statistics using helper functions
    $hadir_result = executeQuery("
        SELECT COUNT(*) as total 
        FROM tb_absensi a 
        JOIN tb_karyawan k ON a.nik=k.nik 
        WHERE a.tanggal='$today' AND k.lantai='$lantai_admin' AND LOWER(a.status)='hadir'
    ", 'get_hadir_count');
    
    if ($hadir_result) {
        $totalHadir = $hadir_result->fetch_assoc()['total'] ?? 0;
    }

    $izin_result = executeQuery("
        SELECT COUNT(*) as total 
        FROM tb_absensi a 
        JOIN tb_karyawan k ON a.nik=k.nik 
        WHERE a.tanggal='$today' AND k.lantai='$lantai_admin' AND LOWER(a.status)='izin'
    ", 'get_izin_count');
    
    if ($izin_result) {
        $totalIzin = $izin_result->fetch_assoc()['total'] ?? 0;
    }

    $sakit_result = executeQuery("
        SELECT COUNT(*) as total 
        FROM tb_absensi a 
        JOIN tb_karyawan k ON a.nik=k.nik 
        WHERE a.tanggal='$today' AND k.lantai='$lantai_admin' AND LOWER(a.status)='sakit'
    ", 'get_sakit_count');
    
    if ($sakit_result) {
        $totalSakit = $sakit_result->fetch_assoc()['total'] ?? 0;
    }

    // Get user statistics with ALL users (like superadmin)
    $k_cols = getTableColumns('tb_karyawan');
    
    // Get ALL users count (like superadmin)
    $user_result = executeQuery("SELECT COUNT(*) as total FROM tb_karyawan", 'get_all_user_count');
    
    if ($user_result) {
        $totalUser = $user_result->fetch_assoc()['total'] ?? 0;
    }
    
    error_log("STATISTICS SUCCESS: Hadir=$totalHadir, Izin=$totalIzin, Sakit=$totalSakit, User=$totalUser (ALL USERS LIKE SUPERADMIN)");
} catch (Exception $e) {
    error_log("Statistics query error: " . $e->getMessage());
}

// GET TODAY'S ATTENDANCE - DEBUG VERSION
try {
    error_log("DEBUG: Today's date = $today");
    error_log("DEBUG: Admin lantai = $lantai_admin");
    
    // First, check if there's any attendance data at all
    $check_all_attendance = executeQuery("SELECT COUNT(*) as total FROM tb_absensi WHERE tanggal='$today'", 'check_all_attendance');
    if ($check_all_attendance) {
        $total_all = $check_all_attendance->fetch_assoc()['total'];
        error_log("DEBUG: Total attendance records for today = $total_all");
    }
    
    // Check users on this floor
    $check_floor_users = executeQuery("SELECT COUNT(*) as total FROM tb_karyawan WHERE lantai='$lantai_admin'", 'check_floor_users');
    if ($check_floor_users) {
        $total_floor_users = $check_floor_users->fetch_assoc()['total'];
        error_log("DEBUG: Total users on floor $lantai_admin = $total_floor_users");
    }
    
    // Get attendance data with more detailed logging
    $q_absen_today = executeQuery("
        SELECT a.*, k.nama, k.lantai as user_lantai
        FROM tb_absensi a 
        JOIN tb_karyawan k ON a.nik = k.nik 
        WHERE a.tanggal = '$today'
        ORDER BY a.jam_masuk DESC
    ", 'get_attendance_today');
    
    if ($q_absen_today && mysqli_num_rows($q_absen_today) > 0) {
        $total_records = mysqli_num_rows($q_absen_today);
        error_log("DEBUG: Found $total_records attendance records for today (all floors)");
        
        // Filter by floor in PHP to debug
        $filtered_records = [];
        while ($row = mysqli_fetch_assoc($q_absen_today)) {
            error_log("DEBUG: User " . $row['nama'] . " (NIK: " . $row['nik'] . ") on floor " . ($row['user_lantai'] ?? 'NULL') . " with status " . $row['status']);
            
            if ($row['user_lantai'] == $lantai_admin) {
                $filtered_records[] = $row;
            }
        }
        
        error_log("DEBUG: Filtered records for floor $lantai_admin = " . count($filtered_records));
        
        // Reset pointer and use filtered data
        mysqli_data_seek($q_absen_today, 0);
    } else {
        error_log("DEBUG: No attendance records found for today");
    }
} catch (Exception $e) {
    error_log("ATTENDANCE EXCEPTION: " . $e->getMessage());
    $q_absen_today = false;
}

// GET ALL USERS ON FLOOR - DEBUG VERSION
try {
    error_log("DEBUG: Getting users for floor $lantai_admin");
    
    // First check all users
    $check_all_users = executeQuery("SELECT COUNT(*) as total FROM tb_karyawan", 'check_all_users');
    if ($check_all_users) {
        $total_all_users = $check_all_users->fetch_assoc()['total'];
        error_log("DEBUG: Total users in database = $total_all_users");
    }
    
    // Get users on this floor
    $q_users = executeQuery("SELECT id, nik, nama, divisi, departemen, start_date, end_date, account_active, status, lantai FROM tb_karyawan WHERE lantai='$lantai_admin' ORDER BY nama ASC", 'get_users');
    
    if ($q_users && mysqli_num_rows($q_users) > 0) {
        $user_count = mysqli_num_rows($q_users);
        error_log("DEBUG: Found $user_count users for lantai: $lantai_admin");
        
        // Log each user for debugging
        while ($user = mysqli_fetch_assoc($q_users)) {
            error_log("DEBUG: User - " . $user['nama'] . " (NIK: " . $user['nik'] . ") on floor " . ($user['lantai'] ?? 'NULL') . " in " . ($user['divisi'] ?? $user['departemen'] ?? 'Unknown'));
        }
        
        // Reset pointer for display
        mysqli_data_seek($q_users, 0);
    } else {
        error_log("DEBUG: No users found for lantai: $lantai_admin");
        
        // Check if there are users on other floors
        $check_other_floors = executeQuery("SELECT DISTINCT lantai, COUNT(*) as count FROM tb_karyawan GROUP BY lantai", 'check_other_floors');
        if ($check_other_floors) {
            while ($floor = mysqli_fetch_assoc($check_other_floors)) {
                error_log("DEBUG: Floor " . $floor['lantai'] . " has " . $floor['count'] . " users");
            }
        }
    }
} catch (Exception $e) {
    error_log("USERS QUERY EXCEPTION: " . $e->getMessage());
    $q_users = false;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com;">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Admin Lantai <?= htmlspecialchars($lantai_admin) ?> - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --telkom-primary: #e31937;
            --telkom-secondary: #003d7a;
            --telkom-accent: #ff6b35;
            --telkom-light: #f8f9fa;
            --telkom-dark: #2c3e50;
            --telkom-gray: #6c757d;
            --telkom-success: #28a745;
            --telkom-warning: #ffc107;
            --telkom-danger: #dc3545;
            --gradient-telkom: linear-gradient(135deg, var(--telkom-primary) 0%, var(--telkom-secondary) 100%);
            --gradient-card: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.16);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23e31937" opacity="0.03"/><circle cx="75" cy="75" r="1" fill="%23003d7a" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            pointer-events: none;
            z-index: 0;
        }

        /* =============================================================================
           SIDEBAR STYLES
           ============================================================================= */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
            z-index: 1000;
            overflow-y: auto;
            border-left: 4px solid rgba(255,255,255,0.1);
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            pointer-events: none;
            z-index: 1;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 20px 20px 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .sidebar-header h4 {
            color: #2c3e50;
            font-weight: 700;
            font-size: 20px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar-header h4 i {
            color: #e31937;
        }

        .sidebar-header .subtitle {
            color: #6c757d;
            font-size: 12px;
            margin-top: 0;
        }

        .sidebar a {
            color: #2c3e50;
            display: flex;
            align-items: center;
            padding: 14px 20px;
            text-decoration: none;
            margin-bottom: 8px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .sidebar a::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateY(-50%);
            transition: left 0.5s ease;
        }

        .sidebar a:hover::before {
            left: 100%;
        }

        .sidebar a:hover, .sidebar a.active {
            background: var(--gradient-telkom);
            color: #ffffff;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(227,25,55,0.2);
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
            color: #6c757d;
            transition: color 0.3s ease;
        }

        .sidebar a:hover i,
        .sidebar a.active i {
            color: #ffffff;
        }

        /* =============================================================================
           CONTENT AREA
           ============================================================================= */
        .content {
            margin-left: 260px;
            padding: 20px;
            position: relative;
            z-index: 1;
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* =============================================================================
           PAGE HEADER
           ============================================================================= */
        .page-header {
            margin: 0 0 30px 0;
            position: relative;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            color: white;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(227,25,55,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 2;
        }

        .page-title i {
            color: #ffffff;
            font-size: 28px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .page-subtitle {
            color: rgba(255,255,255,0.9);
            margin-top: 12px;
            font-size: 16px;
            position: relative;
            z-index: 2;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .card {
            background: var(--gradient-card);
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-telkom);
        }

        /* =============================================================================
           STATISTICS CARDS
           ============================================================================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 0 0 40px 0;
        }

        .stat-card {
            background: var(--gradient-card);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 1;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            transform: rotate(45deg);
            pointer-events: none;
            transition: all 0.4s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-card:hover::after {
            top: -30%;
            right: -30%;
        }

        .stat-card.success { 
            border-left: 5px solid #28a745; 
            background: linear-gradient(145deg, #ffffff 0%, #f8fff9 100%);
        }
        
        .stat-card.warning { 
            border-left: 5px solid #ffc107; 
            background: linear-gradient(145deg, #ffffff 0%, #fffbf0 100%);
        }
        
        .stat-card.info { 
            border-left: 5px solid #17a2b8; 
            background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%);
        }
        
        .stat-card.primary { 
            border-left: 5px solid var(--telkom-primary); 
            background: linear-gradient(145deg, #ffffff 0%, #fff5f5 100%);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stat-icon.success { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            color: white; 
        }
        
        .stat-icon.warning { 
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); 
            color: white; 
        }
        
        .stat-icon.info { 
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); 
            color: white; 
        }
        
        .stat-icon.primary { 
            background: linear-gradient(135deg, #e31937 0%, #003d7a 100%); 
            color: white; 
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: #003d7a;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: #6c757d;
            font-size: 15px;
            font-weight: 600;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-telkom {
            background: var(--gradient-telkom);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-telkom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .sidebar {
            background: var(--gradient-card);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 120px;
            height: fit-content;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--telkom-dark);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }

        .sidebar-menu a:hover {
            background: var(--telkom-light);
            color: var(--telkom-primary);
            transform: translateX(4px);
        }

        .sidebar-menu a.active {
            background: var(--gradient-telkom);
            color: white;
        }

        .table-container {
            position: relative;
            margin: 0 0 30px 0;
        }

        .table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-telkom);
            border-radius: 16px 16px 0 0;
            z-index: 1;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            position: relative;
            z-index: 2;
        }

        .table-title {
            font-size: 20px;
            font-weight: 600;
            color: #003d7a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-title i {
            color: #e31937;
            font-size: 18px;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .btn-table {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #e31937;
            background: white;
            color: #e31937;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-table:hover {
            background: #e31937;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(227,25,55,0.2);
        }

        .table-responsive {
            margin: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table {
            margin: 0;
            background: white;
        }

        .table thead th {
            background: var(--gradient-telkom);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 18px 15px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table thead th:first-child {
            border-top-left-radius: 12px;
        }

        .table thead th:last-child {
            border-top-right-radius: 12px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f5;
        }

        .table tbody tr:hover {
            background-color: rgba(227, 25, 55, 0.05);
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: #212529;
        }

        .badge-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
            color: white;
        }

        .badge-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .badge-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .modal-header {
            background: var(--gradient-telkom);
            color: white;
            border-radius: 16px 16px 0 0;
            border: none;
            padding: 20px 24px;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e9ecef;
        }

        .modal-dialog {
            margin: 20px auto;
        }

        /* Modal responsiveness */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 10px;
                width: calc(100% - 20px);
            }
            
            .modal-content {
                border-radius: 12px;
            }
            
            .modal-header {
                padding: 16px 20px;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .modal-footer {
                padding: 16px 20px;
                flex-direction: column;
                gap: 10px;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--telkom-primary);
            box-shadow: 0 0 0 0.2rem rgba(227,25,55,0.25);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--telkom-gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* =============================================================================
           MOBILE MENU TOGGLE
           ============================================================================= */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--gradient-telkom);
            border: none;
            border-radius: 12px;
            color: white;
            width: 50px;
            height: 50px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        .mobile-menu-toggle.active {
            background: var(--telkom-danger);
        }

        /* =============================================================================
           RESPONSIVE DESIGN
           ============================================================================= */
        @media (max-width: 1200px) {
            .sidebar {
                width: 260px;
                padding: 25px 20px;
            }
            
            .content {
                margin-left: 260px;
                padding: 30px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
        }
        
        @media (max-width: 992px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .content {
                margin-left: 0;
                padding: 80px 20px 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .page-header {
                padding: 20px;
                margin-top: 20px;
            }
            
            .page-title {
                font-size: 24px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .stat-value {
                font-size: 28px;
            }
            
            .page-title {
                font-size: 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .page-header {
                padding: 15px;
                margin-top: 15px;
            }
            
            .content {
                padding: 70px 15px 15px;
            }
            
            /* Table improvements for mobile */
            .table-container {
                margin: 20px 0;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px;
            }
            
            .table-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .btn-table {
                padding: 8px 12px;
                font-size: 12px;
                flex: 1;
                text-align: center;
                justify-content: center;
            }
            
            .table-responsive {
                border-radius: 8px;
            }
            
            .table thead th {
                padding: 12px 8px;
                font-size: 11px;
            }
            
            .table tbody td {
                padding: 12px 8px;
                font-size: 13px;
            }
            
            /* Hide some columns on very small screens */
            .table th:nth-child(2),
            .table td:nth-child(2) {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .mobile-menu-toggle {
                width: 45px;
                height: 45px;
                font-size: 16px;
                top: 15px;
                left: 15px;
            }
            
            .sidebar {
                width: 100%;
                max-width: 280px;
            }
            
            .sidebar-header h4 {
                font-size: 18px;
            }
            
            .sidebar a {
                padding: 12px 15px;
                font-size: 14px;
            }
            
            .sidebar a i {
                width: 20px;
                font-size: 14px;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 12px;
            }
            
            .table-actions {
                width: 100%;
                justify-content: space-between;
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-table {
                padding: 8px 12px;
                font-size: 12px;
                width: 100%;
            }
            
            .page-title {
                font-size: 18px;
            }
            
            .page-subtitle {
                font-size: 14px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            .stat-label {
                font-size: 13px;
            }
            
            /* Hide more table columns on very small screens */
            .table th:nth-child(2),
            .table td:nth-child(2),
            .table th:nth-child(4),
            .table td:nth-child(4) {
                display: none;
            }
            
            .table thead th {
                padding: 10px 6px;
                font-size: 10px;
            }
            
            .table tbody td {
                padding: 10px 6px;
                font-size: 12px;
            }
            
            .badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
        }

        /* =============================================================================
           VISUAL HIERARCHY IMPROVEMENTS
           ============================================================================= */
        
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e9ecef, transparent);
            margin: 40px 0;
            border: none;
        }

        .card-spacer {
            margin-bottom: 30px;
        }

        /* Improve alert positioning */
        .alert-container {
            position: sticky;
            top: 0;
            z-index: 100;
            margin-bottom: 20px;
            margin-top: 0;
        }

        /* Better modal backdrop */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        /* Improved button hover states */
        .btn-telkom:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }

        /* Better focus states for accessibility */
        .btn-telkom:focus,
        .form-control:focus,
        .form-select:focus,
        .btn-table:focus {
            outline: 2px solid var(--telkom-primary);
            outline-offset: 2px;
        }

        /* Smooth transitions */
        * {
            transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }

        /* Table Cell Styling */
        .date-cell, .user-cell, .time-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
        }

        .date-cell i, .user-cell i, .time-cell i {
            font-size: 14px;
            width: 20px;
            text-align: center;
        }

        .time-text {
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* Table Enhancements */
        .data-table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .data-table thead th {
            background: var(--gradient-telkom);
            color: white;
            padding: 16px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            border: none;
        }

        .data-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .data-table tbody tr:hover {
            background: rgba(227,25,55,0.05);
            transform: scale(1.01);
        }

        .data-table tbody td {
            padding: 16px;
            vertical-align: middle;
            border: none;
        }

        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 20px;
            }
            
            .sidebar {
                position: static;
                margin-bottom: 20px;
            }

            .date-cell, .user-cell, .time-cell {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .data-table {
                font-size: 0.85rem;
            }

            .data-table thead th,
            .data-table tbody td {
                padding: 8px;
            }
        }

        /* Form Container Styling */
        .form-container {
            background: var(--gradient-card);
            border-radius: 16px;
            padding: 0;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(227,25,55,0.1);
            overflow: hidden;
        }

        .form-header {
            background: var(--gradient-telkom);
            color: white;
            padding: 24px;
            text-align: center;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .form-section {
            padding: 24px;
            border-bottom: 1px solid #e9ecef;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            color: var(--telkom-primary);
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: var(--telkom-dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-text {
            color: var(--telkom-gray);
            font-size: 0.85rem;
            margin-top: 6px;
            display: block;
        }

        .form-footer {
            padding: 24px;
            background: #f8f9fa;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .alert-content {
            display: flex;
            align-items: center;
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: transparent;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-1px);
        }

        /* Responsive Form Design */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .form-section {
                padding: 20px;
            }

            .form-footer {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4>
                <i class="fas fa-shield-alt"></i>
                ADMIN LANTAI <?= htmlspecialchars($lantai_admin) ?>
            </h4>
            <div class="subtitle">Sistem Presensi Magang</div>
        </div>
        
        <a href="admin_dashboard.php" class="active">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a href="#" onclick="showProfileModal()">
            <i class="fas fa-user"></i>
            Profile
        </a>
        <a href="tambah_user.php">
            <i class="fas fa-user-plus"></i>
            Tambah User
        </a>
        <a href="data_user.php">
            <i class="fas fa-users"></i>
            Data User
        </a>
        <a href="riwayat_absen.php">
            <i class="fas fa-history"></i>
            Riwayat Absen
        </a>
        <a href="laporan_absen.php">
            <i class="fas fa-file-alt"></i>
            Laporan Absen
        </a>
        <a href="pengaturan.php">
            <i class="fas fa-cog"></i>
            Pengaturan
        </a>
        <a href="logout_admin.php">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <!-- Alert Container -->
        <div class="alert-container">
                <!-- Success/Error Messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Alert untuk user yang masa magang sudah habis -->
                <?php if (!empty($expired_users)): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Ada <?= count($expired_users) ?> user yang masa magangnya sudah habis:
                        <ul class="mb-0 mt-2">
                            <?php foreach ($expired_users as $user): ?>
                                <li>
                                    <strong><?= htmlspecialchars($user['nama']) ?></strong> (<?= htmlspecialchars($user['nik']) ?>) - 
                                    <small>Selesai: <?= date('d/m/Y', strtotime($user['end_date'])) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Alert untuk user yang akan habis masa magangnya -->
                <?php if (!empty($expiring_soon)): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Info!</strong> Ada <?= count($expiring_soon) ?> User Magangnya akan habis:
                        <ul class="mb-0 mt-2">
                            <?php foreach ($expiring_soon as $user): ?>
                                <li>
                                    <strong><?= htmlspecialchars($user['nama']) ?></strong> (<?= htmlspecialchars($user['nik']) ?>) - 
                                    <small>Selesai: <?= date('d/m/Y', strtotime($user['end_date'])) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-line"></i>
                Dashboard Admin Lantai <?= htmlspecialchars($lantai_admin) ?>
            </h1>
            <div class="page-subtitle">
                Selamat datang di panel administrasi sistem presensi magang
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value"><?= number_format($totalHadir) ?></div>
                <div class="stat-label">Hadir Hari Ini</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon warning">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-value"><?= number_format($totalIzin) ?></div>
                <div class="stat-label">Izin Hari Ini</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon info">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-value"><?= number_format($totalSakit) ?></div>
                <div class="stat-label">Sakit Hari Ini</div>
            </div>
            
            <div class="stat-card primary">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= number_format($totalUser) ?></div>
                <div class="stat-label">Total User Aktif</div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-table"></i>
                    Data Absensi Terbaru
                </h2>
                <div class="table-actions">
                    <a href="riwayat_absen.php" class="btn-table">
                        <i class="fas fa-history me-1"></i>
                        Lihat Semua
                    </a>
                    <a href="export_absen.php" class="btn-table">
                        <i class="fas fa-download me-1"></i>
                        Export
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_absen_today && mysqli_num_rows($q_absen_today) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($q_absen_today)): ?>
                                <?php if ($row['user_lantai'] == $lantai_admin): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($row['nik']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= $row['jam_masuk'] ?: '-' ?></td>
                                        <td><?= $row['jam_pulang'] ?: '-' ?></td>
                                        <td>
                                            <?php
                                            $status = $row['status'];
                                            $statusClass = '';
                                            $statusIcon = '';
                                            
                                            switch($status) {
                                                case 'Hadir':
                                                    $statusClass = 'badge-success';
                                                    $statusIcon = 'fa-check-circle';
                                                    break;
                                                case 'Telat':
                                                    $statusClass = 'badge-warning';
                                                    $statusIcon = 'fa-clock';
                                                    break;
                                                case 'Izin':
                                                    $statusClass = 'badge-primary';
                                                    $statusIcon = 'fa-info-circle';
                                                    break;
                                                case 'Sakit':
                                                    $statusClass = 'badge-warning';
                                                    $statusIcon = 'fa-heartbeat';
                                                    break;
                                                case 'Selesai':
                                                    $statusClass = 'badge-info';
                                                    $statusIcon = 'fa-check-double';
                                                    break;
                                                default:
                                                    $statusClass = 'badge-secondary';
                                                    $statusIcon = 'fa-question-circle';
                                            }
                                            ?>
                                            <span class="badge <?= $statusClass ?>">
                                                <i class="fas <?= $statusIcon ?> me-1"></i>
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h5>Belum Ada Data Absensi</h5>
                                        <p>Belum ada data absensi yang tercatat hari ini untuk lantai <?= htmlspecialchars($lantai_admin) ?>.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Tambah User Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="tambah_user">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NIK</label>
                                <input type="text" name="nik_user" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_user" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Divisi</label>
                                <input type="text" name="divisi" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Mulai PKL</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Selesai PKL</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            User akan ditambahkan ke lantai <strong><?= htmlspecialchars($lantai_admin) ?></strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-telkom">
                            <i class="fas fa-save me-2"></i>Simpan User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="edit_id" id="edit_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NIK</label>
                                <input type="text" id="edit_nik" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="edit_nama" id="edit_nama" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Divisi</label>
                                <input type="text" name="edit_divisi" id="edit_divisi" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status Akun</label>
                                <select name="edit_status" id="edit_status" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Mulai PKL</label>
                                <input type="date" name="edit_start_date" id="edit_start_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Selesai PKL</label>
                                <input type="date" name="edit_end_date" id="edit_end_date" class="form-control">
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-key me-2"></i>Reset Password
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="reset_password" name="reset_password">
                                    <label class="form-check-label" for="reset_password">
                                        Reset password user (centang untuk mengubah password)
                                    </label>
                                </div>
                                <div id="password_fields" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" name="new_password" id="new_password" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Konfirmasi Password</label>
                                        <input type="password" id="confirm_password" class="form-control">
                                        <div class="form-text">Password minimal 6 karakter</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-telkom">
                            <i class="fas fa-save me-2"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Detail User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="detail_avatar" src="" class="rounded-circle" style="width: 80px; height: 80px;">
                    </div>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>NIK:</strong></td>
                            <td id="detail_nik"></td>
                        </tr>
                        <tr>
                            <td><strong>Nama:</strong></td>
                            <td id="detail_nama"></td>
                        </tr>
                        <tr>
                            <td><strong>Divisi:</strong></td>
                            <td id="detail_divisi"></td>
                        </tr>
                        <tr>
                            <td><strong>Mulai PKL:</strong></td>
                            <td id="detail_start"></td>
                        </tr>
                        <tr>
                            <td><strong>Selesai PKL:</strong></td>
                            <td id="detail_end"></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td id="detail_status"></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Attendance Modal -->
    <div class="modal fade" id="exportAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-download me-2"></i>Export Riwayat Absensi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="exportAttendanceForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="export_attendance">
                        
                        <div class="mb-3">
                            <label class="form-label">Pilih Bulan</label>
                            <select name="bulan_export" class="form-select" required>
                                <?php
                                $bulan_names = [
                                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                                    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                                    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                                    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                ];
                                foreach ($bulan_names as $value => $label) {
                                    $selected = ($value == date('m')) ? 'selected' : '';
                                    echo "<option value=\"$value\" $selected>$label</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pilih Tahun</label>
                            <select name="tahun_export" class="form-select" required>
                                <?php
                                $current_year = date('Y');
                                for ($year = $current_year; $year >= $current_year - 5; $year--) {
                                    $selected = ($year == $current_year) ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Data absensi akan diexport dalam format CSV untuk bulan dan tahun yang dipilih.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download me-2"></i>Export CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Profile Admin
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_admin) ?>&size=100&background=e31937&color=fff" 
                             class="rounded-circle" style="width: 100px; height: 100px;">
                    </div>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Nama Admin:</strong></td>
                            <td><?= htmlspecialchars($nama_admin) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Lantai:</strong></td>
                            <td><?= htmlspecialchars($lantai_admin) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td><span class="badge badge-success">Aktif</span></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cog me-2"></i>Pengaturan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notifikasi Email</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="emailNotif" checked>
                            <label class="form-check-label" for="emailNotif">
                                Aktifkan notifikasi email untuk absensi
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batas Waktu Keterlambatan</label>
                        <select class="form-select">
                            <option>08:15</option>
                            <option>08:30</option>
                            <option>09:00</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Otomatis Nonaktif User</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoDeactivate" checked>
                            <label class="form-check-label" for="autoDeactivate">
                                Nonaktifkan user setelah tanggal selesai PKL
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-telkom">Simpan Pengaturan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // ==================== MODAL FUNCTIONS ====================
        
        /**
         * Show Add User Modal
         */
        function showAddUserModal() {
            const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
            modal.show();
        }

        /**
         * Show Profile Modal
         */
        function showProfileModal() {
            const modal = new bootstrap.Modal(document.getElementById('profileModal'));
            modal.show();
        }

        /**
         * Show Settings Modal
         */
        function showSettingsModal() {
            const modal = new bootstrap.Modal(document.getElementById('settingsModal'));
            modal.show();
        }

        // ==================== USER MANAGEMENT FUNCTIONS ====================
        
        /**
         * View User Details
         * @param {string} id - User ID
         * @param {string} nama - User Nama
         * @param {string} nik - User NIK
         * @param {string} divisi - User Divisi
         * @param {string} start_date - Start Date
         * @param {string} end_date - End Date
         * @param {number} status - Status (1=aktif, 0=nonaktif)
         */
        function viewDetails(id, nama, nik, divisi, start_date, end_date, status) {
            // Set modal data
            document.getElementById('detail_nik').textContent = nik;
            document.getElementById('detail_nama').textContent = nama;
            document.getElementById('detail_divisi').textContent = divisi || '-';
            document.getElementById('detail_start').textContent = start_date || '-';
            document.getElementById('detail_end').textContent = end_date || '-';
            
            // Set status badge
            const statusEl = document.getElementById('detail_status');
            if (status == 1) {
                statusEl.innerHTML = '<span class="badge badge-success"><i class="fas fa-check me-1"></i>Aktif</span>';
            } else {
                statusEl.innerHTML = '<span class="badge badge-secondary"><i class="fas fa-times me-1"></i>Nonaktif</span>';
            }
            
            // Set avatar
            const avatarEl = document.getElementById('detail_avatar');
            avatarEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(nama)}&size=80&background=e31937&color=fff`;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));
            modal.show();
        }

        /**
         * Edit User (SIMPLIFIED)
         */
        function editUser(id, nama, nik, divisi, start_date, end_date, status) {
            console.log('Edit user called:', {id, nama, nik, divisi, start_date, end_date, status});
            
            try {
                // Set form data
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nik').value = nik;
                document.getElementById('edit_nama').value = nama;
                document.getElementById('edit_divisi').value = divisi || '';
                document.getElementById('edit_status').value = status;
                document.getElementById('edit_start_date').value = start_date || '';
                document.getElementById('edit_end_date').value = end_date || '';
                
                // Reset password fields
                document.getElementById('reset_password').checked = false;
                document.getElementById('password_fields').style.display = 'none';
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_password').value = '';
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
                
                console.log('Edit modal opened successfully');
            } catch (error) {
                console.error('Error opening edit modal:', error);
                alert('Gagal membuka form edit: ' + error.message);
            }
        }

        /**
         * Delete User
         * @param {string} id - User ID
         * @param {string} nama - User Nama
         */
        function deleteUser(id, nama) {
            if (confirm(`Apakah Anda yakin ingin menghapus user "${nama}"?\n\nTindakan ini tidak dapat dibatalkan.`)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                // Add CSRF token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = 'csrf_token';
                csrf.value = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
                form.appendChild(csrf);
                
                // Add action
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete_user';
                form.appendChild(action);
                
                // Add delete_id
                const deleteId = document.createElement('input');
                deleteId.type = 'hidden';
                deleteId.name = 'delete_id';
                deleteId.value = id;
                form.appendChild(deleteId);
                
                // Submit form
                document.body.appendChild(form);
                form.submit();
            }
        }

        // ==================== EXPORT FUNCTIONS ====================
        
        /**
         * Export Users to CSV
         */
        function exportUsers() {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // Add CSRF token
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
            form.appendChild(csrf);
            
            // Add action
            const action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'export_users';
            form.appendChild(action);
            
            // Submit form
            document.body.appendChild(form);
            form.submit();
        }

        /**
         * Show Export Attendance Modal
         */
        function showExportAttendanceModal() {
            const modal = new bootstrap.Modal(document.getElementById('exportAttendanceModal'));
            modal.show();
        }

        // ==================== FORM VALIDATION ====================
        
        /**
         * Initialize form validation and event listeners
         */
        function initializeForms() {
            // Toggle password fields
            const resetPasswordCheckbox = document.getElementById('reset_password');
            const passwordFields = document.getElementById('password_fields');
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (resetPasswordCheckbox && passwordFields && newPasswordInput && confirmPasswordInput) {
                resetPasswordCheckbox.addEventListener('change', function() {
                    console.log('Reset password changed:', this.checked);
                    if (this.checked) {
                        passwordFields.style.display = 'block';
                        newPasswordInput.setAttribute('required', 'required');
                        confirmPasswordInput.setAttribute('required', 'required');
                    } else {
                        passwordFields.style.display = 'none';
                        newPasswordInput.removeAttribute('required');
                        confirmPasswordInput.removeAttribute('required');
                        newPasswordInput.value = '';
                        confirmPasswordInput.value = '';
                    }
                });
            }
            
            // Validate edit user form
            const editUserForm = document.getElementById('editUserForm');
            if (editUserForm) {
                editUserForm.addEventListener('submit', function(e) {
                    console.log('Edit form submitted');
                    const resetPassword = document.getElementById('reset_password').checked;
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    console.log('Password validation:', {resetPassword, newPassword, confirmPassword});
                    
                    if (resetPassword) {
                        if (newPassword.length < 6) {
                            e.preventDefault();
                            alert('Password minimal 6 karakter!');
                            return;
                        }
                        
                        if (newPassword !== confirmPassword) {
                            e.preventDefault();
                            alert('Password dan konfirmasi password tidak cocok!');
                            return;
                        }
                    }
                    
                    console.log('Form validation passed, submitting...');
                });
            }
        }

        // ==================== MOBILE MENU FUNCTIONS ====================
        
        /**
         * Toggle sidebar for mobile
         */
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobileMenuToggle');
            
            sidebar.classList.toggle('active');
            toggle.classList.toggle('active');
            
            // Change icon
            const icon = toggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
        
        /**
         * Close sidebar when clicking outside
         */
        function closeSidebarOnClickOutside(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobileMenuToggle');
            
            if (window.innerWidth <= 992 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        }

        // ==================== UTILITY FUNCTIONS ====================
        
        /**
         * Auto-hide alerts after 5 seconds
         */
        function autoHideAlerts() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        }

        /**
         * Initialize page functionality
         */
        function initializePage() {
            // Auto-hide alerts
            autoHideAlerts();
        }

        // ==================== PAGE INITIALIZATION ====================
        
        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Dashboard DOM loaded');
            
            // Initialize forms
            initializeForms();
            
            // Auto-hide alerts
            autoHideAlerts();
            
            // Add click outside listener for mobile menu
            document.addEventListener('click', closeSidebarOnClickOutside);
            
            // Handle window resize
            window.addEventListener('resize', function() {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.getElementById('mobileMenuToggle');
                
                if (window.innerWidth > 992) {
                    sidebar.classList.remove('active');
                    toggle.classList.remove('active');
                    
                    // Reset icon
                    const icon = toggle.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
            
            console.log('Admin Dashboard initialized');
        });
    </script>
</body>
</html>
