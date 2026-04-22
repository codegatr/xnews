<?php
/**
 * XNEWS - Yönetim Paneli (Tek Dosya)
 * Asama 3A: Login + Dashboard + Kaynaklar + Kategoriler + Çıkış
 *
 * Asama 3B'de eklenecek: Haberler CRUD, Reklam, Ayarlar, Kullanıcılar, Loglar, Manuel RSS
 *
 * CODEGA pattern: single-file PHP
 */
define('XNEWS', true);
require __DIR__ . '/baglan.php';

$sayfa = preg_replace('/[^a-z0-9-]/i', '', $_GET['sayfa'] ?? 'dashboard');
$islem = preg_replace('/[^a-z0-9-]/i', '', $_GET['islem'] ?? '');
$id    = (int)($_GET['id'] ?? 0);

$mesaj = '';
$mesaj_tip = '';

// Flash mesaj
if (!empty($_SESSION['flash'])) {
    $mesaj = $_SESSION['flash']['mesaj'];
    $mesaj_tip = $_SESSION['flash']['tip'];
    unset($_SESSION['flash']);
}
function flash(string $mesaj, string $tip = 'basari'): void {
    $_SESSION['flash'] = ['mesaj' => $mesaj, 'tip' => $tip];
}

// =====================================================
// CIKIS
// =====================================================
if ($sayfa === 'cikis') {
    log_ekle('bilgi', 'Oturum kapatildi', null, $_SESSION['yonetici_id'] ?? null);
    session_unset();
    session_destroy();
    yonlendir(url('yonetim.php'));
}

// =====================================================
// LOGIN
// =====================================================
if ($sayfa === 'giris' || !giris_kontrol()) {
    $hata = '';
    if (post()) {
        $k = trim($_POST['kullanici'] ?? '');
        $s = $_POST['sifre'] ?? '';
        if (!csrf_dogrula($_POST['_csrf'] ?? '')) {
            $hata = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.';
        } elseif (empty($k) || empty($s)) {
            $hata = 'Kullanıcı adı ve şifre zorunludur.';
        } else {
            $stmt = $db->prepare("SELECT * FROM " . DB_PREFIX . "users WHERE (kullanici_adi = ? OR eposta = ?) AND durum = 1 LIMIT 1");
            $stmt->execute([$k, $k]);
            $kullanici = $stmt->fetch();

            // Brute force koruma - basit rate limit
            $limit_anahtar = 'giris_deneme_' . md5(istemci_ip());
            $deneme = $_SESSION[$limit_anahtar] ?? ['sayi' => 0, 'son' => 0];
            if ($deneme['sayi'] >= 5 && (time() - $deneme['son']) < 300) {
                $hata = 'Çok fazla başarısız giriş denemesi. 5 dakika sonra tekrar deneyin.';
                log_ekle('guvenlik', 'Rate limit: Giriş denemesi engelle');
            } elseif ($kullanici && password_verify($s, $kullanici['sifre_hash'])) {
                // Başarılı
                session_regenerate_id(true);
                $_SESSION['yonetici_id'] = $kullanici['id'];
                $_SESSION['son_aktivite'] = time();
                unset($_SESSION[$limit_anahtar]);
                $db->prepare("UPDATE " . DB_PREFIX . "users SET son_giris = NOW(), son_ip = ? WHERE id = ?")
                   ->execute([istemci_ip(), $kullanici['id']]);
                log_ekle('bilgi', 'Yönetici girisi', $kullanici['kullanici_adi'], $kullanici['id']);
                yonlendir(url('yonetim.php'));
            } else {
                $hata = 'Kullanıcı adı veya şifre hatali.';
                $deneme['sayi']++;
                $deneme['son'] = time();
                $_SESSION[$limit_anahtar] = $deneme;
                log_ekle('guvenlik', 'Başarısız giriş denemesi', $k);
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Giriş - <?= h(ayar('site_adi', 'XNEWS')) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= url('favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=IBM+Plex+Sans:wght@400;500;600;700&subset=latin-ext&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=<?= h(ayar('mevcut_surum', '1.0.0')) ?>">
</head>
<body class="login-sayfa">
<div class="login-kutu">
    <div class="login-bas">
        <div class="login-logo"><span class="x">X</span>NEWS</div>
        <div class="login-alt">Yönetim Paneli</div>
    </div>
    <form class="login-form" method="post" autocomplete="on">
        <?= csrf_input() ?>
        <h2>Giriş Yap</h2>
        <p class="alt">Devam etmek için hesabinizla giriş yapin.</p>
        <?php if ($hata): ?>
            <div class="login-giris-uyari">&#9888; <?= h($hata) ?></div>
        <?php endif; ?>
        <div class="form-grup">
            <label>Kullanıcı adı veya e-posta</label>
            <input type="text" name="kullanici" value="<?= h($_POST['kullanici'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-grup">
            <label>Şifre</label>
            <input type="password" name="sifre" required>
        </div>
        <button type="submit" class="buton" style="width:100%;padding:13px;font-size:14px">Giriş Yap</button>
        <p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:20px">
            <a href="<?= url() ?>" style="color:#94a3b8">&larr; Public siteye don</a>
        </p>
    </form>
</div>
</body>
</html>
<?php
    exit;
}

// =====================================================
// YETKI GEREKLI - BURADAN SONRA GIRIS YAPMIS KULLANICI
// =====================================================
$yonetici = yonetici_zorunlu();
$prefix = DB_PREFIX;

// =====================================================
// POST ISLEMLER - KAYNAKLAR
// =====================================================
if ($sayfa === 'kaynaklar' && post()) {
    if (!csrf_dogrula($_POST['_csrf'] ?? '')) {
        flash('Güvenlik doğrulaması başarısız.', 'hata');
        yonlendir(url('yonetim.php?sayfa=kaynaklar'));
    }
    try {
        if ($islem === 'ekle' || $islem === 'duzenle') {
            $ad          = trim($_POST['ad'] ?? '');
            $site_url_d  = trim($_POST['site_url'] ?? '');
            $rss_url     = trim($_POST['rss_url'] ?? '');
            $kat_id      = (int)($_POST['varsayilan_kategori_id'] ?? 0) ?: null;
            $aciklama    = trim($_POST['aciklama'] ?? '');
            $atfi        = trim($_POST['atfi_metin'] ?? $ad);
            $dil         = trim($_POST['dil'] ?? 'tr');
            $sikligi     = max(5, (int)($_POST['cekim_sikligi'] ?? 10));
            $max_haber   = max(5, min(200, (int)($_POST['max_haber_adet'] ?? 50)));
            $aktif       = isset($_POST['aktif']) ? 1 : 0;

            if (empty($ad) || empty($rss_url) || empty($site_url_d)) {
                throw new Exception('Ad, site URL ve RSS URL zorunlu.');
            }
            if (!filter_var($rss_url, FILTER_VALIDATE_URL) || !filter_var($site_url_d, FILTER_VALIDATE_URL)) {
                throw new Exception('Geçerli URL girin.');
            }

            if ($islem === 'ekle') {
                $slug = benzersiz_slug($db, "{$prefix}sources", slug_olustur($ad));
                $stmt = $db->prepare("INSERT INTO {$prefix}sources
                    (ad, slug, site_url, rss_url, varsayilan_kategori_id, logo, aciklama, dil, atfi_metin, aktif, cekim_sikligi, max_haber_adet)
                    VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ad, $slug, $site_url_d, $rss_url, $kat_id, $aciklama, $dil, $atfi, $aktif, $sikligi, $max_haber]);
                log_ekle('islem', 'Kaynak eklendi', $ad, $yonetici['id']);
                flash('Kaynak başarıyla eklendi.', 'basari');
            } else {
                if ($id < 1) throw new Exception('Gecersiz kaynak ID.');
                $stmt = $db->prepare("UPDATE {$prefix}sources
                    SET ad = ?, site_url = ?, rss_url = ?, varsayilan_kategori_id = ?, aciklama = ?, dil = ?,
                        atfi_metin = ?, aktif = ?, cekim_sikligi = ?, max_haber_adet = ? WHERE id = ?");
                $stmt->execute([$ad, $site_url_d, $rss_url, $kat_id, $aciklama, $dil, $atfi, $aktif, $sikligi, $max_haber, $id]);
                log_ekle('islem', 'Kaynak güncellendi', $ad, $yonetici['id']);
                flash('Kaynak güncellendi.', 'basari');
            }
            yonlendir(url('yonetim.php?sayfa=kaynaklar'));
        }
        if ($islem === 'sil' && $id > 0) {
            $db->prepare("DELETE FROM {$prefix}sources WHERE id = ?")->execute([$id]);
            log_ekle('islem', 'Kaynak silindi', 'ID: ' . $id, $yonetici['id']);
            flash('Kaynak silindi. Bu kaynaktan gelen haberler korundu.', 'basari');
            yonlendir(url('yonetim.php?sayfa=kaynaklar'));
        }
        if ($islem === 'durum' && $id > 0) {
            $db->prepare("UPDATE {$prefix}sources SET aktif = 1 - aktif WHERE id = ?")->execute([$id]);
            flash('Kaynak durumu değiştirildi.', 'basari');
            yonlendir(url('yonetim.php?sayfa=kaynaklar'));
        }
    } catch (Throwable $e) {
        flash('Hata: ' . $e->getMessage(), 'hata');
    }
}

// =====================================================
// POST ISLEMLER - KATEGORILER
// =====================================================
if ($sayfa === 'kategoriler' && post()) {
    if (!csrf_dogrula($_POST['_csrf'] ?? '')) {
        flash('Güvenlik doğrulaması başarısız.', 'hata');
        yonlendir(url('yonetim.php?sayfa=kategoriler'));
    }
    try {
        if ($islem === 'ekle' || $islem === 'duzenle') {
            $ad       = trim($_POST['ad'] ?? '');
            $aciklama = trim($_POST['aciklama'] ?? '');
            $renk     = trim($_POST['renk'] ?? '#c8102e');
            $sira     = (int)($_POST['sira'] ?? 0);
            $aktif    = isset($_POST['aktif']) ? 1 : 0;

            if (empty($ad)) throw new Exception('Kategori adi zorunlu.');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $renk)) $renk = '#c8102e';

            if ($islem === 'ekle') {
                $slug = benzersiz_slug($db, "{$prefix}categories", slug_olustur($ad));
                $stmt = $db->prepare("INSERT INTO {$prefix}categories (ad, slug, aciklama, renk, sira, aktif) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ad, $slug, $aciklama, $renk, $sira, $aktif]);
                log_ekle('islem', 'Kategori eklendi', $ad, $yonetici['id']);
                flash('Kategori eklendi.', 'basari');
            } else {
                if ($id < 1) throw new Exception('Gecersiz kategori.');
                $stmt = $db->prepare("UPDATE {$prefix}categories SET ad = ?, aciklama = ?, renk = ?, sira = ?, aktif = ? WHERE id = ?");
                $stmt->execute([$ad, $aciklama, $renk, $sira, $aktif, $id]);
                log_ekle('islem', 'Kategori güncellendi', $ad, $yonetici['id']);
                flash('Kategori güncellendi.', 'basari');
            }
            yonlendir(url('yonetim.php?sayfa=kategoriler'));
        }
        if ($islem === 'sil' && $id > 0) {
            // Haberleri olan kategori silinmesin
            $sayi = (int)$db->prepare("SELECT COUNT(*) FROM {$prefix}news WHERE kategori_id = ?")->execute([$id]);
            $c = $db->prepare("SELECT COUNT(*) FROM {$prefix}news WHERE kategori_id = ?");
            $c->execute([$id]);
            if ((int)$c->fetchColumn() > 0) {
                flash('Bu kategoride haberler var, önce tasiyin veya silin.', 'hata');
            } else {
                $db->prepare("DELETE FROM {$prefix}categories WHERE id = ?")->execute([$id]);
                log_ekle('islem', 'Kategori silindi', 'ID: ' . $id, $yonetici['id']);
                flash('Kategori silindi.', 'basari');
            }
            yonlendir(url('yonetim.php?sayfa=kategoriler'));
        }
    } catch (Throwable $e) {
        flash('Hata: ' . $e->getMessage(), 'hata');
    }
}

// =====================================================
// POST ISLEMLER - HABERLER
// =====================================================
if ($sayfa === 'haberler' && post()) {
    if (!csrf_dogrula($_POST['_csrf'] ?? '')) {
        flash('Güvenlik doğrulaması başarısız.', 'hata');
        yonlendir(url('yonetim.php?sayfa=haberler'));
    }
    try {
        if ($islem === 'ekle' || $islem === 'duzenle') {
            $baslik      = trim($_POST['baslik'] ?? '');
            $ozet        = trim($_POST['ozet'] ?? '');
            $icerik      = $_POST['icerik'] ?? '';
            $kat_id      = (int)($_POST['kategori_id'] ?? 0);
            $kaynak_id   = (int)($_POST['kaynak_id'] ?? 0) ?: null;
            $resim       = trim($_POST['resim'] ?? '');
            $resim_alt   = trim($_POST['resim_alt'] ?? '');
            $yazar       = trim($_POST['yazar'] ?? '');
            $orij_url    = trim($_POST['orijinal_url'] ?? '');
            $durum       = in_array($_POST['durum'] ?? '', ['yayinda','taslak','arsiv','beklemede'], true) ? $_POST['durum'] : 'yayinda';
            $manset      = isset($_POST['manset']) ? 1 : 0;
            $one_cikan   = isset($_POST['one_cikan']) ? 1 : 0;
            $son_dakika  = isset($_POST['son_dakika']) ? 1 : 0;
            $seo_baslik  = trim($_POST['seo_baslik'] ?? '');
            $seo_aciklama= trim($_POST['seo_aciklama'] ?? '');
            $yayin_tarihi= trim($_POST['yayin_tarihi'] ?? '') ?: date('Y-m-d H:i:s');

            if (empty($baslik)) throw new Exception('Başlık zorunlu.');
            if ($kat_id < 1)   throw new Exception('Kategori secin.');

            if ($islem === 'ekle') {
                $slug = benzersiz_slug($db, "{$prefix}news", slug_olustur($baslik, 200));
                $hash = sha1(mb_strtolower($baslik, 'UTF-8') . '|' . $kat_id);
                $stmt = $db->prepare("INSERT INTO {$prefix}news
                    (baslik, slug, ozet, icerik, resim, resim_alt, kaynak_id, kategori_id, yazar, yazar_id,
                     orijinal_url, icerik_hash, manset, one_cikan, son_dakika, durum, seo_baslik, seo_aciklama, yayin_tarihi)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$baslik, $slug, $ozet, $icerik, $resim, $resim_alt, $kaynak_id, $kat_id, $yazar, $yonetici['id'],
                    $orij_url, $hash, $manset, $one_cikan, $son_dakika, $durum, $seo_baslik, $seo_aciklama, $yayin_tarihi]);
                log_ekle('islem', 'Haber eklendi', $baslik, $yonetici['id']);
                flash('Haber eklendi.', 'basari');
                yonlendir(url('yonetim.php?sayfa=haberler'));
            } else {
                if ($id < 1) throw new Exception('Gecersiz haber.');
                $stmt = $db->prepare("UPDATE {$prefix}news SET
                    baslik = ?, ozet = ?, icerik = ?, resim = ?, resim_alt = ?, kaynak_id = ?, kategori_id = ?,
                    yazar = ?, orijinal_url = ?, manset = ?, one_cikan = ?, son_dakika = ?, durum = ?,
                    seo_baslik = ?, seo_aciklama = ?, yayin_tarihi = ? WHERE id = ?");
                $stmt->execute([$baslik, $ozet, $icerik, $resim, $resim_alt, $kaynak_id, $kat_id, $yazar, $orij_url,
                    $manset, $one_cikan, $son_dakika, $durum, $seo_baslik, $seo_aciklama, $yayin_tarihi, $id]);
                log_ekle('islem', 'Haber güncellendi', $baslik, $yonetici['id']);
                flash('Haber güncellendi.', 'basari');
                yonlendir(url('yonetim.php?sayfa=haberler&islem=duzenle&id=' . $id));
            }
        }
        if ($islem === 'sil' && $id > 0) {
            $db->prepare("DELETE FROM {$prefix}news WHERE id = ?")->execute([$id]);
            log_ekle('islem', 'Haber silindi', 'ID: ' . $id, $yonetici['id']);
            flash('Haber silindi.', 'basari');
            yonlendir(url('yonetim.php?sayfa=haberler'));
        }
        if ($islem === 'toplu') {
            $idler = array_map('intval', $_POST['secili'] ?? []);
            $eylem = $_POST['toplu_eylem'] ?? '';
            if (empty($idler)) throw new Exception('Hicbir haber secilmedi.');
            $ph = implode(',', array_fill(0, count($idler), '?'));
            switch ($eylem) {
                case 'sil':
                    $db->prepare("DELETE FROM {$prefix}news WHERE id IN ({$ph})")->execute($idler);
                    flash(count($idler) . ' haber silindi.', 'basari');
                    break;
                case 'yayinda':
                case 'taslak':
                case 'arsiv':
                    $db->prepare("UPDATE {$prefix}news SET durum = ? WHERE id IN ({$ph})")->execute(array_merge([$eylem], $idler));
                    flash(count($idler) . ' haber ' . $eylem . ' yapildi.', 'basari');
                    break;
                default:
                    throw new Exception('Gecersiz islem.');
            }
            log_ekle('islem', 'Toplu islem: ' . $eylem, count($idler) . ' haber', $yonetici['id']);
            yonlendir(url('yonetim.php?sayfa=haberler'));
        }
        if (in_array($islem, ['manset', 'one_cikan', 'son_dakika'], true) && $id > 0) {
            $alan = $islem;
            $db->prepare("UPDATE {$prefix}news SET `{$alan}` = 1 - `{$alan}` WHERE id = ?")->execute([$id]);
            flash('Durum değiştirildi.', 'basari');
            yonlendir(url('yonetim.php?sayfa=haberler'));
        }
    } catch (Throwable $e) {
        flash('Hata: ' . $e->getMessage(), 'hata');
    }
}

// =====================================================
// POST ISLEMLER - REKLAMLAR
// =====================================================
if ($sayfa === 'reklamlar' && post()) {
    if (!csrf_dogrula($_POST['_csrf'] ?? '')) {
        flash('Güvenlik doğrulaması başarısız.', 'hata');
        yonlendir(url('yonetim.php?sayfa=reklamlar'));
    }
    try {
        if ($islem === 'ekle' || $islem === 'duzenle') {
            $ad         = trim($_POST['ad'] ?? '');
            $konum      = $_POST['konum'] ?? '';
            $tip        = in_array($_POST['tip'] ?? '', ['kod','gorsel','adsense'], true) ? $_POST['tip'] : 'gorsel';
            $kod        = $_POST['kod'] ?? '';
            $gorsel     = trim($_POST['gorsel'] ?? '');
            $hedef_url  = trim($_POST['hedef_url'] ?? '');
            $genislik   = (int)($_POST['genislik'] ?? 0) ?: null;
            $yukseklik  = (int)($_POST['yukseklik'] ?? 0) ?: null;
            $baslangic  = trim($_POST['baslangic'] ?? '') ?: null;
            $bitis      = trim($_POST['bitis'] ?? '') ?: null;
            $aktif      = isset($_POST['aktif']) ? 1 : 0;

            $gecerli_konumlar = ['ust_banner','sidebar_ust','sidebar_alt','makale_ust','makale_ic','makale_alt','alt_banner','mobil_sabit','popup'];
            if (empty($ad)) throw new Exception('Reklam adi zorunlu.');
            if (!in_array($konum, $gecerli_konumlar, true)) throw new Exception('Gecersiz konum.');
            if ($tip === 'gorsel' && empty($gorsel)) throw new Exception('Görsel URL zorunlu.');
            if (($tip === 'kod' || $tip === 'adsense') && empty($kod)) throw new Exception('Kod icerigi zorunlu.');

            if ($islem === 'ekle') {
                $stmt = $db->prepare("INSERT INTO {$prefix}ads (ad, konum, tip, kod, gorsel, hedef_url, genislik, yukseklik, baslangic, bitis, aktif) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$ad, $konum, $tip, $kod, $gorsel, $hedef_url, $genislik, $yukseklik, $baslangic, $bitis, $aktif]);
                flash('Reklam eklendi.', 'basari');
            } else {
                if ($id < 1) throw new Exception('Gecersiz reklam.');
                $stmt = $db->prepare("UPDATE {$prefix}ads SET ad = ?, konum = ?, tip = ?, kod = ?, gorsel = ?, hedef_url = ?, genislik = ?, yukseklik = ?, baslangic = ?, bitis = ?, aktif = ? WHERE id = ?");
                $stmt->execute([$ad, $konum, $tip, $kod, $gorsel, $hedef_url, $genislik, $yukseklik, $baslangic, $bitis, $aktif, $id]);
                flash('Reklam güncellendi.', 'basari');
            }
            log_ekle('islem', 'Reklam ' . ($islem === 'ekle' ? 'eklendi' : 'güncellendi'), $ad, $yonetici['id']);
            yonlendir(url('yonetim.php?sayfa=reklamlar'));
        }
        if ($islem === 'sil' && $id > 0) {
            $db->prepare("DELETE FROM {$prefix}ads WHERE id = ?")->execute([$id]);
            flash('Reklam silindi.', 'basari');
            yonlendir(url('yonetim.php?sayfa=reklamlar'));
        }
    } catch (Throwable $e) {
        flash('Hata: ' . $e->getMessage(), 'hata');
    }
}

// =====================================================
// POST ISLEMLER - KULLANICILAR (sadece admin)
// =====================================================
if ($sayfa === 'kullanicilar' && post()) {
    if ($yonetici['rol'] !== 'admin') { flash('Bu islem için admin yetkisi gerekli.', 'hata'); yonlendir(url('yonetim.php')); }
    if (!csrf_dogrula($_POST['_csrf'] ?? '')) { flash('Güvenlik doğrulaması başarısız.', 'hata'); yonlendir(url('yonetim.php?sayfa=kullanicilar')); }
    try {
        if ($islem === 'ekle' || $islem === 'duzenle') {
            $k_ad    = trim($_POST['kullanici_adi'] ?? '');
            $eposta  = trim($_POST['eposta'] ?? '');
            $ad_soy  = trim($_POST['ad_soyad'] ?? '');
            $rol     = in_array($_POST['rol'] ?? '', [
                'admin','yonetici','genel_yayin_yonetmeni','yazi_isleri_muduru',
                'sorumlu_mudur','editor','haber_sorumlusu','haber_muhabiri',
                'muhabir','fotograf_editoru','yazar','stajyer'
            ], true) ? $_POST['rol'] : 'editor';
            $telefon     = trim($_POST['telefon'] ?? '');
            $gorev_tanim = trim($_POST['gorev_tanimi'] ?? '');
            $sifre   = $_POST['sifre'] ?? '';
            $aktif   = isset($_POST['durum']) ? 1 : 0;

            if (strlen($k_ad) < 4)                            throw new Exception('Kullanıcı adı en az 4 karakter.');
            if (!filter_var($eposta, FILTER_VALIDATE_EMAIL))  throw new Exception('Geçerli e-posta girin.');
            if (empty($ad_soy))                               throw new Exception('Ad soyad zorunlu.');

            if ($islem === 'ekle') {
                if (strlen($sifre) < 8) throw new Exception('Şifre en az 8 karakter.');
                $stmt = $db->prepare("INSERT INTO {$prefix}users (kullanici_adi, eposta, sifre_hash, ad_soyad, rol, telefon, gorev_tanimi, durum) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$k_ad, $eposta, password_hash($sifre, PASSWORD_BCRYPT), $ad_soy, $rol, $telefon ?: null, $gorev_tanim ?: null, $aktif]);
                flash('Kullanıcı eklendi.', 'basari');
            } else {
                if ($id < 1) throw new Exception('Gecersiz kullanici.');
                if ($id == $yonetici['id'] && $rol !== 'admin') throw new Exception('Kendi rolunuzu degistiremezsiniz.');
                if (!empty($sifre)) {
                    if (strlen($sifre) < 8) throw new Exception('Şifre en az 8 karakter.');
                    $db->prepare("UPDATE {$prefix}users SET kullanici_adi=?, eposta=?, ad_soyad=?, rol=?, telefon=?, gorev_tanimi=?, durum=?, sifre_hash=? WHERE id=?")
                       ->execute([$k_ad, $eposta, $ad_soy, $rol, $telefon ?: null, $gorev_tanim ?: null, $aktif, password_hash($sifre, PASSWORD_BCRYPT), $id]);
                } else {
                    $db->prepare("UPDATE {$prefix}users SET kullanici_adi=?, eposta=?, ad_soyad=?, rol=?, telefon=?, gorev_tanimi=?, durum=? WHERE id=?")
                       ->execute([$k_ad, $eposta, $ad_soy, $rol, $telefon ?: null, $gorev_tanim ?: null, $aktif, $id]);
                }
                flash('Kullanıcı güncellendi.', 'basari');
            }
            log_ekle('guvenlik', 'Kullanıcı ' . ($islem === 'ekle' ? 'eklendi' : 'güncellendi'), $k_ad, $yonetici['id']);
            yonlendir(url('yonetim.php?sayfa=kullanicilar'));
        }
        if ($islem === 'sil' && $id > 0) {
            if ($id == $yonetici['id']) throw new Exception('Kendinizi silemezsiniz.');
            $db->prepare("DELETE FROM {$prefix}users WHERE id = ?")->execute([$id]);
            log_ekle('guvenlik', 'Kullanıcı silindi', 'ID: ' . $id, $yonetici['id']);
            flash('Kullanıcı silindi.', 'basari');
            yonlendir(url('yonetim.php?sayfa=kullanicilar'));
        }
    } catch (Throwable $e) {
        flash('Hata: ' . $e->getMessage(), 'hata');
    }
}

// =====================================================
// POST ISLEMLER - AYARLAR
// =====================================================
if ($sayfa === 'ayarlar' && post()) {
    if (!csrf_dogrula($_POST['_csrf'] ?? '')) { flash('Güvenlik doğrulaması başarısız.', 'hata'); yonlendir(url('yonetim.php?sayfa=ayarlar')); }
    try {
        $ayarlar = $_POST['ayar'] ?? [];
        foreach ($ayarlar as $anahtar => $deger) {
            $anahtar = preg_replace('/[^a-z0-9_]/i', '', $anahtar);
            if (empty($anahtar)) continue;
            ayar_guncelle($anahtar, is_array($deger) ? implode(',', $deger) : $deger);
        }

        // ÖZEL: AdSense/ads.txt ayarları değiştiyse fiziksel ads.txt dosyasını yaz
        if (isset($ayarlar['adsense_client_id']) || isset($ayarlar['ads_txt_icerik'])) {
            $icerik = trim(ayar('ads_txt_icerik', ''));
            $client = trim(ayar('adsense_client_id', ''));
            if (empty($icerik) && !empty($client) && preg_match('/^ca-pub-(\d+)$/', $client, $m)) {
                // Otomatik üret
                $icerik = "# XNEWS - Otomatik olusturuldu\n";
                $icerik .= "google.com, pub-{$m[1]}, DIRECT, f08c47fec0942fa0\n";
            }
            if (!empty($icerik)) {
                $ads_dosya = dirname(__DIR__) . '/ads.txt';
                // Proje kokunde oldugumuz icin __DIR__ = public_html; ama admin scriptleri de kokte; hadi deneyelim
                $ads_dosya = __DIR__ . '/ads.txt';
                @file_put_contents($ads_dosya, $icerik);
                log_ekle('islem', 'ads.txt dosyasi guncellendi', 'Boyut: ' . strlen($icerik) . ' byte', $yonetici['id']);
            }
        }

        log_ekle('islem', 'Ayarlar güncellendi', count($ayarlar) . ' alan', $yonetici['id']);
        flash('Ayarlar kaydedildi.' . (isset($ayarlar['adsense_client_id']) ? ' ads.txt dosyasi otomatik yazildi.' : ''), 'basari');
        yonlendir(url('yonetim.php?sayfa=ayarlar' . (!empty($_GET['grup']) ? '&grup=' . h($_GET['grup']) : '')));
    } catch (Throwable $e) {
        flash('Hata: ' . $e->getMessage(), 'hata');
    }
}

// =====================================================
// MANUEL RSS CEKIMI (cron.php'yi cagir)
// =====================================================
if ($sayfa === 'cekim-tetik' && post()) {
    if (!csrf_dogrula($_POST['_csrf'] ?? '')) { flash('Güvenlik hatasi.', 'hata'); yonlendir(url('yonetim.php')); }
    $cron_url = url('cron.php?anahtar=' . CRON_ANAHTARI . '&manuel=1');
    $r = http_getir($cron_url, 60);
    if ($r['kod'] === 200) {
        flash('RSS çekimi tetiklendi. Aşama 4 henüz hazir degilse hicbir haber eklenmez.', 'bilgi');
    } else {
        flash('Çekim tetiklenemedi: HTTP ' . $r['kod'] . ' (Asama 4 cron.php henüz yuklenmemis olabilir)', 'uyari');
    }
    yonlendir(url('yonetim.php'));
}

// =====================================================
// LAYOUT + NAVIGASYON
// =====================================================

// Menu tanimi
$menu = [
    'ANA' => [
        ['dashboard',  'Dashboard',   'layout-dashboard'],
    ],
    'ICERIK' => [
        ['haberler',   'Haberler',    'newspaper'],
        ['kategoriler','Kategoriler', 'folder'],
        ['talepler',   'Kaldırma Talepleri','shield'],
    ],
    'SISTEM' => [
        ['kaynaklar',  'RSS Kaynakları','rss'],
        ['reklamlar',  'Reklamlar',   'megaphone'],
        ['kullanicilar','Kullanıcılar','users'],
        ['ayarlar',    'Ayarlar',     'settings'],
        ['cron-rehber','Cron Görevi', 'activity'],
        ['loglar',     'Loglar',      'file-text'],
    ],
];
// Mevcut sayfa bilgisi (breadcrumb için)
$sayfa_adlari = [
    'dashboard'    => 'Dashboard',
    'kaynaklar'    => 'RSS Kaynakları',
    'kategoriler'  => 'Kategoriler',
    'haberler'     => 'Haberler',
    'reklamlar'    => 'Reklamlar',
    'ayarlar'      => 'Ayarlar',
    'kullanicilar' => 'Kullanıcılar',
    'loglar'       => 'Loglar',
    'etiketler'    => 'Etiketler',
    'talepler'     => 'Kaldırma Talepleri',
    'adsense-rehber' => 'AdSense Rehberi',
    'cron-rehber' => 'Cron Zamanlanmış Görev',
];
$sayfa_basligi = $sayfa_adlari[$sayfa] ?? 'Yönetim';

// Ikon SVG'leri
function ikon(string $ad): string {
    $ikonlar = [
        'layout-dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'newspaper'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6z"/></svg>',
        'folder'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/></svg>',
        'tag'              => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
        'rss'              => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>',
        'megaphone'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
        'users'            => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'settings'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'file-text'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'plus'             => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        'edit'             => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        'trash'            => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
        'logout'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'arrow-up-right'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>',
        'toggle'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="5" width="22" height="14" rx="7" ry="7"/><circle cx="16" cy="12" r="3"/></svg>',
        'arrow-left'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
        'check'            => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'save'             => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>',
        'activity'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
        'shield'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    ];
    return $ikonlar[$ad] ?? '';
}

// Kısa ad - avatar için
$avatar_kisa = mb_strtoupper(mb_substr($yonetici['ad_soyad'] ?: $yonetici['kullanici_adi'], 0, 1, 'UTF-8'), 'UTF-8');

// Aktif sayfa sidebar'da
function menu_aktif(string $mevcut, string $slug): string {
    return $mevcut === $slug ? 'aktif' : '';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($sayfa_basligi) ?> - <?= h(ayar('site_adi', 'XNEWS')) ?> Yönetim</title>
<link rel="icon" type="image/svg+xml" href="<?= url('favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=IBM+Plex+Sans:wght@400;500;600;700&subset=latin-ext&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=<?= h(ayar('mevcut_surum', '1.0.0')) ?>">
</head>
<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sb-bas">
            <div class="sb-logo-mini">X</div>
            <div>
                <div class="baslik">XNEWS</div>
                <div class="alt">Yönetim v<?= h(ayar('mevcut_surum', '1.0.0')) ?></div>
            </div>
        </div>
        <nav class="sb-nav">
            <?php foreach ($menu as $grup => $ogeler): ?>
                <div class="sb-grup-baslik"><?= h($grup) ?></div>
                <?php foreach ($ogeler as $oge):
                    [$s, $ad, $ik] = [$oge[0], $oge[1], $oge[2]];
                    $rozet = $oge[3] ?? null;
                ?>
                <a href="<?= url('yonetim.php?sayfa=' . $s) ?>" class="<?= menu_aktif($sayfa, $s) ?>">
                    <?= ikon($ik) ?>
                    <span><?= h($ad) ?></span>
                    <?php if ($rozet): ?><span class="rozet" style="background:#334155"><?= h($rozet) ?></span><?php endif; ?>
                </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sb-alt">
            <a href="<?= url('guncelle.php') ?>" style="display:block;padding:8px 0;color:#f87171"><?= ikon('activity') ?> Güncelleme Kontrol</a>
            <a href="<?= url() ?>" target="_blank">&rarr; Siteyi Görüntüle</a>
        </div>
    </aside>

    <div class="karartma" onclick="xadmin.sidebarKapat()"></div>

    <!-- MAIN -->
    <div class="main">
        <div class="ust-bar">
            <div class="ust-bar-sol">
                <button class="mobil-menu-buton" onclick="xadmin.sidebarAc()" aria-label="Menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="breadcrumb">Yönetim / <strong><?= h($sayfa_basligi) ?></strong></div>
            </div>
            <div class="ust-bar-sag">
                <a href="<?= url() ?>" target="_blank" class="ust-buton">
                    <?= ikon('arrow-up-right') ?><span>Siteyi Gör</span>
                </a>
                <div class="kullanici-dugme">
                    <div class="avatar"><?= h($avatar_kisa) ?></div>
                    <span><?= h($yonetici['ad_soyad'] ?: $yonetici['kullanici_adi']) ?></span>
                </div>
                <a href="<?= url('yonetim.php?sayfa=cikis') ?>" class="ust-buton" title="Çıkış Yap">
                    <?= ikon('logout') ?>
                </a>
            </div>
        </div>

        <div class="icerik">
            <?php if ($mesaj): ?>
                <div class="alert alert-<?= h($mesaj_tip) ?>" data-otomatik-kaldir>
                    <?= h($mesaj) ?>
                </div>
            <?php endif; ?>

            <?php
            // ============================================
            // SAYFA ICERIKLERI
            // ============================================
            switch ($sayfa):

            // ====================================
            // DASHBOARD
            // ====================================
            case 'dashboard':
                // Istatistikler
                $s_kaynak    = (int)$db->query("SELECT COUNT(*) FROM {$prefix}sources")->fetchColumn();
                $s_kaynak_a  = (int)$db->query("SELECT COUNT(*) FROM {$prefix}sources WHERE aktif = 1")->fetchColumn();
                $s_kategori  = (int)$db->query("SELECT COUNT(*) FROM {$prefix}categories WHERE aktif = 1")->fetchColumn();
                $s_haber     = (int)$db->query("SELECT COUNT(*) FROM {$prefix}news")->fetchColumn();
                $s_haber_b   = (int)$db->query("SELECT COUNT(*) FROM {$prefix}news WHERE DATE(olusturma) = CURDATE()")->fetchColumn();
                $s_okunma    = (int)$db->query("SELECT COALESCE(SUM(okunma), 0) FROM {$prefix}news")->fetchColumn();

                $son_haberler = $db->query("SELECT n.id, n.baslik, n.slug, n.olusturma, n.okunma, s.ad AS kaynak_ad
                    FROM {$prefix}news n LEFT JOIN {$prefix}sources s ON s.id = n.kaynak_id
                    ORDER BY n.olusturma DESC LIMIT 8")->fetchAll();

                $son_cron = $db->query("SELECT c.*, s.ad AS kaynak_ad FROM {$prefix}cron_history c
                    LEFT JOIN {$prefix}sources s ON s.id = c.kaynak_id
                    ORDER BY c.olusturma DESC LIMIT 6")->fetchAll();
            ?>
                <div class="saray-hosgeldin">
                    <h1>Hoş geldiniz, <span class="altin"><?= h(explode(' ', $yonetici['ad_soyad'] ?: $yonetici['kullanici_adi'])[0]) ?></span> 👋</h1>
                    <p>XNEWS Haber İmparatorluğu yönetim sarayına eriştiniz.<br>Aşağıdan tüm imparatorluğunuzu görüntüleyebilirsiniz.</p>
                    <span class="tarih"><?= h(tr_tarih(date('Y-m-d H:i:s'))) ?> · <?= h(date('l')) ?></span>
                </div>

                <div class="hizli-eylemler" style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px 22px;margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <strong style="color:var(--saray-koyu);font-size:14px">⚡ Hızlı Eylemler:</strong>
                    <a href="<?= url('yonetim.php?sayfa=kaynaklar') ?>" class="buton">RSS Kaynakları</a>
                    <a href="<?= url('yonetim.php?sayfa=haberler&islem=ekle') ?>" class="buton ikincil">Manuel Haber Ekle</a>
                    <form method="post" action="<?= url('yonetim.php?sayfa=cekim-tetik') ?>" style="display:inline">
                        <?= csrf_input() ?>
                        <button type="submit" class="buton ikincil" title="cron.php'yi çağırır"><?= ikon('activity') ?>RSS Çekimini Tetikle</button>
                    </form>
                </div>

                <div class="stat-grid">
                    <div class="stat-kart mavi">
                        <div class="ikon"><?= ikon('rss') ?></div>
                        <div class="sayi"><?= number_format($s_kaynak_a, 0, ',', '.') ?></div>
                        <div class="etiket">Aktif RSS kaynağı (<?= $s_kaynak ?> toplam)</div>
                    </div>
                    <div class="stat-kart yesil">
                        <div class="ikon"><?= ikon('folder') ?></div>
                        <div class="sayi"><?= number_format($s_kategori, 0, ',', '.') ?></div>
                        <div class="etiket">Kategori</div>
                    </div>
                    <div class="stat-kart kirmizi">
                        <div class="ikon"><?= ikon('newspaper') ?></div>
                        <div class="sayi"><?= number_format($s_haber, 0, ',', '.') ?></div>
                        <div class="etiket">Toplam haber (bugün: <?= $s_haber_b ?>)</div>
                    </div>
                    <div class="stat-kart mor">
                        <div class="ikon"><?= ikon('activity') ?></div>
                        <div class="sayi"><?= number_format($s_okunma, 0, ',', '.') ?></div>
                        <div class="etiket">Toplam okunma</div>
                    </div>
                </div>

                <?php
                // SISTEM SAĞLIK PANELİ - tek bakışta her şey
                $son_cron = ayar('son_cron_tarihi', '');
                $cron_dk = $son_cron ? (time() - strtotime($son_cron)) / 60 : null;
                $cron_durum = $cron_dk === null ? 'yok' : ($cron_dk < 30 ? 'iyi' : ($cron_dk < 180 ? 'uyari' : 'sorun'));

                $hatali_rss = (int)$db->query("SELECT COUNT(*) FROM {$prefix}sources WHERE aktif=1 AND (son_hata IS NOT NULL AND son_hata != '')")->fetchColumn();
                $toplam_rss = (int)$db->query("SELECT COUNT(*) FROM {$prefix}sources WHERE aktif=1")->fetchColumn();
                $rss_durum = $hatali_rss == 0 ? 'iyi' : ($hatali_rss < 5 ? 'uyari' : 'sorun');

                $ssl_aktif = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['SERVER_PORT'] ?? '') == 443;
                $cron_anahtar_mevcut = defined('CRON_ANAHTARI') && !empty(CRON_ANAHTARI);
                $bekleyen_talep = (int)$db->query("SELECT COUNT(*) FROM {$prefix}takedown WHERE durum='bekliyor'")->fetchColumn();
                $ads_txt_var = file_exists(__DIR__ . '/ads.txt');

                $durumlar = [
                    ['ad' => 'Cron Görev',    'durum' => $cron_durum, 'deger' => $son_cron ? goreceli_zaman($son_cron) : 'Çalışmadı', 'link' => 'yonetim.php?sayfa=cron-rehber'],
                    ['ad' => 'RSS Kaynakları','durum' => $rss_durum,  'deger' => $hatali_rss ? "$hatali_rss hatalı / $toplam_rss" : "$toplam_rss aktif",  'link' => 'yonetim.php?sayfa=kaynaklar'],
                    ['ad' => 'SSL',           'durum' => $ssl_aktif ? 'iyi' : 'uyari', 'deger' => $ssl_aktif ? 'HTTPS' : 'HTTP', 'link' => null],
                    ['ad' => 'Cron Anahtarı', 'durum' => $cron_anahtar_mevcut ? 'iyi' : 'sorun', 'deger' => $cron_anahtar_mevcut ? 'Tanımlı' : 'Eksik!', 'link' => 'yonetim.php?sayfa=cron-rehber'],
                    ['ad' => 'Kaldırma Talepleri', 'durum' => $bekleyen_talep == 0 ? 'iyi' : 'uyari', 'deger' => $bekleyen_talep ? "$bekleyen_talep bekliyor" : 'Temiz', 'link' => 'yonetim.php?sayfa=talepler'],
                    ['ad' => 'ads.txt',       'durum' => $ads_txt_var ? 'iyi' : 'uyari', 'deger' => $ads_txt_var ? 'Mevcut' : 'Yok', 'link' => 'yonetim.php?sayfa=adsense-rehber'],
                ];
                $renk = ['iyi' => '#10b981', 'uyari' => '#f59e0b', 'sorun' => '#dc2626', 'yok' => '#94a3b8'];
                $emoji = ['iyi' => '✓', 'uyari' => '!', 'sorun' => '×', 'yok' => '?'];
                ?>
                <div class="panel" style="margin-bottom:20px">
                    <div class="panel-bas" style="display:flex;align-items:center;justify-content:space-between">
                        <h3 style="margin:0">🩺 Sistem Sağlık Paneli</h3>
                        <small style="color:var(--muted)">Tek bakışta sistem durumu</small>
                    </div>
                    <div class="panel-ic">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
                            <?php foreach ($durumlar as $d): ?>
                            <a href="<?= $d['link'] ? url($d['link']) : '#' ?>" style="text-decoration:none;color:inherit;display:block;padding:12px 14px;border:1px solid var(--border);border-radius:10px;border-left:4px solid <?= $renk[$d['durum']] ?>;background:#fff;transition:all .2s" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,.06)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                                    <strong style="font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em"><?= h($d['ad']) ?></strong>
                                    <span style="width:22px;height:22px;border-radius:50%;background:<?= $renk[$d['durum']] ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700"><?= $emoji[$d['durum']] ?></span>
                                </div>
                                <div style="font-size:14px;font-weight:600;color:#1f2937"><?= h($d['deger']) ?></div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                $as_id = trim(ayar('adsense_client_id', ''));
                $as_auto = ayar('adsense_auto_ads', '0') === '1';
                $as_valid = !empty($as_id) && preg_match('/^ca-pub-\d{10,20}$/', $as_id);
                $ads_txt_mevcut = file_exists(__DIR__ . '/ads.txt');
                $reklam_ads_adedi = (int)$db->query("SELECT COUNT(*) FROM {$prefix}ads WHERE aktif=1 AND tip IN('adsense','kod')")->fetchColumn();
                ?>
                <div class="panel" style="margin-bottom:20px;background:<?= $as_valid ? 'linear-gradient(135deg,#ecfdf5,#fff)' : 'linear-gradient(135deg,#fef3c7,#fff)' ?>;border-left:4px solid <?= $as_valid ? '#10b981' : '#f59e0b' ?>">
                    <div class="panel-ic" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
                        <div style="font-size:44px"><?= $as_valid ? '💰' : '⚠️' ?></div>
                        <div style="flex:1;min-width:260px">
                            <h3 style="margin:0 0 4px;color:<?= $as_valid ? '#065f46' : '#92400e' ?>">
                                Google AdSense:
                                <?= $as_valid ? 'Aktif ✓' : 'Henüz Yapılandırılmadı' ?>
                            </h3>
                            <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;color:var(--muted);margin-top:8px">
                                <span>📌 Client: <strong style="color:<?= $as_valid ? '#10b981' : '#dc2626' ?>"><?= $as_valid ? h($as_id) : 'Girilmedi' ?></strong></span>
                                <span>🤖 Auto Ads: <strong style="color:<?= $as_auto ? '#10b981' : '#94a3b8' ?>"><?= $as_auto ? 'Açık' : 'Kapalı' ?></strong></span>
                                <span>📄 ads.txt: <strong style="color:<?= $ads_txt_mevcut ? '#10b981' : '#dc2626' ?>"><?= $ads_txt_mevcut ? 'Mevcut' : 'Yok' ?></strong></span>
                                <span>🎯 Manuel: <strong><?= $reklam_ads_adedi ?> adet</strong></span>
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <a href="<?= url('yonetim.php?sayfa=ayarlar&grup=reklam') ?>" class="buton">AdSense Ayarları</a>
                            <a href="<?= url('yonetim.php?sayfa=adsense-rehber') ?>" class="buton ikincil">📚 Rehber</a>
                        </div>
                    </div>
                </div>

                <div class="iki-kolon">
                    <div class="panel">
                        <div class="panel-bas">
                            <h3>Son Eklenen Haberler</h3>
                            <a href="<?= url('yonetim.php?sayfa=haberler') ?>" class="buton sm ikincil">Tümünü Gör</a>
                        </div>
                        <ul class="mini-liste">
                        <?php if (empty($son_haberler)): ?>
                            <li style="padding:40px 20px;justify-content:center;color:var(--muted);display:block;text-align:center">
                                Henüz haber yok. RSS cron çalıştırın veya manuel ekleyin.
                            </li>
                        <?php else: foreach ($son_haberler as $h): ?>
                            <li>
                                <div style="flex:1;min-width:0">
                                    <a class="baslik" href="<?= h(haber_url(['id' => $h['id'], 'slug' => $h['slug']])) ?>" target="_blank"><?= h(kisalt($h['baslik'], 80)) ?></a>
                                    <div class="meta">
                                        <?= h($h['kaynak_ad'] ?? 'Manuel') ?> · <?= h(goreceli_zaman($h['olusturma'])) ?> · <?= (int)$h['okunma'] ?> okuma
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; endif; ?>
                        </ul>
                    </div>

                    <div class="panel">
                        <div class="panel-bas">
                            <h3>Son RSS Çekimleri</h3>
                        </div>
                        <ul class="mini-liste">
                        <?php if (empty($son_cron)): ?>
                            <li style="padding:40px 20px;display:block;text-align:center;color:var(--muted)">
                                Henüz cron çalışmadı. DirectAdmin'den cron ekleyin veya "RSS Çekimini Tetikle" butonuna basın.
                            </li>
                        <?php else: foreach ($son_cron as $c): ?>
                            <li>
                                <div class="durum-nokta <?= h($c['durum']) ?>"></div>
                                <div style="flex:1;min-width:0">
                                    <div class="baslik"><?= h($c['kaynak_ad'] ?? 'Bilinmeyen') ?></div>
                                    <div class="meta">
                                        <?= (int)$c['eklenen'] ?> eklendi · <?= (int)$c['atlanan'] ?> atlandı · <?= h(goreceli_zaman($c['olusturma'])) ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; endif; ?>
                        </ul>
                    </div>
                </div>
                <?php break;

            // ====================================
            // KAYNAKLAR - LISTE
            // ====================================
            case 'kaynaklar':
                if ($islem === 'ekle' || $islem === 'duzenle'):
                    $kaynak_d = null;
                    if ($islem === 'duzenle' && $id > 0) {
                        $st = $db->prepare("SELECT * FROM {$prefix}sources WHERE id = ?");
                        $st->execute([$id]);
                        $kaynak_d = $st->fetch();
                        if (!$kaynak_d) { flash('Kaynak bulunamadı.', 'hata'); yonlendir(url('yonetim.php?sayfa=kaynaklar')); }
                    }
                    $kategoriler = $db->query("SELECT id, ad FROM {$prefix}categories WHERE aktif = 1 ORDER BY sira, ad")->fetchAll();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1><?= $islem === 'ekle' ? 'Yeni Kaynak Ekle' : 'Kaynagi Düzenle' ?></h1>
                        <div class="alt-metin"><?= $kaynak_d ? h($kaynak_d['ad']) : 'Yeni bir RSS kaynagi tanimlayin' ?></div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=kaynaklar') ?>" class="buton ikincil"><?= ikon('arrow-left') ?>Geri</a>
                </div>

                <form method="post" class="panel">
                    <?= csrf_input() ?>
                    <div class="panel-bas"><h3>Kaynak Bilgileri</h3></div>
                    <div class="panel-ic">
                        <div class="form-satir">
                            <div class="form-grup">
                                <label>Kaynak Adi *</label>
                                <input type="text" name="ad" value="<?= h($kaynak_d['ad'] ?? '') ?>" required>
                                <div class="ipucu">Ornek: TRT Haber, Haberturk</div>
                            </div>
                            <div class="form-grup">
                                <label>Kaynak Atfi</label>
                                <input type="text" name="atfi_metin" value="<?= h($kaynak_d['atfi_metin'] ?? '') ?>" placeholder="Bos birakilirsa kaynak adi kullanilir">
                                <div class="ipucu">Haberlerde "Kaynak: X" olarak gosterilir (yasal)</div>
                            </div>
                        </div>
                        <div class="form-grup">
                            <label>Site URL *</label>
                            <input type="url" name="site_url" value="<?= h($kaynak_d['site_url'] ?? '') ?>" placeholder="https://www.ornek.com" required>
                        </div>
                        <div class="form-grup">
                            <label>RSS URL *</label>
                            <input type="url" name="rss_url" value="<?= h($kaynak_d['rss_url'] ?? '') ?>" placeholder="https://www.ornek.com/rss.xml" required>
                        </div>
                        <div class="form-satir-3">
                            <div class="form-grup">
                                <label>Varsayılan Kategori</label>
                                <select name="varsayilan_kategori_id">
                                    <option value="">Kategori yok</option>
                                    <?php foreach ($kategoriler as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= (isset($kaynak_d['varsayilan_kategori_id']) && $kaynak_d['varsayilan_kategori_id'] == $k['id']) ? 'selected' : '' ?>><?= h($k['ad']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="ipucu">RSS'te kategori yoksa kullanilir</div>
                            </div>
                            <div class="form-grup">
                                <label>Dil</label>
                                <select name="dil">
                                    <option value="tr" <?= ($kaynak_d['dil'] ?? 'tr') === 'tr' ? 'selected' : '' ?>>Türkçe</option>
                                    <option value="en" <?= ($kaynak_d['dil'] ?? '') === 'en' ? 'selected' : '' ?>>Ingilizce</option>
                                    <option value="ar" <?= ($kaynak_d['dil'] ?? '') === 'ar' ? 'selected' : '' ?>>Arapca</option>
                                </select>
                            </div>
                            <div class="form-grup">
                                <label>Çekim Sıklığı (dakika)</label>
                                <input type="number" name="cekim_sikligi" min="5" max="1440" value="<?= (int)($kaynak_d['cekim_sikligi'] ?? 10) ?>">
                                <div class="ipucu">Minimum: 5 dk</div>
                            </div>
                        </div>
                        <div class="form-satir">
                            <div class="form-grup">
                                <label>Max Haber Adedi (tek cekimde)</label>
                                <input type="number" name="max_haber_adet" min="5" max="200" value="<?= (int)($kaynak_d['max_haber_adet'] ?? 50) ?>">
                            </div>
                            <div class="form-grup">
                                <label>Durum</label>
                                <div style="padding-top:10px">
                                    <label class="switch">
                                        <input type="checkbox" name="aktif" <?= (empty($kaynak_d) || $kaynak_d['aktif']) ? 'checked' : '' ?>>
                                        <span class="kutu"></span>
                                        <span>Aktif (cron bu kaynagi ceker)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-grup">
                            <label>Açıklama (opsiyonel)</label>
                            <textarea name="aciklama" rows="3" placeholder="Bu kaynakla ilgili kısa not..."><?= h($kaynak_d['aciklama'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="panel-ic" style="border-top:1px solid var(--border);background:#f8fafc">
                        <div style="display:flex;justify-content:flex-end;gap:10px">
                            <a href="<?= url('yonetim.php?sayfa=kaynaklar') ?>" class="buton ikincil">İptal</a>
                            <button type="submit" class="buton"><?= ikon('save') ?><?= $islem === 'ekle' ? 'Kaynagi Ekle' : 'Degisiklikleri Kaydet' ?></button>
                        </div>
                    </div>
                </form>
                <?php else:
                    // Liste
                    $arama = trim($_GET['q'] ?? '');
                    $filtre_aktif = isset($_GET['aktif']) ? (int)$_GET['aktif'] : -1;
                    $where = []; $params = [];
                    if ($arama)               { $where[] = '(s.ad LIKE ? OR s.rss_url LIKE ?)'; $params[] = "%$arama%"; $params[] = "%$arama%"; }
                    if ($filtre_aktif !== -1) { $where[] = 's.aktif = ?'; $params[] = $filtre_aktif; }
                    $sqlw = $where ? 'WHERE ' . implode(' AND ', $where) : '';

                    $st = $db->prepare("SELECT s.*, k.ad AS kategori_ad, k.renk AS kategori_renk
                                        FROM {$prefix}sources s
                                        LEFT JOIN {$prefix}categories k ON k.id = s.varsayilan_kategori_id
                                        {$sqlw} ORDER BY s.aktif DESC, s.ad ASC");
                    $st->execute($params);
                    $kaynak_liste = $st->fetchAll();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>RSS Kaynakları</h1>
                        <div class="alt-metin">Haber toplanacak RSS beslemelerini yonetin</div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=kaynaklar&islem=ekle') ?>" class="buton"><?= ikon('plus') ?>Yeni Kaynak</a>
                </div>

                <div class="panel">
                    <div class="panel-bas">
                        <form class="filtre-cubuk" method="get">
                            <input type="hidden" name="sayfa" value="kaynaklar">
                            <input type="search" name="q" value="<?= h($arama) ?>" placeholder="Ara (ad veya URL)...">
                            <select name="aktif" onchange="this.form.submit()">
                                <option value="-1" <?= $filtre_aktif === -1 ? 'selected' : '' ?>>Tümü</option>
                                <option value="1"  <?= $filtre_aktif === 1  ? 'selected' : '' ?>>Aktif</option>
                                <option value="0"  <?= $filtre_aktif === 0  ? 'selected' : '' ?>>Pasif</option>
                            </select>
                            <button type="submit" class="buton sm">Filtrele</button>
                        </form>
                        <div class="sag"><span style="color:var(--muted);font-size:13px"><?= count($kaynak_liste) ?> kaynak</span></div>
                    </div>
                    <div class="panel-ic sikisik">
                        <div class="tablo-sarmal">
                            <table class="tablo">
                                <thead>
                                    <tr>
                                        <th>Ad</th>
                                        <th>Varsayılan Kategori</th>
                                        <th>Haber</th>
                                        <th>Son Çekim</th>
                                        <th>Durum</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($kaynak_liste)): ?>
                                    <tr><td colspan="6" class="bos">
                                        <?= $arama ? 'Aramayla eslesen kaynak bulunamadı.' : 'Henüz kaynak eklenmemis.' ?>
                                    </td></tr>
                                <?php else: foreach ($kaynak_liste as $k): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600"><?= h($k['ad']) ?></div>
                                            <div style="font-size:12px;color:var(--muted-light);font-family:'IBM Plex Mono',monospace"><?= h(kisalt($k['rss_url'], 60)) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($k['kategori_ad']): ?>
                                                <span class="renk-nokta" style="background:<?= h($k['kategori_renk']) ?>"></span><?= h($k['kategori_ad']) ?>
                                            <?php else: ?>
                                                <span style="color:var(--muted-light)">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)$k['toplam_haber'] ?></td>
                                        <td>
                                            <?php if ($k['son_cekim']): ?>
                                                <div style="font-size:13px"><?= h(goreceli_zaman($k['son_cekim'])) ?></div>
                                                <?php if ($k['son_durum'] === 'hata'): ?>
                                                    <span class="rozet hata" style="margin-top:2px">Hata</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color:var(--muted-light)">Hic cekilmedi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="rozet <?= $k['aktif'] ? 'aktif' : 'pasif' ?>"><?= $k['aktif'] ? 'Aktif' : 'Pasif' ?></span></td>
                                        <td class="islemler">
                                            <form method="post" action="<?= url('yonetim.php?sayfa=kaynaklar&islem=durum&id=' . $k['id']) ?>" style="display:inline">
                                                <?= csrf_input() ?>
                                                <button title="<?= $k['aktif'] ? 'Pasif yap' : 'Aktif yap' ?>"><?= ikon('toggle') ?></button>
                                            </form>
                                            <a href="<?= url('yonetim.php?sayfa=kaynaklar&islem=duzenle&id=' . $k['id']) ?>" title="Düzenle"><?= ikon('edit') ?></a>
                                            <form method="post" action="<?= url('yonetim.php?sayfa=kaynaklar&islem=sil&id=' . $k['id']) ?>" style="display:inline" onsubmit="return xadmin.silOnayla('Bu kaynagi silmek istediginize emin misiniz? Mevcut haberler korunur.')">
                                                <?= csrf_input() ?>
                                                <button class="sil" title="Sil"><?= ikon('trash') ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; break;

            // ====================================
            // KATEGORILER
            // ====================================
            case 'kategoriler':
                if ($islem === 'ekle' || $islem === 'duzenle'):
                    $kat_d = null;
                    if ($islem === 'duzenle' && $id > 0) {
                        $st = $db->prepare("SELECT * FROM {$prefix}categories WHERE id = ?");
                        $st->execute([$id]);
                        $kat_d = $st->fetch();
                        if (!$kat_d) { flash('Kategori bulunamadı.', 'hata'); yonlendir(url('yonetim.php?sayfa=kategoriler')); }
                    }
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1><?= $islem === 'ekle' ? 'Yeni Kategori' : 'Kategoriyi Düzenle' ?></h1>
                        <div class="alt-metin"><?= $kat_d ? h($kat_d['ad']) : 'Haberleri gruplamak için yeni bir kategori olusturun' ?></div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=kategoriler') ?>" class="buton ikincil"><?= ikon('arrow-left') ?>Geri</a>
                </div>
                <form method="post" class="panel">
                    <?= csrf_input() ?>
                    <div class="panel-bas"><h3>Kategori Bilgileri</h3></div>
                    <div class="panel-ic">
                        <div class="form-grup">
                            <label>Kategori Adi *</label>
                            <input type="text" name="ad" value="<?= h($kat_d['ad'] ?? '') ?>" required>
                            <div class="ipucu">Türkçe karakter kullanabilirsiniz, URL için otomatik ASCII slug uretilir.</div>
                        </div>
                        <div class="form-grup">
                            <label>Açıklama</label>
                            <textarea name="aciklama" rows="2"><?= h($kat_d['aciklama'] ?? '') ?></textarea>
                        </div>
                        <div class="form-satir-3">
                            <div class="form-grup">
                                <label>Renk</label>
                                <input type="color" name="renk" value="<?= h($kat_d['renk'] ?? '#c8102e') ?>">
                            </div>
                            <div class="form-grup">
                                <label>Sıra</label>
                                <input type="number" name="sira" value="<?= (int)($kat_d['sira'] ?? 0) ?>">
                                <div class="ipucu">Dusuk = onde</div>
                            </div>
                            <div class="form-grup">
                                <label>Durum</label>
                                <div style="padding-top:10px">
                                    <label class="switch">
                                        <input type="checkbox" name="aktif" <?= (empty($kat_d) || $kat_d['aktif']) ? 'checked' : '' ?>>
                                        <span class="kutu"></span>
                                        <span>Aktif</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-ic" style="border-top:1px solid var(--border);background:#f8fafc">
                        <div style="display:flex;justify-content:flex-end;gap:10px">
                            <a href="<?= url('yonetim.php?sayfa=kategoriler') ?>" class="buton ikincil">İptal</a>
                            <button type="submit" class="buton"><?= ikon('save') ?>Kaydet</button>
                        </div>
                    </div>
                </form>
                <?php else:
                    $kat_liste = $db->query("SELECT k.*, (SELECT COUNT(*) FROM {$prefix}news n WHERE n.kategori_id = k.id) AS haber_sayisi FROM {$prefix}categories k ORDER BY k.sira, k.ad")->fetchAll();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>Kategoriler</h1>
                        <div class="alt-metin">Haberleri gruplamak için kullanilan kategoriler</div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=kategoriler&islem=ekle') ?>" class="buton"><?= ikon('plus') ?>Yeni Kategori</a>
                </div>
                <div class="panel">
                    <div class="panel-ic sikisik">
                        <div class="tablo-sarmal">
                            <table class="tablo">
                                <thead>
                                    <tr><th>Ad</th><th>Slug</th><th>Haber</th><th>Sıra</th><th>Durum</th><th></th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($kat_liste as $k): ?>
                                    <tr>
                                        <td><span class="renk-nokta" style="background:<?= h($k['renk']) ?>"></span><strong><?= h($k['ad']) ?></strong></td>
                                        <td><code style="font-size:12px;color:var(--muted)"><?= h($k['slug']) ?></code></td>
                                        <td><?= (int)$k['haber_sayisi'] ?></td>
                                        <td><?= (int)$k['sira'] ?></td>
                                        <td><span class="rozet <?= $k['aktif'] ? 'aktif' : 'pasif' ?>"><?= $k['aktif'] ? 'Aktif' : 'Pasif' ?></span></td>
                                        <td class="islemler">
                                            <a href="<?= url('kategori/' . $k['slug']) ?>" target="_blank" title="Sitede gor"><?= ikon('arrow-up-right') ?></a>
                                            <a href="<?= url('yonetim.php?sayfa=kategoriler&islem=duzenle&id=' . $k['id']) ?>" title="Düzenle"><?= ikon('edit') ?></a>
                                            <?php if ($k['haber_sayisi'] == 0): ?>
                                                <form method="post" action="<?= url('yonetim.php?sayfa=kategoriler&islem=sil&id=' . $k['id']) ?>" style="display:inline" onsubmit="return xadmin.silOnayla('Bu kategoriyi silmek istediginize emin misiniz?')">
                                                    <?= csrf_input() ?>
                                                    <button class="sil" title="Sil"><?= ikon('trash') ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; break;

            // ====================================
            // HABERLER
            // ====================================
            case 'haberler':
                if ($islem === 'ekle' || $islem === 'duzenle'):
                    $haber_d = null;
                    if ($islem === 'duzenle' && $id > 0) {
                        $st = $db->prepare("SELECT * FROM {$prefix}news WHERE id = ?");
                        $st->execute([$id]);
                        $haber_d = $st->fetch();
                        if (!$haber_d) { flash('Haber bulunamadı.', 'hata'); yonlendir(url('yonetim.php?sayfa=haberler')); }
                    }
                    $kategoriler_liste = $db->query("SELECT id, ad FROM {$prefix}categories WHERE aktif = 1 ORDER BY sira, ad")->fetchAll();
                    $kaynaklar_liste   = $db->query("SELECT id, ad FROM {$prefix}sources ORDER BY ad")->fetchAll();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1><?= $islem === 'ekle' ? 'Yeni Haber' : 'Haberi Düzenle' ?></h1>
                        <div class="alt-metin"><?= $haber_d ? h(kisalt($haber_d['baslik'], 60)) : 'Manuel haber olusturun' ?></div>
                    </div>
                    <div style="display:flex;gap:8px">
                        <?php if ($haber_d): ?>
                            <a href="<?= h(haber_url($haber_d)) ?>" target="_blank" class="buton hayalet"><?= ikon('arrow-up-right') ?>Sitede Gör</a>
                        <?php endif; ?>
                        <a href="<?= url('yonetim.php?sayfa=haberler') ?>" class="buton ikincil"><?= ikon('arrow-left') ?>Geri</a>
                    </div>
                </div>
                <form method="post">
                    <?= csrf_input() ?>
                    <div class="iki-kolon">
                        <div>
                            <div class="panel">
                                <div class="panel-bas"><h3>Haber İçeriği</h3></div>
                                <div class="panel-ic">
                                    <div class="form-grup">
                                        <label>Başlık *</label>
                                        <input type="text" name="baslik" value="<?= h($haber_d['baslik'] ?? '') ?>" required maxlength="255">
                                    </div>
                                    <div class="form-grup">
                                        <label>Özet</label>
                                        <textarea name="ozet" rows="3" maxlength="500"><?= h($haber_d['ozet'] ?? '') ?></textarea>
                                        <div class="ipucu">Ana sayfa ve kategori listelerinde gosterilir.</div>
                                    </div>
                                    <div class="form-grup">
                                        <label>İçerik *</label>
                                        <textarea name="icerik" rows="14" style="font-family:'IBM Plex Mono',monospace;font-size:13px"><?= h($haber_d['icerik'] ?? '') ?></textarea>
                                        <div class="ipucu">HTML destekli. Paragraflari &lt;p&gt; ile sarmalayin (3. paragraftan sonra otomatik reklam enjekte edilir).</div>
                                    </div>
                                    <div class="form-satir">
                                        <div class="form-grup">
                                            <label>Kapak Gorseli (URL)</label>
                                            <input type="url" name="resim" value="<?= h($haber_d['resim'] ?? '') ?>" placeholder="https://...">
                                        </div>
                                        <div class="form-grup">
                                            <label>Görsel Alt Metni</label>
                                            <input type="text" name="resim_alt" value="<?= h($haber_d['resim_alt'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-bas"><h3>SEO</h3></div>
                                <div class="panel-ic">
                                    <div class="form-grup">
                                        <label>SEO Başlık</label>
                                        <input type="text" name="seo_baslik" value="<?= h($haber_d['seo_baslik'] ?? '') ?>" maxlength="200">
                                        <div class="ipucu">Bos birakilirsa haber basligi kullanilir.</div>
                                    </div>
                                    <div class="form-grup">
                                        <label>SEO Açıklama</label>
                                        <textarea name="seo_aciklama" rows="2" maxlength="300"><?= h($haber_d['seo_aciklama'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="panel">
                                <div class="panel-bas"><h3>Yayınlama</h3></div>
                                <div class="panel-ic">
                                    <div class="form-grup">
                                        <label>Durum</label>
                                        <select name="durum">
                                            <?php foreach (['yayinda'=>'Yayında','taslak'=>'Taslak','beklemede'=>'Beklemede','arsiv'=>'Arsiv'] as $d => $l): ?>
                                                <option value="<?= $d ?>" <?= ($haber_d['durum'] ?? 'yayinda') === $d ? 'selected' : '' ?>><?= $l ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-grup">
                                        <label>Yayın Tarihi</label>
                                        <input type="datetime-local" name="yayin_tarihi" value="<?= h(date('Y-m-d\TH:i', strtotime($haber_d['yayin_tarihi'] ?? 'now'))) ?>">
                                    </div>
                                    <div class="form-grup" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                                        <label style="margin-bottom:10px">Özel Isaretler</label>
                                        <div style="display:flex;flex-direction:column;gap:10px">
                                            <label class="switch"><input type="checkbox" name="manset" <?= !empty($haber_d['manset']) ? 'checked' : '' ?>><span class="kutu"></span><span>Manset</span></label>
                                            <label class="switch"><input type="checkbox" name="one_cikan" <?= !empty($haber_d['one_cikan']) ? 'checked' : '' ?>><span class="kutu"></span><span>One Cikan</span></label>
                                            <label class="switch"><input type="checkbox" name="son_dakika" <?= !empty($haber_d['son_dakika']) ? 'checked' : '' ?>><span class="kutu"></span><span>Son Dakika</span></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="panel">
                                <div class="panel-bas"><h3>Kategori ve Kaynak</h3></div>
                                <div class="panel-ic">
                                    <div class="form-grup">
                                        <label>Kategori *</label>
                                        <select name="kategori_id" required>
                                            <option value="">Seçin...</option>
                                            <?php foreach ($kategoriler_liste as $k): ?>
                                                <option value="<?= $k['id'] ?>" <?= ($haber_d['kategori_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>><?= h($k['ad']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-grup">
                                        <label>Kaynak</label>
                                        <select name="kaynak_id">
                                            <option value="">Manuel (kaynak yok)</option>
                                            <?php foreach ($kaynaklar_liste as $kay): ?>
                                                <option value="<?= $kay['id'] ?>" <?= ($haber_d['kaynak_id'] ?? 0) == $kay['id'] ? 'selected' : '' ?>><?= h($kay['ad']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-grup">
                                        <label>Yazar</label>
                                        <input type="text" name="yazar" value="<?= h($haber_d['yazar'] ?? '') ?>">
                                    </div>
                                    <div class="form-grup">
                                        <label>Orijinal URL</label>
                                        <input type="url" name="orijinal_url" value="<?= h($haber_d['orijinal_url'] ?? '') ?>">
                                        <div class="ipucu">Kaynak atfi ile birlikte gosterilir.</div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="buton" style="width:100%;padding:12px"><?= ikon('save') ?>Kaydet</button>
                        </div>
                    </div>
                </form>
                <?php else:
                    // Haber listesi
                    $arama    = trim($_GET['q'] ?? '');
                    $f_kat    = (int)($_GET['kategori'] ?? 0);
                    $f_durum  = $_GET['durum'] ?? '';
                    $f_kaynak = (int)($_GET['kaynak'] ?? 0);
                    $sayfa_no_l = max(1, (int)($_GET['p'] ?? 1));
                    $limit_l    = 25;
                    $offset_l   = ($sayfa_no_l - 1) * $limit_l;

                    $where = []; $params = [];
                    if ($arama)    { $where[] = 'n.baslik LIKE ?'; $params[] = "%$arama%"; }
                    if ($f_kat)    { $where[] = 'n.kategori_id = ?'; $params[] = $f_kat; }
                    if ($f_durum && in_array($f_durum, ['yayinda','taslak','arsiv','beklemede'], true)) { $where[] = 'n.durum = ?'; $params[] = $f_durum; }
                    if ($f_kaynak) { $where[] = 'n.kaynak_id = ?'; $params[] = $f_kaynak; }
                    $sqlw = $where ? 'WHERE ' . implode(' AND ', $where) : '';

                    $c = $db->prepare("SELECT COUNT(*) FROM {$prefix}news n {$sqlw}");
                    $c->execute($params);
                    $toplam_h = (int)$c->fetchColumn();
                    $toplam_sayfa = max(1, (int)ceil($toplam_h / $limit_l));

                    $st = $db->prepare("SELECT n.id, n.baslik, n.slug, n.durum, n.manset, n.one_cikan, n.son_dakika, n.yayin_tarihi, n.okunma,
                                               k.ad AS kategori_ad, k.renk AS kategori_renk, s.ad AS kaynak_ad
                                        FROM {$prefix}news n
                                        LEFT JOIN {$prefix}categories k ON k.id = n.kategori_id
                                        LEFT JOIN {$prefix}sources s ON s.id = n.kaynak_id
                                        {$sqlw} ORDER BY n.yayin_tarihi DESC LIMIT {$limit_l} OFFSET {$offset_l}");
                    $st->execute($params);
                    $haber_liste = $st->fetchAll();
                    $kategoriler_liste = $db->query("SELECT id, ad FROM {$prefix}categories ORDER BY sira, ad")->fetchAll();
                    $kaynaklar_liste   = $db->query("SELECT id, ad FROM {$prefix}sources ORDER BY ad")->fetchAll();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>Haberler</h1>
                        <div class="alt-metin"><?= $toplam_h ?> haber</div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=haberler&islem=ekle') ?>" class="buton"><?= ikon('plus') ?>Yeni Haber</a>
                </div>

                <div class="panel">
                    <div class="panel-bas">
                        <form class="filtre-cubuk" method="get">
                            <input type="hidden" name="sayfa" value="haberler">
                            <input type="search" name="q" value="<?= h($arama) ?>" placeholder="Başlık ara...">
                            <select name="kategori" onchange="this.form.submit()">
                                <option value="0">Tüm kategoriler</option>
                                <?php foreach ($kategoriler_liste as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= $f_kat == $k['id'] ? 'selected' : '' ?>><?= h($k['ad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="durum" onchange="this.form.submit()">
                                <option value="">Tüm durumlar</option>
                                <?php foreach (['yayinda'=>'Yayında','taslak'=>'Taslak','beklemede'=>'Beklemede','arsiv'=>'Arsiv'] as $d => $l): ?>
                                    <option value="<?= $d ?>" <?= $f_durum === $d ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="kaynak" onchange="this.form.submit()">
                                <option value="0">Tüm kaynaklar</option>
                                <?php foreach ($kaynaklar_liste as $kay): ?>
                                    <option value="<?= $kay['id'] ?>" <?= $f_kaynak == $kay['id'] ? 'selected' : '' ?>><?= h($kay['ad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="buton sm">Filtrele</button>
                        </form>
                    </div>

                    <form method="post" action="<?= url('yonetim.php?sayfa=haberler&islem=toplu') ?>">
                        <?= csrf_input() ?>
                        <div class="panel-ic sikisik">
                            <div class="tablo-sarmal">
                                <table class="tablo">
                                    <thead>
                                        <tr>
                                            <th style="width:30px"><input type="checkbox" onchange="this.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach(c=>c.checked=this.checked)"></th>
                                            <th>Başlık</th>
                                            <th>Kategori</th>
                                            <th>Durum</th>
                                            <th>Yayın</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($haber_liste)): ?>
                                        <tr><td colspan="6" class="bos">Haber bulunamadı. <a href="<?= url('yonetim.php?sayfa=haberler&islem=ekle') ?>" style="color:var(--brand)">Yeni ekle</a> veya RSS cron calissin (Asama 4).</td></tr>
                                    <?php else: foreach ($haber_liste as $h): ?>
                                        <tr>
                                            <td><input type="checkbox" name="secili[]" value="<?= $h['id'] ?>"></td>
                                            <td>
                                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px">
                                                    <?php if ($h['manset']): ?><span class="rozet aktif" style="background:#fee2e2;color:#991b1b">M</span><?php endif; ?>
                                                    <?php if ($h['one_cikan']): ?><span class="rozet aktif" style="background:#fef3c7;color:#78350f">OC</span><?php endif; ?>
                                                    <?php if ($h['son_dakika']): ?><span class="rozet hata">SD</span><?php endif; ?>
                                                    <strong style="font-size:14px"><?= h(kisalt($h['baslik'], 80)) ?></strong>
                                                </div>
                                                <div style="font-size:12px;color:var(--muted-light)"><?= h($h['kaynak_ad'] ?? 'Manuel') ?> · <?= (int)$h['okunma'] ?> okuma</div>
                                            </td>
                                            <td><?php if ($h['kategori_ad']): ?><span class="renk-nokta" style="background:<?= h($h['kategori_renk']) ?>"></span><?= h($h['kategori_ad']) ?><?php endif; ?></td>
                                            <td><span class="rozet <?= $h['durum'] === 'yayinda' ? 'aktif' : ($h['durum'] === 'taslak' ? 'taslak' : 'pasif') ?>"><?= h($h['durum']) ?></span></td>
                                            <td style="font-size:12px;color:var(--muted)"><?= h(goreceli_zaman($h['yayin_tarihi'])) ?></td>
                                            <td class="islemler">
                                                <a href="<?= h(haber_url($h)) ?>" target="_blank" title="Sitede gor"><?= ikon('arrow-up-right') ?></a>
                                                <a href="<?= url('yonetim.php?sayfa=haberler&islem=duzenle&id=' . $h['id']) ?>" title="Düzenle"><?= ikon('edit') ?></a>
                                                <form method="post" action="<?= url('yonetim.php?sayfa=haberler&islem=sil&id=' . $h['id']) ?>" style="display:inline" onsubmit="return xadmin.silOnayla('Bu haberi silmek istediginize emin misiniz?')">
                                                    <?= csrf_input() ?>
                                                    <button class="sil" title="Sil"><?= ikon('trash') ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if (!empty($haber_liste)): ?>
                        <div class="admin-sayfalama">
                            <div style="display:flex;gap:10px;align-items:center">
                                <select name="toplu_eylem" required style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px">
                                    <option value="">Toplu islem...</option>
                                    <option value="yayinda">Yayında yap</option>
                                    <option value="taslak">Taslaga al</option>
                                    <option value="arsiv">Arsivle</option>
                                    <option value="sil">Sil</option>
                                </select>
                                <button type="submit" class="buton sm" onclick="return confirm('Seçili haberlere bu islemi uygulamak istediginize emin misiniz?')">Uygula</button>
                            </div>
                            <?php if ($toplam_sayfa > 1): ?>
                            <div class="linkler">
                                <?php for ($p = max(1, $sayfa_no_l - 2); $p <= min($toplam_sayfa, $sayfa_no_l + 2); $p++):
                                    $qs = $_GET; $qs['p'] = $p; ?>
                                    <a href="?<?= http_build_query($qs) ?>" class="<?= $p == $sayfa_no_l ? 'aktif' : '' ?>"><?= $p ?></a>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                <?php endif; break;

            // ====================================
            // REKLAMLAR
            // ====================================
            case 'reklamlar':
                $konum_adlari = [
                    'ust_banner'   => 'Üst Banner (970×90)',
                    'sidebar_ust'  => 'Sidebar Üst (300×250)',
                    'sidebar_alt'  => 'Sidebar Alt (300×600)',
                    'makale_ust'   => 'Makale Üst (728×90)',
                    'makale_ic'    => 'Makale Ici (3. paragrafa enjekte)',
                    'makale_alt'   => 'Makale Alt (728×90)',
                    'alt_banner'   => 'Alt Banner (970×90)',
                    'mobil_sabit'  => 'Mobil Sabit (320×50)',
                    'popup'        => 'Popup',
                ];
                if ($islem === 'ekle' || $islem === 'duzenle'):
                    $reklam_d = null;
                    if ($islem === 'duzenle' && $id > 0) {
                        $st = $db->prepare("SELECT * FROM {$prefix}ads WHERE id = ?");
                        $st->execute([$id]);
                        $reklam_d = $st->fetch();
                        if (!$reklam_d) { flash('Reklam bulunamadı.', 'hata'); yonlendir(url('yonetim.php?sayfa=reklamlar')); }
                    }
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1><?= $islem === 'ekle' ? 'Yeni Reklam' : 'Reklami Düzenle' ?></h1>
                        <div class="alt-metin"><?= $reklam_d ? h($reklam_d['ad']) : 'Site icinde reklam slotu tanimla' ?></div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=reklamlar') ?>" class="buton ikincil"><?= ikon('arrow-left') ?>Geri</a>
                </div>
                <form method="post" class="panel">
                    <?= csrf_input() ?>
                    <div class="panel-bas"><h3>Reklam Bilgileri</h3></div>
                    <div class="panel-ic">
                        <div class="form-satir">
                            <div class="form-grup">
                                <label>Reklam Adi *</label>
                                <input type="text" name="ad" value="<?= h($reklam_d['ad'] ?? '') ?>" required>
                                <div class="ipucu">Sadece panel icinde gorunur</div>
                            </div>
                            <div class="form-grup">
                                <label>Konum *</label>
                                <select name="konum" required>
                                    <option value="">Seçin...</option>
                                    <?php foreach ($konum_adlari as $k => $ad): ?>
                                        <option value="<?= $k ?>" <?= ($reklam_d['konum'] ?? '') === $k ? 'selected' : '' ?>><?= h($ad) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-grup">
                            <label>Reklam Tipi *</label>
                            <select name="tip" id="reklam-tip" onchange="document.querySelectorAll('[data-tip]').forEach(el => el.style.display = el.dataset.tip === this.value ? 'block' : 'none')">
                                <option value="gorsel"  <?= ($reklam_d['tip'] ?? 'gorsel') === 'gorsel'  ? 'selected' : '' ?>>Görsel (URL)</option>
                                <option value="kod"     <?= ($reklam_d['tip'] ?? '') === 'kod'     ? 'selected' : '' ?>>HTML/JavaScript Kodu</option>
                                <option value="adsense" <?= ($reklam_d['tip'] ?? '') === 'adsense' ? 'selected' : '' ?>>Google AdSense</option>
                            </select>
                        </div>
                        <div data-tip="gorsel" style="display:<?= ($reklam_d['tip'] ?? 'gorsel') === 'gorsel' ? 'block' : 'none' ?>">
                            <div class="form-satir">
                                <div class="form-grup">
                                    <label>Görsel URL</label>
                                    <input type="url" name="gorsel" value="<?= h($reklam_d['gorsel'] ?? '') ?>" placeholder="https://...">
                                </div>
                                <div class="form-grup">
                                    <label>Hedef URL (tiklanince)</label>
                                    <input type="url" name="hedef_url" value="<?= h($reklam_d['hedef_url'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-satir">
                                <div class="form-grup">
                                    <label>Genislik (px)</label>
                                    <input type="number" name="genislik" value="<?= (int)($reklam_d['genislik'] ?? 0) ?: '' ?>">
                                </div>
                                <div class="form-grup">
                                    <label>Yukseklik (px)</label>
                                    <input type="number" name="yukseklik" value="<?= (int)($reklam_d['yukseklik'] ?? 0) ?: '' ?>">
                                </div>
                            </div>
                        </div>
                        <div data-tip="kod" style="display:<?= ($reklam_d['tip'] ?? '') === 'kod' ? 'block' : 'none' ?>">
                            <div class="form-grup">
                                <label>HTML/JavaScript Kodu</label>
                                <textarea name="kod" rows="8" style="font-family:'IBM Plex Mono',monospace;font-size:12px"><?= h($reklam_d['kod'] ?? '') ?></textarea>
                                <div class="ipucu">Ham HTML/script - konuma aynen yapistirilir.</div>
                            </div>
                        </div>
                        <div data-tip="adsense" style="display:<?= ($reklam_d['tip'] ?? '') === 'adsense' ? 'block' : 'none' ?>">
                            <div class="form-grup">
                                <label>AdSense Kodu</label>
                                <textarea name="kod" rows="8" style="font-family:'IBM Plex Mono',monospace;font-size:12px" placeholder="<script async src=&quot;https://pagead2...&quot;></script>..."><?= h($reklam_d['kod'] ?? '') ?></textarea>
                                <div class="ipucu">AdSense panelinden kopyalayin.</div>
                            </div>
                        </div>
                        <div class="form-satir-3">
                            <div class="form-grup">
                                <label>Başlangıç Tarihi</label>
                                <input type="date" name="baslangic" value="<?= h($reklam_d['baslangic'] ?? '') ?>">
                                <div class="ipucu">Bos birakilirsa hemen baslar</div>
                            </div>
                            <div class="form-grup">
                                <label>Bitiş Tarihi</label>
                                <input type="date" name="bitis" value="<?= h($reklam_d['bitis'] ?? '') ?>">
                            </div>
                            <div class="form-grup">
                                <label>Durum</label>
                                <div style="padding-top:10px">
                                    <label class="switch">
                                        <input type="checkbox" name="aktif" <?= (empty($reklam_d) || $reklam_d['aktif']) ? 'checked' : '' ?>>
                                        <span class="kutu"></span><span>Aktif</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-ic" style="border-top:1px solid var(--border);background:#f8fafc">
                        <div style="display:flex;justify-content:flex-end;gap:10px">
                            <a href="<?= url('yonetim.php?sayfa=reklamlar') ?>" class="buton ikincil">İptal</a>
                            <button type="submit" class="buton"><?= ikon('save') ?>Kaydet</button>
                        </div>
                    </div>
                </form>
                <?php else:
                    $reklam_liste = $db->query("SELECT * FROM {$prefix}ads ORDER BY konum, ad")->fetchAll();

                    // AdSense entegrasyon durumu
                    $as_id_r = trim(ayar('adsense_client_id', ''));
                    $as_valid_r = !empty($as_id_r) && preg_match('/^ca-pub-\d{10,20}$/', $as_id_r);
                    $as_auto_r = ayar('adsense_auto_ads', '0') === '1';
                    $as_adet_r = (int)$db->query("SELECT COUNT(*) FROM {$prefix}ads WHERE tip IN('adsense','kod') AND aktif=1")->fetchColumn();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>Reklamlar</h1>
                        <div class="alt-metin">9 farkli konumda reklam slotu yonet</div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=reklamlar&islem=ekle') ?>" class="buton"><?= ikon('plus') ?>Yeni Reklam</a>
                </div>

                <!-- AdSense Hızlı Kurulum Paneli -->
                <div class="panel" style="margin-bottom:18px;background:<?= $as_valid_r ? 'linear-gradient(135deg,#ecfdf5,#fff)' : 'linear-gradient(135deg,#fef3c7,#fff)' ?>;border-left:4px solid <?= $as_valid_r ? '#10b981' : '#f59e0b' ?>">
                    <div class="panel-ic">
                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:14px">
                            <div style="font-size:36px"><?= $as_valid_r ? '💰' : '⚙️' ?></div>
                            <div style="flex:1;min-width:240px">
                                <h3 style="margin:0 0 4px;color:<?= $as_valid_r ? '#065f46' : '#92400e' ?>">
                                    Google AdSense <?= $as_valid_r ? 'Aktif ✓' : 'Yapılandırılmadı' ?>
                                </h3>
                                <div style="font-size:13px;color:var(--muted)">
                                    <?php if ($as_valid_r): ?>
                                        Client: <code><?= h($as_id_r) ?></code>
                                        · Auto Ads: <strong style="color:<?= $as_auto_r ? '#10b981' : '#94a3b8' ?>"><?= $as_auto_r ? 'Açık' : 'Kapalı' ?></strong>
                                        · <?= $as_adet_r ?> manuel reklam aktif
                                    <?php else: ?>
                                        Önce AdSense Publisher ID'nizi girin (<code>ca-pub-xxxxxxxxxx</code>)
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <?php if (!$as_valid_r): ?>
                                <a href="<?= url('yonetim.php?sayfa=ayarlar&grup=reklam') ?>" class="buton">AdSense Ayarla</a>
                                <?php endif; ?>
                                <a href="<?= url('yonetim.php?sayfa=adsense-rehber') ?>" class="buton ikincil">📚 Kurulum Rehberi</a>
                            </div>
                        </div>

                        <?php if ($as_valid_r): ?>
                        <div style="padding:12px 14px;background:#fff;border-radius:8px;border:1px dashed #cbd5e1;font-size:13px;line-height:1.6">
                            <strong>🎯 AdSense Reklam Birimi Ekleme:</strong>
                            <ol style="margin:6px 0 0;padding-left:22px">
                                <li><a href="https://www.google.com/adsense/new/u/0/pub-<?= h(preg_replace('/^ca-pub-/', '', $as_id_r)) ?>/myads/units" target="_blank">AdSense → Reklamlar → Reklam Birimleri</a> bölümünden yeni birim oluştur.</li>
                                <li>Birimin HTML kodunu kopyala (<code>&lt;script async...&gt;&lt;ins class="adsbygoogle"...&gt;</code> ile başlayan blok).</li>
                                <li>Aşağıdaki <strong>"Yeni Reklam"</strong> butonuna bas, <strong>Tip: Google AdSense</strong> seç, kodu yapıştır, konum belirle, kaydet.</li>
                            </ol>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-ic sikisik">
                        <div class="tablo-sarmal">
                            <table class="tablo">
                                <thead>
                                    <tr><th>Ad</th><th>Konum</th><th>Tip</th><th>Gösterim</th><th>Tıklanma</th><th>Durum</th><th></th></tr>
                                </thead>
                                <tbody>
                                <?php if (empty($reklam_liste)): ?>
                                    <tr><td colspan="7" class="bos">Henüz reklam eklenmemis.</td></tr>
                                <?php else: foreach ($reklam_liste as $r): ?>
                                    <tr>
                                        <td><strong><?= h($r['ad']) ?></strong></td>
                                        <td><?= h($konum_adlari[$r['konum']] ?? $r['konum']) ?></td>
                                        <td><?= strtoupper(h($r['tip'])) ?></td>
                                        <td><?= number_format((int)$r['gosterim'], 0, ',', '.') ?></td>
                                        <td><?= number_format((int)$r['tiklanma'], 0, ',', '.') ?></td>
                                        <td><span class="rozet <?= $r['aktif'] ? 'aktif' : 'pasif' ?>"><?= $r['aktif'] ? 'Aktif' : 'Pasif' ?></span></td>
                                        <td class="islemler">
                                            <a href="<?= url('yonetim.php?sayfa=reklamlar&islem=duzenle&id=' . $r['id']) ?>" title="Düzenle"><?= ikon('edit') ?></a>
                                            <form method="post" action="<?= url('yonetim.php?sayfa=reklamlar&islem=sil&id=' . $r['id']) ?>" style="display:inline" onsubmit="return xadmin.silOnayla('Bu reklami silmek istediginize emin misiniz?')">
                                                <?= csrf_input() ?><button class="sil" title="Sil"><?= ikon('trash') ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; break;

            // ====================================
            // KULLANICILAR (sadece admin)
            // ====================================
            case 'kullanicilar':
                if ($yonetici['rol'] !== 'admin') {
                    echo '<div class="alert alert-hata">Bu bolume sadece admin yetkili kullanicilar erisebilir.</div>';
                    break;
                }
                if ($islem === 'ekle' || $islem === 'duzenle'):
                    $usr_d = null;
                    if ($islem === 'duzenle' && $id > 0) {
                        $st = $db->prepare("SELECT * FROM {$prefix}users WHERE id = ?");
                        $st->execute([$id]);
                        $usr_d = $st->fetch();
                        if (!$usr_d) { flash('Kullanıcı bulunamadı.', 'hata'); yonlendir(url('yonetim.php?sayfa=kullanicilar')); }
                    }
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1><?= $islem === 'ekle' ? 'Yeni Kullanıcı' : 'Kullaniciyi Düzenle' ?></h1>
                        <div class="alt-metin"><?= $usr_d ? h($usr_d['kullanici_adi']) : 'Yönetim paneline erisecek bir hesap olustur' ?></div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=kullanicilar') ?>" class="buton ikincil"><?= ikon('arrow-left') ?>Geri</a>
                </div>
                <form method="post" class="panel">
                    <?= csrf_input() ?>
                    <div class="panel-bas"><h3>Kullanıcı Bilgileri</h3></div>
                    <div class="panel-ic">
                        <div class="form-satir">
                            <div class="form-grup">
                                <label>Kullanıcı Adi *</label>
                                <input type="text" name="kullanici_adi" value="<?= h($usr_d['kullanici_adi'] ?? '') ?>" minlength="4" required>
                            </div>
                            <div class="form-grup">
                                <label>E-posta *</label>
                                <input type="email" name="eposta" value="<?= h($usr_d['eposta'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="form-grup">
                            <label>Ad Soyad *</label>
                            <input type="text" name="ad_soyad" value="<?= h($usr_d['ad_soyad'] ?? '') ?>" required>
                        </div>
                        <div class="form-satir">
                            <div class="form-grup">
                                <label>Rol *</label>
                                <select name="rol" <?= ($usr_d && $usr_d['id'] == $yonetici['id']) ? 'disabled' : '' ?>>
                                    <optgroup label="🔴 Yönetim Kadrosu">
                                        <option value="admin"                  <?= ($usr_d['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin / İmtiyaz Sahibi</option>
                                        <option value="yonetici"               <?= ($usr_d['rol'] ?? '') === 'yonetici' ? 'selected' : '' ?>>Yönetici / İşletme Müdürü</option>
                                        <option value="genel_yayin_yonetmeni"  <?= ($usr_d['rol'] ?? '') === 'genel_yayin_yonetmeni' ? 'selected' : '' ?>>Genel Yayın Yönetmeni</option>
                                        <option value="yazi_isleri_muduru"     <?= ($usr_d['rol'] ?? '') === 'yazi_isleri_muduru' ? 'selected' : '' ?>>Yazı İşleri Müdürü</option>
                                        <option value="sorumlu_mudur"          <?= ($usr_d['rol'] ?? '') === 'sorumlu_mudur' ? 'selected' : '' ?>>Sorumlu Müdür</option>
                                    </optgroup>
                                    <optgroup label="📝 Editörlük">
                                        <option value="editor"                 <?= ($usr_d['rol'] ?? 'editor') === 'editor' ? 'selected' : '' ?>>Editör</option>
                                        <option value="haber_sorumlusu"        <?= ($usr_d['rol'] ?? '') === 'haber_sorumlusu' ? 'selected' : '' ?>>Haber Sorumlusu</option>
                                        <option value="fotograf_editoru"       <?= ($usr_d['rol'] ?? '') === 'fotograf_editoru' ? 'selected' : '' ?>>Fotoğraf Editörü</option>
                                    </optgroup>
                                    <optgroup label="🖊 Muhabir / Yazar">
                                        <option value="haber_muhabiri"         <?= ($usr_d['rol'] ?? '') === 'haber_muhabiri' ? 'selected' : '' ?>>Haber Muhabiri</option>
                                        <option value="muhabir"                <?= ($usr_d['rol'] ?? '') === 'muhabir' ? 'selected' : '' ?>>Muhabir</option>
                                        <option value="yazar"                  <?= ($usr_d['rol'] ?? '') === 'yazar' ? 'selected' : '' ?>>Köşe Yazarı</option>
                                        <option value="stajyer"                <?= ($usr_d['rol'] ?? '') === 'stajyer' ? 'selected' : '' ?>>Stajyer</option>
                                    </optgroup>
                                </select>
                                <?php if ($usr_d && $usr_d['id'] == $yonetici['id']): ?>
                                    <input type="hidden" name="rol" value="admin">
                                    <div class="ipucu">Kendi rolunuzu degistiremezsiniz.</div>
                                <?php endif; ?>
                            </div>
                            <div class="form-grup">
                                <label>Şifre <?= $islem === 'ekle' ? '*' : '(degistirmek için)' ?></label>
                                <input type="password" name="sifre" <?= $islem === 'ekle' ? 'required minlength="8"' : 'minlength="8"' ?> placeholder="<?= $islem === 'duzenle' ? 'Bos birakirsaniz degismez' : 'En az 8 karakter' ?>">
                            </div>
                        </div>
                        <div class="form-satir">
                            <div class="form-grup">
                                <label>Telefon <small style="color:var(--muted)">(opsiyonel)</small></label>
                                <input type="tel" name="telefon" value="<?= h($usr_d['telefon'] ?? '') ?>" placeholder="0 532 123 45 67">
                            </div>
                            <div class="form-grup">
                                <label>Görev Tanımı / Uzmanlık <small style="color:var(--muted)">(künyede gösterilir)</small></label>
                                <input type="text" name="gorev_tanimi" value="<?= h($usr_d['gorev_tanimi'] ?? '') ?>" placeholder="Siyaset Editörü, Spor Muhabiri, vb." maxlength="200">
                            </div>
                        </div>
                        <div class="form-grup">
                            <label class="switch">
                                <input type="checkbox" name="durum" <?= (empty($usr_d) || $usr_d['durum']) ? 'checked' : '' ?>>
                                <span class="kutu"></span><span>Aktif (hesap girise izinli)</span>
                            </label>
                        </div>
                    </div>
                    <div class="panel-ic" style="border-top:1px solid var(--border);background:#f8fafc">
                        <div style="display:flex;justify-content:flex-end;gap:10px">
                            <a href="<?= url('yonetim.php?sayfa=kullanicilar') ?>" class="buton ikincil">İptal</a>
                            <button type="submit" class="buton"><?= ikon('save') ?>Kaydet</button>
                        </div>
                    </div>
                </form>
                <?php else:
                    $usr_liste = $db->query("SELECT * FROM {$prefix}users ORDER BY
                        FIELD(rol,'admin','yonetici','genel_yayin_yonetmeni','yazi_isleri_muduru','sorumlu_mudur','editor','haber_sorumlusu','fotograf_editoru','haber_muhabiri','muhabir','yazar','stajyer'),
                        kullanici_adi")->fetchAll();
                    $rol_adlari = [
                        'admin' => 'Admin / İmtiyaz Sahibi',
                        'yonetici' => 'Yönetici',
                        'genel_yayin_yonetmeni' => 'Genel Yayın Yönetmeni',
                        'yazi_isleri_muduru' => 'Yazı İşleri Müdürü',
                        'sorumlu_mudur' => 'Sorumlu Müdür',
                        'editor' => 'Editör',
                        'haber_sorumlusu' => 'Haber Sorumlusu',
                        'fotograf_editoru' => 'Fotoğraf Editörü',
                        'haber_muhabiri' => 'Haber Muhabiri',
                        'muhabir' => 'Muhabir',
                        'yazar' => 'Köşe Yazarı',
                        'stajyer' => 'Stajyer',
                    ];
                    $rol_renkleri = [
                        'admin' => '#dc2626', 'yonetici' => '#b91c1c',
                        'genel_yayin_yonetmeni' => '#7c2d12', 'yazi_isleri_muduru' => '#9a3412', 'sorumlu_mudur' => '#c2410c',
                        'editor' => '#2563eb', 'haber_sorumlusu' => '#1d4ed8', 'fotograf_editoru' => '#7c3aed',
                        'haber_muhabiri' => '#0891b2', 'muhabir' => '#0e7490',
                        'yazar' => '#6b21a8', 'stajyer' => '#6b7280',
                    ];
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>Kullanıcılar</h1>
                        <div class="alt-metin"><?= count($usr_liste) ?> kullanıcı · Gazete künyesindeki yayın kadrosu buradan yönetilir</div>
                    </div>
                    <a href="<?= url('yonetim.php?sayfa=kullanicilar&islem=ekle') ?>" class="buton"><?= ikon('plus') ?>Yeni Kullanıcı</a>
                </div>
                <div class="panel">
                    <div class="panel-ic sikisik">
                        <div class="tablo-sarmal">
                            <table class="tablo">
                                <thead>
                                    <tr><th>Kullanıcı</th><th>Rol / Görev</th><th>İletişim</th><th>Son Giriş</th><th>Durum</th><th></th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($usr_liste as $u):
                                    $av = mb_strtoupper(mb_substr($u['ad_soyad'] ?: $u['kullanici_adi'], 0, 1, 'UTF-8'), 'UTF-8');
                                    $rol_ad = $rol_adlari[$u['rol']] ?? $u['rol'];
                                    $rol_rnk = $rol_renkleri[$u['rol']] ?? '#64748b';
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex;gap:10px;align-items:center">
                                                <div class="avatar" style="width:36px;height:36px;font-size:14px;background:<?= $rol_rnk ?>;color:#fff"><?= h($av) ?></div>
                                                <div>
                                                    <strong><?= h($u['ad_soyad'] ?: $u['kullanici_adi']) ?></strong>
                                                    <div style="font-size:12px;color:var(--muted)">@<?= h($u['kullanici_adi']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background:<?= $rol_rnk ?>;color:#fff;padding:3px 10px;border-radius:30px;font-size:11px;font-weight:600;letter-spacing:.02em"><?= h($rol_ad) ?></span>
                                            <?php if (!empty($u['gorev_tanimi'])): ?>
                                                <div style="font-size:12px;color:var(--muted);margin-top:3px"><?= h($u['gorev_tanimi']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:12px">
                                            <?= h($u['eposta']) ?>
                                            <?php if (!empty($u['telefon'])): ?><br><span style="color:var(--muted)"><?= h($u['telefon']) ?></span><?php endif; ?>
                                        </td>
                                        <td style="font-size:12px;color:var(--muted)"><?= $u['son_giris'] ? h(goreceli_zaman($u['son_giris'])) : 'Hic giris yapmadi' ?></td>
                                        <td><span class="rozet <?= $u['durum'] ? 'aktif' : 'pasif' ?>"><?= $u['durum'] ? 'Aktif' : 'Pasif' ?></span></td>
                                        <td class="islemler">
                                            <a href="<?= url('yonetim.php?sayfa=kullanicilar&islem=duzenle&id=' . $u['id']) ?>" title="Düzenle"><?= ikon('edit') ?></a>
                                            <?php if ($u['id'] != $yonetici['id']): ?>
                                                <form method="post" action="<?= url('yonetim.php?sayfa=kullanicilar&islem=sil&id=' . $u['id']) ?>" style="display:inline" onsubmit="return xadmin.silOnayla('Bu kullaniciyi silmek istediginize emin misiniz?')">
                                                    <?= csrf_input() ?><button class="sil" title="Sil"><?= ikon('trash') ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; break;

            // ====================================
            // AYARLAR (grup bazli)
            // ====================================
            case 'ayarlar':
                $grup = $_GET['grup'] ?? 'genel';

                // Bozuk karakter temizlik aracı
                if ($grup === 'araclar' && ($_POST['islem'] ?? '') === 'karakter_temizle') {
                    // CSRF
                    if (csrf_dogrula($_POST['_csrf'] ?? '')) {
                        $guncellendi = 0;
                        $toplam = (int)$db->query("SELECT COUNT(*) FROM {$prefix}news")->fetchColumn();
                        // Tüm haberleri sayfalı oku, temizle, güncelle
                        $offset = 0;
                        $limit  = 500;
                        while ($offset < $toplam) {
                            $st = $db->prepare("SELECT id, baslik, ozet, icerik, yazar FROM {$prefix}news LIMIT ? OFFSET ?");
                            $st->bindValue(1, $limit, PDO::PARAM_INT);
                            $st->bindValue(2, $offset, PDO::PARAM_INT);
                            $st->execute();
                            $haberler = $st->fetchAll();
                            foreach ($haberler as $hb) {
                                $yb = temizle_metin($hb['baslik']);
                                $yo = temizle_metin($hb['ozet'] ?? '');
                                $yi = temizle_metin($hb['icerik'] ?? '');
                                $yy = temizle_metin($hb['yazar'] ?? '');
                                if ($yb !== $hb['baslik'] || $yo !== ($hb['ozet'] ?? '')
                                    || $yi !== ($hb['icerik'] ?? '') || $yy !== ($hb['yazar'] ?? '')) {
                                    $up = $db->prepare("UPDATE {$prefix}news SET baslik=?, ozet=?, icerik=?, yazar=? WHERE id=?");
                                    $up->execute([$yb, $yo, $yi, $yy, $hb['id']]);
                                    $guncellendi++;
                                }
                            }
                            $offset += $limit;
                        }
                        $_SESSION['flash'] = "Temizlik tamamlandı. {$toplam} haberden {$guncellendi} tanesi güncellendi.";
                    }
                    header('Location: ' . url('yonetim.php?sayfa=ayarlar&grup=araclar'));
                    exit;
                }

                $gruplar = [
                    'genel'       => 'Genel',
                    'sosyal'      => 'Sosyal Medya',
                    'seo'         => 'SEO & Analytics',
                    'goruntuleme' => 'Goruntuleme',
                    'cron'        => 'RSS / Cron',
                    'reklam'      => 'Reklam',
                    'araclar'     => '🛠 Araçlar',
                ];

                if ($grup === 'araclar') {
                    // ads.txt fiziksel yazma aracı
                    if (($_POST['islem'] ?? '') === 'adstxt_yaz' && csrf_dogrula($_POST['_csrf'] ?? '')) {
                        $icerik = trim(ayar('ads_txt_icerik', ''));
                        $client = trim(ayar('adsense_client_id', ''));
                        if (empty($icerik) && !empty($client) && preg_match('/^ca-pub-(\d+)$/', $client, $m)) {
                            $icerik = "# XNEWS - Otomatik olusturuldu\ngoogle.com, pub-{$m[1]}, DIRECT, f08c47fec0942fa0\n";
                        }
                        if (empty($icerik)) {
                            $_SESSION['flash'] = 'AdSense Client ID veya ads.txt icerigi girilmemis. Once Reklam ayarlarindan doldurun.';
                        } else {
                            $yazildi = @file_put_contents(__DIR__ . '/ads.txt', $icerik);
                            $_SESSION['flash'] = $yazildi
                                ? "ads.txt dosyasi yazildi ({$yazildi} byte). Test: xnews.com.tr/ads.txt"
                                : 'ads.txt yazilamadi. Dosya izinlerini kontrol edin (public_html yazilabilir olmali).';
                        }
                        header('Location: ' . url('yonetim.php?sayfa=ayarlar&grup=araclar'));
                        exit;
                    }

                    $bozuk_adet = 0;
                    $ornek = [];
                    $stmt = $db->query("SELECT id, baslik FROM {$prefix}news WHERE
                        baslik REGEXP '[[:cntrl:]]'
                        OR baslik LIKE CONCAT('%', CHAR(0xE2, 0x80, 0x8B USING utf8), '%')
                        OR baslik LIKE CONCAT('%', CHAR(0xE2, 0x80, 0x8C USING utf8), '%')
                        OR baslik LIKE CONCAT('%', CHAR(0xE2, 0x80, 0x8D USING utf8), '%')
                        OR baslik LIKE CONCAT('%', CHAR(0xEF, 0xBB, 0xBF USING utf8), '%')
                        LIMIT 5");
                    if ($stmt) $ornek = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $bozuk_adet = count($ornek);
                    ?>
                    <div class="icerik-bas">
                        <div>
                            <h1>🛠 Araçlar</h1>
                            <div class="alt-metin">Bakım ve temizlik araçları</div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-bas" style="padding:0">
                            <div style="display:flex;gap:2px;overflow-x:auto;width:100%">
                                <?php foreach ($gruplar as $g => $gad): ?>
                                    <a href="<?= url('yonetim.php?sayfa=ayarlar&grup=' . $g) ?>" style="padding:14px 20px;font-size:13px;font-weight:<?= $grup === $g ? '600' : '500' ?>;color:<?= $grup === $g ? 'var(--brand)' : 'var(--ink-muted)' ?>;border-bottom:2px solid <?= $grup === $g ? 'var(--brand)' : 'transparent' ?>;white-space:nowrap"><?= h($gad) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="panel-ic">
                            <h3 style="margin:0 0 6px">🧹 Bozuk Karakter Temizleme</h3>
                            <p style="color:var(--muted);font-size:14px;margin:0 0 16px;line-height:1.5">
                                RSS'ten gelen eski haberlerde <strong>zero-width / kontrol karakterleri</strong> olabilir.
                                Bu karakterler başlık altında renkli kutucuklar gibi görünür.
                                Bu araç veritabanındaki tüm haberleri tarar ve bu görünmez karakterleri temizler.
                                <br><br>
                                <strong>Not:</strong> Yeni gelecek haberler otomatik temizleniyor (v1.1.2+). Bu araç sadece eski kayıtlar için.
                            </p>
                            <?php if (!empty($ornek)): ?>
                            <div style="background:#fee;border-left:3px solid #c8102e;padding:12px 16px;margin-bottom:16px;border-radius:4px">
                                <strong style="color:#c8102e">⚠ Bozuk karakter içerme ihtimali olan haberler tespit edildi:</strong>
                                <ul style="margin:8px 0 0;padding-left:24px;font-size:13px">
                                    <?php foreach ($ornek as $o): ?>
                                        <li><?= h(mb_substr($o['baslik'], 0, 80, 'UTF-8')) ?>...</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            <form method="post" onsubmit="return confirm('Tüm haberlerde bozuk karakter temizliği yapılacak. Devam edilsin mi?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="islem" value="karakter_temizle">
                                <button type="submit" class="btn btn-ana">🧹 Tüm Haberleri Temizle</button>
                            </form>

                            <hr style="margin:30px 0;border:none;border-top:1px solid var(--border)">

                            <h3 style="margin:0 0 6px">📄 ads.txt Dosyasını Fiziksel Olarak Yaz</h3>
                            <p style="color:var(--muted);font-size:14px;margin:0 0 16px;line-height:1.5">
                                Google AdSense ve diğer reklam ağları <code>yoursite.com/ads.txt</code> dosyasının <strong>fiziksel</strong> olarak sunucuda durmasını ister.
                                Dinamik route bazı hostinglerde tanınmayabiliyor. Bu buton <code>public_html/ads.txt</code> olarak <strong>gerçek dosya</strong> yazar.
                                <br><br>
                                İçerik: <strong>Yönetim → Ayarlar → Reklam → AdSense Publisher ID</strong> ya da <strong>ads.txt İçeriği</strong> alanından alınır.
                                Her ikisi de boşsa hiçbir şey yazılmaz.
                            </p>
                            <?php
                            $adstxt_mevcut = file_exists(__DIR__ . '/ads.txt');
                            $adstxt_icerik = $adstxt_mevcut ? @file_get_contents(__DIR__ . '/ads.txt') : '';
                            $adsense_id_mevcut = trim(ayar('adsense_client_id', ''));
                            ?>
                            <?php if ($adstxt_mevcut): ?>
                            <div style="background:#d1fae5;border-left:3px solid #10b981;padding:12px 16px;margin-bottom:14px;border-radius:4px;font-size:13px">
                                <strong style="color:#065f46">✓ ads.txt mevcut</strong> (<?= strlen($adstxt_icerik) ?> byte)<br>
                                <code style="display:block;margin-top:6px;padding:6px 10px;background:#fff;border-radius:3px;font-size:12px;white-space:pre-wrap"><?= h($adstxt_icerik) ?></code>
                                <a href="<?= url('ads.txt') ?>" target="_blank" style="font-size:12px;margin-top:6px;display:inline-block">xnews.com.tr/ads.txt test →</a>
                            </div>
                            <?php elseif (!$adsense_id_mevcut): ?>
                            <div style="background:#fef3c7;border-left:3px solid #f59e0b;padding:12px 16px;margin-bottom:14px;border-radius:4px;font-size:13px">
                                <strong>⚠ AdSense Publisher ID henüz girilmemiş</strong><br>
                                Önce <a href="<?= url('yonetim.php?sayfa=ayarlar&grup=reklam') ?>">Reklam Ayarları</a> bölümünden <code>ca-pub-xxxxxxxxx</code> ID'nizi girin.
                            </div>
                            <?php endif; ?>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="islem" value="adstxt_yaz">
                                <button type="submit" class="btn btn-ana">📄 ads.txt Dosyasını Şimdi Yaz</button>
                            </form>
                        </div>
                    </div>
                    <?php
                    break;
                }

                $stmt = $db->prepare("SELECT * FROM {$prefix}settings WHERE grup = ? ORDER BY sira, anahtar");
                $stmt->execute([$grup]);
                $ayar_liste = $stmt->fetchAll();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>Ayarlar</h1>
                        <div class="alt-metin">Site ayarlarini grup bazli yonetin</div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-bas" style="padding:0">
                        <div style="display:flex;gap:2px;overflow-x:auto;width:100%">
                            <?php foreach ($gruplar as $g => $gad): ?>
                                <a href="<?= url('yonetim.php?sayfa=ayarlar&grup=' . $g) ?>" style="padding:14px 20px;font-size:13px;font-weight:<?= $grup === $g ? '600' : '500' ?>;color:<?= $grup === $g ? 'var(--brand)' : 'var(--ink-muted)' ?>;border-bottom:2px solid <?= $grup === $g ? 'var(--brand)' : 'transparent' ?>;white-space:nowrap"><?= h($gad) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <form method="post">
                        <?= csrf_input() ?>
                        <div class="panel-ic">
                            <?php if (empty($ayar_liste)): ?>
                                <p style="color:var(--muted);text-align:center;padding:40px 0">Bu grupta ayar yok.</p>
                            <?php else: foreach ($ayar_liste as $a): ?>
                                <div class="form-grup">
                                    <label><?= h($a['etiket'] ?: $a['anahtar']) ?></label>
                                    <?php if ($a['tip'] === 'html' || $a['tip'] === 'metin-uzun' || ($a['aciklama'] && stripos($a['aciklama'], 'kod') !== false)): ?>
                                        <textarea name="ayar[<?= h($a['anahtar']) ?>]" rows="5" style="font-family:'IBM Plex Mono',monospace;font-size:12px"><?= h($a['deger']) ?></textarea>
                                    <?php elseif ($a['tip'] === 'boolean' || $a['tip'] === 'onoff'): ?>
                                        <label class="switch">
                                            <input type="hidden" name="ayar[<?= h($a['anahtar']) ?>]" value="0">
                                            <input type="checkbox" name="ayar[<?= h($a['anahtar']) ?>]" value="1" <?= $a['deger'] ? 'checked' : '' ?>>
                                            <span class="kutu"></span><span>Aktif</span>
                                        </label>
                                    <?php elseif ($a['tip'] === 'sayi'): ?>
                                        <input type="number" name="ayar[<?= h($a['anahtar']) ?>]" value="<?= h($a['deger']) ?>">
                                    <?php else: ?>
                                        <input type="text" name="ayar[<?= h($a['anahtar']) ?>]" value="<?= h($a['deger']) ?>">
                                    <?php endif; ?>
                                    <?php if ($a['aciklama']): ?><div class="ipucu"><?= h($a['aciklama']) ?></div><?php endif; ?>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <?php if (!empty($ayar_liste)): ?>
                        <div class="panel-ic" style="border-top:1px solid var(--border);background:#f8fafc">
                            <div style="display:flex;justify-content:flex-end">
                                <button type="submit" class="buton"><?= ikon('save') ?>Kaydet</button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                <?php break;

            // ====================================
            // ADSENSE KURULUM REHBERİ
            // ====================================
            case 'adsense-rehber':
                $as_id = trim(ayar('adsense_client_id', ''));
                $as_valid = !empty($as_id) && preg_match('/^ca-pub-\d{10,20}$/', $as_id);
                $ads_txt_mevcut = file_exists(__DIR__ . '/ads.txt');
                $auto_ads = ayar('adsense_auto_ads', '0') === '1';
                $reklam_as_adet = (int)$db->query("SELECT COUNT(*) FROM {$prefix}ads WHERE tip IN('adsense','kod') AND aktif=1")->fetchColumn();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>📚 Google AdSense Kurulum Rehberi</h1>
                        <div class="alt-metin">XNEWS ile AdSense'i tam entegre etmenin adım adım rehberi</div>
                    </div>
                </div>

                <!-- Adım 1: AdSense Hesabı -->
                <div class="panel" style="margin-bottom:18px">
                    <div class="panel-bas" style="display:flex;align-items:center;gap:12px">
                        <div style="width:36px;height:36px;border-radius:50%;background:<?= $as_valid ? '#10b981' : '#cbd5e1' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">1</div>
                        <h3 style="margin:0">AdSense Hesabı ve Site Kaydı</h3>
                        <span style="margin-left:auto;color:<?= $as_valid ? '#10b981' : '#94a3b8' ?>"><?= $as_valid ? '✓ Tamamlandı' : 'Bekliyor' ?></span>
                    </div>
                    <div class="panel-ic">
                        <ol style="margin:0;padding-left:22px;line-height:1.8">
                            <li><a href="https://adsense.google.com" target="_blank">adsense.google.com</a> adresine git, hesap oluştur veya giriş yap.</li>
                            <li>"Siteler" menüsünden <strong>xnews.com.tr</strong>'yi ekle.</li>
                            <li>"Publisher ID" değerini kopyala (format: <code>ca-pub-7160164794098300</code>).</li>
                        </ol>
                    </div>
                </div>

                <!-- Adım 2: ID Kaydetme -->
                <div class="panel" style="margin-bottom:18px">
                    <div class="panel-bas" style="display:flex;align-items:center;gap:12px">
                        <div style="width:36px;height:36px;border-radius:50%;background:<?= $as_valid ? '#10b981' : '#cbd5e1' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">2</div>
                        <h3 style="margin:0">Publisher ID'yi XNEWS'e Gir</h3>
                        <span style="margin-left:auto;color:<?= $as_valid ? '#10b981' : '#94a3b8' ?>"><?= $as_valid ? '✓ ' . h($as_id) : 'Bekliyor' ?></span>
                    </div>
                    <div class="panel-ic">
                        <ol style="margin:0;padding-left:22px;line-height:1.8">
                            <li><strong>Yönetim → Ayarlar → Reklam</strong> bölümüne git.</li>
                            <li>"AdSense Publisher ID" alanına kopyaladığın ID'yi yapıştır.</li>
                            <li>"Kaydet" butonuna bas.</li>
                            <li>Sistem otomatik olarak:
                                <ul style="margin:6px 0;padding-left:20px">
                                    <li>Tüm sayfalara <code>&lt;meta name="google-adsense-account"&gt;</code> ekler.</li>
                                    <li><code>&lt;script&gt;</code> adsbygoogle.js head'e enjekte edilir.</li>
                                    <li><code>public_html/ads.txt</code> fiziksel olarak oluşturulur.</li>
                                </ul>
                            </li>
                        </ol>
                        <a href="<?= url('yonetim.php?sayfa=ayarlar&grup=reklam') ?>" class="buton" style="margin-top:10px">Reklam Ayarlarına Git →</a>
                    </div>
                </div>

                <!-- Adım 3: Doğrulama -->
                <div class="panel" style="margin-bottom:18px">
                    <div class="panel-bas" style="display:flex;align-items:center;gap:12px">
                        <div style="width:36px;height:36px;border-radius:50%;background:<?= $ads_txt_mevcut ? '#10b981' : '#cbd5e1' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">3</div>
                        <h3 style="margin:0">AdSense Panelinde Siteyi Doğrula</h3>
                        <span style="margin-left:auto;color:<?= $ads_txt_mevcut ? '#10b981' : '#94a3b8' ?>"><?= $ads_txt_mevcut ? '✓ ads.txt mevcut' : 'ads.txt yok' ?></span>
                    </div>
                    <div class="panel-ic">
                        <ol style="margin:0;padding-left:22px;line-height:1.8">
                            <li>AdSense paneline dön.</li>
                            <li>"Siteniz doğrulanıyor" uyarısında <strong>"Kodu yerleştirdim"</strong> kutusunu işaretle.</li>
                            <li><strong>"Doğrula"</strong> butonuna bas.</li>
                            <li>Google birkaç saat içinde siteyi onaylayacak.</li>
                            <li>Ayrıca <a href="<?= url('ads.txt') ?>" target="_blank">xnews.com.tr/ads.txt</a> adresini aç — içerik olmalı.</li>
                        </ol>
                    </div>
                </div>

                <!-- Adım 4: Reklamları Göster -->
                <div class="panel" style="margin-bottom:18px">
                    <div class="panel-bas" style="display:flex;align-items:center;gap:12px">
                        <div style="width:36px;height:36px;border-radius:50%;background:<?= ($auto_ads || $reklam_as_adet > 0) ? '#10b981' : '#cbd5e1' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">4</div>
                        <h3 style="margin:0">Reklamları Sitede Göster</h3>
                        <span style="margin-left:auto;color:<?= ($auto_ads || $reklam_as_adet > 0) ? '#10b981' : '#94a3b8' ?>"><?= ($auto_ads || $reklam_as_adet > 0) ? '✓ Aktif' : 'Bekliyor' ?></span>
                    </div>
                    <div class="panel-ic">
                        <p style="margin:0 0 12px">İki yöntem var — istediğini seçebilir veya ikisini birden kullanabilirsin:</p>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-bottom:14px">
                            <div style="padding:16px;border:2px solid <?= $auto_ads ? '#10b981' : '#e5e7eb' ?>;border-radius:10px">
                                <h4 style="margin:0 0 6px">🤖 Yöntem A: Auto Ads (Önerilen)</h4>
                                <p style="margin:0 0 8px;color:var(--muted);font-size:13px;line-height:1.5">
                                    Google otomatik olarak en uygun yerlere reklam yerleştirir.
                                    Sen hiçbir şey yapmak zorunda değilsin.
                                </p>
                                <p style="margin:0;font-size:13px">
                                    <strong>Reklam Ayarları → Otomatik Reklamlar = Açık</strong>
                                </p>
                                <div style="margin-top:8px;font-size:12px;color:<?= $auto_ads ? '#10b981' : '#94a3b8' ?>">
                                    Durum: <?= $auto_ads ? 'AÇIK ✓' : 'Kapalı' ?>
                                </div>
                            </div>

                            <div style="padding:16px;border:2px solid <?= $reklam_as_adet > 0 ? '#10b981' : '#e5e7eb' ?>;border-radius:10px">
                                <h4 style="margin:0 0 6px">🎯 Yöntem B: Manuel Slot'lar</h4>
                                <p style="margin:0 0 8px;color:var(--muted);font-size:13px;line-height:1.5">
                                    AdSense'de "Reklam birimi" oluştur, kodunu kopyala, XNEWS'te bir slot'a yapıştır.
                                    Daha fazla kontrol isteyenler için.
                                </p>
                                <p style="margin:0;font-size:13px">
                                    <strong>Yönetim → Reklamlar → Yeni Ekle</strong>
                                </p>
                                <div style="margin-top:8px;font-size:12px;color:<?= $reklam_as_adet > 0 ? '#10b981' : '#94a3b8' ?>">
                                    <?= $reklam_as_adet ?> adet aktif
                                </div>
                            </div>
                        </div>

                        <p style="margin:0;padding:10px 14px;background:#eff6ff;border-radius:6px;font-size:13px;color:#1e3a8a">
                            <strong>💡 İpucu:</strong> Yeni yayına başlayan siteler için Auto Ads daha kolaydır. Site büyüdükçe Yöntem B ile reklam yerleştirmesini optimize edebilirsin.
                        </p>
                    </div>
                </div>

                <!-- Mevcut Slot Konumları -->
                <div class="panel" style="margin-bottom:18px">
                    <div class="panel-bas">
                        <h3 style="margin:0">📍 XNEWS'te Mevcut Reklam Slot Konumları</h3>
                    </div>
                    <div class="panel-ic">
                        <table class="tablo" style="margin:0">
                            <thead><tr><th>Slot Kodu</th><th>Konum</th><th>Önerilen Boyut</th></tr></thead>
                            <tbody>
                                <tr><td><code>ust_banner</code></td><td>Header altı, tüm sayfalarda</td><td>Leaderboard 728×90</td></tr>
                                <tr><td><code>sidebar_ust</code></td><td>Yan panel üst</td><td>Medium Rectangle 300×250</td></tr>
                                <tr><td><code>sidebar_alt</code></td><td>Yan panel alt</td><td>Large Skyscraper 300×600</td></tr>
                                <tr><td><code>makale_ust</code></td><td>Haber başlığından sonra</td><td>Billboard 970×250</td></tr>
                                <tr><td><code>makale_ic</code></td><td>Haber metninde 3. paragraf sonrası</td><td>In-article responsive</td></tr>
                                <tr><td><code>makale_alt</code></td><td>Haber sonunda</td><td>Medium Rectangle 300×250</td></tr>
                                <tr><td><code>alt_banner</code></td><td>Footer öncesi</td><td>Leaderboard 728×90</td></tr>
                                <tr><td><code>mobil_sabit</code></td><td>Mobilde alta yapışık</td><td>Mobile Banner 320×50</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Politika Uyarıları -->
                <div class="panel" style="background:#fef3c7;border-left:4px solid #f59e0b">
                    <div class="panel-ic">
                        <h3 style="margin:0 0 10px;color:#92400e">⚠️ AdSense Politika Uyarıları</h3>
                        <ul style="margin:0;padding-left:22px;line-height:1.7;color:#78350f;font-size:13px">
                            <li><strong>Telif:</strong> XNEWS bir haber agregatörüdür. AdSense politikası "özgün içerik" ister. Tam metni yerine <strong>özet + kaynak linki</strong> göster (zaten böyle).</li>
                            <li><strong>Footer uyarısı:</strong> Sitemizde "RSS agregatörü" bildirimi var (v1.2.2) — AdSense incelemesi için avantaj.</li>
                            <li><strong>KVKK + Gizlilik:</strong> Gerekli sayfalar mevcut (v1.0.9).</li>
                            <li><strong>Trafik:</strong> AdSense minimum trafik istemez ama düzenli içerik (RSS ile zaten otomatik) ve kaliteli tasarım (v1.2.0) önemli.</li>
                            <li><strong>Botlar:</strong> AdSense tıklamalarını kontrol eder. Kendin tıklamayın, başkalarına tıklatmayın — hesap kapatılır.</li>
                        </ul>
                    </div>
                </div>
                <?php break;


            // ====================================
            // DIRECTADMIN CRON ZAMANLANMIŞ GÖREV REHBERİ
            // ====================================
            case 'cron-rehber':
                $site = $_SERVER['HTTP_HOST'] ?? 'xnews.com.tr';
                $cron_anahtar = defined('CRON_ANAHTARI') ? CRON_ANAHTARI : '';
                $son_cekim = ayar('son_cron_tarihi', '');
                $dokuman_kok = rtrim(str_replace('\\', '/', __DIR__), '/');
                $cron_php_yolu = $dokuman_kok . '/cron.php';

                // DirectAdmin sample'ına birebir uyumlu (en basit, en güvenilir)
                $komut_directadmin = 'php ' . $cron_php_yolu . ' ' . $cron_anahtar;
                // Alternatif: tam PHP binary yolu
                $komut_tam_yol = '/usr/local/bin/php ' . $cron_php_yolu . ' ' . $cron_anahtar;
                // PHP 8.3 spesifik (DirectAdmin bazı hostinglerde default 7.x olabiliyor)
                $komut_php83 = '/usr/local/php83/bin/php ' . $cron_php_yolu . ' ' . $cron_anahtar;
                // HTTP tetikleme (wget)
                $komut_wget = "wget -q -O /dev/null 'https://" . $site . "/cron.php?anahtar=" . $cron_anahtar . "'";
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>⏰ Cron Zamanlanmış Görev</h1>
                        <div class="alt-metin">DirectAdmin panelinde RSS otomatik çekimi için adım adım rehber</div>
                    </div>
                </div>

                <!-- Durum -->
                <div class="panel" style="margin-bottom:18px;background:<?= $son_cekim ? 'linear-gradient(135deg,#ecfdf5,#fff)' : 'linear-gradient(135deg,#fef3c7,#fff)' ?>;border-left:4px solid <?= $son_cekim ? '#10b981' : '#f59e0b' ?>">
                    <div class="panel-ic">
                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                            <div style="font-size:38px"><?= $son_cekim ? '⏰' : '⚠️' ?></div>
                            <div style="flex:1;min-width:260px">
                                <h3 style="margin:0 0 4px;color:<?= $son_cekim ? '#065f46' : '#92400e' ?>">
                                    Cron Durumu: <?= $son_cekim ? 'Çalışıyor ✓' : 'Henüz kurulmadı / çalışmadı' ?>
                                </h3>
                                <div style="font-size:13px;color:var(--muted)">
                                    <?= $son_cekim ? ('Son çekim: ' . h(goreceli_zaman($son_cekim))) : 'Cron kurduysanız bir kaç dakika bekleyin. Hemen test etmek isterseniz alt taraftaki "Manuel Tetikle" butonunu kullanın.' ?>
                                </div>
                            </div>
                            <form method="post" action="<?= url('yonetim.php?sayfa=cekim-tetik') ?>">
                                <?= csrf_input() ?>
                                <button type="submit" class="buton">⚡ Manuel Tetikle (Test)</button>
                            </form>
                        </div>
                    </div>
                </div>

                <?php if (empty($cron_anahtar)): ?>
                <div class="panel" style="margin-bottom:18px;background:#fee2e2;border-left:4px solid #dc2626">
                    <div class="panel-ic" style="color:#991b1b">
                        <h3 style="margin:0 0 6px;color:#991b1b">⛔ CRON_ANAHTARI Tanımlı Değil</h3>
                        <p style="margin:0;font-size:13px">config.php içinde <code>define('CRON_ANAHTARI', '...')</code> satırı eksik. Cron çalışamaz. FTP ile public_html/config.php açın, config.sample.php'den referans alın.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ANA KOMUT (DirectAdmin Sample'ına Uygun) -->
                <div class="panel" style="margin-bottom:18px;border:2px solid #10b981">
                    <div class="panel-bas" style="background:#ecfdf5">
                        <h3 style="margin:0;color:#065f46">⭐ DirectAdmin İçin Önerilen Komut</h3>
                    </div>
                    <div class="panel-ic">
                        <p style="margin:0 0 12px;font-size:14px">
                            DirectAdmin <em>"Sample Cron commands"</em> bölümündeki <code>php /yol/script.php</code> formatına birebir uygun. Sadece <strong>"Command"</strong> alanına bu tek satırı yapıştırın:
                        </p>
                        <div style="display:flex;gap:8px;align-items:stretch;margin-bottom:12px">
                            <input type="text" readonly value="<?= h($komut_directadmin) ?>" style="flex:1;font-family:monospace;font-size:12px;background:#f8fafc;padding:12px" id="komut1" onclick="this.select()">
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('komut1').value).then(()=>{this.innerText='✓ Kopyalandı';setTimeout(()=>this.innerText='📋 Kopyala',2000)})" class="buton" style="white-space:nowrap">📋 Kopyala</button>
                        </div>
                        <div style="padding:10px 14px;background:#eff6ff;border-radius:6px;font-size:12px;color:#1e3a8a">
                            <strong>💡 Neden bu format?</strong> DirectAdmin'in kendi örnek komutu da tam bu şekilde: <code>php /home/KULLANICI/domains/SITE/public_html/script.php</code>. <code>php</code> otomatik PATH'ten bulunur, ekstra yol gerektirmez.
                        </div>
                    </div>
                </div>

                <!-- Zamanlama -->
                <div class="panel" style="margin-bottom:18px">
                    <div class="panel-bas"><h3 style="margin:0">⏱ Zamanlama Alanları (DirectAdmin Form)</h3></div>
                    <div class="panel-ic">
                        <p style="margin:0 0 12px;font-size:14px">
                            Komutu yapıştırdıktan sonra yukarıdaki 5 alanı doldurun:
                        </p>
                        <table class="tablo" style="margin:0">
                            <thead>
                                <tr><th>Sıklık</th><th>Minute</th><th>Hour</th><th>Day</th><th>Month</th><th>Weekday</th></tr>
                            </thead>
                            <tbody>
                                <tr style="background:#ecfdf5"><td><strong>Her 5 dk ⭐</strong></td><td><code>*/5</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td></tr>
                                <tr><td>Her 10 dk</td><td><code>*/10</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td></tr>
                                <tr><td>Her 15 dk</td><td><code>*/15</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td></tr>
                                <tr><td>Saat başı</td><td><code>0</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td></tr>
                            </tbody>
                        </table>
                        <p style="margin:14px 0 0;padding:10px 14px;background:#fef3c7;border-radius:6px;font-size:13px;color:#78350f">
                            <strong>💡 ÖNEMLİ:</strong> DirectAdmin form altında sağdaki yeşil <strong>"PREVENT E-MAIL"</strong> butonuna bas (cron her çalıştığında mail göndermesin). Sonra mavi <strong>"SAVE"</strong> butonuna basıp kaydet.
                        </p>
                    </div>
                </div>

                <!-- Alternatif Komutlar -->
                <div class="panel" style="margin-bottom:18px">
                    <div class="panel-bas">
                        <h3 style="margin:0">🔄 Alternatif Komut Seçenekleri</h3>
                    </div>
                    <div class="panel-ic">
                        <p style="margin:0 0 14px;color:var(--muted);font-size:13px">Üstteki önerilen format çalışmazsa aşağıdakileri deneyin:</p>

                        <h4 style="margin:0 0 6px;font-size:14px">Seçenek 2: Tam PHP yolu</h4>
                        <div style="display:flex;gap:8px;align-items:stretch;margin-bottom:14px">
                            <input type="text" readonly value="<?= h($komut_tam_yol) ?>" style="flex:1;font-family:monospace;font-size:11px" id="komut2">
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('komut2').value).then(()=>{this.innerText='✓';setTimeout(()=>this.innerText='📋',2000)})" class="buton ikincil" style="white-space:nowrap">📋</button>
                        </div>

                        <h4 style="margin:0 0 6px;font-size:14px">Seçenek 3: PHP 8.3 spesifik yol (default PHP 7.x ise)</h4>
                        <div style="display:flex;gap:8px;align-items:stretch;margin-bottom:14px">
                            <input type="text" readonly value="<?= h($komut_php83) ?>" style="flex:1;font-family:monospace;font-size:11px" id="komut3">
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('komut3').value).then(()=>{this.innerText='✓';setTimeout(()=>this.innerText='📋',2000)})" class="buton ikincil" style="white-space:nowrap">📋</button>
                        </div>

                        <h4 style="margin:0 0 6px;font-size:14px">Seçenek 4: HTTP ile tetikleme (CLI çalışmıyorsa)</h4>
                        <div style="display:flex;gap:8px;align-items:stretch">
                            <input type="text" readonly value="<?= h($komut_wget) ?>" style="flex:1;font-family:monospace;font-size:11px" id="komut4">
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('komut4').value).then(()=>{this.innerText='✓';setTimeout(()=>this.innerText='📋',2000)})" class="buton ikincil" style="white-space:nowrap">📋</button>
                        </div>
                    </div>
                </div>

                <!-- Bileşenler (Debug) -->
                <div class="panel" style="margin-bottom:18px">
                    <div class="panel-bas"><h3 style="margin:0">🔍 Kullanılan Yollar (Debug)</h3></div>
                    <div class="panel-ic">
                        <table style="width:100%;font-size:13px">
                            <tr><td style="padding:6px 0;color:var(--muted);width:160px">Script tam yolu:</td><td><code><?= h($cron_php_yolu) ?></code></td></tr>
                            <tr><td style="padding:6px 0;color:var(--muted)">Doküman kök:</td><td><code><?= h($dokuman_kok) ?></code></td></tr>
                            <tr><td style="padding:6px 0;color:var(--muted)">Anahtar:</td><td><code><?= $cron_anahtar ? h($cron_anahtar) : '<span style="color:#dc2626">TANIMLI DEĞİL</span>' ?></code></td></tr>
                            <tr><td style="padding:6px 0;color:var(--muted)">PHP_SAPI:</td><td><code><?= h(PHP_SAPI) ?></code></td></tr>
                            <tr><td style="padding:6px 0;color:var(--muted)">PHP sürümü:</td><td><code><?= h(PHP_VERSION) ?></code></td></tr>
                            <tr><td style="padding:6px 0;color:var(--muted)">Site URL:</td><td><code>https://<?= h($site) ?></code></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Sorun Giderme -->
                <div class="panel" style="background:#fef3c7;border-left:4px solid #f59e0b">
                    <div class="panel-ic">
                        <h3 style="margin:0 0 10px;color:#92400e">🔧 Cron Kurdum Ama Çalışmadı?</h3>
                        <ol style="margin:0;padding-left:22px;line-height:1.7;color:#78350f;font-size:13px">
                            <li><strong>5 dakika bekleyin</strong> — cron ilk çalıştığında 5 dk geçmesi gerekir.</li>
                            <li><strong>SAVE'e bastınız mı?</strong> Komutu yazmak yetmez, altta mavi SAVE butonuna basılmalı.</li>
                            <li><strong>Prevent E-Mail:</strong> DirectAdmin her cron çalışmasında e-posta gönderir. Bu e-postaları okuyun — "No such file" varsa yol yanlış, PHP syntax hatası varsa başka sorun.</li>
                            <li><strong>Test için komut satırında manuel çalıştırın:</strong> DirectAdmin Terminal veya SSH'tan: <code style="display:block;padding:6px 10px;background:#fff;border-radius:3px;margin-top:4px"><?= h($komut_directadmin) ?></code></li>
                            <li><strong>PHP sürümü uyumsuzluğu:</strong> Çıktıda "Parse error" veya "syntax error" varsa PHP 7.x kullanılmış olabilir. Seçenek 3 (PHP 8.3 spesifik yol) deneyin.</li>
                            <li><strong>Anahtar yanlış:</strong> Komutta girdiğiniz anahtar config.php'deki <code>CRON_ANAHTARI</code> ile birebir aynı olmalı.</li>
                            <li><strong>Hepsi başarısız:</strong> Seçenek 4 (wget HTTP) deneyin.</li>
                        </ol>
                        <div style="margin-top:14px;padding:10px 14px;background:#fff;border-radius:6px;font-size:13px">
                            <strong>🩺 Hızlı Teşhis:</strong>
                            <a href="<?= url('yonetim.php?sayfa=loglar&tip=cron') ?>">📋 Cron Logları</a>
                            ·
                            <a href="<?= url('yonetim.php?sayfa=kaynaklar') ?>">🔍 RSS Kaynakları (AKTİF/HATA)</a>
                            ·
                            <a href="<?= url('yonetim.php') ?>">🏠 Dashboard (Son çekim zamanı)</a>
                        </div>
                    </div>
                </div>
                <?php break;

            // ====================================
            // KALDIRMA TALEPLERİ (Takedown)
            // ====================================
            case 'talepler':
                // Durum güncelleme
                if (($_POST['islem'] ?? '') === 'durum_guncelle' && csrf_dogrula($_POST['_csrf'] ?? '')) {
                    $tid = (int)($_POST['id'] ?? 0);
                    $yeni_durum = in_array($_POST['durum'] ?? '', ['bekliyor', 'inceleniyor', 'kabul', 'red', 'bilgi_istendi'], true) ? $_POST['durum'] : 'bekliyor';
                    $admin_not = trim($_POST['admin_not'] ?? '');
                    $st = $db->prepare("UPDATE {$prefix}takedown SET durum=?, admin_not=?, islem_tarihi=NOW(), islem_yapan_id=? WHERE id=?");
                    $st->execute([$yeni_durum, $admin_not, $yonetici['id'], $tid]);
                    flash('Talep güncellendi (#' . $tid . ' → ' . $yeni_durum . ').', 'basari');
                    yonlendir(url('yonetim.php?sayfa=talepler' . (($_POST['geri'] ?? '') ? '&id=' . $tid : '')));
                }

                // Tek talep detay
                $detay_id = (int)($_GET['id'] ?? 0);
                if ($detay_id) {
                    $st = $db->prepare("SELECT t.*, n.baslik AS haber_baslik, n.slug AS haber_slug
                        FROM {$prefix}takedown t LEFT JOIN {$prefix}news n ON n.id = t.haber_id
                        WHERE t.id = ?");
                    $st->execute([$detay_id]);
                    $talep = $st->fetch();
                    if (!$talep) { flash('Talep bulunamadı.', 'hata'); yonlendir(url('yonetim.php?sayfa=talepler')); }

                    $iliski_ad = [
                        'hak_sahibi' => 'Hak Sahibi', 'yayin_sahibi' => 'Yayın Sahibi',
                        'avukat' => 'Avukat/Temsilci', 'kisisel' => 'Kişisel',
                        'diger' => 'Diğer',
                    ][$talep['iliski']] ?? $talep['iliski'];
                    $sebep_ad = [
                        'telif' => 'Telif Hakkı İhlali', 'kisilik' => 'Kişilik Hakkı İhlali',
                        'kvkk' => 'KVKK - Kişisel Veri', 'yanlis' => 'Yanlış/Yanıltıcı Bilgi',
                        'itibar' => 'İtibar Zedeleyici', 'diger' => 'Diğer',
                    ][$talep['sebep']] ?? $talep['sebep'];
                    $durum_renk = ['bekliyor'=>'#f59e0b','inceleniyor'=>'#3b82f6','kabul'=>'#16a34a','red'=>'#dc2626','bilgi_istendi'=>'#8b5cf6'];
                ?>
                    <div class="icerik-bas">
                        <div>
                            <h1>Kaldırma Talebi #<?= $detay_id ?></h1>
                            <div class="alt-metin"><?= h(tr_tarih($talep['olusturma_tarihi'])) ?></div>
                        </div>
                        <a href="<?= url('yonetim.php?sayfa=talepler') ?>" class="buton ikincil"><?= ikon('arrow-left') ?>Tüm Talepler</a>
                    </div>

                    <div class="panel" style="margin-bottom:20px">
                        <div class="panel-ic">
                            <div style="display:flex;flex-wrap:wrap;gap:20px 40px;margin-bottom:18px">
                                <div><span style="color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em">Durum</span><br><strong style="color:<?= $durum_renk[$talep['durum']] ?? '#000' ?>;font-size:16px"><?= h(mb_strtoupper($talep['durum'], 'UTF-8')) ?></strong></div>
                                <div><span style="color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em">İlişki</span><br><strong><?= h($iliski_ad) ?></strong></div>
                                <div><span style="color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em">Sebep</span><br><strong><?= h($sebep_ad) ?></strong></div>
                                <div><span style="color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em">IP</span><br><code><?= h($talep['ip'] ?? '') ?></code></div>
                            </div>

                            <h3 style="margin:0 0 6px">Başvuran Kişi</h3>
                            <table style="width:100%;margin-bottom:18px">
                                <tr><td style="padding:6px 0;color:var(--muted);width:140px">Ad Soyad:</td><td><strong><?= h($talep['ad_soyad']) ?></strong></td></tr>
                                <tr><td style="padding:6px 0;color:var(--muted)">E-posta:</td><td><a href="mailto:<?= h($talep['eposta']) ?>"><?= h($talep['eposta']) ?></a></td></tr>
                                <?php if (!empty($talep['telefon'])): ?><tr><td style="padding:6px 0;color:var(--muted)">Telefon:</td><td><?= h($talep['telefon']) ?></td></tr><?php endif; ?>
                                <?php if (!empty($talep['kurum'])): ?><tr><td style="padding:6px 0;color:var(--muted)">Kurum:</td><td><?= h($talep['kurum']) ?></td></tr><?php endif; ?>
                            </table>

                            <h3 style="margin:0 0 6px">Şikayet Edilen İçerik</h3>
                            <?php if (!empty($talep['haber_baslik'])): ?>
                                <p style="margin:0 0 6px"><a href="<?= url('haber/' . (int)$talep['haber_id'] . '-' . h($talep['haber_slug'] ?? 'haber')) ?>" target="_blank" style="font-weight:600"><?= h($talep['haber_baslik']) ?></a> <span style="color:var(--muted)">#<?= (int)$talep['haber_id'] ?></span></p>
                            <?php endif; ?>
                            <?php if (!empty($talep['url'])): ?>
                                <p style="margin:0 0 12px;font-size:13px;color:var(--muted)">URL: <a href="<?= h($talep['url']) ?>" target="_blank"><?= h($talep['url']) ?></a></p>
                            <?php endif; ?>

                            <h3 style="margin:16px 0 6px">Açıklama</h3>
                            <div style="background:#f8fafc;padding:14px 18px;border-radius:8px;border-left:3px solid var(--brand);white-space:pre-wrap;font-size:14px;line-height:1.6"><?= h($talep['aciklama']) ?></div>

                            <?php if (!empty($talep['kanit_url'])): ?>
                                <h3 style="margin:16px 0 6px">Kanıt Belge URL</h3>
                                <p><a href="<?= h($talep['kanit_url']) ?>" target="_blank"><?= h($talep['kanit_url']) ?></a></p>
                            <?php endif; ?>

                            <?php if (!empty($talep['admin_not'])): ?>
                                <h3 style="margin:16px 0 6px">Yönetim Notu</h3>
                                <div style="background:#fef3c7;padding:14px 18px;border-radius:8px;border-left:3px solid #f59e0b;white-space:pre-wrap;font-size:14px;line-height:1.6"><?= h($talep['admin_not']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-bas"><h3 style="margin:0">Durum Güncelle</h3></div>
                        <form method="post" class="panel-ic">
                            <?= csrf_input() ?>
                            <input type="hidden" name="islem" value="durum_guncelle">
                            <input type="hidden" name="id" value="<?= $detay_id ?>">
                            <input type="hidden" name="geri" value="1">

                            <div class="form-grup">
                                <label>Yeni Durum</label>
                                <select name="durum" required>
                                    <option value="bekliyor" <?= $talep['durum']==='bekliyor'?'selected':'' ?>>🟡 Bekliyor</option>
                                    <option value="inceleniyor" <?= $talep['durum']==='inceleniyor'?'selected':'' ?>>🔵 İnceleniyor</option>
                                    <option value="bilgi_istendi" <?= $talep['durum']==='bilgi_istendi'?'selected':'' ?>>🟣 Bilgi İstendi</option>
                                    <option value="kabul" <?= $talep['durum']==='kabul'?'selected':'' ?>>🟢 Kabul Edildi</option>
                                    <option value="red" <?= $talep['durum']==='red'?'selected':'' ?>>🔴 Reddedildi</option>
                                </select>
                            </div>

                            <div class="form-grup">
                                <label>Yönetim Notu / Talep Sahibine Yanıt</label>
                                <textarea name="admin_not" rows="5" placeholder="Karar gerekçesi, başvurana iletilecek mesaj vb."><?= h($talep['admin_not'] ?? '') ?></textarea>
                                <small style="color:var(--muted);font-size:12px">Bu not sadece yönetici tarafında görünür. Talep sahibine cevap yazacaksanız e-postayla iletiniz.</small>
                            </div>

                            <button type="submit" class="buton">💾 Durumu Güncelle</button>
                        </form>
                    </div>
                <?php break;
                } // detay sonu

                // LİSTE GÖRÜNÜMÜ
                $f_durum = $_GET['durum'] ?? '';
                $where_t = []; $params_t = [];
                if ($f_durum && in_array($f_durum, ['bekliyor','inceleniyor','kabul','red','bilgi_istendi'], true)) {
                    $where_t[] = 't.durum = ?'; $params_t[] = $f_durum;
                }
                $wq = $where_t ? ('WHERE ' . implode(' AND ', $where_t)) : '';

                $toplam_t = (int)$db->query("SELECT COUNT(*) FROM {$prefix}takedown t $wq")->fetchColumn();
                $st = $db->prepare("SELECT t.*, n.baslik AS haber_baslik
                    FROM {$prefix}takedown t LEFT JOIN {$prefix}news n ON n.id = t.haber_id
                    $wq ORDER BY t.olusturma_tarihi DESC LIMIT 100");
                $st->execute($params_t);
                $talepler = $st->fetchAll();

                $sayim = $db->query("SELECT durum, COUNT(*) adet FROM {$prefix}takedown GROUP BY durum")->fetchAll(PDO::FETCH_KEY_PAIR);
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>Kaldırma Talepleri</h1>
                        <div class="alt-metin"><?= (int)($sayim['bekliyor'] ?? 0) ?> talep incelenmeyi bekliyor</div>
                    </div>
                </div>

                <!-- Durum filtreleri -->
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px">
                    <a href="<?= url('yonetim.php?sayfa=talepler') ?>" class="buton <?= !$f_durum ? '' : 'ikincil' ?>" style="padding:8px 14px;font-size:13px">Tümü (<?= array_sum($sayim) ?>)</a>
                    <a href="<?= url('yonetim.php?sayfa=talepler&durum=bekliyor') ?>" class="buton <?= $f_durum==='bekliyor' ? '' : 'ikincil' ?>" style="padding:8px 14px;font-size:13px;background:<?= $f_durum==='bekliyor' ? '#f59e0b' : '' ?>">🟡 Bekliyor (<?= (int)($sayim['bekliyor'] ?? 0) ?>)</a>
                    <a href="<?= url('yonetim.php?sayfa=talepler&durum=inceleniyor') ?>" class="buton <?= $f_durum==='inceleniyor' ? '' : 'ikincil' ?>" style="padding:8px 14px;font-size:13px;background:<?= $f_durum==='inceleniyor' ? '#3b82f6' : '' ?>">🔵 İnceleniyor (<?= (int)($sayim['inceleniyor'] ?? 0) ?>)</a>
                    <a href="<?= url('yonetim.php?sayfa=talepler&durum=kabul') ?>" class="buton <?= $f_durum==='kabul' ? '' : 'ikincil' ?>" style="padding:8px 14px;font-size:13px;background:<?= $f_durum==='kabul' ? '#16a34a' : '' ?>">🟢 Kabul (<?= (int)($sayim['kabul'] ?? 0) ?>)</a>
                    <a href="<?= url('yonetim.php?sayfa=talepler&durum=red') ?>" class="buton <?= $f_durum==='red' ? '' : 'ikincil' ?>" style="padding:8px 14px;font-size:13px;background:<?= $f_durum==='red' ? '#dc2626' : '' ?>">🔴 Red (<?= (int)($sayim['red'] ?? 0) ?>)</a>
                </div>

                <div class="panel">
                    <?php if (empty($talepler)): ?>
                        <div class="panel-ic" style="text-align:center;padding:60px 20px;color:var(--muted)">
                            <div style="font-size:48px;margin-bottom:12px">🛡️</div>
                            <h3 style="margin:0 0 6px">Henüz talep yok</h3>
                            <p style="margin:0">Kullanıcılar <code>/kaldirma-talebi</code> sayfasından başvuru yapabilir.</p>
                        </div>
                    <?php else: ?>
                    <table class="tablo">
                        <thead>
                            <tr>
                                <th style="width:60px">#</th>
                                <th>Ad Soyad / E-posta</th>
                                <th>Şikayet</th>
                                <th style="width:140px">Sebep</th>
                                <th style="width:120px">Durum</th>
                                <th style="width:140px">Tarih</th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sebep_kisa = ['telif'=>'Telif','kisilik'=>'Kişilik','kvkk'=>'KVKK','yanlis'=>'Yanlış','itibar'=>'İtibar','diger'=>'Diğer'];
                            $durum_renk = ['bekliyor'=>'#f59e0b','inceleniyor'=>'#3b82f6','kabul'=>'#16a34a','red'=>'#dc2626','bilgi_istendi'=>'#8b5cf6'];
                            foreach ($talepler as $t):
                            ?>
                            <tr>
                                <td>#<?= (int)$t['id'] ?></td>
                                <td>
                                    <strong><?= h($t['ad_soyad']) ?></strong><br>
                                    <small style="color:var(--muted)"><?= h($t['eposta']) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($t['haber_baslik'])): ?>
                                        <span style="font-size:13px"><?= h(mb_substr($t['haber_baslik'], 0, 70, 'UTF-8')) ?><?= mb_strlen($t['haber_baslik'], 'UTF-8') > 70 ? '...' : '' ?></span>
                                    <?php elseif (!empty($t['url'])): ?>
                                        <small style="color:var(--muted)">URL: <?= h(mb_substr($t['url'], 0, 60, 'UTF-8')) ?></small>
                                    <?php else: ?>
                                        <em style="color:var(--muted)">Genel</em>
                                    <?php endif; ?>
                                </td>
                                <td><span style="background:#f1f5f9;padding:3px 8px;border-radius:4px;font-size:12px"><?= h($sebep_kisa[$t['sebep']] ?? $t['sebep']) ?></span></td>
                                <td>
                                    <span style="background:<?= $durum_renk[$t['durum']] ?? '#64748b' ?>;color:#fff;padding:4px 10px;border-radius:30px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em"><?= h($t['durum']) ?></span>
                                </td>
                                <td><small style="color:var(--muted)"><?= h(tr_tarih($t['olusturma_tarihi'])) ?></small></td>
                                <td><a href="<?= url('yonetim.php?sayfa=talepler&id=' . (int)$t['id']) ?>" class="buton ikincil" style="padding:6px 12px;font-size:12px">Aç →</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php break;

            // ====================================
            // LOGLAR
            // ====================================
            case 'loglar':
                $tip_f = $_GET['tip'] ?? '';
                $where_l = []; $params_l = [];
                if ($tip_f && in_array($tip_f, ['bilgi','uyari','hata','guvenlik','cron','islem'], true)) {
                    $where_l[] = 'tip = ?'; $params_l[] = $tip_f;
                }
                $sqlw_l = $where_l ? 'WHERE ' . implode(' AND ', $where_l) : '';
                $st = $db->prepare("SELECT l.*, u.kullanici_adi FROM {$prefix}logs l LEFT JOIN {$prefix}users u ON u.id = l.kullanici_id {$sqlw_l} ORDER BY l.olusturma DESC LIMIT 100");
                $st->execute($params_l);
                $log_liste = $st->fetchAll();
            ?>
                <div class="icerik-bas">
                    <div>
                        <h1>Sistem Loglari</h1>
                        <div class="alt-metin">Son 100 kayit</div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-bas">
                        <form class="filtre-cubuk" method="get">
                            <input type="hidden" name="sayfa" value="loglar">
                            <select name="tip" onchange="this.form.submit()">
                                <option value="">Tüm tipler</option>
                                <?php foreach (['bilgi','uyari','hata','guvenlik','cron','islem'] as $t): ?>
                                    <option value="<?= $t ?>" <?= $tip_f === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="panel-ic sikisik">
                        <div class="tablo-sarmal">
                            <table class="tablo">
                                <thead><tr><th>Tip</th><th>Başlık</th><th>Detay</th><th>Kullanıcı</th><th>IP</th><th>Tarih</th></tr></thead>
                                <tbody>
                                <?php if (empty($log_liste)): ?>
                                    <tr><td colspan="6" class="bos">Log kaydi yok.</td></tr>
                                <?php else: foreach ($log_liste as $l):
                                    $rozet_sinif = match($l['tip']) {
                                        'hata'     => 'hata',
                                        'uyari'    => 'taslak',
                                        'guvenlik' => 'hata',
                                        'cron'     => 'bekliyor',
                                        'islem'    => 'aktif',
                                        default    => 'pasif',
                                    };
                                ?>
                                    <tr>
                                        <td><span class="rozet <?= $rozet_sinif ?>"><?= h($l['tip']) ?></span></td>
                                        <td><strong><?= h(kisalt($l['baslik'], 50)) ?></strong></td>
                                        <td style="font-size:12px;color:var(--muted)"><?= h(kisalt($l['detay'] ?? '', 60)) ?></td>
                                        <td style="font-size:12px"><?= h($l['kullanici_adi'] ?? '—') ?></td>
                                        <td style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:var(--muted-light)"><?= h($l['ip']) ?></td>
                                        <td style="font-size:12px;color:var(--muted)"><?= h(goreceli_zaman($l['olusturma'])) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php break;

            default:
                yonlendir(url('yonetim.php'));
            endswitch;
            ?>
        </div>
    </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>?v=<?= h(ayar('mevcut_surum', '1.0.0')) ?>" defer></script>
</body>
</html>
