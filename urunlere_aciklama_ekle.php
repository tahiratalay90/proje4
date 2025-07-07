<?php
require_once __DIR__ . '/moduller/db.php';

// HTML açıklamayı oluşturan fonksiyon
function aciklama_html_olustur($urun) {
    $rengi = '';
    $adi = strtolower($urun['urun_adi']);
    if (str_contains($adi, 'siyah'))      $rengi = 'Mono Siyah (Black)';
    elseif (str_contains($adi, 'mavi'))   $rengi = 'Mavi (Cyan)';
    elseif (str_contains($adi, 'kırmızı'))$rengi = 'Kırmızı (Magenta)';
    elseif (str_contains($adi, 'sarı'))   $rengi = 'Sarı (Yellow)';

    $html = '
<div id="selam" class="container mt-3">
<h3><b>' . htmlspecialchars($urun['urun_adi']) . '</b></h3>
<table class="table table-striped table-hover mt-3"><tbody>
<tr><td class="col-4"><b>Toner Modeli</b></td><td>' . htmlspecialchars($urun['urun_adi']) . '</td></tr>
<tr><td><b>Stok No</b></td><td>' . htmlspecialchars($urun['stok_kodu']) . '</td></tr>
<tr><td><b>Durumu</b></td><td>Muadil Sıfır Ürün</td></tr>
<tr><td><b>Muadil Marka</b></td><td>' . htmlspecialchars($urun['muadil_marka']) . '</td></tr>
<tr><td><b>Orijinal Marka</b></td><td>' . htmlspecialchars($urun['marka']) . '</td></tr>
<tr><td><b>Baskı Rengi</b></td><td>' . $rengi . '</td></tr>
<tr><td><b>Baskı Sayısı</b></td><td>' . htmlspecialchars($urun['baski_kapasitesi']) . '</td></tr>
<tr><td><b>Baskı Teknolojisi</b></td><td>Lazer</td></tr>
<tr><td><b>Çalışma Isısı</b></td><td>10 - 32,5°C</td></tr>
<tr><td><b>Garanti Süresi</b></td><td>24 ay</td></tr>
<tr><td><b>Kalite Kontrol</b></td><td>ISO 9001:2008</td></tr>
<tr><td><b>Test Sayfası - 1</b></td><td><a href="http://hementeknoloji.com.tr/wp-content/uploads/2025/05/600dpi_Test.pdf">ImageExpert 600dpi Test Sayfası</a></td></tr>
<tr><td><b>Test Sayfası - 2</b></td><td><a href="http://hementeknoloji.com.tr/wp-content/uploads/2025/05/ASTM_Test.pdf">ASTM Test Sayfaları</a></td></tr>
<tr><td><b>Test Sayfası - 3</b></td><td><a href="http://hementeknoloji.com.tr/wp-content/uploads/2025/05/Pattern_QEA.pdf">%5 Sayfa Doluluk Testi (Monochrome)</a></td></tr>
<tr><td><b>Test Sayfası - 4</b></td><td><a href="http://hementeknoloji.com.tr/wp-content/uploads/2025/05/Yuzde_5_Safya_Doluluk_Test_Sayfasi_Monochrome.pdf">%5 Sayfa Doluluk Testi</a></td></tr>
<tr><td><b>Tanıtım Filmleri</b></td>
<td><a href="http://hementeknoloji.com.tr/wp-content/uploads/2025/05/Esi-Benzeri-Sadece-Orijinali-PrintPen.mp4">Printpen</a> -
<a href="http://hementeknoloji.com.tr/wp-content/uploads/2025/05/Esi-Benzeri-Sadece-Orijinali-PrintPen.mp4">Kalite Kontrol</a> -
<a href="http://hementeknoloji.com.tr/wp-content/uploads/2025/05/Esi-Benzeri-Sadece-Orijinali-PrintPen.mp4">Test Videosu</a></td></tr>
<tr><td><b>Uyumlu Yazıcı Listesi</b></td><td>' . htmlspecialchars($urun['yazici_uyumluluk_listesi']) . '</td></tr>
</tbody></table></div>';

    return $html;
}

// Bütün ürünleri al
$sql = "SELECT * FROM urunler";
$stmt = db()->prepare($sql);
$stmt->execute();
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sayac = 0;

foreach ($urunler as $urun) {
    $aciklama = aciklama_html_olustur($urun);

    // Veritabanına yaz
    $sql = "UPDATE urunler SET aciklama = :aciklama WHERE stok_kodu = :sku";
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':aciklama' => $aciklama,
        ':sku' => $urun['stok_kodu']
    ]);

    $sayac++;
}

echo "✅ Toplam {$sayac} ürünün açıklaması güncellendi.";
