<?php
// moduller/db.php
// Bağlantı türünü ve bilgilerini buradan yönet

// -- KURULUM --
// 1. Şu anda SQLite ile başlatıyoruz.
// 2. Sonraki geçiş için sadece 'driver' ve ilgili ayarları değiştirmen yeterli olacak.

define('DB_DRIVER', 'sqlite'); // 'sqlite' veya 'mysql'

// SQLite ayarı
define('DB_SQLITE_PATH', __DIR__ . '/../urunler.db');

// MySQL ayarları (hazır dursun)
define('DB_HOST',     'localhost');
define('DB_NAME',     'urunler_db');
define('DB_USER',     'root');
define('DB_PASSWORD', '');

// Bağlantı fonksiyonu
function db() {
    static $db = null;
    if ($db === null) {
        if (DB_DRIVER == 'sqlite') {
            $dsn = 'sqlite:' . DB_SQLITE_PATH;
            $db = new PDO($dsn);
        } elseif (DB_DRIVER == 'mysql') {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $db = new PDO($dsn, DB_USER, DB_PASSWORD);
        } else {
            die("Desteklenmeyen veritabanı türü!");
        }
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}
?>
