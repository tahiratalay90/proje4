
<?php
ob_start(); // Çıktı tamponu başlat
require_once __DIR__ . '/../../moduller/db.php';
$tablo = 'urunler';
$filtre_ayar_kodu = 'guncelle';
include __DIR__ . '/../../moduller/filtre.php'; // filtre paneli + filtre_where + alanlar

// SİL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_id'])) {
    db()->prepare("DELETE FROM $tablo WHERE id = ?")->execute([intval($_POST['sil_id'])]);
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

// GÜNCELLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle_id'])) {
    $id = intval($_POST['guncelle_id']);
    $setler = [];
    $veriler = [];

    foreach ($alanlar as $alan) {
        $setler[] = "$alan = ?";
        $veriler[] = $_POST[$alan] ?? '';
    }
    $veriler[] = $id;

    $sql = "UPDATE $tablo SET " . implode(', ', $setler) . " WHERE id = ?";
    db()->prepare($sql)->execute($veriler);

    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

// LİSTELE
$stmt = db()->prepare("SELECT * FROM $tablo $filtre_where ORDER BY id DESC");
$stmt->execute($filtre_veri);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>Ürün Güncelleme</h3>

<table class="table table-bordered table-sm bg-white">
    <thead class="table-success">
        <tr>
            <th>#</th>
            <?php foreach ($alanlar as $alan): ?>
                <th><?= htmlspecialchars(str_replace('_',' ', ucfirst($alan))) ?></th>
            <?php endforeach; ?>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($urunler as $i => $row): ?>
        <tr>
            <form method="post">
                <td><?= $i + 1 ?></td>
                <?php foreach ($alanlar as $alan): ?>
                    <td>
                        <input type="text" name="<?= $alan ?>" value="<?= htmlspecialchars($row[$alan]) ?>" class="form-control form-control-sm">
                    </td>
                <?php endforeach; ?>
                <td style="white-space:nowrap;">
                    <input type="hidden" name="guncelle_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning">Güncelle</button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Silinsin mi?')">
                <input type="hidden" name="sil_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Sil</button>
            </form>
                </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($urunler)): ?>
            <tr><td colspan="<?= count($alanlar) + 2 ?>">Kayıt bulunamadı.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php ob_end_flush(); // Tamponu sonlandır ?>
