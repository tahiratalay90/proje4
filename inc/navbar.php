<?php ob_start(); ?>
<nav class="navbar navbar-expand-lg navbar-custom navbar-dark shadow-sm" style="background:linear-gradient(90deg,#2e8b57 80%,#21ba6b 100%);">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php?sayfa=anasayfa">
            <span class="material-icons me-2" style="font-size:30px;">dashboard_customize</span>
            Ürün Yönetim Sistemi
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Menüyü Aç">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php
                $aktif = $_GET['sayfa'] ?? 'anasayfa';
                $ikonlar = [
                    'anasayfa' => 'home',
                    'urunler' => 'list_alt',
                    'ekle' => 'add_circle_outline',
                    'toplu_guncelleme' => 'upload_file',
                    'guncelle' => 'edit_note',
                    'yardim' => 'help_outline'
                ];

                // 1. Ana dizindeki dosyaları direkt ekle
                foreach (glob(__DIR__ . '/../sayfalar/*.php') as $dosya) {
                    $anahtar = basename($dosya, '.php');
                    $etiket = ucfirst(str_replace('_', ' ', $anahtar));
                    $icon = isset($ikonlar[$anahtar]) ? $ikonlar[$anahtar] : 'chevron_right';
                    $active = ($aktif == $anahtar) ? 'active' : '';
                    echo '<li class="nav-item">
                        <a class="nav-link '.$active.'" href="index.php?sayfa='.$anahtar.'">
                            <span class="material-icons align-middle">'.$icon.'</span> '.$etiket.'
                        </a>
                    </li>';
                }

                // 2. Alt klasörleri başlık olarak ekle, altına dropdown ile dosyalarını ekle
                foreach (glob(__DIR__ . '/../sayfalar/*', GLOB_ONLYDIR) as $klasor) {
                    $klasor_adi = basename($klasor);
                    $alt_sayfalar = glob($klasor.'/*.php');
                    if ($alt_sayfalar) {
                        $aktif_alt = (strpos($aktif, $klasor_adi.'/') === 0) ? 'active' : '';
                        echo '<li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle '.$aktif_alt.'" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="material-icons align-middle">folder_open</span> '.ucfirst($klasor_adi).'
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">';
                        foreach ($alt_sayfalar as $alt) {
                            $alt_anahtar = $klasor_adi.'/'.basename($alt, '.php');
                            $alt_etiket = ucfirst(str_replace('_', ' ', basename($alt, '.php')));
                            $active2 = ($aktif == $alt_anahtar) ? 'active' : '';
                            echo '<li>
                                <a class="dropdown-item '.$active2.'" href="index.php?sayfa='.$alt_anahtar.'">
                                    <span class="material-icons align-middle" style="font-size:18px;">chevron_right</span> '.$alt_etiket.'
                                </a>
                            </li>';
                        }
                        echo '</ul></li>';
                    }
                }
                ?>
            </ul>
        </div>
    </div>
</nav>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
