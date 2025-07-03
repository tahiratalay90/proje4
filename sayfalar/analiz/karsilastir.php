<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../moduller/db.php';
include __DIR__ . '/../../moduller/filtre.php';

$tablo = 'urunler';

// Denetle durumu GET parametresinden alınır
$denetle = isset($_GET['denetle']) && $_GET['denetle'] == '1';

// Sıralama parametresi
$sort_wc = $_GET['sort_wc'] ?? '';

// Local ürünleri filtreli çek
$sql = "SELECT * FROM $tablo $filtre_where ORDER BY id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($filtre_veri);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
$toplam = count($urunler);

// WooCommerce ürünleri dizisi (sku => true)
$wc_urunler = [];

if ($denetle) {
    $api_json = file_get_contents(__DIR__ . '/../../ayarlar/wc_api.json');
    $api = json_decode($api_json, true);

    if (!$api || !isset($api['site_url'], $api['consumer_key'], $api['consumer_secret'])) {
        die("API bilgileri eksik veya geçersiz.");
    }

    $wc_api_url = rtrim($api['site_url'], '/') . '/wp-json/wc/v3/products';
    $consumer_key = $api['consumer_key'];
    $consumer_secret = $api['consumer_secret'];

    // WooCommerce ürünlerini sayfa sayfa çek
    function wc_get_products($url, $key, $secret) {
        $urunler = [];
        $page = 1;
        do {
            $ch = curl_init("$url?per_page=100&page=$page");
            curl_setopt($ch, CURLOPT_USERPWD, $key . ':' . $secret);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($res, true);
            if (!empty($data) && is_array($data)) {
                foreach ($data as $urun) {
                    if (!empty($urun['sku'])) {
                        $urunler[$urun['sku']] = true;
                    }
                }
            }
            $page++;
        } while (count($data) == 100);
        return $urunler;
    }

    $wc_urunler = wc_get_products($wc_api_url, $consumer_key, $consumer_secret);
}

// Sıralama (yalnızca denetle sonrası çalışacak)
if ($denetle && ($sort_wc === 'asc' || $sort_wc === 'desc')) {
    usort($urunler, function($a, $b) use ($wc_urunler, $sort_wc) {
        $a_var = isset($wc_urunler[$a['stok_kodu']]) ? 1 : 0;
        $b_var = isset($wc_urunler[$b['stok_kodu']]) ? 1 : 0;
        return $sort_wc === 'asc' ? ($b_var <=> $a_var) : ($a_var <=> $b_var);
    });
}

// Sort link hazırlama, denetle parametresini koruyoruz
$new_sort = ($sort_wc === 'asc') ? 'desc' : 'asc';
$query_params = $_GET;
$query_params['sort_wc'] = $new_sort;
$sort_link = $_SERVER['PHP_SELF'] . '?' . http_build_query($query_params);
?>

<h3>Ürün Listesi <small class="text-muted">(<?= $toplam ?> adet)</small></h3>

<form method="get" style="margin-bottom:15px;">
    <?php
    // Mevcut GET parametrelerini koru, denetle parametresini ekle
    foreach ($_GET as $key => $value) {
        if ($key !== 'denetle') {
            echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'">';
        }
    }
    ?>
    <input type="hidden" name="denetle" value="1">
    <button type="submit" class="btn btn-primary">Denetle</button>
</form>

<table class="table table-bordered table-sm bg-white">
    <thead class="table-success">
        <tr>
            <th>#</th>
            <?php foreach($alanlar as $alan): ?>
                <th><?= htmlspecialchars(str_replace('_', ' ', ucfirst($alan))) ?></th>
            <?php endforeach; ?>
            <th>
                <a href="<?= htmlspecialchars($sort_link) ?>">
                    WooCommerce <?= $sort_wc === 'asc' ? '↑' : ($sort_wc === 'desc' ? '↓' : '') ?>
                </a>
            </th>
        </tr>
    </thead>
    <tbody>
    <?php if ($toplam > 0): 
        $i = 0;
        foreach ($urunler as $row): 
            $sku = $row['stok_kodu'] ?? '';
            $wc_var_mi = $denetle && isset($wc_urunler[$sku]);
            ?>
            <tr>
                <td><?= ++$i ?></td>
                <?php foreach ($alanlar as $alan): ?>
                    <td><?= htmlspecialchars($row[$alan]) ?></td>
                <?php endforeach; ?>
                <td style="text-align:center; font-weight:bold; font-size:18px;">
                    <?= $denetle ? ($wc_var_mi ? '&#10004;' : '&#10006;') : '' ?>
                </td>
            </tr>
        <?php endforeach; 
    else: ?>
        <tr><td colspan="<?= count($alanlar) + 2 ?>"><em>Henüz ürün yok.</em></td></tr>
    <?php endif; ?>
    </tbody>
</table>
