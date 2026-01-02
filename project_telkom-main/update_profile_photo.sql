-- Add profile photo column to tb_karyawan table
ALTER TABLE tb_karyawan ADD COLUMN photo_path VARCHAR(255) NULL AFTER telepon;

-- Create uploads directory for profile photos if not exists
-- Note: This needs to be done manually or via PHP script
