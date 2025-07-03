<?php include 'inc/header.php'; ?>
<?php include 'inc/navbar.php'; ?>

<div class="container" style="max-width:950px; margin:36px auto 28px auto;">
    <div class="card shadow-sm">
        <div class="card-body">
            <?php
            // DİNAMİK KISIM: sadece içerik dosyası buraya!
            $izinli = [];
            foreach (glob(__DIR__ . '/sayfalar/*.php') as $dosya) {
                $anahtar = basename($dosya, '.php');
                $izinli[$anahtar] = 'sayfalar/' . basename($dosya);
            }
            $sayfa = $_GET['sayfa'] ?? 'anasayfa';
            if (array_key_exists($sayfa, $izinli) && file_exists($izinli[$sayfa])) {
                include $izinli[$sayfa];
            } else {
                include $izinli['anasayfa'];
            }
            ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
