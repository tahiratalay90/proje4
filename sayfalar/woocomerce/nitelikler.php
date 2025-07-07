<?php
require_once __DIR__ . '/../../moduller/db.php'; // API bağlantı bilgileri buradaysa
ini_set('display_errors', 1);
error_reporting(E_ALL);

// API bilgileri
$api = json_decode(file_get_contents(__DIR__ . '/../../ayarlar/wc_api.json'), true);
$api_url = rtrim($api['site_url'], '/');
$auth = $api['consumer_key'] . ':' . $api['consumer_secret'];

// 1. Tüm nitelikleri çek
$ch = curl_init("$api_url/wp-json/wc/v3/products/attributes");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $auth,
    CURLOPT_RETURNTRANSFER => true
]);
$response = curl_exec($ch);
curl_close($ch);
$attributes = json_decode($response, true);

// 2. Her nitelik için terimleri çek
echo "<h3>🧩 WooCommerce Nitelikleri ve Alt Terimleri</h3>";
echo "<ul style='font-family:monospace; font-size:15px;'>";

foreach ($attributes as $attr) {
    echo "<li><strong>🟦 " . htmlspecialchars($attr['name']) . "</strong></li>";

    // Terimleri çek
    $id = $attr['id'];
    $ch = curl_init("$api_url/wp-json/wc/v3/products/attributes/$id/terms");
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => $auth,
        CURLOPT_RETURNTRANSFER => true
    ]);
    $terms_json = curl_exec($ch);
    curl_close($ch);
    $terms = json_decode($terms_json, true);

    if (!empty($terms)) {
        echo "<ul>";
        foreach ($terms as $term) {
            echo "<li>↳ " . htmlspecialchars($term['name']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<ul><li><em>↳ Hiç terim yok</em></li></ul>";
    }
}
echo "</ul>";
?>
