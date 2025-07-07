<?php
require_once __DIR__ . '/../../moduller/db.php';

$klasor = __DIR__ . '/../../assets/resimler/';
$uzantilar = ['jpg', 'jpeg']; // Birden fazla uzantı destekleniyor

$sql = "SELECT id, stok_kodu, urun_adi FROM urunler ORDER BY id DESC";
$stmt = db()->prepare($sql);
$stmt->execute();
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>❌ Resmi Olmayan Ürünler</h3>";
echo "<table class='table table-bordered table-sm bg-white'>";
echo "<thead><tr><th>#</th><th>Stok Kodu</th><th>Ürün Adı</th></tr></thead><tbody>";

$sayac = 0;
foreach ($urunler as $urun) {
    $sku = trim($urun['stok_kodu']);
    $dosya_var = false;

    foreach ($uzantilar as $uzanti) {
        $dosya_yolu = $klasor . $sku . '.' . $uzanti;
        if (file_exists($dosya_yolu)) {
            $dosya_var = true;
            break;
        }
    }

    if (!$dosya_var) {
        $sayac++;
        echo "<tr>
            <td>{$sayac}</td>
            <td>{$sku}</td>
            <td>{$urun['urun_adi']}</td>
        </tr>";
    }
}

if ($sayac === 0) {
    echo "<tr><td colspan='3'><em>Bütün ürünlerin resmi mevcut.</em></td></tr>";
}
echo "</tbody></table>";
?>
