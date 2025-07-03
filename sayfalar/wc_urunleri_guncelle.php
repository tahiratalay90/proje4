<?php
set_time_limit(0); // Süre limiti kaldırıldı
ini_set('memory_limit', '1024M'); // RAM artırıldı
ini_set('display_errors', 1); error_reporting(E_ALL);

// 1. WooCommerce API ayarlarını oku
$api = json_decode(file_get_contents(__DIR__ . '/../ayarlar/wc_api.json'), true);
$site_url = rtrim($api['site_url'], '/');
$consumer_key = $api['consumer_key'];
$consumer_secret = $api['consumer_secret'];

// 2. Sayfa sayfa ürünleri çek ve işle
$page = 1;
$toplam_guncellenen = 0;
$toplam_atlanan = 0;

while (true) {
    $url = "$site_url/wp-json/wc/v3/products?per_page=100&page=$page";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, "$consumer_key:$consumer_secret");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $urunler = json_decode($response, true);
    if (!is_array($urunler) || count($urunler) == 0) break;

    foreach ($urunler as $urun) {
        $id = $urun['id'];
        $sku = $urun['sku'] ?? '';

        if (str_starts_with($sku, 'HT_')) {
            $yeni_sku = substr($sku, 3);

            // Güncelleme isteği
            $update_url = "$site_url/wp-json/wc/v3/products/$id";
            $payload = json_encode(['sku' => $yeni_sku]);

            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $update_url);
            curl_setopt($ch2, CURLOPT_USERPWD, "$consumer_key:$consumer_secret");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $payload);
            $update_response = curl_exec($ch2);
            curl_close($ch2);

            $response_data = json_decode($update_response, true);

            if (isset($response_data['id'])) {
                echo "✅ SKU güncellendi: <strong>$sku → $yeni_sku</strong><br>";
                $toplam_guncellenen++;
            } elseif (isset($response_data['message'])) {
                echo "❌ HATA: {$response_data['message']} ($sku)<br>";
            } else {
                echo "❓ Beklenmeyen durum: $sku<br>";
            }
        } else {
            echo "⏭️ Atlandı (HT_ yok): $sku<br>";
            $toplam_atlanan++;
        }

        // Tarayıcıyı yormamak için ara çizgi
        if (($toplam_guncellenen + $toplam_atlanan) % 50 === 0) {
            echo "<hr>";
            @ob_flush(); flush(); // anlık çıktıyı göster
        }
    }

    $page++;
}

// Özet bilgi
echo "<hr>";
echo "<strong>✅ Toplam güncellenen SKU:</strong> $toplam_guncellenen<br>";
echo "<strong>⏭️ HT_ olmayan ve atlanan:</strong> $toplam_atlanan<br>";
