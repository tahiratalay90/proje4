<?php
require_once __DIR__ . '/../../moduller/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Verileri al
$sql = "SELECT * FROM urunler ORDER BY id ASC";
$stmt = db()->prepare($sql);
$stmt->execute();
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$urunler) {
    die("❌ Ürün bulunamadı.");
}

// Excel nesnesi oluştur
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Başlıkları yaz
$basliklar = array_keys($urunler[0]);
$sheet->fromArray($basliklar, null, 'A1');

// Satırları yaz
$rowNum = 2;
foreach ($urunler as $urun) {
    $satir = [];
    foreach ($urun as $deger) {
        // HTML etiketlerini ve satır atlamalarını temizle
        $temiz = str_replace(["\r", "\n"], ' ', (string)$deger);

        $satir[] = $temiz;
    }
    $sheet->fromArray($satir, null, 'A' . $rowNum++);
}

// Kaydet
$klasor = __DIR__ . '/csv';
if (!is_dir($klasor)) mkdir($klasor, 0777, true);

$dosya_adi = 'urunler_' . date('Ymd_His') . '.xlsx';
$dosya_yolu = $klasor . '/' . $dosya_adi;

$writer = new Xlsx($spreadsheet);
$writer->save($dosya_yolu);

// Link oluştur
echo "<h3>✅ Excel dosyası başarıyla oluşturuldu</h3>";
echo "<a href='/sayfalar/local_islemler/csv/$dosya_adi' target='_blank' class='btn btn-success'>📥 Dosyayı İndir</a>";
