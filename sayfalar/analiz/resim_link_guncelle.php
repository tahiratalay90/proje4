<?php
require_once __DIR__ . '/../../moduller/db.php';

$klasor = __DIR__ . '/../../assets/resimler/';
$base_url = 'http://hementeknoloji.com.tr/wp-content/uploads/2025/07/';

// SEO uyumlu dosya adı
function seo_dosya_adi($metin) {
    $metin = strtolower(trim($metin));
    $turkce = ['ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç'];
    $ascii  = ['s','s','i','i','g','g','u','u','o','o','c','c'];
    $metin = str_replace($turkce, $ascii, $metin);
    $metin = preg_replace('/[^a-z0-9]/', '-', $metin);
    $metin = preg_replace('/-+/', '-', $metin);
    return trim($metin, '-');
}

// 1. Ürünleri al
$sql = "SELECT id, stok_kodu, urun_adi, resim_link FROM urunler WHERE stok_kodu IS NOT NULL AND stok_kodu != ''";
$stmt = db()->prepare($sql);
$stmt->execute();
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Hızlı eşleşme için map
$urun_map = [];
foreach ($urunler as $urun) {
    $sku = trim($urun['stok_kodu']);
    $urun_map[$sku] = $urun;
}

// 3. Klasördeki tüm resimleri tara
$sayac_rename = 0;
$sayac_sil = 0;

foreach (scandir($klasor) as $dosya_adi) {
    if (in_array($dosya_adi, ['.', '..'])) continue;

    $tam_yol = $klasor . $dosya_adi;
    if (!is_file($tam_yol)) continue;

    $sku = pathinfo($dosya_adi, PATHINFO_FILENAME);
    $uzanti = pathinfo($dosya_adi, PATHINFO_EXTENSION);

    // 📌 SKU veritabanında yoksa → dosyayı sil
    if (!isset($urun_map[$sku])) {
        if (unlink($tam_yol)) {
            echo "🗑️ Silindi (veritabanında yok): $dosya_adi<br>";
            $sayac_sil++;
        } else {
            echo "❌ Silinemedi: $dosya_adi<br>";
        }
        continue;
    }

    $urun = $urun_map[$sku];
    $id = $urun['id'];
    $urun_adi = $urun['urun_adi'];
    $eski_resim_link = $urun['resim_link'];

    // 🔒 Standart resimse dokunma
    if (
        str_contains($eski_resim_link, 'Xbox_Standart.jpeg') ||
        str_contains($eski_resim_link, 'Printpen_Standart.jpeg')
    ) {
        echo "🔒 Standart → atlandı: $dosya_adi<br>";
        continue;
    }

    // SEO dosya adı oluştur
    $seo_ad = seo_dosya_adi($urun_adi) . '.' . $uzanti;
    $yeni_yol = $klasor . $seo_ad;

    // Aynı isim varsa sil
    if (file_exists($yeni_yol) && $yeni_yol !== $tam_yol) {
        unlink($yeni_yol);
    }

    // Yeniden adlandır
    if (rename($tam_yol, $yeni_yol)) {
        $yeni_link = $base_url . $seo_ad;

        $update = db()->prepare("UPDATE urunler SET resim_link = :link WHERE id = :id");
        $update->execute([
            'link' => $yeni_link,
            'id' => $id
        ]);

        echo "✅ [$sku] → $dosya_adi → $seo_ad olarak değiştirildi ve link güncellendi.<br>";
        $sayac_rename++;
    } else {
        echo "❌ [$sku] → Dosya adı değiştirilemedi: $dosya_adi<br>";
    }
}

echo "<hr><strong>Toplam:</strong><br>";
echo "📁 Yeniden adlandırılan dosya: $sayac_rename<br>";
echo "🗑️ Silinen geçersiz dosya: $sayac_sil<br>";
?>
