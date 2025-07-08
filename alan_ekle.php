<?php
require_once 'moduller/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = db();

// Aranacak ve değiştirilecek URL
$eski_link = "http://hementeknoloji.com.tr/wp-content/uploads/2025/07/Printpen_Standart.jpeg";
$yeni_link = "http://hementeknoloji.com.tr/wp-content/uploads/2025/07/Printpen_Standart_x1.jpeg";

// Güncelleme sorgusu
$stmt = $db->prepare("UPDATE urunler SET resim_link = ? WHERE resim_link = ?");
$stmt->execute([$yeni_link, $eski_link]);

$adet = $stmt->rowCount();
echo "✅ $adet adet ürünün resmi Xbox_Standart_x1.jpeg olarak güncellendi.";
