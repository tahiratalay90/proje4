<?php
$kaynak_klasor = __DIR__ . '/../../assets/resimler/';
$hedef_klasor  = __DIR__ . '/../../aksel-son-sistem/resimler/';

// 1. Kaynak klasördeki dosya adlarını oku (uzantısız)
$stok_kodlari = [];

foreach (scandir($kaynak_klasor) as $dosya) {
    if (in_array($dosya, ['.', '..'])) continue;
    if (!is_file($kaynak_klasor . $dosya)) continue;

    $sku = pathinfo($dosya, PATHINFO_FILENAME);
    $stok_kodlari[$sku] = true;
}

// 2. Hedef klasörü tara, eşleşenleri sil
$silinen_sayisi = 0;

foreach (scandir($hedef_klasor) as $dosya) {
    if (in_array($dosya, ['.', '..'])) continue;
    $dosya_yolu = $hedef_klasor . $dosya;

    if (!is_file($dosya_yolu)) continue;

    $sku = pathinfo($dosya, PATHINFO_FILENAME);

    if (isset($stok_kodlari[$sku])) {
        if (unlink($dosya_yolu)) {
            echo "🗑️ Silindi: $dosya<br>";
            $silinen_sayisi++;
        } else {
            echo "❌ Silinemedi: $dosya<br>";
        }
    }
}

echo "<hr><strong>Toplam silinen dosya: $silinen_sayisi</strong>";
?>
