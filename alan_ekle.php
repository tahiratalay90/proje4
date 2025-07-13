<?php
$db = new PDO('sqlite:urunler.db');

// Tüm ürünleri çek
$urunler = $db->query("SELECT id, urun_adi FROM urunler")->fetchAll(PDO::FETCH_ASSOC);

foreach ($urunler as $u) {
    $aciklama = <<<EOT
<h1>{$u['urun_adi']}</h1>
<b>Kesintisiz ve Yüksek Performanslı Baskı Deneyimi</b><br>
{$u['urun_adi']}, ofisinizin ve evinizin tüm baskı ihtiyaçlarında maksimum verimlilik sağlar. Orijinal kaliteyle yarışan bu toner sayesinde her baskınızda net ve keskin sonuçlar elde edersiniz. Uzun ömürlü yapısı ve istikrarlı performansı ile belgelerinizde mükemmel netlik sunar. Yazıcı dostu formülü sayesinde yazıcınızda sorunsuz çalışır ve kartuş değişim sürecini zahmetsiz kılar.<br><br>
<b>Ekonomik Çözümlerle Yüksek Tasarruf</b><br>
Yüksek baskı kapasitesine sahip {$u['urun_adi']}, sayfa başı maliyetlerinizi önemli ölçüde azaltır. Kaliteden ödün vermeden daha fazla baskı yapabilir, bütçenizi verimli kullanabilirsiniz. Özellikle yoğun baskı gereksinimi olan iş yerleri için ideal olan bu toner, uzun vadede önemli bir tasarruf sağlar. Ekonomik fiyatı ve yüksek verimiyle hem cebinizi hem de işinizi korur.<br><br>
<b>Çevre Dostu ve Güvenilir Seçim</b><br>
Çevre bilinciyle üretilmiş {$u['urun_adi']}, doğaya duyarlı yapısıyla sürdürülebilir bir tercih sunar. Yeniden doldurulabilir ve geri dönüştürülebilir özellikleriyle çevre dostu çözümler üretir. Tüm kalite testlerinden geçmiş, %100 uyumlu ve güvenilir yapısıyla baskı işlerinizde daima yanınızda!
EOT;

    $stmt = $db->prepare("UPDATE urunler SET aciklama1 = ? WHERE id = ?");
    $stmt->execute([$aciklama, $u['id']]);
}

echo "Tüm ürünlerde aciklama1 alanı H1 ve bold başlıklarla güncellendi!";
?>
