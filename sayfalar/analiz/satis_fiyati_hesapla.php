<?php
ob_start();
require_once __DIR__ . '/../../moduller/db.php';

$tablo = 'urunler';
$filtre_ayar_kodu = 'toplu_guncelle';
include __DIR__ . '/../../moduller/filtre.php';

$alanlar_tumu = [];
$sorgu = db()->query("PRAGMA table_info($tablo)");
foreach ($sorgu as $satir) {
    $alanlar_tumu[] = $satir['name'];
}

// ✅ SATIŞ FİYATI HESAPLAMA (her ürün kendi gelis_fiyat_usd değerini kullanacak)
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['secili_idler'], $_POST['dolar_kuru'], $_POST['kdv_orani'], $_POST['kar_marji'], $_POST['kar_limit_tl'], $_POST['kar_limit_altinda_marj'])
) {
    $id_listesi = array_map('intval', $_POST['secili_idler']);
    $kur = floatval($_POST['dolar_kuru']);
    $kdv = floatval($_POST['kdv_orani']);
    $kar_marji = floatval($_POST['kar_marji']);
    $kar_limit = floatval($_POST['kar_limit_tl']);
    $kar_alt_marj = floatval($_POST['kar_limit_altinda_marj']);

    $placeholders = implode(',', array_fill(0, count($id_listesi), '?'));
    $stmt = db()->prepare("SELECT id, gelis_fiyat_usd FROM $tablo WHERE id IN ($placeholders)");
    $stmt->execute($id_listesi);
    $urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($urunler as $urun) {
        $gelis = floatval($urun['gelis_fiyat_usd']);
        $ara = $gelis * $kur;
        $kdvli = $ara * (1 + $kdv / 100);
        $kar = $kdvli < $kar_limit ? $kar_alt_marj : $kar_marji;
        $satis = round($kdvli * (1 + $kar / 100), 2);

        $guncelle = db()->prepare("UPDATE $tablo SET satis_fiyati = :satis WHERE id = :id");
        $guncelle->execute([
            'satis' => $satis,
            'id' => $urun['id']
        ]);
    }

    $url = $_SERVER['REQUEST_URI'];
    $url = preg_replace('/([&?])durum=[^&]*/', '', $url);
    $url = rtrim($url, '?&');
    $url .= (strpos($url, '?') === false ? '?' : '&') . 'durum=satis_fiyat_guncellendi';
    echo "<script>window.location.href='$url';</script>";
    exit;
}

$stmt = db()->prepare("SELECT * FROM $tablo $filtre_where ORDER BY id DESC");
$stmt->execute($filtre_veri);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>Toplu Satış Fiyatı Hesaplama</h3>

<?php if (isset($_GET['durum']) && $_GET['durum'] === 'satis_fiyat_guncellendi'): ?>
    <div class="alert alert-success">✅ Seçilen ürünlerin satış fiyatı güncellendi.</div>
<?php endif; ?>

<form method="post">
    <div class="border rounded-3 p-3 bg-white shadow-sm mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-2 col-6">
                <label class="form-label mb-1">Dolar Kuru</label>
                <input type="number" step="0.01" name="dolar_kuru" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label mb-1">KDV (%)</label>
                <input type="number" step="0.01" name="kdv_orani" class="form-control form-control-sm" value="20" required>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label mb-1">Kar Marjı (%)</label>
                <input type="number" step="0.01" name="kar_marji" class="form-control form-control-sm" value="25" required>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label mb-1">Kar Limit TL</label>
                <input type="number" step="0.01" name="kar_limit_tl" class="form-control form-control-sm" value="100" required>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label mb-1">Altında Marj (%)</label>
                <input type="number" step="0.01" name="kar_limit_altinda_marj" class="form-control form-control-sm" value="30" required>
            </div>
            <div class="col-md-2 col-12">
                <button type="submit" class="btn btn-primary btn-sm w-100">✅ Hesapla ve Kaydet</button>
            </div>
        </div>
    </div>

    <table class="table table-bordered table-sm bg-white">
        <thead class="table-success">
            <tr>
                <th><input type="checkbox" onclick="toggleAll(this)"></th>
                <th>#</th>
                <?php foreach ($alanlar as $alan): ?>
                    <th><?= htmlspecialchars(str_replace('_',' ', ucfirst($alan))) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($urunler as $i => $row): ?>
                <tr>
                    <td><input type="checkbox" name="secili_idler[]" value="<?= $row['id'] ?>"></td>
                    <td><?= $i + 1 ?></td>
                    <?php foreach ($alanlar as $alan): ?>
                        <td><?= htmlspecialchars($row[$alan]) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</form>

<script>
function toggleAll(source) {
    document.querySelectorAll('input[name="secili_idler[]"]').forEach(cb => cb.checked = source.checked);
}
</script>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<?php ob_end_flush(); ?>