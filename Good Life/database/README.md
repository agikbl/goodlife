# Database setup

## New database

1. Create the MySQL/MariaDB database and a least-privilege application account.
2. Apply `migrations/001_initial_schema.sql`.
3. Apply `migrations/003_product_sort_order.sql`.
4. Apply `migrations/004_media_assets.sql`.
5. Apply `migrations/006_media_asset_chunks.sql`.
6. Copy `config.example.php` to `config.local.php` and set the local connection values. Do not commit `config.local.php`.
7. Run `php database/import_initial_data.php` from the project root. This imports categories, products, store settings, gallery paths, and support messages from `data/`. Orders and reviews are intentionally not imported.
8. Run `php database/import_media_assets.php` from the project root. This copies the referenced menu and gallery image bytes into `media_assets` and `media_asset_chunks`.
9. Verify the connection with `php database/check_connection.php`.

Both import scripts are one-time only and validate their inputs before committing. Image BLOBs are stored in 256 KiB database chunks to support MariaDB servers with a small `max_allowed_packet`. Keep the source JSON and image files as offline backups; the `data/` directory denies direct HTTP access on Apache. Runtime menu and gallery images are served from the database by `media.php`. Branding assets such as the logo and hero background remain static website assets.

For deployment, configure the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` environment variables with the hosting provider's database credentials. Do not point a deployed application at the development computer's database. Configure equivalent deny rules for `/data`, `/assets/menu`, and `/assets/gallery` when the host does not honor Apache `.htaccess` files.

## Existing database

Apply each unapplied SQL migration once, in version order. For an already-imported database, apply `004_media_assets.sql` and `006_media_asset_chunks.sql`, then run `php database/import_media_assets.php` once. The importer uses the retained files to populate/verify the chunked BLOBs. The current runtime reads and writes application data and menu/gallery images through MySQL/MariaDB; the remaining JSON and referenced image files are retained only as import/backup sources.

## Integration checks

Run `php database/integration_test.php` from the project root with the local database configured. The test creates uniquely named temporary records, exercises product/category CRUD, multi-chunk image BLOB persistence, the order status lifecycle, single-review enforcement, and support-message persistence, then removes its test data.
