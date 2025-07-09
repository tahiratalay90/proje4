<?php
set_time_limit(0);
require_once __DIR__ . '/../../moduller/db.php';

// --- MODÜLER FİLTRE KULLANIMI ---
$filtre_ayar_kodu = 'urun_listele'; // ya da ilgili sayfanın kodu
include __DIR__ . '/../../moduller/filtre.php'; // $filtre_where, $filtre_veri, $alanlar oluşur

// --- WOO API ---
$woo_api = json_decode(file_get_contents(__DIR__ . '/../../ayarlar/wc_api.json'), true);

// --- TEMİZLEYİCİ FUNK ---
function temiz_sku($sku) {
    $sku = strtolower($sku);                        // Küçük harfe çevir
    $sku = trim($sku);                              // Kenar boşluklarını sil
    $sku = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $sku); // Gizli karakterleri sil
    $sku = str_replace([" ", "\t", "\n", "\r", "\0", "\x0B"], '', $sku); // Boşluk benzerlerini sil
    return $sku;
}

// --- WOO'DAKİ TÜM SKU'LARI AL ---
function woo_tum_sku_dizisi($api) {
    $sku_set = [];
    $page = 1;
    do {
        $url = $api['site_url'] . "/wp-json/wc/v3/products?per_page=100&page=$page";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $api['consumer_key'] . ':' . $api['consumer_secret']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        if (!empty($data)) {
            foreach ($data as $w) {
                if (!empty($w['sku'])) {
                    $temiz = temiz_sku($w['sku']);
                    $sku_set[$temiz] = true;
                }
            }
            $page++;
        } else {
            break;
        }
    } while (count($data) === 100);
    return $sku_set;
}

// --- VERİTABANI ÜRÜNLERİ ---
$tablo = 'urunler';
$sql = "SELECT * FROM $tablo $filtre_where ORDER BY id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($filtre_veri);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);
$toplam = count($urunler);

// --- KAYDETME ---
if (isset($_POST['kaydet_sonuclar']) && isset($_POST['kontroller'])) {
    $gelenler = json_decode($_POST['kontroller'], true);
    foreach ($gelenler as $id => $varmi) {
        $deger = ($varmi == 1) ? 'var' : 'yok';
        $stmt = db()->prepare("UPDATE $tablo SET wooda_varmi = :v WHERE id = :id");
        $stmt->execute([':v' => $deger, ':id' => $id]);
    }
    echo "<div class='alert alert-success'>Başarıyla kaydedildi!</div>";
}

// --- DENETLEME İŞLEMİ ---
$kontroller = [];
if (isset($_POST['denetle'])) {
    $woo_sku_set = woo_tum_sku_dizisi($woo_api);
    foreach ($urunler as $row) {
        $sku = temiz_sku($row['stok_kodu'] ?? '');
        $kontroller[$row['id']] = isset($woo_sku_set[$sku]) ? 1 : 0;

        // Hata ayıklama için log (isteğe bağlı)
        /*
        if (!isset($woo_sku_set[$sku])) {
            error_log("Bulunamadı: SKU = [$sku] | ID = {$row['id']}");
        }
        */
    }
}
?>

<!-- FİLTRE FORMU -->
<h3>Ürün Listesi <small class="text-muted">(<?= $toplam ?> adet)</small></h3>

<form method="post">
    <button type="submit" name="denetle" class="btn btn-warning mb-2">Denetle</button>
    <?php foreach($_GET as $k => $v): ?>
        <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
    <?php endforeach; ?>
</form>

<?php if (!empty($kontroller)): ?>
    <form method="post">
        <input type="hidden" name="kaydet_sonuclar" value="1">
        <input type="hidden" name="kontroller" value='<?= json_encode($kontroller) ?>'>
        <?php foreach($_GET as $k => $v): ?>
            <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endforeach; ?>
        <button type="submit" class="btn btn-success mb-3">Tümünü Kaydet</button>
    </form>
<?php endif; ?>

<table class="table table-bordered table-sm bg-white">
    <thead class="table-success">
        <tr>
            <th>#</th>
            <?php foreach($alanlar as $alan): ?>
                <th><?= htmlspecialchars(str_replace('_', ' ', ucfirst($alan))) ?></th>
            <?php endforeach; ?>
            <th>Denetim Sonucu</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if ($toplam > 0) {
        $i = 0;
        foreach($urunler as $row) {
            echo "<tr><td>".(++$i)."</td>";
            foreach($alanlar as $alan) {
                echo "<td>" . htmlspecialchars($row[$alan]) . "</td>";
            }
            echo "<td class='text-center'>";
            echo isset($kontroller[$row['id']]) ? $kontroller[$row['id']] : "-";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='".(count($alanlar)+2)."'><em>Henüz ürün yok.</em></td></tr>";
    }
    ?>
    </tbody>
</table>
<?php

if (isset($_POST['denetle'])) {
    $woo_sku_set = woo_tum_sku_dizisi($woo_api);
    foreach ($urunler as $row) {
        $orj_sku = $row['stok_kodu'] ?? '';
        $sku = temiz_sku($orj_sku);
        $kontroller[$row['id']] = isset($woo_sku_set[$sku]) ? 1 : 0;

        if (!isset($woo_sku_set[$sku])) {
            // Gerçek farkı göster
            foreach ($woo_sku_set as $ws => $v) {
                if (levenshtein($sku, $ws) <= 2) { // neredeyse eşit olanları yazdır
                    error_log("❌ BULUNAMADI: DB SKU = [$sku] | ORJ = [$orj_sku] | Woo Benzeri = [$ws]");
                }
            }
        }
    }
}
