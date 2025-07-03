<?php
// filtre.php

if (!isset($tablo)) $tablo = 'urunler';

// Buraya select (dropdown) olacak alanların isimlerini yaz
$select_alanlar = ['kategori', 'marka', 'toptanci','urun_tipi']; // Örnek alanlar, kendi tablonun alanlarını yazabilirsin

if (!isset($filtre_ayar_kodu)) {
    $sayfa_adi = basename($_SERVER['SCRIPT_NAME'], '.php'); // örnek: guncelle, urunler
    $filtre_ayar_kodu = $sayfa_adi;
}

$ayarlar_dosyasi = __DIR__.'/../assets/ayarlar/' . $filtre_ayar_kodu . '.json';





if (!is_dir(__DIR__.'/../assets/ayarlar')) {
    mkdir(__DIR__.'/../assets/ayarlar', 0777, true);
}

$alanlar_tumu = [];
$sorgu2 = db()->query("PRAGMA table_info($tablo)");
foreach($sorgu2 as $satir) {
    $alanlar_tumu[] = $satir['name'];
}

$ayarlar_json = @file_get_contents($ayarlar_dosyasi);
$ayarlar = $ayarlar_json ? json_decode($ayarlar_json, true) : [];
$gorunen_alanlar = $ayarlar['gorunen_alanlar'] ?? $alanlar_tumu;
$gizli_alanlar = array_diff($alanlar_tumu, $gorunen_alanlar);
$alanlar = $gorunen_alanlar;

if (isset($_POST['alan_ayar_guncelle'])) {
    $secili = $_POST['gorunen_alanlar'] ?? [];
    $ayarlar = ['gorunen_alanlar' => $secili];
    file_put_contents($ayarlar_dosyasi, json_encode($ayarlar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . (isset($_GET['sayfa']) ? '?sayfa='.htmlspecialchars($_GET['sayfa']) : ''));
    exit;
}

$filtre_sql = [];
$filtre_veri = [];
foreach($alanlar as $alan) {
    if (!empty($_GET[$alan])) {
        $filtre_sql[] = "$alan LIKE ?";
        $filtre_veri[] = '%' . $_GET[$alan] . '%';
    }
}
$filtre_where = $filtre_sql ? 'WHERE ' . implode(' AND ', $filtre_sql) : '';
$filtre_acik = count(array_filter($_GET, fn($k)=>in_array($k, $alanlar), ARRAY_FILTER_USE_KEY)) > 0;
?>

<!-- Filtre ve Alan Ayarları Butonları -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <!-- Sol: Filtre aç/kapa -->
  <button type="button" class="btn btn-outline-success btn-sm" onclick="document.getElementById('filtre_paneli').classList.toggle('d-none')">
    Filtreyi Göster/Gizle
  </button>

  <!-- Sağ: Ayar çarkı dropdown -->
  <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="ayarDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Alanları Göster/Gizle">
      <span class="material-icons" style="vertical-align: middle; font-size: 20px;">settings</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="ayarDropdown" style="min-width: 250px; max-height: 300px; overflow-y: auto;">
      <form method="post" id="alanAyarForm">
        <?php foreach($alanlar_tumu as $al): ?>
          <li class="form-check">
            <input class="form-check-input" type="checkbox" name="gorunen_alanlar[]" value="<?=htmlspecialchars($al)?>" id="chk_<?=$al?>" <?=in_array($al, $gorunen_alanlar)?'checked':''?>>
            <label class="form-check-label" for="chk_<?=$al?>">
              <?=ucwords(str_replace('_',' ',$al))?>
            </label>
          </li>
        <?php endforeach; ?>
        <li class="mt-2">
          <button type="submit" name="alan_ayar_guncelle" class="btn btn-primary w-100 btn-sm">Kaydet</button>
        </li>
      </form>
    </ul>
  </div>
</div>

<!-- Filtre Paneli -->
<div id="filtre_paneli" class="mb-3">
  <div class="border rounded-3 p-3 bg-white shadow-sm">
    <form method="get" class="row g-2 align-items-end">
      <?php if(isset($_GET['sayfa'])): ?>
        <input type="hidden" name="sayfa" value="<?=htmlspecialchars($_GET['sayfa'])?>">
      <?php endif; ?>
      <?php foreach($alanlar as $alan): ?>
        <div class="col-md-2 col-6">
          <?php if(in_array($alan, $select_alanlar)): ?>
            <?php
            $secenekler = [];
            $q = db()->query("SELECT DISTINCT $alan FROM $tablo WHERE $alan IS NOT NULL AND $alan != ''");
            foreach($q as $row) $secenekler[] = $row[$alan];
            ?>
            <?php
$etiket = ucwords(str_replace('_', ' ', $alan));
?>
            <select name="<?=$alan?>" class="form-control form-control-sm">
              <option value=""><?=$etiket?></option>
              <?php foreach($secenekler as $sec): ?>
                <option value="<?=htmlspecialchars($sec)?>" <?=isset($_GET[$alan])&&$_GET[$alan]==$sec?'selected':''?>><?=htmlspecialchars($sec)?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input type="text" class="form-control form-control-sm"
                   name="<?=$alan?>"
                   value="<?=isset($_GET[$alan]) ? htmlspecialchars($_GET[$alan]) : ''?>"
                   placeholder="<?=ucwords(str_replace(['_','id'], [' ', 'ID'], $alan))?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <div class="col-auto">
        <button type="submit" class="btn btn-success btn-sm">Filtrele</button>
        <a href="?<?=(isset($_GET['sayfa'])?'sayfa='.htmlspecialchars($_GET['sayfa']):'')?>" class="btn btn-outline-secondary btn-sm">Sıfırla</a>
      </div>
    </form>
  </div>
</div>

<!-- Gerekli stil ve scriptler -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
