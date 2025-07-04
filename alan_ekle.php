<?php
require_once 'moduller/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Aynı stok kodundan birden fazla olanları çek
$sql = "
    SELECT stok_kodu, COUNT(*) as adet
    FROM urunler
    WHERE stok_kodu IS NOT NULL AND stok_kodu != ''
    GROUP BY stok_kodu
    HAVING adet > 1
";
$stmt = db()->prepare($sql);
$stmt->execute();
$cakisanlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Şimdi her stok kodu için detayları çekelim
echo "<h3>🟠 Aynı stok koduna sahip ürünler</h3>";

if (count($cakisanlar) > 0) {
    foreach ($cakisanlar as $row) {
        $sku = $row['stok_kodu'];
        echo "<div style='margin-bottom:12px;'><strong>SKU: $sku</strong> ({$row['adet']} adet)<br>";

        $alt_sorgu = db()->prepare("SELECT id, marka FROM urunler WHERE stok_kodu = :sku");
        $alt_sorgu->execute([':sku' => $sku]);
        $urunler = $alt_sorgu->fetchAll(PDO::FETCH_ASSOC);

        foreach ($urunler as $u) {
            echo "- ID: {$u['id']}, Marka: {$u['marka']}<br>";
        }

        echo "</div>";
    }
} else {
    echo "<div class='text-muted'>✔️ Tüm stok kodları benzersiz.</div>";
}
