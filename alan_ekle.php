<?php
require_once 'moduller/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// SEO dönüştürme fonksiyonu
function seoYap($metin) {
    $turkce = ['ç','ğ','ı','ö','ş','ü','Ç','Ğ','İ','Ö','Ş','Ü'];
    $duzgun = ['c','g','i','o','s','u','c','g','i','o','s','u'];
    $metin = str_replace($turkce, $duzgun, $metin);
    $metin = strtolower($metin);

    // 🔧 virgül ve & gibi özel ayraçları tireye çevir
    $metin = str_replace([',', '&', '/', '+'], '-', $metin);

    // alfasayısal, boşluk ve tire dışındaki her şeyi kaldır
    $metin = preg_replace('/[^a-z0-9\s-]/', '', $metin);
    $metin = preg_replace('/[\s]+/', '-', $metin);
    return trim($metin, '-');
}

// Klasör yolları
$kaynak_klasor = __DIR__ . '/assets/resimler2/';
$hedef_klasor  = __DIR__ . '/assets/resimler3/';
$url_base = "http://hementeknoloji.com.tr/wp-content/uploads/2025/07/";

// Hedef klasör yoksa oluştur
if (!is_dir($hedef_klasor)) {
    mkdir($hedef_klasor, 0777, true);
}
if (!is_writable($hedef_klasor)) {
    die("❌ HATA: $hedef_klasor klasörü yazılamıyor. CHMOD 777 yap.");
}

$db = db();
$urunler = $db->query("SELECT id, stok_kodu, urun_adi FROM urunler")->fetchAll(PDO::FETCH_ASSOC);
$sayac = 0;

foreach ($urunler as $urun) {
    $id = $urun['id'];
    $sku = $urun['stok_kodu'];
    $adi = $urun['urun_adi'];

    $bulundu = false;
    $uzanti = null;
    $orijinal_yol = null;

    // .jpg ve .jpeg dosyaları büyük/küçük harf duyarsız kontrol
    foreach (['jpg', 'jpeg', 'JPG', 'JPEG'] as $ext) {
        $yol = $kaynak_klasor . $sku . '.' . $ext;
        if (file_exists($yol)) {
            $bulundu = true;
            $uzanti = strtolower($ext); // hedefte küçük harf olarak kaydedeceğiz
            $orijinal_yol = $yol;
            break;
        }
    }

    if ($bulundu) {
        $seo_ad = seoYap($adi);
        $yeni_dosya = $seo_ad . '.' . $uzanti;
        $yeni_yol = $hedef_klasor . $yeni_dosya;

        if (!file_exists($yeni_yol)) {
            if (!copy($orijinal_yol, $yeni_yol)) {
                echo "❌ Kopyalanamadı: $orijinal_yol → $yeni_yol<br>";
                continue;
            } else {
                echo "✅ Kopyalandı: $orijinal_yol → $yeni_yol<br>";
            }
        } else {
            echo "ℹ️ Zaten var: $yeni_dosya<br>";
        }

        $resim_link = $url_base . $yeni_dosya;
    } elseif (stripos($adi, 'xbox') !== false) {
        $resim_link = $url_base . 'Xbox_Standart.jpeg';
        echo "⚠️ Eşleşme yok, XBox geçiyor: $adi → Xbox_Standart.jpeg<br>";
    } else {
        $resim_link = $url_base . 'Printpen_Standart.jpeg';
        echo "⚠️ Eşleşme yok, XBox geçmiyor: $adi → Printpen_Standart.jpeg<br>";
    }

    // Veritabanına yaz
    $stmt = $db->prepare("UPDATE urunler SET resim_link = ? WHERE id = ?");
    $stmt->execute([$resim_link, $id]);
    $sayac++;
}

echo "<hr>✅ Toplam $sayac ürün işlendi ve resim_link alanı güncellendi.";
