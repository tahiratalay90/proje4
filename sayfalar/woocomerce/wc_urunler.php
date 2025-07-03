<?php
ini_set('display_errors', 1); error_reporting(E_ALL);

// API Bilgilerini oku
$api_json = file_get_contents(ROOT . '/ayarlar/wc_api.json');
$api = json_decode($api_json, true);

if (!$api || !isset($api['site_url'], $api['consumer_key'], $api['consumer_secret'])) {
    echo "<div style='color:red'>API bilgileri eksik veya hatalı! Lütfen <b>ayarlar/wc_api.json</b> dosyasını kontrol et.</div>";
    exit;
}

$per_page_options = [10, 25, 50, 100];
$per_page = isset($_GET['per_page']) && in_array(intval($_GET['per_page']), $per_page_options) ? intval($_GET['per_page']) : 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Filtreler
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$category   = isset($_GET['category']) ? trim($_GET['category']) : '';
$brand      = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$sku        = isset($_GET['sku']) ? trim($_GET['sku']) : '';
$min_price  = isset($_GET['min_price']) ? trim($_GET['min_price']) : '';
$max_price  = isset($_GET['max_price']) ? trim($_GET['max_price']) : '';
$stock_status = isset($_GET['stock_status']) ? trim($_GET['stock_status']) : '';

// Kategori listesini API'dan çek
$cat_endpoint = rtrim($api['site_url'], '/') . "/wp-json/wc/v3/products/categories?per_page=100&orderby=name&order=asc";
$ch_cat = curl_init();
curl_setopt($ch_cat, CURLOPT_URL, $cat_endpoint);
curl_setopt($ch_cat, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_cat, CURLOPT_USERPWD, $api['consumer_key'] . ':' . $api['consumer_secret']);
$cat_response = curl_exec($ch_cat);
curl_close($ch_cat);
$categories = json_decode($cat_response, true);

// Marka listesini çek (brand attribute slug’ı: “brand” – gerekirse değiştir)
$brand_endpoint = rtrim($api['site_url'], '/') . "/wp-json/wc/v3/products/attributes";
$ch_brand = curl_init();
curl_setopt($ch_brand, CURLOPT_URL, $brand_endpoint);
curl_setopt($ch_brand, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_brand, CURLOPT_USERPWD, $api['consumer_key'] . ':' . $api['consumer_secret']);
$brand_response = curl_exec($ch_brand);
curl_close($ch_brand);
$attributes = json_decode($brand_response, true);

$brand_tax_id = null;
if ($attributes) {
    foreach ($attributes as $attr) {
        if (in_array(strtolower($attr['slug']), ['brand', 'marka'])) {
            $brand_tax_id = $attr['id'];
            break;
        }
    }
}

$brand_terms = [];
if ($brand_tax_id) {
    $brand_terms_endpoint = rtrim($api['site_url'], '/') . "/wp-json/wc/v3/products/attributes/$brand_tax_id/terms?per_page=100";
    $ch_bt = curl_init();
    curl_setopt($ch_bt, CURLOPT_URL, $brand_terms_endpoint);
    curl_setopt($ch_bt, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_bt, CURLOPT_USERPWD, $api['consumer_key'] . ':' . $api['consumer_secret']);
    $bt_response = curl_exec($ch_bt);
    curl_close($ch_bt);
    $brand_terms = json_decode($bt_response, true);
}

// API parametreleri oluştur
$params = [
    'per_page' => $per_page,
    'page' => $page
];
if ($search != '') $params['search'] = $search;
if ($category != '') $params['category'] = $category;
if ($brand != '') $params['attribute'] = $brand_tax_id;
if ($brand != '') $params['attribute_term'] = $brand;
if ($sku != '') $params['sku'] = $sku;
if ($min_price != '') $params['min_price'] = $min_price;
if ($max_price != '') $params['max_price'] = $max_price;
if ($stock_status != '') $params['stock_status'] = $stock_status;

$endpoint = rtrim($api['site_url'], '/') . "/wp-json/wc/v3/products?" . http_build_query($params);

// Ürünleri çek
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api['consumer_key'] . ':' . $api['consumer_secret']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    echo "<div style='color:red'>WooCommerce API isteği başarısız! (HTTP $http_code)</div>";
    exit;
}
$products = json_decode($response, true);

if (!is_array($products)) {
    echo "<div style='color:red'>API'den düzgün veri alınamadı!</div>";
    exit;
}
?>

<!-- Detaylı Filtre Formu -->
<div style="margin-bottom:15px;">
    <form method="get" class="form-inline" style="display: flex; gap: 7px; flex-wrap: wrap;">
        <input type="hidden" name="sayfa" value="woocomerce/wc_urunler">
        <!-- Arama Kutusu -->
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Ad, açıklama, SKU..." class="form-control form-control-sm" style="min-width:120px;">

        <!-- SKU -->
        <input type="text" name="sku" value="<?= htmlspecialchars($sku) ?>" placeholder="SKU" class="form-control form-control-sm" style="min-width:90px;">

        <!-- Kategori Select -->
        <select name="category" class="form-select form-select-sm" style="min-width:130px;">
            <option value="">Tüm Kategoriler</option>
            <?php if (is_array($categories)) foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Marka Select -->
        <?php if (!empty($brand_terms)): ?>
        <select name="brand" class="form-select form-select-sm" style="min-width:110px;">
            <option value="">Tüm Markalar</option>
            <?php foreach ($brand_terms as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $brand == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <!-- Fiyat aralığı -->
        <input type="number" step="any" name="min_price" value="<?= htmlspecialchars($min_price) ?>" placeholder="Min ₺" class="form-control form-control-sm" style="width:80px;">
        <input type="number" step="any" name="max_price" value="<?= htmlspecialchars($max_price) ?>" placeholder="Max ₺" class="form-control form-control-sm" style="width:80px;">

        <!-- Stok durumu -->
        <select name="stock_status" class="form-select form-select-sm" style="min-width:90px;">
            <option value="">Stok Durumu</option>
            <option value="instock" <?= $stock_status === 'instock' ? 'selected' : '' ?>>Stokta Var</option>
            <option value="outofstock" <?= $stock_status === 'outofstock' ? 'selected' : '' ?>>Tükendi</option>
            <option value="onbackorder" <?= $stock_status === 'onbackorder' ? 'selected' : '' ?>>Ön Sipariş</option>
        </select>

        <!-- Kayıt sayısı -->
        <select name="per_page" class="form-select form-select-sm" style="width:72px;">
            <?php foreach ($per_page_options as $option): ?>
                <option value="<?= $option ?>" <?= $per_page == $option ? 'selected' : '' ?>><?= $option ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-sm btn-primary">Filtrele</button>
        <a href="?sayfa=woocomerce/wc_urunler" class="btn btn-sm btn-secondary">Tümü</a>
    </form>
</div>

<h3>WooCommerce Ürün Listesi</h3>
<table class='table table-bordered table-sm'><thead><tr>
<th>#</th><th>ID</th><th>İsim</th><th>SKU</th><th>Kategori</th><th>Marka</th><th>Fiyat</th><th>Stok</th>
</tr></thead><tbody>
<?php foreach ($products as $i => $u): 
    $kategori = isset($u['categories'][0]['name']) ? $u['categories'][0]['name'] : '';
    // Marka bilgisini al
    $brand_name = '';
    if (!empty($u['attributes'])) {
        foreach ($u['attributes'] as $att) {
            if (in_array(strtolower($att['name']), ['brand', 'marka'])) {
                $brand_name = is_array($att['options']) ? implode(', ', $att['options']) : $att['options'];
                break;
            }
        }
    }
    $stock = isset($u['stock_status']) ? $u['stock_status'] : '';
    if ($stock == 'instock') $stock = 'Var';
    elseif ($stock == 'outofstock') $stock = 'Tükendi';
    elseif ($stock == 'onbackorder') $stock = 'Ön Sipariş';
?>
<tr>
    <td><?= (($page - 1) * $per_page + $i + 1) ?></td>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['name']) ?></td>
    <td><?= htmlspecialchars($u['sku']) ?></td>
    <td><?= htmlspecialchars($kategori) ?></td>
    <td><?= htmlspecialchars($brand_name) ?></td>
    <td><?= $u['price'] ?></td>
    <td><?= $stock ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>

<!-- Sayfalama -->
<div class="d-flex justify-content-between align-items-center mt-3">
    <?php
    $base_url = "?sayfa=woocomerce/wc_urunler&per_page=$per_page&search=" . urlencode($search) . "&category=" . urlencode($category) . "&brand=" . urlencode($brand) . "&min_price=" . urlencode($min_price) . "&max_price=" . urlencode($max_price) . "&stock_status=" . urlencode($stock_status) . "&sku=" . urlencode($sku);
    ?>
    <?php if ($page > 1): ?>
        <a href="<?= $base_url ?>&page=<?= $page - 1 ?>" class="btn btn-sm btn-outline-info">← Önceki</a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>

    <span>Sayfa <?= $page ?></span>

    <?php if (count($products) == $per_page): ?>
        <a href="<?= $base_url ?>&page=<?= $page + 1 ?>" class="btn btn-sm btn-outline-success">Sonraki →</a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>
</div>
