<?php
require_once __DIR__ . '/../../moduller/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// WooCommerce alanları
$woo_fields = [
    'name' => 'Ürün Adı',
    'sku' => 'Stok Kodu',
    'price' => 'Fiyat',
    'description' => 'Açıklama',
    'stock_quantity' => 'Stok Adedi',
    'categories' => 'Kategori',
    'brand' => 'Marka (meta)',
    'images' => 'Görseller'
];

// Local veritabanı alanları
$local_fields = [];
$stmt = db()->query("PRAGMA table_info(urunler)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    $local_fields[] = $col['name'];
}
?>

<h3>🛠 WooCommerce Alan Eşleştirme</h3>
<form method="post" action="">
    <table class="table table-bordered" style="max-width:700px;">
        <thead class="table-light">
            <tr>
                <th>WooCommerce Alanı</th>
                <th>Eşlenecek Local Alan</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($woo_fields as $woo_key => $woo_label): ?>
            <tr>
                <td><strong><?= htmlspecialchars($woo_label) ?></strong> <small class="text-muted">(<?= $woo_key ?>)</small></td>
                <td>
                    <select name="mapping[<?= $woo_key ?>]" class="form-select">
                        <option value="">-- Seçiniz --</option>
                        <?php foreach ($local_fields as $field): ?>
                            <option value="<?= $field ?>"><?= $field ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <button type="submit" class="btn btn-success">🚀 5 Ürünü Gönder</button>
</form>

<?php
// Post işlemi sonrası
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mapping'])):
    $mapping = $_POST['mapping'];

    echo "<h4 class='mt-4'>📋 Eşlemeler</h4><ul>";
    foreach ($mapping as $woo_key => $local_field) {
        if ($local_field) {
            echo "<li><strong>$woo_key</strong> ⇨ $local_field</li>";
        }
    }
    echo "</ul>";

    // WooCommerce API bilgilerini yükle
    $api_json = file_get_contents(__DIR__ . '/../../ayarlar/wc_api.json');
    $api = json_decode($api_json, true);
    if (!$api) {
        echo "<div class='alert alert-danger'>API bilgileri okunamadı.</div>";
        return;
    }

    // SKU kontrol fonksiyonu
    function urun_var_mi_woo($sku, $api) {
        $url = $api['site_url'] . "/wp-json/wc/v3/products?sku=" . urlencode($sku);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $api['consumer_key'] . ':' . $api['consumer_secret']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $products = json_decode($response, true);
        return is_array($products) && count($products) > 0;
    }

    // İlk 5 ürünü çek
    $sku_alan = $mapping['sku'] ?? null;
    if (!$sku_alan) {
        echo "<div class='alert alert-warning'>⚠️ SKU alanı eşleştirilmediği için kontrol yapılamaz.</div>";
    } else {
        $sql = "SELECT * FROM urunler WHERE $sku_alan IS NOT NULL AND $sku_alan != '' LIMIT 5";
        $stmt = db()->prepare($sql);
        $stmt->execute();
        $urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h5 class='mt-4'>🔍 WooCommerce'de SKU Kontrolü</h5><ul>";
        foreach ($urunler as $urun) {
            $sku = $urun[$sku_alan];
            if (urun_var_mi_woo($sku, $api)) {
                echo "<li>❗ <b>$sku</b> zaten WooCommerce'de var. <span style='color:#c00;'>Güncellenebilir</span></li>";
            } else {
                echo "<li>✅ <b>$sku</b> yok, eklenebilir.</li>";
            }
        }
        echo "</ul>";



echo "<h5 class='mt-4'>🚀 WooCommerce'e Ürün Ekleme</h5><ul>";

foreach ($urunler as $urun) {
    $sku = $urun[$sku_alan];

    if (urun_var_mi_woo($sku, $api)) {
        // Zaten varsa, eklemiyoruz
        continue;
    }

    // WooCommerce'e gönderilecek veri hazırlanıyor
    $data = [];

    foreach ($mapping as $woo_key => $local_field) {
        if (!$local_field || !isset($urun[$local_field])) continue;

        $value = $urun[$local_field];

        // Özel işlem gereken alanlar
        if ($woo_key === 'categories') {
            // Kategori ismini ID'ye çevirmek yerine şimdilik isimle ekleyelim
            $data['categories'][] = ['name' => $value];
        } elseif ($woo_key === 'images') {
            // Virgülle ayrılmış URL'leri diziye çevir
            $urls = explode(',', $value);
            foreach ($urls as $url) {
                $data['images'][] = ['src' => trim($url)];
            }
        } elseif ($woo_key === 'brand') {
            // Marka meta alan olarak eklenecek
            $data['meta_data'][] = [
                'key' => 'brand',
                'value' => $value
            ];
        } else {
            $data[$woo_key] = $value;
        }
    }

    // API isteği
    $ch = curl_init($api['site_url'] . "/wp-json/wc/v3/products");
    curl_setopt($ch, CURLOPT_USERPWD, $api['consumer_key'] . ':' . $api['consumer_secret']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode >= 200 && $httpcode < 300) {
        echo "<li>✅ <b>{$sku}</b> başarıyla eklendi.</li>";
    } else {
        echo "<li>❌ <b>{$sku}</b> eklenemedi. <code>$httpcode</code></li>";
    }
}

echo "</ul>";




    }

endif;
?>
