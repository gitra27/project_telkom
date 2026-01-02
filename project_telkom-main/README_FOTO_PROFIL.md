# Fitur Foto Profil

## Cara Penggunaan

1. **Upload Foto Profil**
   - Buka halaman dashboard
   - Pada bagian profil kiri, klik "Ubah Foto Profil"
   - Pilih file foto (JPEG, PNG, atau GIF)
   - Klik tombol "Upload Foto"

2. **Persyaratan Foto**
   - Format: JPEG, PNG, atau GIF
   - Ukuran maksimal: 2MB
   - Foto akan otomatis di-resize menjadi lingkaran (avatar)

3. **Fitur Tambahan**
   - Preview foto sebelum upload
   - Update foto profil secara real-time tanpa reload halaman
   - Validasi tipe file dan ukuran file
   - Penyimpanan otomatis di folder `uploads/profile/`

## Struktur Database

Kolom `photo_path` telah ditambahkan ke tabel `tb_karyawan` untuk menyimpan path foto profil.

## Troubleshooting

Jika foto tidak muncul:
1. Pastikan folder `uploads/profile/` memiliki permission yang tepat
2. Periksa apakah file berhasil diupload ke folder yang benar
3. Refresh halaman browser (Ctrl+F5)

## Setup

Jalankan `setup_profile_photo.php` sekali untuk:
- Membuat folder `uploads/profile/`
- Menambahkan kolom `photo_path` ke database
