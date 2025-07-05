<?php
require_once __DIR__ . '/../../moduller/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Sayfa arayüzü
echo '<form method="POST" style="margin-bottom:20px;">
    <label><b>📄 HTML Dosyası Seç:</b></label>
    <select name="dosya" class="form-control" style="width: 300px; display: inline-block; margin-right: 10px;">';

$klasor = __DIR__ . '/../../aksel-alan2/';
foreach (glob($klasor . '*.html') as $dosya) {
    $ad = basename($dosya);
    echo "<option value=\"$ad\">$ad</option>";
}

echo '</select>
    <button type="submit" class="btn btn-success">🔄 İşlem Yap</button>
</form>
<hr>';

// İşlem yapılacaksa başla
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dosya'])) {
    $dosya_adi = basename($_POST['dosya']);
    $dosya_yolu = $klasor . $dosya_adi;

    if (!file_exists($dosya_yolu)) {
        echo "<div class='alert alert-danger'>❌ Dosya bulunamadı: $dosya_adi</div>";
        exit;
    }

    echo "<div class='alert alert-info'>📂 Seçilen Dosya: <b>$dosya_adi</b></div>";

    // HTML dosyasını düz string olarak oku
    $html = file_get_contents($dosya_yolu);

    // Ürün bloklarını çek
    preg_match_all('/Stok Kodu:\s*(\d+).*?İzmir Stok Durumu\s*<\/b>\s*:\s*([^<]+).*?İstanbul Stok Durumu\s*<\/b>\s*:\s*([^<]+)/s', $html, $eslesenler, PREG_SET_ORDER);

    function sayiyaCevir($deger) {
        if (preg_match('/(\d+)/', $deger, $m)) {
            return (int)$m[1];
        }
        return 0;
    }

    $toplam = count($eslesenler);
    $guncellenen = 0;
    $ayni = 0;
    $yok = 0;

    echo "<h4>🔍 İşlem Sonuçları</h4>";

    foreach ($eslesenler as $satir) {
        $sku = $satir[1];
        $izmir_stok = sayiyaCevir($satir[2]);
        $istanbul_stok = sayiyaCevir($satir[3]);

        // Mevcut kayıt var mı?
        $sorgu = db()->prepare("SELECT stok, kendi_depomuz FROM urunler WHERE stok_kodu = ?");
        $sorgu->execute([$sku]);
        $mevcut = $sorgu->fetch(PDO::FETCH_ASSOC);

        if (!$mevcut) {
            echo "❌ Veritabanında yok: <b>$sku</b><br>";
            $yok++;
            continue;
        }

        if ($mevcut['stok'] != $izmir_stok || $mevcut['kendi_depomuz'] != $istanbul_stok) {
            $guncelle = db()->prepare("UPDATE urunler SET stok = ?, kendi_depomuz = ? WHERE stok_kodu = ?");
            $guncelle->execute([$izmir_stok, $istanbul_stok, $sku]);
            echo "🟢 Güncellendi: <b>$sku</b> → İzmir: $izmir_stok | İstanbul: $istanbul_stok<br>";
            $guncellenen++;
        } else {
            echo "⚪ Değişmedi: <b>$sku</b><br>";
            $ayni++;
        }
    }

    echo "<hr><b>Toplam:</b> $toplam ürün bulundu.<br>";
    echo "<b>✔ Güncellendi:</b> $guncellenen<br>";
    echo "<b>⚪ Zaten Aynı:</b> $ayni<br>";
    echo "<b>❌ Veritabanında Yok:</b> $yok<br>";
}
?>
