<?php
// Woo API bilgilerini al
$api = json_decode(file_get_contents(__DIR__ . '/../../ayarlar/wc_api.json'), true);

if (!$api || !isset($api['site_url'], $api['consumer_key'], $api['consumer_secret'])) {
    die("API bilgileri eksik.");
}

// Kategorileri çek
$url = $api['site_url'] . "/wp-json/wc/v3/products/categories?per_page=100";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, $api['consumer_key'] . ':' . $api['consumer_secret']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$kategoriler = json_decode($response, true);

// Ağaç yapısı için önce ID'ye göre grupla
$kategori_agaci = [];
$kategori_index = [];

foreach ($kategoriler as $kat) {
    $kat['children'] = []; // altlar için boş yer
    $kategori_index[$kat['id']] = $kat;
}

// Ağaç yapısını oluştur
foreach ($kategori_index as $id => $kat) {
    if ($kat['parent'] != 0 && isset($kategori_index[$kat['parent']])) {
        $kategori_index[$kat['parent']]['children'][] = &$kategori_index[$id];
    } else {
        $kategori_agaci[] = &$kategori_index[$id];
    }
}

// Girintili olarak yazdıran fonksiyon
function kategori_yazdir($kategoriler, $seviye = 0) {
    foreach ($kategoriler as $kat) {
        echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $seviye);
        echo "- <b>ID:</b> {$kat['id']} – <b>Ad:</b> {$kat['name']}<br>";
        if (!empty($kat['children'])) {
            kategori_yazdir($kat['children'], $seviye + 1);
        }
    }
}

// Göster
echo "<h3>WooCommerce Kategori Listesi (Girintili)</h3>";
kategori_yazdir($kategori_agaci);
