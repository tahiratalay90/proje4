<?php
require_once __DIR__ . '/moduller/db.php';

try {
    $db = db();

    $db->exec("ALTER TABLE urunler ADD COLUMN satis_fiyati REAL DEFAULT NULL");
  

    echo "✅ Tüm alanlar başarıyla eklendi.";
} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage();
}
?>
