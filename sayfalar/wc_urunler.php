<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');
ini_set('display_errors', 1); error_reporting(E_ALL);

// === 1. Ayarları oku ===
$api = json_decode(file_get_contents(__DIR__ . '/../ayarlar/wc_api.json'), true);
$site_url = rtrim($api['site_url'], '/');
$consumer_key = $api['consumer_key'];
$consumer_secret = $api['consumer_secret'];

// === 2. Ürünleri tüm sayfalardan çek ===
$urunler = [];
$page = 1;

while (true) {
    $url = $site_url . "/wp-json/wc/v3/products?per_page=100&page=$page";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, $consumer_key . ':' . $consumer_secret);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (!is_array($data) || count($data) === 0) break;

    $urunler = array_merge($urunler, $data);
    $page++;
}

$toplam = count($urunler);
?>

<h3>WooCommerce Tüm Ürünler <small class="text-muted">(<?= $toplam ?> adet)</small></h3>

<table class="table table-bordered table-sm bg-white">
    <thead class="table-success">
        <tr>
            <th>#</th>
            <th>ID</th>
            <th>Adı</th>
            <th>SKU</th>
            <th>Kategori</th>
            <th>Fiyat</th>
            <th>Stok</th>
            <th>Resim</th>
            <th>Açıklama</th>
        </tr>
    </thead>
    <tbody>
    <?php $i = 0; foreach ($urunler as $urun): ?>
        <tr>
            <td><?= ++$i ?></td>
            <td><?= $urun['id'] ?></td>
            <td><?= htmlspecialchars($urun['name']) ?></td>
            <td><?= $urun['sku'] ?? '-' ?></td>
            <td>
                <?php
                    $kategoriler = array_map(fn($k) => $k['name'], $urun['categories']);
                    echo implode(', ', $kategoriler);
                ?>
            </td>
            <td><?= $urun['price'] ?? '-' ?></td>
            <td><?= $urun['stock_quantity'] ?? '-' ?></td>
            <td>
                <?php if (!empty($urun['images'][0]['src'])): ?>
                    <img src="<?= $urun['images'][0]['src'] ?>" width="40">
                <?php endif; ?>
            </td>
            <td><?= mb_strimwidth(strip_tags($urun['short_description']), 0, 100, "...") ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
