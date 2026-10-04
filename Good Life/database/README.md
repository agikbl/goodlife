# Database setup

## New database

1. Create the MySQL/MariaDB database and a least-privilege application account.
2. Apply `migrations/001_initial_schema.sql`.
3. Apply `migrations/003_product_sort_order.sql`.
4. Apply `migrations/004_media_assets.sql`.
5. Apply `migrations/006_media_asset_chunks.sql`.
6. Copy `config.example.php` to `config.local.php` and set the local connection values. Do not commit `config.local.php`.
7. Run `php database/rename_schema_indonesian.php` from the project root. This renames the schema tables and columns without changing the database name or removing data.
8. Run `php database/import_initial_data.php` from the project root. This imports categories, products, store settings, gallery paths, and support messages from `data/`. Orders and reviews are intentionally not imported.
9. Run `php database/import_media_assets.php` from the project root. This copies referenced menu and gallery image bytes into `aset_media` and `potongan_aset_media`.
10. Verify the connection with `php database/check_connection.php`.

Both import scripts are one-time only and validate their inputs before committing. Image BLOBs are stored in 256 KiB database chunks to support MariaDB servers with a small `max_allowed_packet`. Keep the source JSON and image files as offline backups; the `data/` directory denies direct HTTP access on Apache. Runtime menu and gallery images are served from the database by `media.php`. Branding assets such as the logo and hero background remain static website assets.

For deployment, configure the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` environment variables with the hosting provider's database credentials. Do not point a deployed application at the development computer's database. Configure equivalent deny rules for `/data`, `/assets/menu`, and `/assets/gallery` when the host does not honor Apache `.htaccess` files.

## Existing database

Apply each unapplied SQL migration once, in version order. Before switching application code to this version, back up the database and run `php database/rename_schema_indonesian.php` from the project root. This forward-only migration renames all 16 application tables and their columns while preserving rows, relationships, indexes, and the database name `goodlife`. It records `008_nama_tabel_dan_kolom_indonesia` in `catatan_migrasi`; rerunning the script is safe after a completed migration. Do not run the old English-name import scripts before this rename. For an already-imported database, rename the schema before running `php database/import_media_assets.php` if that media import is still pending. The importer uses retained files to populate/verify chunked BLOBs. Runtime reads and writes application data and menu/gallery images through MySQL/MariaDB; remaining JSON and referenced image files are retained only as import/backup sources.

## Indonesian schema names

The database itself remains `goodlife`. The migration changes physical table and column names; PHP endpoints keep their existing request/response field names so the customer and admin interfaces do not change.

| Existing table name | Indonesian table name | Purpose |
|---|---|---|
| `schema_migrations` | `catatan_migrasi` | Applied schema migration history. |
| `categories` | `kategori` | Food and drink categories. |
| `products` | `produk` | Menu products and toppings. |
| `store_settings` | `pengaturan_toko` | Store identity, hours, and open/closed status. |
| `store_gallery` | `galeri_toko` | Store gallery image paths and display order. |
| `admin_users` | `admin` | Admin login accounts. |
| `orders` | `pesanan` | Customer, delivery, payment, and order status. |
| `order_items` | `detail_pesanan` | Product and price snapshots for each order line. |
| `order_item_toppings` | `topping_detail_pesanan` | Toppings selected for each order line. |
| `reviews` | `ulasan` | Customer ratings and comments. |
| `support_messages` | `pesan_bantuan` | Customer support form submissions. |
| `push_subscriptions` | `langganan_notifikasi` | Browser push notification subscriptions. |
| `payment_transactions` | `transaksi_pembayaran` | Payment provider transaction records. |
| `payment_webhook_events` | `catatan_webhook_pembayaran` | Received payment provider webhook events. |
| `media_assets` | `aset_media` | Image metadata. |
| `media_asset_chunks` | `potongan_aset_media` | Binary image chunks. |

Examples of column names: `admin.id` becomes `admin.id_admin`; `pesanan.pickup_time` becomes `pesanan.jam_pengambilan`; `pesanan.customer_name` becomes `pesanan.nama_pelanggan`; and `detail_pesanan.order_id` becomes `detail_pesanan.id_pesanan`. The migration script contains the complete old-to-new column mapping. Foreign keys and existing rows are retained.

## Integration checks

Run `php database/integration_test.php` from the project root with the local database configured. The test creates uniquely named temporary records, exercises product/category CRUD, multi-chunk image BLOB persistence, the order status lifecycle, single-review enforcement, and support-message persistence, then removes its test data.
