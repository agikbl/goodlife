CREATE TABLE media_asset_chunks (
    asset_path VARCHAR(255) NOT NULL,
    chunk_index SMALLINT UNSIGNED NOT NULL,
    chunk_data MEDIUMBLOB NOT NULL,
    PRIMARY KEY (asset_path, chunk_index),
    CONSTRAINT fk_media_asset_chunks_asset
        FOREIGN KEY (asset_path) REFERENCES media_assets (asset_path)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE media_assets
    DROP COLUMN image_data;

INSERT INTO schema_migrations (version) VALUES ('006_media_asset_chunks');
