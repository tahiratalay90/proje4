<?php
ob_start();
require_once __DIR__ . '/../moduller/db.php';

$tablo = 'urunler';
$filtre_ayar_kodu = 'toplu_guncelle';
include __DIR__ . '/../moduller/filtre.php';

// ✅ TÜM ALANLARI VERİTABANINDAN AL (güncelleme için sadece bunu kullan)
$alanlar_tumu = [];
$sorgu = db()->query("PRAGMA table_info($tablo)");
foreach ($sorgu as $satir) {
    $alanlar_tumu[] = $satir['name'];
}

// ✅ TOPLU GÜNCELLEME
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['secili_idler'], $_POST['hedef_alan'], $_POST['hedef_deger'], $_POST['toplu_guncelle'])
) {
    $hedef_alan = $_POST['hedef_alan'];
    $hedef_deger = $_POST['hedef_deger'];
    $id_listesi = array_map('intval', $_POST['secili_idler']);

    if (in_array($hedef_alan, $alanlar_tumu) && count($id_listesi) > 0) {
        $placeholders = implode(',', array_fill(0, count($id_listesi), '?'));
        $sql = "UPDATE $tablo SET $hedef_alan = ? WHERE id IN ($placeholders)";
        db()->prepare($sql)->execute(array_merge([$hedef_deger], $id_listesi));

        $url = $_SERVER['REQUEST_URI'];
        $url = preg_replace('/([&?])durum=[^&]*/', '', $url);
        $url = rtrim($url, '?&');
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'durum=secili_guncellendi';
        echo "<script>window.location.href='$url';</script>";
        exit;

        
    }
}

// ✅ TOPLU SİLME
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['secili_idler'], $_POST['toplu_sil'])
) {
    $id_listesi = array_map('intval', $_POST['secili_idler']);
    if (count($id_listesi) > 0) {
        $placeholders = implode(',', array_fill(0, count($id_listesi), '?'));
        $sql = "DELETE FROM $tablo WHERE id IN ($placeholders)";
        db()->prepare($sql)->execute($id_listesi);

        $url = $_SERVER['REQUEST_URI'];
        $url = preg_replace('/([&?])durum=[^&]*/', '', $url);
        $url = rtrim($url, '?&');
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'durum=secili_silindi';
        echo "<script>window.location.href='$url';</script>";
        exit;
        
    }
}

// ✅ Ürünleri çek
$stmt = db()->prepare("SELECT * FROM $tablo $filtre_where ORDER BY id DESC");
$stmt->execute($filtre_veri);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>Toplu Güncelleme</h3>

<?php if (isset($_GET['durum']) && $_GET['durum'] === 'secili_guncellendi'): ?>
    <div class="alert alert-success">✅ Seçilen ürünler başarıyla güncellendi.</div>
<?php elseif (isset($_GET['durum']) && $_GET['durum'] === 'secili_silindi'): ?>
    <div class="alert alert-danger">🗑 Seçilen ürünler başarıyla silindi.</div>
<?php endif; ?>

<!-- ✅ TEK FORM -->
<form method="post">
    <!-- Kontrol Paneli -->
    <div class="border rounded-3 p-3 bg-white shadow-sm mb-4">
        <div class="mb-2 fw-bold text-success" style="font-size:16px;">
            <span class="material-icons align-middle">edit</span> Toplu Güncelleme Formu
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-6">
                <label class="form-label mb-1">Alan Seç</label>
                <select name="hedef_alan" class="form-select form-select-sm">
                    <option value="">Güncelleme yapılacak alanı seç</option>
                    <?php foreach ($alanlar_tumu as $alan): ?>
                        <option value="<?= $alan ?>"><?= ucwords(str_replace('_',' ', $alan)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 col-6">
                <label class="form-label mb-1">Yeni Değer</label>
                <input type="text" name="hedef_deger" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label mb-1 d-none d-md-block">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" name="toplu_guncelle" class="btn btn-success btn-sm w-100">✅ Güncelle</button>
                    <button type="submit" name="toplu_sil" class="btn btn-danger btn-sm w-100" onclick="return confirm('Seçilen ürünler silinecek. Emin misin?')">🗑 Sil</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ ÜRÜN TABLOSU -->
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
            <?php if (empty($urunler)): ?>
                <tr><td colspan="<?= count($alanlar) + 2 ?>">Kayıt bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</form>

<script>
function toggleAll(source) {
    document.querySelectorAll('input[name="secili_idler[]"]').forEach(cb => cb.checked = source.checked);
}

// ✅ Güncellemede alanlar zorunlu, silmede değil
const form = document.querySelector('form');
const alanSec = form.querySelector('[name="hedef_alan"]');
const degerInput = form.querySelector('[name="hedef_deger"]');

form.addEventListener('submit', function (e) {
    const clickedButton = document.activeElement;
    if (clickedButton && clickedButton.name === 'toplu_guncelle') {
        alanSec.required = true;
        degerInput.required = true;
    } else {
        alanSec.required = false;
        degerInput.required = false;
    }
});
</script>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php ob_end_flush(); ?>
