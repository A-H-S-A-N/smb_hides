<?php
require __DIR__ . '/config.php';

$db = db();

$db->exec("
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'Jackets',
    description TEXT DEFAULT '',
    price REAL NOT NULL DEFAULT 0,
    sale_price REAL DEFAULT NULL,
    sizes TEXT DEFAULT 'S,M,L,XL,XXL',
    image TEXT DEFAULT '',
    badge TEXT DEFAULT '',
    stock INTEGER NOT NULL DEFAULT 0,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_no TEXT UNIQUE NOT NULL,
    customer_name TEXT NOT NULL,
    phone TEXT NOT NULL,
    email TEXT NOT NULL,
    city TEXT NOT NULL,
    address TEXT NOT NULL,
    total REAL NOT NULL,
    status TEXT NOT NULL DEFAULT 'New',
    payment_method TEXT NOT NULL DEFAULT 'Cash on Delivery',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER,
    product_name TEXT NOT NULL,
    size TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    price REAL NOT NULL,
    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
);
");

$count = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();

if ($count === 0) {
    $stmt = $db->prepare("INSERT INTO products
        (name, category, description, price, sale_price, sizes, image, badge, stock)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $demo = [
        ['Classic Leather Jacket','Jackets','Premium leather jacket with a timeless silhouette and refined finishing.',14999,null,'S,M,L,XL,XXL','https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=1000&q=85','NEW',20],
        ['Premium Leather Jacket','Jackets','Clean modern leather jacket designed for everyday premium style.',14999,null,'S,M,L,XL,XXL','https://images.unsplash.com/photo-1520975958225-7b2c5f0b6d8f?auto=format&fit=crop&w=1000&q=85','BEST SELLER',15],
        ['Leather Bomber Jacket','Jackets','Modern bomber silhouette with premium leather construction.',14999,null,'S,M,L,XL,XXL','https://images.unsplash.com/photo-1548883354-94bcfe321cbb?auto=format&fit=crop&w=1000&q=85','TRENDING',12],
        ['Leather Blazer','Coats','Sophisticated leather blazer for a refined look.',14999,null,'S,M,L,XL,XXL','https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=1000&q=85','',10]
    ];
    foreach ($demo as $p) $stmt->execute($p);
}

echo '<!doctype html><html><head><meta charset="utf-8"><title>SMB HIDES Setup</title>
<style>body{font-family:Arial;background:#090909;color:#eee;display:grid;place-items:center;min-height:100vh}.box{max-width:650px;padding:35px;background:#151311;border:1px solid #3b3021}h1{color:#e0b76e}code{color:#f0c77b}</style></head><body><div class="box">
<h1>SMB HIDES setup complete</h1>
<p>Database and demo products are ready.</p>
<p>Admin login: <code>admin</code></p>
<p>Default password: <code>SMBHIDES@2026</code></p>
<p><strong>Change the password in config.php before going live.</strong></p>
<p><a href="index.php" style="color:#e0b76e">Open store</a> &nbsp; | &nbsp; <a href="admin.php" style="color:#e0b76e">Open admin panel</a></p>
</div></body></html>';
