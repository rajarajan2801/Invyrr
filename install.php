#!/usr/bin/env php
<?php
/**
 * Invyrr — Database Schema Installer v2.0
 * Run: php install.php  OR  visit http://yourhost/invyrr/install.php
 */
$isCli = PHP_SAPI === 'cli';
function out(string $msg): void {
    global $isCli;
    echo $isCli ? $msg . PHP_EOL : "<p style='font-family:monospace'>$msg</p>";
}
require __DIR__ . '/includes/db.php';

$tables = <<<SQL
CREATE TABLE IF NOT EXISTS settings (
    k VARCHAR(100) PRIMARY KEY,
    v TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS locations (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(200) NOT NULL,
    address    TEXT,
    phone      VARCHAR(30),
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150) NOT NULL,
    email        VARCHAR(150) NULL DEFAULT NULL,
    password     VARCHAR(255) NOT NULL,
    role         ENUM('admin','manager','Picker','partner','Cashier') NOT NULL DEFAULT 'Picker',
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    last_login   TIMESTAMP NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    user_name  VARCHAR(150),
    action     VARCHAR(100) NOT NULL,
    entity     VARCHAR(50),
    entity_id  INT,
    detail     TEXT,
    ip         VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user   (user_id),
    INDEX idx_entity (entity, entity_id),
    INDEX idx_date   (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(200) NOT NULL,
    phone      VARCHAR(30),
    email      VARCHAR(150),
    address    TEXT,
    gst        VARCHAR(30),
    notes      TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name  (name),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vendors (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(200) NOT NULL,
    contact    VARCHAR(150),
    phone      VARCHAR(30),
    email      VARCHAR(150),
    city       VARCHAR(100),
    gst        VARCHAR(30),
    address    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(250) NOT NULL,
    sku         VARCHAR(100),
    brand       VARCHAR(150),
    category    VARCHAR(100),
    vendor_id   INT,
    cost        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    sell        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock       INT NOT NULL DEFAULT 0,
    min_stock   INT NOT NULL DEFAULT 0,
    unit        VARCHAR(30) DEFAULT 'pcs',
    description TEXT,
    image       VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
    INDEX idx_name (name), INDEX idx_sku (sku), INDEX idx_brand (brand), INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_locations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    location_id INT NOT NULL,
    stock       INT NOT NULL DEFAULT 0,
    min_stock   INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_product_location (product_id, location_id),
    FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    po_number    VARCHAR(50) NOT NULL UNIQUE,
    vendor_id    INT,
    location_id  INT,
    status       ENUM('draft','sent','partial','received','cancelled') NOT NULL DEFAULT 'draft',
    expected_date DATE,
    notes        TEXT,
    misc_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_by   INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id)   REFERENCES vendors(id)   ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_status (status), INDEX idx_vendor (vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    po_id      INT NOT NULL,
    product_id INT NOT NULL,
    qty_ordered INT NOT NULL DEFAULT 0,
    qty_received INT NOT NULL DEFAULT 0,
    cost       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (po_id)      REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_in (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    location_id INT,
    vendor_id   INT,
    po_id       INT,
    qty         INT NOT NULL,
    cost        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    date        DATE NOT NULL,
    note        VARCHAR(300),
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)  REFERENCES products(id)         ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id)        ON DELETE SET NULL,
    FOREIGN KEY (vendor_id)   REFERENCES vendors(id)          ON DELETE SET NULL,
    FOREIGN KEY (po_id)       REFERENCES purchase_orders(id)  ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)            ON DELETE SET NULL,
    INDEX idx_product(product_id), INDEX idx_location(location_id), INDEX idx_date(date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id  INT,
    customer_name VARCHAR(200),
    location_id  INT,
    subtotal     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_rate     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    tax_amount   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT 'cash',
    packing_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    misc_charges    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_received DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status       ENUM('draft','paid','cancelled') NOT NULL DEFAULT 'paid',
    notes        TEXT,
    date         DATE NOT NULL,
    created_by   INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id)  REFERENCES customers(id)  ON DELETE SET NULL,
    FOREIGN KEY (location_id)  REFERENCES locations(id)  ON DELETE SET NULL,
    FOREIGN KEY (created_by)   REFERENCES users(id)      ON DELETE SET NULL,
    INDEX idx_number   (invoice_number),
    INDEX idx_customer (customer_id),
    INDEX idx_date     (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(250) NOT NULL,
    qty        INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    cost       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total      DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_out (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    location_id INT,
    invoice_id  INT,
    qty         INT NOT NULL,
    sell_price  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cost        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    customer    VARCHAR(200),
    date        DATE NOT NULL,
    note        VARCHAR(300),
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id)  REFERENCES invoices(id)  ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_product(product_id), INDEX idx_location(location_id), INDEX idx_date(date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_adjustments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    location_id INT,
    qty_change  INT NOT NULL,
    reason      ENUM('damage','theft','correction','recount','other') NOT NULL,
    note        VARCHAR(300),
    date        DATE NOT NULL,
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_product(product_id), INDEX idx_date(date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_transfers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    from_location INT NOT NULL,
    to_location   INT NOT NULL,
    product_id    INT NOT NULL,
    qty           INT NOT NULL,
    note          VARCHAR(300),
    date          DATE NOT NULL,
    created_by    INT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_location) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (to_location)   REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id)    REFERENCES products(id)  ON DELETE CASCADE,
    FOREIGN KEY (created_by)    REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_product(product_id), INDEX idx_date(date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

try {
    $pdo = getDB();
    foreach (array_filter(array_map('trim', explode(';', $tables))) as $stmt) {
        $pdo->exec($stmt);
        if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $stmt, $m))
            out("✅ Table '{$m[1]}' ready.");
    }

    // ── Column migrations ────────────────────────────────
    $migs = [
        ['products',  'brand',    "ALTER TABLE products ADD COLUMN brand VARCHAR(150) AFTER sku"],
        ['vendors',   'type',     "ALTER TABLE vendors ADD COLUMN type VARCHAR(50) DEFAULT '' AFTER name"],
        ['categories','sku_prefix',"ALTER TABLE categories ADD COLUMN sku_prefix VARCHAR(10) DEFAULT NULL AFTER name"],
        ['products',  'item_code',"ALTER TABLE products ADD COLUMN item_code INT UNSIGNED DEFAULT NULL AFTER sku"],
        ['products',  'image',    "ALTER TABLE products ADD COLUMN image VARCHAR(255) AFTER description"],
        ['products',  'case_content', "ALTER TABLE products ADD COLUMN case_content INT UNSIGNED DEFAULT NULL AFTER image"],
        ['products',  'landing_cost', "ALTER TABLE products ADD COLUMN landing_cost DECIMAL(12,2) DEFAULT NULL AFTER case_content"],
        ['products',  'box_content',  "ALTER TABLE products ADD COLUMN box_content VARCHAR(100) DEFAULT NULL AFTER case_content"],
        ['products',  'wholesale_price', "ALTER TABLE products ADD COLUMN wholesale_price DECIMAL(12,2) DEFAULT NULL AFTER landing_cost"],
        ['products',  'combo',    "ALTER TABLE products ADD COLUMN combo TINYINT(1) NOT NULL DEFAULT 0 AFTER landing_cost"],
        ['products',  'pending_vendor_name', "ALTER TABLE products ADD COLUMN pending_vendor_name VARCHAR(200) DEFAULT NULL AFTER combo"],
        ['stock_in',  'location_id', "ALTER TABLE stock_in ADD COLUMN location_id INT AFTER product_id, ADD FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL"],
        ['stock_in',  'po_id',    "ALTER TABLE stock_in ADD COLUMN po_id INT AFTER vendor_id, ADD FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE SET NULL"],
        ['stock_in',  'created_by',"ALTER TABLE stock_in ADD COLUMN created_by INT, ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL"],
        ['stock_out', 'location_id',"ALTER TABLE stock_out ADD COLUMN location_id INT AFTER product_id, ADD FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL"],
        ['stock_out', 'invoice_id',"ALTER TABLE stock_out ADD COLUMN invoice_id INT, ADD FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL"],
        ['stock_out', 'created_by',"ALTER TABLE stock_out ADD COLUMN created_by INT, ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL"],
    ];
    foreach ($migs as [$tbl, $col, $sql]) {
        if (empty($pdo->query("SHOW COLUMNS FROM `$tbl` LIKE '$col'")->fetchAll())) {
            $pdo->exec($sql);
            out("✅ Migration: added '$col' to $tbl.");
        }
    }

    // ── Default location ─────────────────────────────────
    if (!(int)$pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn()) {
        $pdo->exec("INSERT INTO locations (name, is_default) VALUES ('Main Store', 1)");
        $mainId = (int)$pdo->lastInsertId();
        $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock)
                    SELECT id,$mainId,stock,min_stock FROM products");
        out("✅ Seeded default location 'Main Store' and migrated existing stock.");
    }

    // ── Default admin user ───────────────────────────────
    if (!(int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (name,email,password,role) VALUES ('Administrator','admin@invyrr.local','$hash','admin')");
        out("✅ Default admin created — username: Administrator  password: admin123");
        out("   ⚠️  Change this password immediately after first login!");
    }

    // ── Default settings ─────────────────────────────────
    $defaults = [
        'business_name'   => 'My Business',
        'business_address'=> '',
        'business_phone'  => '',
        'business_email'  => '',
        'business_gst'    => '',
        'currency_symbol' => '₹',
        'invoice_prefix'  => 'INV',
        'po_prefix'       => 'PO',
        'tax_rate'        => '0',
        'low_stock_email' => '',
        'smtp_host'       => '',
        'smtp_port'       => '587',
        'smtp_user'       => '',
        'smtp_pass'       => '',
    ];
    $ins = $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)");
    foreach ($defaults as $k => $v) { $ins->execute([$k, $v]); }
    out("✅ Default settings seeded.");

    // ── Categories ────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(150) NOT NULL UNIQUE,
        description VARCHAR(500) DEFAULT '',
        color       VARCHAR(20)  DEFAULT '',
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    out("✅ categories table ready.");

    // Migrate existing product categories into the table
    $existing = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> ''")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($existing as $cat) {
        $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$cat]);
    }
    if ($existing) out("✅ Migrated ".count($existing)." existing product categories.");


    // ── users.email allow NULL (for users without email) ────
    try {
        $colNull = $pdo->query("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='email'")->fetchColumn();
        if ($colNull === 'NO') {
            $pdo->exec("ALTER TABLE users MODIFY COLUMN email VARCHAR(150) NULL");
            out("✅ Migration: users.email changed to NULL.");
        }
    } catch(Exception $e) {}

    // ── box_content INT → VARCHAR (allow alphanumeric) ──────
    try {
        $colType = $pdo->query("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='box_content'")->fetchColumn();
        if ($colType && strtolower($colType) !== 'varchar') {
            $pdo->exec("ALTER TABLE products MODIFY COLUMN box_content VARCHAR(100) DEFAULT NULL");
            out("✅ Migration: box_content changed to VARCHAR(100).");
        }
    } catch(Exception $e) {}

    // ── users.email → nullable (so blank emails don't conflict on UNIQUE) ──
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN email VARCHAR(150) NULL");
        // Convert empty strings to NULL so the UNIQUE index permits multiple blanks
        $pdo->exec("UPDATE users SET email=NULL WHERE email=''");
        out("✅ Migration: users.email now nullable.");
    } catch(Exception $e) {}

    // ── Payees (payment accounts / parties) ─────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS payees (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(200) NOT NULL,
        type        VARCHAR(50)  DEFAULT '' COMMENT 'Person, Bank Account, UPI, Cash, Cheque',
        account_no  VARCHAR(100) DEFAULT '',
        bank_name   VARCHAR(150) DEFAULT '',
        ifsc        VARCHAR(20)  DEFAULT '',
        upi_id      VARCHAR(150) DEFAULT '',
        phone       VARCHAR(30)  DEFAULT '',
        notes       VARCHAR(500) DEFAULT '',
        is_active   TINYINT(1)   NOT NULL DEFAULT 1,
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    out("✅ payees table ready.");

    // ── Vendor Payments ──────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS vendor_payments (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        vendor_id       INT NOT NULL,
        payee_id        INT DEFAULT NULL,
        amount          DECIMAL(14,2) NOT NULL DEFAULT 0,
        payment_mode    VARCHAR(50)  DEFAULT 'Cash' COMMENT 'Cash,Bank Transfer,Cheque,UPI,Other',
        reference_no    VARCHAR(100) DEFAULT '',
        payment_date    DATE         NOT NULL,
        notes           VARCHAR(500) DEFAULT '',
        type            VARCHAR(20)  NOT NULL DEFAULT 'payment' COMMENT 'payment, credit_note, opening_balance',
        created_by      INT DEFAULT NULL,
        created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    out("✅ vendor_payments table ready.");

    // ── Fix email column: allow NULL and drop unique constraint ─
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN email VARCHAR(150) NULL DEFAULT NULL");
        // Drop unique index if it exists (MySQL allows multiple NULLs in unique index, but let's be safe)
        $idxRows = $pdo->query("SHOW INDEX FROM users WHERE Key_name='email'")->fetchAll();
        if ($idxRows) $pdo->exec("ALTER TABLE users DROP INDEX email");
        out("✅ Migration: users.email fixed to nullable.");
    } catch(Exception $e2) {}

    // ── invoices: packing_charges, misc_charges, amount_received columns ─
    try {
        $col = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='invoices' AND COLUMN_NAME='packing_charges'")->fetchColumn();
        if (!$col) {
            $pdo->exec("ALTER TABLE invoices ADD COLUMN packing_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER payment_method,
                                             ADD COLUMN misc_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER packing_charges,
                                             ADD COLUMN amount_received DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER misc_charges");
            out("✅ Migration: packing_charges, misc_charges & amount_received added to invoices.");
        } else {
            // Check if amount_received exists separately
            $col2 = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='invoices' AND COLUMN_NAME='amount_received'")->fetchColumn();
            if (!$col2) {
                $pdo->exec("ALTER TABLE invoices ADD COLUMN amount_received DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER misc_charges");
                out("✅ Migration: amount_received added to invoices.");
            }
        }
    } catch(Exception $e) { out("Note: ".$e->getMessage()); }

    $backupDir = __DIR__ . '/backups';
    try {
        $col = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='purchase_orders' AND COLUMN_NAME='misc_charges'")->fetchColumn();
        if (!$col) {
            $pdo->exec("ALTER TABLE purchase_orders ADD COLUMN misc_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER notes");
            out("✅ Migration: misc_charges column added to purchase_orders.");
        }
    } catch(Exception $e) {}

    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
        file_put_contents($backupDir . '/.htaccess', "Deny from all\n");
        out("✅ Created backups directory.");
    }

    // ── Upload directories ───────────────────────────────
    $uploadDir = __DIR__ . '/assets/uploads/products';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        out("✅ Created upload directory: assets/uploads/products/");
    }
    // Protect uploads from direct PHP execution
    $htaccess = __DIR__ . '/assets/uploads/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "php_flag engine off\nOptions -ExecCGI\n");
        out("✅ Created security .htaccess in uploads directory.");
    }

    out('');
    out('🎉 Invyrr v2.0 schema ready!');
    out('   → Delete install.php after setup.');
} catch (PDOException $e) {
    out('❌ Error: ' . $e->getMessage());
}
