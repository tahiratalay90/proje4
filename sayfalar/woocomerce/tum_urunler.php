<?php
ini_set('display_errors', 1); error_reporting(E_ALL);

$api_json = file_get_contents(ROOT . '/ayarlar/wc_api.json');
$api = json_decode($api_json, true);
if (!$api || !isset($api['site_url'], $api['consumer_key'], $api['consumer_secret'])) {
    echo "<div style='color:red'>API bilgileri eksik!</div>"; exit;
}

$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Filtreler
$search = $_GET['search'] ?? '';
$sku = $_GET['sku'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';

// Kategori çek
$cat_url = rtrim($api['site_url'], '/') . '/wp-json/wc/v3/products/categories?per_page=100';
$ch1 = curl_init($cat_url);
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $api['consumer_key'] . ':' . $api['consumer_secret']
]);
$categories = json_decode(curl_exec($ch1), true);
curl_close($ch1);

// Marka çek
$brand_attr_url = rtrim($api['site_url'], '/') . '/wp-json/wc/v3/products/attributes';
$ch2 = curl_init($brand_attr_url);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $api['consumer_key'] . ':' . $api['consumer_secret']
]);
$attributes = json_decode(curl_exec($ch2), true);
curl_close($ch2);

$brand_tax_id = null;
foreach ($attributes as $attr) {
    if (in_array(strtolower($attr['slug']), ['brand', 'marka'])) {
        $brand_tax_id = $attr['id'];
        break;
    }
}

$brand_terms = [];
if ($brand_tax_id) {
    $bt_url = rtrim($api['site_url'], '/') . "/wp-json/wc/v3/products/attributes/{$brand_tax_id}/terms?per_page=100";
    $ch3 = curl_init($bt_url);
    curl_setopt_array($ch3, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $api['consumer_key'] . ':' . $api['consumer_secret']
    ]);
    $brand_terms = json_decode(curl_exec($ch3), true);
    curl_close($ch3);
}

// Ürünleri çek
$params = ['per_page' => $per_page, 'page' => $page];
if ($search) $params['search'] = $search;
if ($sku) $params['sku'] = $sku;
if ($min_price) $params['min_price'] = $min_price;
if ($max_price) $params['max_price'] = $max_price;
if ($stock_status) $params['stock_status'] = $stock_status;
if ($category) $params['category'] = $category;
if ($brand_tax_id && $brand) {
    $params['attribute'] = $brand_tax_id;
    $params['attribute_term'] = $brand;
}

$endpoint = rtrim($api['site_url'], '/') . '/wp-json/wc/v3/products?' . http_build_query($params);
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $api['consumer_key'] . ':' . $api['consumer_secret'],
    CURLOPT_HEADER => true
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

$total_urun = 0;
if (preg_match('/X-WP-Total:\s*(\d+)/i', $header, $m)) $total_urun = (int)$m[1];

$products = json_decode($body, true);
if ($http_code != 200 || !is_array($products)) {
    echo "<div style='color:red'>WooCommerce API Hatası (HTTP $http_code)</div>"; exit;
}
?>

<h4>Woo Ürün Listesi (Toplam: <?= $total_urun ?>)</h4>

<form method="get" class="mb-3" style="display:flex; flex-wrap:wrap; gap:8px;">
    <input type="hidden" name="sayfa" value="woocomerce/tum_urunler">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Ara..." class="form-control form-control-sm">
    <input type="text" name="sku" value="<?= htmlspecialchars($sku) ?>" placeholder="SKU" class="form-control form-control-sm">
    <input type="number" step="any" name="min_price" value="<?= htmlspecialchars($min_price) ?>" placeholder="Min ₺" class="form-control form-control-sm">
    <input type="number" step="any" name="max_price" value="<?= htmlspecialchars($max_price) ?>" placeholder="Max ₺" class="form-control form-control-sm">

    <select name="category" class="form-select form-select-sm">
        <option value="">Kategori</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $category == $c['id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
        <?php endforeach; ?>
    </select>

    <?php if (!empty($brand_terms)): ?>
    <select name="brand" class="form-select form-select-sm">
        <option value="">Marka</option>
        <?php foreach ($brand_terms as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $brand == $b['id'] ? 'selected' : '' ?>><?= $b['name'] ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <select name="stock_status" class="form-select form-select-sm">
        <option value="">Stok Durumu</option>
        <option value="instock" <?= $stock_status == 'instock' ? 'selected' : '' ?>>Stokta Var</option>
        <option value="outofstock" <?= $stock_status == 'outofstock' ? 'selected' : '' ?>>Tükendi</option>
        <option value="onbackorder" <?= $stock_status == 'onbackorder' ? 'selected' : '' ?>>Ön Sipariş</option>
    </select>

    <select name="per_page" class="form-select form-select-sm">
        <?php foreach ([10,25,50,100] as $opt): ?>
            <option value="<?= $opt ?>" <?= $per_page == $opt ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
    </select>

    <button class="btn btn-sm btn-primary">Filtrele</button>
</form>

<table class="table table-sm table-bordered">
    <thead><tr><th>#</th><th>Ad</th><th>SKU</th><th>Fiyat</th><th>Stok</th></tr></thead>
    <tbody>
        <?php foreach ($products as $i => $p): ?>
        <tr>
            <td><?= (($page - 1) * $per_page + $i + 1) ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= $p['sku'] ?></td>
            <td><?= $p['price'] ?></td>
            <td><?= $p['stock_status'] == 'instock' ? 'Var' : 'Yok' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
