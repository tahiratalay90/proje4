<?php
require_once __DIR__ . '/../moduller/db.php';
$tablo = 'urunler';
include __DIR__ . '/../moduller/filtre.php'; // Filtre formunu ekler

// Sonra filtrelenmiş sonucu çek
$sql = "SELECT * FROM $tablo $filtre_where ORDER BY id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($filtre_veri);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
$toplam = count($urunler);
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
                echo "<td>" . htmlspecialchars($row[$alan]) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='".(count($alanlar)+1)."'><em>Henüz ürün yok.</em></td></tr>";
    }
    ?>
    </tbody>
</table>
