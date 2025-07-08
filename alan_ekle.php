<?php
require_once 'moduller/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = db();

$urunler = $db->query("SELECT id, stok_kodu FROM urunler WHERE marka = 'Xerox-Samsung'")->fetchAll(PDO::FETCH_ASSOC);
$sayac = 0;

foreach ($urunler as $urun) {
    $id = $urun['id'];
    $sku = $urun['stok_kodu'];

    if (substr($sku, -1) !== '_') {
        $yeni_sku = $sku . '_';

        $stmt = $db->prepare("UPDATE urunler SET stok_kodu = ? WHERE id = ?");
        $stmt->execute([$yeni_sku, $id]);
        $sayac++;
    }
}

echo "✅ $sayac adet Canon-Hp ürünü stok_kodu güncellendi.";
