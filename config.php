<?php
// SMB HIDES - Configuration
session_start();

define('DB_FILE', __DIR__ . '/smb_hides.sqlite');
define('STORE_NAME', 'SMB HIDES');
define('STORE_EMAIL', 'smbhides@gmail.com');
define('STORE_PHONE', '03496235602');

// Change these before going live.
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'SMBHIDES@2026');

function db() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("PRAGMA foreign_keys = ON");
    }
    return $db;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return 'PKR ' . number_format((float)$value, 0);
}

function admin_logged_in() {
    return !empty($_SESSION['smb_admin']);
}

function require_admin() {
    if (!admin_logged_in()) {
        header('Location: admin.php');
        exit;
    }
}

function order_number() {
    return 'SMB-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}
