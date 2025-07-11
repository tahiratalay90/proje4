<?php
// --- Türkçe karakter ve bom sorunsuzluğu için:
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_level()) ob_end_clean();
if (headers_sent()) die("Önceden çıktı var!");

echo "\xEF\xBB\xBF"; // UTF-8 BOM

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="urunler.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// --- API anahtarlarını oku
$api_json = file_get_contents(__DIR__ . '/ayarlar/wc_api.json');
$api_data = json_decode($api_json, true);

$consumer_key = $api_data['consumer_key'];
$consumer_secret = $api_data['consumer_secret'];
$site_url = rtrim($api_data['site_url'], '/');

// --- API bağlantısı
$api_url = $site_url . "/wp-json/wc/v3/products?per_page=100";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $consumer_key . ":" . $consumer_secret);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
$response = curl_exec($ch);
curl_close($ch);

// --- CSV oluştur
$products = json_decode($response, true);
$output = fopen('php://output', 'w');
fputcsv($output, ['isim', 'link', 'gorsel']);

foreach ($products as $product) {
    if ($product['status'] !== 'publish') continue;
    $isim = $product['name'];
    $link = $product['permalink'];
    $gorsel = isset($product['images'][0]['src']) ? $product['images'][0]['src'] : '';
    fputcsv($output, [$isim, $link, $gorsel]);
}
fclose($output);
exit;
