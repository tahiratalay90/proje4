<?php
ob_start();
require_once __DIR__ . '/../../moduller/db.php';

$tablo = 'urunler';
$hata = '';
$basari = '';

// ✅ CSV Yüklendiğinde işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_dosya'])) {
    $csv_dosya = $_FILES['csv_dosya']['tmp_name'];

    if (!is_uploaded_file($csv_dosya)) {
        $hata = "CSV dosyası yüklenemedi.";
    } else {
        $handle = fopen($csv_dosya, 'r');
        if ($handle === false) {
            $hata = "CSV dosyası açılamadı.";
        } else {
            $veritabani_alanlar = [];
            $sorgu = db()->query("PRAGMA table_info($tablo)");
            foreach ($sorgu as $satir) {
                $veritabani_alanlar[] = $satir['name'];
            }

            $basliklar = fgetcsv($handle, 1000, ',');
            $uygun_alanlar = array_intersect($basliklar, $veritabani_alanlar);

            if (empty($uygun_alanlar)) {
                $hata = "CSV dosyasında veritabanı ile eşleşen alan başlığı bulunamadı.";
            } else {
                $sayac = 0;
                while (($veri = fgetcsv($handle, 1000, ',')) !== false) {
                    $veri_map = array_combine($basliklar, $veri);
                    $alanlar = [];
                    $degerler = [];
                    foreach ($uygun_alanlar as $alan) {
                        $alanlar[] = $alan;
                        $degerler[] = $veri_map[$alan];
                    }

                    $sql = "INSERT INTO $tablo (" . implode(', ', $alanlar) . ") VALUES (" . implode(',', array_fill(0, count($degerler), '?')) . ")";
                    $stmt = db()->prepare($sql);
                    $stmt->execute($degerler);
                    $sayac++;
                }

                fclose($handle);
                $basari = "✅ Toplam $sayac ürün başarıyla eklendi.";
            }
        }
    }
}
?>

<h3>Toplu Ürün Ekle</h3>

<?php if ($basari): ?>
    <div class="alert alert-success"><?= $basari ?></div>
<?php elseif ($hata): ?>
    <div class="alert alert-danger"><?= $hata ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">CSV Dosyası Yükle</label>
        <input type="file" name="csv_dosya" accept=".csv" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">📥 Ürünleri Ekle</button>
</form>

<div class="mt-4">
    <p class="text-muted">📝 CSV dosyasının ilk satırı <strong>başlık</strong> olmalıdır. Örnek başlıklar:</p>
    <pre>urun_adi,fiyat,stok_kodu,stok_izmir,stok_istanbul</pre>
</div>

<?php ob_end_flush(); ?>
