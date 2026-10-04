CREATE TABLE schema_migrations (
    version VARCHAR(100) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id VARCHAR(64) NOT NULL,
    name VARCHAR(60) NOT NULL,
    group_type ENUM('makanan', 'minuman') NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    KEY idx_categories_group_order (group_type, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id VARCHAR(64) NOT NULL,
    product_type ENUM('menu', 'topping') NOT NULL,
    category_id VARCHAR(64) NULL,
    topping_group ENUM('makanan', 'minuman') NULL,
    name VARCHAR(300) NOT NULL,
    price BIGINT UNSIGNED NOT NULL,
    is_favorite TINYINT(1) NOT NULL DEFAULT 0,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    image_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_products_category_availability (category_id, is_available),
    KEY idx_products_type_group (product_type, topping_group),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE store_settings (
    id TINYINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(900) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    opening_time TIME NOT NULL,
    closing_time TIME NOT NULL,
    is_open TINYINT(1) NOT NULL DEFAULT 1,
    activity VARCHAR(100) NOT NULL,
    status_updated_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE store_gallery (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_settings_id TINYINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_store_gallery_image_path (image_path),
    KEY idx_store_gallery_order (store_settings_id, sort_order),
    CONSTRAINT fk_store_gallery_settings
        FOREIGN KEY (store_settings_id) REFERENCES store_settings (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_code VARCHAR(32) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    fulfillment_type ENUM('ambil', 'antar') NOT NULL,
    pickup_time TIME NULL,
    address VARCHAR(900) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    straight_distance_km DECIMAL(8,3) NULL,
    estimated_distance_km DECIMAL(8,3) NULL,
    delivery_fee BIGINT UNSIGNED NOT NULL DEFAULT 0,
    shipping_payment_method ENUM('qris', 'tunai') NULL,
    payment_method ENUM('qris', 'tunai') NOT NULL,
    payment_status ENUM('Belum dibayar', 'Lunas') NOT NULL DEFAULT 'Belum dibayar',
    status VARCHAR(24) NOT NULL DEFAULT 'Diterima',
    subtotal BIGINT UNSIGNED NOT NULL,
    total BIGINT UNSIGNED NOT NULL,
    business_timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Makassar',
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_order_code (order_code),
    KEY idx_orders_created_at (created_at),
    KEY idx_orders_status_created (status, created_at),
    KEY idx_orders_payment_created (payment_status, created_at),
    KEY idx_orders_whatsapp_created (whatsapp, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id VARCHAR(64) NULL,
    product_name_snapshot VARCHAR(300) NOT NULL,
    base_unit_price BIGINT UNSIGNED NOT NULL,
    unit_price BIGINT UNSIGNED NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    notes VARCHAR(500) NOT NULL DEFAULT '',
    line_total BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_item_toppings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_item_id BIGINT UNSIGNED NOT NULL,
    product_id VARCHAR(64) NULL,
    topping_name_snapshot VARCHAR(300) NOT NULL,
    unit_price_snapshot BIGINT UNSIGNED NOT NULL,
    quantity_per_item SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_order_item_toppings_item (order_item_id),
    KEY idx_order_item_toppings_product (product_id),
    CONSTRAINT fk_order_item_toppings_item
        FOREIGN KEY (order_item_id) REFERENCES order_items (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_item_toppings_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_name_snapshot VARCHAR(100) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_order (order_id),
    CONSTRAINT fk_reviews_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE support_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(150) NOT NULL,
    category VARCHAR(40) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Baru',
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_support_messages_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id BIGINT UNSIGNED NULL,
    endpoint VARCHAR(2048) NOT NULL,
    endpoint_hash BINARY(32) NOT NULL,
    p256dh_key VARCHAR(200) NOT NULL,
    auth_key VARCHAR(100) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_push_subscriptions_endpoint_hash (endpoint_hash),
    KEY idx_push_subscriptions_admin (admin_user_id),
    CONSTRAINT fk_push_subscriptions_admin
        FOREIGN KEY (admin_user_id) REFERENCES admin_users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(40) NOT NULL,
    provider_transaction_id VARCHAR(191) NULL,
    idempotency_key VARCHAR(191) NOT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'IDR',
    status ENUM('pending', 'paid', 'failed', 'expired', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    checkout_url TEXT NULL,
    expires_at DATETIME(6) NULL,
    paid_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_transactions_idempotency (idempotency_key),
    UNIQUE KEY uq_payment_transactions_provider_ref (provider, provider_transaction_id),
    KEY idx_payment_transactions_order (order_id, created_at),
    KEY idx_payment_transactions_status_expiry (status, expires_at),
    CONSTRAINT fk_payment_transactions_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_webhook_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider VARCHAR(40) NOT NULL,
    provider_event_id VARCHAR(191) NOT NULL,
    payload MEDIUMTEXT NOT NULL,
    processing_status ENUM('pending', 'processed', 'failed') NOT NULL DEFAULT 'pending',
    received_at DATETIME(6) NOT NULL,
    processed_at DATETIME(6) NULL,
    error_message VARCHAR(500) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_webhook_provider_event (provider, provider_event_id),
    KEY idx_payment_webhooks_status_received (processing_status, received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version) VALUES ('001_initial_schema');
