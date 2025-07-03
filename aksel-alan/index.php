<?php
// HTML içeriğini bir dosyadan oku
$html = file_get_contents("veri.html");

libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
$xpath = new DOMXPath($doc);

// Ürünleri bul
$products = $xpath->query("//div[contains(@class, 'kt-portlet__body')]");

// Toplam ürün sayısı
$total = $products->length;
echo "<h2 style='font-family:Arial;'>Toplam Ürün: $total adet</h2>";

// Tabloyu başlat
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-family:Arial; font-size:14px;'>
<tr style='background:#f2f2f2'>
    <th>#</th>
    <th>Ürün Adı</th>
    <th>Fiyat (USD)</th>
    <th>Fiyat (TL)</th>
    <th>Stok Kodu</th>
    <th>İzmir Stok</th>
    <th>İstanbul Stok</th>
    <th>Baskı Kapasitesi</th>
</tr>";

$index = 1;
foreach ($products as $product) {
    $name = $xpath->query(".//a[contains(@class, 'kt-widget__username')]/h5/span", $product);
    $nameText = $name->length > 0 ? trim($name->item(0)->nodeValue) : "";

    $left = $xpath->query(".//div[contains(@style, 'float:left')]", $product);
    $leftText = $left->length > 0 ? $left->item(0)->textContent : "";

    $right = $xpath->query(".//div[contains(@style, 'float:right')]", $product);
    $rightText = $right->length > 0 ? $right->item(0)->textContent : "";

    preg_match('/Fiyat:\s*([\d.,]+)\s*USD\s*\(([\d.,]+)\s*TL\)/', $leftText, $fiyat);
    preg_match('/Stok Kodu:\s*(\d+)/', $leftText, $stok_kodu);
    preg_match('/İzmir Stok Durumu\s*:\s*([^\s<]+)/', $rightText, $izmir);
    preg_match('/İstanbul Stok Durumu\s*:\s*([^\s<]+)/', $rightText, $istanbul);
    preg_match('/Baskı Kapasitesi\s*:\s*([\d.,]+)/', $rightText, $kapasite);

    echo "<tr>
        <td>$index</td>
        <td>" . htmlspecialchars($nameText) . "</td>
        <td>" . ($fiyat[1] ?? '-') . "</td>
        <td>" . ($fiyat[2] ?? '-') . "</td>
        <td>" . ($stok_kodu[1] ?? '-') . "</td>
        <td>" . ($izmir[1] ?? '-') . "</td>
        <td>" . ($istanbul[1] ?? '-') . "</td>
        <td>" . ($kapasite[1] ?? '-') . "</td>
    </tr>";
    $index++;
}

echo "</table>";
?>
