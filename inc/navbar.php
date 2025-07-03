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
                // Sayfalar klasöründen tüm .php dosyalarını oku ve menüyü oluştur
                $aktif = $_GET['sayfa'] ?? 'anasayfa';
                foreach (glob(__DIR__ . '/../sayfalar/*.php') as $dosya) {
                    $anahtar = basename($dosya, '.php');
                    // Menüde göstermek için dosya adını baş harf büyük ve Türkçeleştir
                    $etiket = ucfirst(str_replace('_', ' ', $anahtar));
                    // Özel ikonlar (isteğe bağlı, dosya adına göre atanıyor)
                    $ikonlar = [
                        'anasayfa' => 'home',
                        'urunler' => 'list_alt',
                        'ekle' => 'add_circle_outline',
                        'toplu_ekle' => 'upload_file',
                        'guncelle' => 'edit_note',
                        'yardim' => 'help_outline'
                    ];
                    $icon = isset($ikonlar[$anahtar]) ? $ikonlar[$anahtar] : 'chevron_right';
                    $active = ($aktif == $anahtar) ? 'active' : '';
                    echo '<li class="nav-item">
                        <a class="nav-link '.$active.'" href="index.php?sayfa='.$anahtar.'">
                            <span class="material-icons align-middle">'.$icon.'</span> '.$etiket.'
                        </a>
                    </li>';
                }
                ?>
            </ul>
        </div>
    </div>
</nav>
<!-- Material Icons CDN -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
