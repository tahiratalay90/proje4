<?php
if (ob_get_level()) ob_end_clean();
if (headers_sent()) die("Çıktı başladı!");

date_default_timezone_set('Europe/Istanbul'); // Zaman dilimi

$baslangic = strtotime('+1 hour'); // Şu andan 1 saat sonrası
$interval = 60 * 60; // Her post arası 1 saat (3600 saniye)

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="publer-urunler-tarihli.csv"');
header('Pragma: no-cache');
header('Expires: 0');
echo "\xEF\xBB\xBF"; // UTF-8 BOM

$api_json = file_get_contents(__DIR__ . '/../ayarlar/wc_api.json');
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

fputcsv($output, [
    "Date - Intl. format or prompt",
    "Text",
    "Link(s) - Separated by comma for FB carousels",
    "Media URL(s) - Separated by comma",
    "Title - For the video, pin, PDF ..",
    "Label(s) - Separated by comma",
    "Alt text(s) - Separated by ||",
    "Comment(s) - Separated by ||",
    "Pin board, FB album, or Google category",
    "Post subtype - I.e. story, reel, PDF ..",
    "CTA - For Facebook links or Google",
    "Reminder - For stories, reels, shorts, and TikToks"
]);

$i = 0;
foreach ($products as $product) {
    $tarih = date('Y-m-d H:i', $baslangic + $i * $interval); // Sıralı tarih-saat
    $isim = $product['name'];
    $link = $product['permalink'];
    $gorsel = isset($product['images'][0]['src']) ? $product['images'][0]['src'] : '';
    fputcsv($output, [
        $tarih,
        $isim,
        $link,
        $gorsel,
        "", "", "", "", "", "", ""
    ]);
    $i++;
}
fclose($output);
exit;
