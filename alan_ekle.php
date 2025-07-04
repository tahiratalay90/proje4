<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = new PDO('sqlite:urunler.db');

// 1. İlgili ürünleri çek (kategori Drum Unitesi ve adında Drum Unit geçenler)
$stmt = $db->prepare("SELECT id, urun_adi FROM urunler WHERE kategori = 'Drum Unitesi' AND urun_adi LIKE '%Drum Unit%'");
$stmt->execute();
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Güncelleme işlemi
foreach ($urunler as $urun) {
    $yeni_ad = str_replace('Drum Unit', 'Drum Ünitesi', $urun['urun_adi']);

    $guncelle = $db->prepare("UPDATE urunler SET urun_adi = :ad WHERE id = :id");
    $guncelle->execute([
        ':ad' => $yeni_ad,
        ':id' => $urun['id']
    ]);
}

echo "✅ Drum Unit → Drum Ünitesi dönüşümü tamamlandı.";
