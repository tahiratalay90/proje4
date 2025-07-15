
<?php
/*

title ve anahtar alanını oluştur
$db = new PDO('sqlite:urunler.db');

function temizle_rank_math_title($urun_adi) {
    $kelimeler = [
        'sarı', 'mavi', 'siyah', 'kırmızı', 'xbox', 'chipsiz', 'drum', 'ünitesi', 'muadil', 'toner', 'universal','yüksek','kapasite','koli','bazında','Reload','kit','Ekstra','Chipli','Extra','With','chip','color'
    ];
    // 1) & işareti ve sonrasını sil (büyük/küçük harf önemli değil)
    $urun_adi = preg_replace('/\s*&.*$/iu', '', $urun_adi);

    // 2) Her kelimenin bütün varyasyonlarını sil (ör: kırmızı., kırmızısı, kırmızı-, kırmızı:, kırmızı,)
    foreach ($kelimeler as $kelime) {
        $desen = '/\b' . preg_quote($kelime, '/') . '[\p{L}\p{M}\p{N}\.\-_:;,]*\b/iu'; // Unicode, büyük/küçük harf duyarsız
        $urun_adi = preg_replace($desen, '', $urun_adi);
    }

    // 3) Fazla boşlukları temizle
    $temiz = preg_replace('/\s+/', ' ', trim($urun_adi));
    return $temiz;
}

$urunler = $db->query("SELECT stok_kodu, urun_adi FROM urunler")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("UPDATE urunler_seo SET rank_math_focus_keyword = ? WHERE sku = ?");

foreach ($urunler as $urun) {
    $temiz_title = temizle_rank_math_title($urun['urun_adi']);
    $stmt->execute([$temiz_title, $urun['stok_kodu']]);
}

echo "Tüm Rank Math Title alanları temizlendi ve güncellendi!";
?>




//description alanını oluştur
$db = new PDO('sqlite:urunler.db');

// Temizleme fonksiyonu
function temizle_rank_math_title($urun_adi) {
    $kelimeler = [
        'sarı', 'mavi', 'siyah', 'kırmızı', 'xbox', 'chipsiz', 'drum', 'ünitesi',  'universal',
        'yüksek','kapasite','koli','bazında','Reload','kit','Ekstra','Chipli','Extra','With','chip','color'
        // 'muadil' buraya eklemedim, açıklamada kalsın diye!
    ];
    $urun_adi = preg_replace('/\s*&.*$/iu', '', $urun_adi);
    foreach ($kelimeler as $kelime) {
        $desen = '/\b' . preg_quote($kelime, '/') . '[\p{L}\p{M}\p{N}\.\-_:;,]*\b/iu';
        $urun_adi = preg_replace($desen, '', $urun_adi);
    }
    $temiz = preg_replace('/\s+/', ' ', trim($urun_adi));
    return $temiz;
}

$urunler = $db->query("SELECT stok_kodu, urun_adi, baski_kapasitesi FROM urunler")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("UPDATE urunler_seo SET rank_math_description = ? WHERE sku = ?");

foreach ($urunler as $urun) {
    $temiz_urun_adi = temizle_rank_math_title($urun['urun_adi']);
    $baski = trim($urun['baski_kapasitesi']);

    if ($temiz_urun_adi && $baski) {
        // Noktasına, virgülüne dikkat!
        $desc = "{$temiz_urun_adi} ile {$baski}’den fazla net ve kaliteli baskı alın. Ekonomik, çevre dostu ve yüksek performanslı baskı deneyimini yaşayın.";
    } else {
        // Boş bırakıyoruz, istersen alternatif metin de ekleyebilirim.
        $desc = "";
    }
    $stmt->execute([$desc, $urun['stok_kodu']]);
}

echo "Tüm Rank Math Description alanları titizlikle dolduruldu!";
?>

*/

$db = new PDO('sqlite:urunler.db');

$urunler = $db->query("SELECT stok_kodu, baski_kapasitesi FROM urunler")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT rank_math_title FROM urunler_seo WHERE sku = ?");
$guncelle = $db->prepare("UPDATE urunler_seo SET rank_math_description = ? WHERE sku = ?");

foreach ($urunler as $urun) {
    // Her ürünün title'ını doğrudan alıyoruz (temizlik yok!)
    $title = '';
    $stmt->execute([$urun['stok_kodu']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['rank_math_title']) {
        $title = $row['rank_math_title'];
    }

    $baski = trim($urun['baski_kapasitesi']);

    if ($title && $baski) {
        $desc = "{$title} ile {$baski}’den fazla net ve kaliteli baskı alın. Ekonomik, çevre dostu ve yüksek performanslı baskı deneyimini yaşayın.";
    } else {
        $desc = "";
    }
    $guncelle->execute([$desc, $urun['stok_kodu']]);
}

echo "Tüm Rank Math Description alanları, title alanındaki değer ile güncellendi!";
?>
