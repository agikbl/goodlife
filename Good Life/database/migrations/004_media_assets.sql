CREATE TABLE media_assets (
    asset_path VARCHAR(255) NOT NULL,
    mime_type ENUM('image/jpeg', 'image/png', 'image/webp') NOT NULL,
    byte_size INT UNSIGNED NOT NULL,
    sha256 BINARY(32) NOT NULL,
    image_data MEDIUMBLOB NOT NULL,
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (asset_path),
    KEY idx_media_assets_sha256 (sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version) VALUES ('004_media_assets');
