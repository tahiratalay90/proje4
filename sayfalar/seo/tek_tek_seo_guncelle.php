<?php
ob_start();
require_once __DIR__ . '/../../moduller/db.php';
$tablo = 'urunler_seo';
$filtre_ayar_kodu = 'seo_guncelle'; // Filtre ayarı eğer özel istiyorsan
include __DIR__ . '/../../moduller/filtre.php'; // filtre paneli + filtre_where + alanlar

// SİL (SEO kaydı silmek istiyorsan aktif bırak)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_id'])) {
    db()->prepare("DELETE FROM $tablo WHERE sku = ?")->execute([$_POST['sil_id']]);
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

// GÜNCELLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle_id'])) {
    $sku = $_POST['guncelle_id'];
    $setler = [];
    $veriler = [];
    foreach ($alanlar as $alan) {
        $setler[] = "$alan = ?";
        $veriler[] = $_POST[$alan] ?? '';
    }
    $veriler[] = $sku;
    $sql = "UPDATE $tablo SET " . implode(', ', $setler) . " WHERE sku = ?";
    db()->prepare($sql)->execute($veriler);

    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

// LİSTELE (Filtreli şekilde)
$stmt = db()->prepare("SELECT * FROM $tablo $filtre_where ORDER BY sku DESC");
$stmt->execute($filtre_veri);
$seo_kayitlari = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>SEO Alanlarını Güncelle</h3>
<table class="table table-bordered table-sm bg-white">
    <thead class="table-success">
        <tr>
            <th>#</th>
            <?php foreach ($alanlar as $alan): ?>
                <th><?= htmlspecialchars(str_replace('_', ' ', ucfirst($alan))) ?></th>
            <?php endforeach; ?>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($seo_kayitlari as $i => $row): ?>
        <tr>
            <tr id="satir-<?= htmlspecialchars($row['sku']) ?>">
                <form method="post" action="?<?= $_SERVER['QUERY_STRING'] ?>#satir-<?= htmlspecialchars($row['sku']) ?>">
                <td><?= $i + 1 ?></td>
                <?php foreach ($alanlar as $alan): ?>
                    <td>
                        <input type="text" name="<?= $alan ?>" value="<?= htmlspecialchars($row[$alan]) ?>" class="form-control form-control-sm">
                    </td>
                <?php endforeach; ?>
                <td style="white-space:nowrap;">
                    <input type="hidden" name="guncelle_id" value="<?= $row['sku'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning">Güncelle</button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Silinsin mi?')">
                <input type="hidden" name="sil_id" value="<?= $row['sku'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Sil</button>
            </form>
                </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($seo_kayitlari)): ?>
            <tr><td colspan="<?= count($alanlar) + 2 ?>">Kayıt bulunamadı.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php ob_end_flush(); ?>
