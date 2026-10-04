ALTER TABLE pengaturan_toko ADD COLUMN menerima_tunai TINYINT(1) NOT NULL DEFAULT 1 AFTER sedang_buka;
INSERT INTO catatan_migrasi (versi) VALUES ('009_penerimaan_tunai');