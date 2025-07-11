<?php
// Herhangi bir çıktı başladı mı kontrol et!
if (headers_sent($file, $line)) {
    die("Header zaten gönderildi! $file satır: $line");
}
ob_clean();
error_reporting(0);
ini_set('display_errors', 0);


error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_level()) ob_end_clean();
if (headers_sent()) die("Önceden çıktı oluşmuş!");

echo "\xEF\xBB\xBF";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="publer-urunler.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$api_json = file_get_contents(__DIR__ . '/ayarlar/wc_api.json');
$api_data = json_decode($api_json, true);

$consumer_key = $api_data['consumer_key'];
$consumer_secret = $api_data['consumer_secret'];
$site_url = rtrim($api_data['site_url'], '/');

$api_url = $site_url . "/wp-json/wc/v3/products?per_page=100";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $consumer_key . ":" . $consumer_secret);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
$response = curl_exec($ch);
curl_close($ch);

$products = json_decode($response, true);
$output = fopen('php://output', 'w');
fputcsv($output, ['Message', 'Link', 'Image']);

foreach ($products as $product) {
    if (empty($product['name']) || empty($product['permalink']) || $product['status'] !== 'publish') continue;
    $name = $product['name'];
    $link = $product['permalink'];
    $image = isset($product['images'][0]['src']) ? $product['images'][0]['src'] : '';
    $message = "Yeni Ürün: $name";
    fputcsv($output, [$message, $link, $image]);
}
fclose($output);
exit;
