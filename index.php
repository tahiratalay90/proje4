<?php define('ROOT', __DIR__); ?>
<?php include 'inc/header.php'; ?>
<?php include 'inc/navbar.php'; ?>

<div class="container" style="max-width:950px; margin:36px auto 28px auto;">
    <div class="card shadow-sm">
        <div class="card-body">
            <?php
            // GLOB_BRACE kullanmadan alt klasörleri tara
            function tum_sayfalar($dizin) {
                $dosyalar = [];
                foreach (scandir($dizin) as $eleman) {
                    if ($eleman == '.' || $eleman == '..') continue;
                    $tam_yol = $dizin . '/' . $eleman;
                    if (is_dir($tam_yol)) {
                        $dosyalar += tum_sayfalar($tam_yol); // ALT KLASÖR
                    } elseif (pathinfo($tam_yol, PATHINFO_EXTENSION) === 'php') {
                        $goreli_yol = str_replace(['\\', '/'], '/', str_replace(__DIR__ . '/sayfalar/', '', $tam_yol));
                        $goreli_yol = str_replace('.php', '', $goreli_yol);
                        $dosyalar[$goreli_yol] = $tam_yol;
                    }
                }
                return $dosyalar;
            }

            $izinli = tum_sayfalar(__DIR__ . '/sayfalar');
            $sayfa = $_GET['sayfa'] ?? 'anasayfa';

            if (isset($izinli[$sayfa]) && file_exists($izinli[$sayfa])) {
                include $izinli[$sayfa];
            } elseif (isset($izinli['anasayfa'])) {
                include $izinli['anasayfa'];
            } else {
                echo '<div style="color:red; font-weight:bold; padding:40px 30px; text-align:center;">
                    Hiçbir içerik bulunamadı!<br>
                    <b>?sayfa=</b> parametresi yanlış veya dosya yok.
                </div>';
            }
            ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
