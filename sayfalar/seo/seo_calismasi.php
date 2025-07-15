<?php
require_once __DIR__ . '/../../moduller/db.php';
$tablo = 'urunler';

// Filtre formunu yine dahil ediyorsun
include __DIR__ . '/../../moduller/filtre.php'; // $filtre_where ve $filtre_veri burada tanımlanıyor

// Önemli: Tablo adını SQL’de değiştiriyoruz!
$sql = "
SELECT 
    urunler.*, 
    urunler_seo.rank_math_title, 
    urunler_seo.rank_math_focus_keyword, 
    urunler_seo.rank_math_description
FROM urunler
LEFT JOIN urunler_seo ON urunler.stok_kodu = urunler_seo.sku
$filtre_where
ORDER BY urunler.id DESC
";

$stmt = db()->prepare($sql);
$stmt->execute($filtre_veri);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
$toplam = count($urunler);

// Alanları dinamik yapıyorsan, buraya SEO başlıklarını ekle
$seo_alanlar = ['rank_math_title', 'rank_math_focus_keyword', 'rank_math_description'];
$alanlar = array_merge($alanlar, $seo_alanlar);
?>

<h3>Ürün Listesi <small class="text-muted">(<?= $toplam ?> adet)</small></h3>

<table class="table table-bordered table-sm bg-white">
    <thead class="table-success">
        <tr>
            <th>#</th>
            <?php foreach($alanlar as $alan): ?>
                <th><?= htmlspecialchars(str_replace('_', ' ', ucfirst($alan))) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php
    if ($toplam > 0) {
        $i = 0;
        foreach($urunler as $row) {
            echo "<tr><td>".(++$i)."</td>";
            foreach($alanlar as $alan) {
                echo "<td>" . htmlspecialchars($row[$alan] ?? '') . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='".(count($alanlar)+1)."'><em>Henüz ürün yok.</em></td></tr>";
    }
    ?>
    </tbody>
</table>
