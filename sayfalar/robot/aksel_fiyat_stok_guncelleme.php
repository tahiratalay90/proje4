<?php
if (isset($_FILES['csvfile'])) {
    $csv = fopen($_FILES['csvfile']['tmp_name'], 'r');
    $headers = fgetcsv($csv);
    fclose($csv);

    // Checkbox formunu göster
    echo '<form method="post" action="" enctype="multipart/form-data">';
    foreach ($headers as $header) {
        echo "<label><input type='checkbox' name='fields[]' value='$header'> $header</label><br>";
    }
    // csv dosya yolunu gizli input olarak aktar
    $tmpFile = tempnam(sys_get_temp_dir(), 'csv');
    move_uploaded_file($_FILES['csvfile']['tmp_name'], $tmpFile);
    echo "<input type='hidden' name='csvfile' value='$tmpFile'>";
    echo '<button type="submit" name="update">Güncelle</button>';
    echo '</form>';
    exit;
}
?>

<!-- CSV Yükleme Formu -->
<form method="post" enctype="multipart/form-data">
    <input type="file" name="csvfile" required>
    <button type="submit">Başlıkları Göster</button>
</form>


<?php
require_once __DIR__ . '/../../moduller/db.php';

if (isset($_POST['update']) && isset($_POST['fields'])) {
    $csvfile = $_POST['csvfile'];
    $fields = $_POST['fields'];
    $csv = fopen($csvfile, 'r');
    $headers = fgetcsv($csv);

    // Hangi alan hangi sütunda?
    $fieldIndexes = [];
    foreach ($fields as $field) {
        $idx = array_search($field, $headers);
        if ($idx !== false) $fieldIndexes[$field] = $idx;
    }

    $updated = 0;
    while (($row = fgetcsv($csv)) !== false) {
        $stok_kodu = $row[array_search('stok_kodu', $headers)];
        $updates = [];
        $params = [];
        foreach ($fieldIndexes as $field => $i) {
            if ($field !== 'stok_kodu') { // stok_kodu update etme!
                $updates[] = "`$field` = ?";
                $params[] = $row[$i];
            }
        }
        $params[] = $stok_kodu;
        if ($updates) {
            $sql = "UPDATE urunler SET ".implode(', ', $updates)." WHERE stok_kodu = ?";
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $updated++;
        }
    }
    fclose($csv);
    echo "$updated ürün güncellendi!";
}
?>
