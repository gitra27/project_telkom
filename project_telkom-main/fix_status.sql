-- Fix untuk menambahkan status 'Selesai' ke tabel tb_absensi
ALTER TABLE tb_absensi MODIFY status ENUM('Hadir','Izin','Sakit','Telat','Selesai') NOT NULL;
