<?php
require_once 'moduller/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Klasör yolu
$klasor = __DIR__ . '/assets/resimler3/';

$db = db();
$urunler = $db->query("SELECT id, urun_adi, resim_link FROM urunler")->fetchAll(PDO::FETCH_ASSOC);
$sayac = 0;
$eksik = 0;

echo "<table border='1' cellpadding='6' style='font-family: monospace; font-size:14px;'>";
echo "<tr><th>ID</th><th>Dosya Adı</th><th>Durum</th></tr>";

foreach ($urunler as $urun) {
    $id = $urun['id'];
    $link = $urun['resim_link'];
    $dosya_adi = basename($link); // sadece dosya adını al

    $tam_yol = $klasor . $dosya_adi;
    if (file_exists($tam_yol)) {
        echo "<tr><td>$id</td><td>$dosya_adi</td><td style='color:green;'>✅ VAR</td></tr>";
        $sayac++;
    } else {
        echo "<tr><td>$id</td><td>$dosya_adi</td><td style='color:red;'>❌ YOK</td></tr>";
        $eksik++;
    }
}

echo "</table>";
echo "<hr><b>Toplam Varlık:</b> $sayac<br>";
echo "<b>Eksik Dosya:</b> $eksik";
