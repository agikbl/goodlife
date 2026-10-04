ALTER TABLE products
    ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER category_id;

INSERT INTO schema_migrations (version) VALUES ('003_product_sort_order');
