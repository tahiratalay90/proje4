<?php
require_once __DIR__ . '/moduller/db.php';

$tablo = 'urunler';

// SADECE orjinal_sku sütunu olanları çekelim
$sql = "SELECT orjinal_sku FROM $tablo WHERE orjinal_sku IS NOT NULL AND orjinal_sku != '' ORDER BY id DESC";
$stmt = db()->prepare($sql);
$stmt->execute();
$skular = $stmt->fetchAll(PDO::FETCH_COLUMN);

// JSON olarak çıktı ver
header('Content-Type: application/json; charset=utf-8');
echo json_encode($skular, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>
