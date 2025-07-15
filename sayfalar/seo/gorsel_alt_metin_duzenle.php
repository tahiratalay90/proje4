<?php
// API bilgilerini oku
$api = json_decode(file_get_contents(__DIR__ . '/../../ayarlar/wc_api.json'), true);
if (!$api || !isset($api['site_url'], $api['consumer_key'], $api['consumer_secret'])) {
    die("API bilgileri eksik.");
}

require_once __DIR__ . '/../../vendor/autoload.php'; // GuzzleHttp için
$db = new PDO('sqlite:urunler.db');
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => rtrim($api['site_url'], '/') . '/wp-json/',
    'auth' => [$api['consumer_key'], $api['consumer_secret']]
]);

// 1. Tüm urunler_seo tablosundan sku ve title oku
$rows = $db->query("SELECT sku, rank_math_title FROM urunler_seo")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $sku = $row['sku'];
    $title = $row['rank_math_title'];
    if (!$sku || !$title) continue;

    // 2. WooCommerce'den SKU ile ürünü bul
    $res = $client->get('wc/v3/products', ['query' => ['sku' => $sku]]);
    $products = json_decode($res->getBody(), true);
    if (empty($products)) {
        echo "<span style='color:red;'>$sku: WooCommerce'de ürün bulunamadı</span><br>";
        continue;
    }
    $product = $products[0];

    // 3. Ana görsel ID'sini al
    if (empty($product['images'][0]['id'])) {
        echo "<span style='color:orange;'>$sku: Ürünün ana görseli yok</span><br>";
        continue;
    }
    $image_id = $product['images'][0]['id'];

    // 4. API ile görselin alt metnini güncelle
    try {
        $res2 = $client->post("wp/v2/media/$image_id", [
            'json' => ['alt_text' => $title]
        ]);
        if ($res2->getStatusCode() == 200) {
            echo "<span style='color:green;'>$sku: Başarılı! Alt metin güncellendi → <b>$title</b></span><br>";
        } else {
            echo "<span style='color:red;'>$sku: Güncellenemedi!</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red;'>$sku: HATA! {$e->getMessage()}</span><br>";
    }
}
echo "<br><b>İşlem tamamlandı.</b>";
?>
