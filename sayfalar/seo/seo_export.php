<?php
require_once __DIR__ . '/../../moduller/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../moduller/filtre.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$urunler_alanlar = ['urun_adi'];
$seo_alanlar = ['sku','rank_math_title', 'rank_math_focus_keyword', 'rank_math_description'];

$alanlar_sql = [];
foreach ($urunler_alanlar as $al) {
    $alanlar_sql[] = "u.$al";
}
foreach ($seo_alanlar as $al) {
    $alanlar_sql[] = "s.$al";
}
$select_alanlar = implode(', ', $alanlar_sql);

$sql = "
SELECT $select_alanlar
FROM urunler u
LEFT JOIN urunler_seo s ON u.stok_kodu = s.sku
$filtre_where
ORDER BY u.id ASC
";

$stmt = db()->prepare($sql);
$stmt->execute($filtre_veri);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$urunler) {
    die("❌ Sonuç bulunamadı.");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Başlıkları yazarken "Meta: " öne ekle
$basliklar = array_map(function($alan) {
    return "Meta: $alan";
}, array_keys($urunler[0]));
$sheet->fromArray($basliklar, null, 'A1');

// Satırları yaz
$rowNum = 2;
foreach ($urunler as $urun) {
    $satir = [];
    foreach ($urun as $deger) {
        $temiz = str_replace(["\r", "\n"], ' ', (string)$deger);
        $satir[] = $temiz;
    }
    $sheet->fromArray($satir, null, 'A' . $rowNum++);
}

$klasor = __DIR__ . '/csv';
if (!is_dir($klasor)) mkdir($klasor, 0777, true);

$dosya_adi = 'seo_export_' . date('Ymd_His') . '.xlsx';
$dosya_yolu = $klasor . '/' . $dosya_adi;

$writer = new Xlsx($spreadsheet);
$writer->save($dosya_yolu);

echo "<h3>✅ SEO Excel dosyası başarıyla oluşturuldu</h3>";
echo "<a href='/sayfalar/seo/csv/$dosya_adi' target='_blank' class='btn btn-success'>📥 Dosyayı İndir</a>";
?>
