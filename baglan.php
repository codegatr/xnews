<?php
/**
 * XNEWS - Merkezi Bağlantı ve Yardimci Fonksiyonlar
 * Tüm dosyalar bu dosyayi include eder.
 */

if (!defined('XNEWS')) { http_response_code(403); die('Erişim reddedildi.'); }

// -----------------------------------------------------
// Konfigurasyon kontrolü
// -----------------------------------------------------
if (!file_exists(__DIR__ . '/config.php')) {
    if (file_exists(__DIR__ . '/kurulum.php') && !defined('KURULUM_MODU')) {
        header('Location: kurulum.php'); exit;
    }
    http_response_code(503);
    die('Konfigurasyon dosyasi bulunamadı. Lutfen kurulumu tamamlayin: <a href="kurulum.php">Kuruluma Git</a>');
}
require_once __DIR__ . '/config.php';

// PHP sürüm kontrolü
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die('Bu uygulama PHP 8.1 ve uzerini gerektirir. Mevcut: ' . PHP_VERSION);
}

// -----------------------------------------------------
// Veritabani baglantisi (PDO)
// -----------------------------------------------------
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
    ]);
} catch (PDOException $e) {
    if (defined('HATA_AYIKLAMA') && HATA_AYIKLAMA) {
        die('Veritabanı baglanti hatasi: ' . $e->getMessage());
    }
    error_log('[XNEWS DB] ' . $e->getMessage());
    http_response_code(503);
    die('Servis gecici olarak kullanilamiyor. Lutfen birazdan tekrar deneyiniz.');
}

// -----------------------------------------------------
// Oturum baslat
// -----------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('XNEWS_SESSION');
    session_start();
}

// =====================================================
// GUVENLIK FONKSIYONLARI
// =====================================================

/**
 * XSS korumasi - HTML ciktisi için
 */
function h(?string $s): string {
    $s = (string)$s;
    // 1. Unicode \p{Cf} = Format kategorisi (zero-width, bidi markers, vs)
    // 2. Variation Selectors FE00-FE0F (text/emoji presentation)
    // 3. Variation Selectors Supplement E0100-E01EF
    // 4. Tag characters E0020-E007F
    // 5. Interlinear Annotation FFF9-FFFB
    $s = preg_replace('/\p{Cf}|[\x{FE00}-\x{FE0F}]|[\x{E0100}-\x{E01EF}]|[\x{E0020}-\x{E007F}]|[\x{FFF9}-\x{FFFB}]/u', '', $s);
    // ASCII kontrol karakterleri (tab, LF, CR hariç)
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
    // Replacement character U+FFFD (bozuk encoding)
    $s = str_replace("\u{FFFD}", '', $s);
    // HTML entity decode + escape
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Veritabanı için ham temizlik (escape'siz)
function temizle_metin(?string $s): string {
    $s = (string)$s;
    $s = preg_replace('/\p{Cf}|[\x{FE00}-\x{FE0F}]|[\x{E0100}-\x{E01EF}]|[\x{E0020}-\x{E007F}]|[\x{FFF9}-\x{FFFB}]/u', '', $s);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
    $s = str_replace("\u{FFFD}", '', $s);
    return trim($s);
}

/**
 * CSRF token uret
 */
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/**
 * CSRF token dogrula
 */
function csrf_dogrula(?string $token): bool {
    return !empty($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

/**
 * CSRF input HTML
 */
function csrf_input(): string {
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

/**
 * IP adresi
 */
function istemci_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $anahtar) {
        if (!empty($_SERVER[$anahtar])) {
            $ip = trim(explode(',', $_SERVER[$anahtar])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * URL dostu slug uret (ASCII - Türkçe karakterler donusturulur)
 * KURAL: URL'lerde asla Türkçe karakter olmaz!
 */
function slug_olustur(string $metin, int $max = 100): string {
    $tr = ['ı','İ','ğ','Ğ','ü','Ü','ş','Ş','ö','Ö','ç','Ç'];
    $en = ['i','i','g','g','u','u','s','s','o','o','c','c'];
    $metin = str_replace($tr, $en, $metin);
    $metin = mb_strtolower($metin, 'UTF-8');
    $metin = preg_replace('/[^a-z0-9\s-]/u', '', $metin);
    $metin = preg_replace('/[\s-]+/', '-', $metin);
    $metin = trim($metin, '-');
    return mb_substr($metin, 0, $max, 'UTF-8');
}

/**
 * Benzersiz slug - zaten varsa -2, -3... ekler
 */
function benzersiz_slug(PDO $db, string $tablo, string $slug, ?int $haric_id = null): string {
    $i = 1; $orijinal = $slug;
    while (true) {
        $sql = "SELECT id FROM `{$tablo}` WHERE slug = ?" . ($haric_id ? " AND id != ?" : "");
        $stmt = $db->prepare($sql);
        $params = $haric_id ? [$slug, $haric_id] : [$slug];
        $stmt->execute($params);
        if (!$stmt->fetch()) return $slug;
        $i++; $slug = $orijinal . '-' . $i;
    }
}

// =====================================================
// AYAR FONKSIYONLARI
// =====================================================

/** Ayar oku (onbellekli) */
function ayar(string $anahtar, $varsayilan = null) {
    global $db;
    static $onbellek = null;
    if ($onbellek === null) {
        $stmt = $db->query("SELECT anahtar, deger FROM `" . DB_PREFIX . "settings`");
        $onbellek = [];
        foreach ($stmt->fetchAll() as $r) $onbellek[$r['anahtar']] = $r['deger'];
    }
    return $onbellek[$anahtar] ?? $varsayilan;
}

/** Ayar guncelle */
function ayar_guncelle(string $anahtar, $deger): bool {
    global $db;
    $stmt = $db->prepare("UPDATE `" . DB_PREFIX . "settings` SET deger = ? WHERE anahtar = ?");
    return $stmt->execute([$deger, $anahtar]);
}

// =====================================================
// LOG
// =====================================================
function log_ekle(string $tip, string $baslik, ?string $detay = null, ?int $kullanici_id = null): void {
    global $db;
    try {
        $stmt = $db->prepare("INSERT INTO `" . DB_PREFIX . "logs` (tip, baslik, detay, kullanici_id, ip, user_agent, url) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            $tip, mb_substr($baslik, 0, 255), $detay, $kullanici_id,
            istemci_ip(),
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            mb_substr($_SERVER['REQUEST_URI'] ?? '', 0, 500),
        ]);
    } catch (Throwable $e) { /* sessiz */ }
}

// =====================================================
// YARDIMCI FONKSIYONLAR
// =====================================================

/** Tarihi Türkçe göster */
function tr_tarih(string $tarih, bool $saat_goster = true): string {
    $ts = strtotime($tarih);
    if (!$ts) return $tarih;
    $aylar = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    $gun = date('j', $ts); $ay = $aylar[date('n', $ts) - 1]; $yil = date('Y', $ts);
    return $saat_goster ? "{$gun} {$ay} {$yil} {$ay}, " . date('H:i', $ts) : "{$gun} {$ay} {$yil}";
}

/** "5 dakika önce" formati */
function goreceli_zaman(string $tarih): string {
    $fark = time() - strtotime($tarih);
    if ($fark < 60)     return 'az önce';
    if ($fark < 3600)   return floor($fark / 60) . ' dakika önce';
    if ($fark < 86400)  return floor($fark / 3600) . ' saat önce';
    if ($fark < 604800) return floor($fark / 86400) . ' gun önce';
    return tr_tarih($tarih, false);
}

/** Metin kisaltma */
function kisalt(?string $metin, int $uzunluk = 150, string $son = '...'): string {
    $metin = strip_tags((string)$metin);
    $metin = preg_replace('/\s+/', ' ', trim($metin));
    if (mb_strlen($metin, 'UTF-8') <= $uzunluk) return $metin;
    return mb_substr($metin, 0, $uzunluk, 'UTF-8') . $son;
}

/** URL olustur */
function url(string $yol = ''): string {
    return rtrim(SITE_URL, '/') . '/' . ltrim($yol, '/');
}

function haber_url(array $haber): string {
    return url('haber/' . $haber['id'] . '-' . $haber['slug']);
}
function kategori_url(array $kat): string {
    return url('kategori/' . $kat['slug']);
}

/** Guvenli yonlendirme */
function yonlendir(string $yol): never {
    header('Location: ' . $yol);
    exit;
}

/** POST mi? */
function post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/** JSON cevap */
function json_cevap(array $veri, int $kod = 200): never {
    http_response_code($kod);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($veri, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// =====================================================
// GORSEL ISLEMLER
// =====================================================

/** Varsayılan gorseli getir (resim bossa) */
function haber_gorsel(?string $resim): string {
    if (!empty($resim)) {
        if (str_starts_with($resim, 'http')) return $resim;
        return url(ltrim($resim, '/'));
    }
    $varsayilan = ayar('varsayilan_resim');
    if ($varsayilan) return url($varsayilan);
    return url('assets/img/placeholder.svg');
}

// =====================================================
// YONETICI YETKI KONTROLU
// =====================================================

function giris_kontrol(): ?array {
    global $db;
    if (empty($_SESSION['yonetici_id'])) return null;

    // Oturum suresi kontrolü
    if (!empty($_SESSION['son_aktivite']) && (time() - $_SESSION['son_aktivite']) > OTURUM_SURESI) {
        session_unset(); session_destroy();
        return null;
    }
    $_SESSION['son_aktivite'] = time();

    $stmt = $db->prepare("SELECT * FROM `" . DB_PREFIX . "users` WHERE id = ? AND durum = 1");
    $stmt->execute([$_SESSION['yonetici_id']]);
    return $stmt->fetch() ?: null;
}

function yonetici_zorunlu(): array {
    $u = giris_kontrol();
    if (!$u) { yonlendir(url('yonetim')); }
    return $u;
}

function admin_zorunlu(): array {
    $u = yonetici_zorunlu();
    if ($u['rol'] !== 'admin') {
        http_response_code(403);
        die('Bu islem için admin yetkisi gerekiyor.');
    }
    return $u;
}

// =====================================================
// HTTP ISTEK (cURL)
// =====================================================

// =====================================================
// REKLAM SISTEMI
// =====================================================

/**
 * Belirli konumda aktif bir reklami goster.
 * Konumlar: ust_banner, sidebar_ust, sidebar_alt, makale_ust,
 *           makale_ic, makale_alt, alt_banner, mobil_sabit, popup
 */
function reklam_goster(string $konum): void {
    global $db;
    if (!ayar('reklam_aktif', '1')) return;

    $stmt = $db->prepare("SELECT * FROM " . DB_PREFIX . "ads
        WHERE konum = ? AND aktif = 1
          AND (baslangic IS NULL OR baslangic <= CURDATE())
          AND (bitis IS NULL OR bitis >= CURDATE())
        ORDER BY RAND() LIMIT 1");
    $stmt->execute([$konum]);
    $r = $stmt->fetch();
    if (!$r) return;

    // Gösterim sayacini rastgele orneklemeyle artir
    if (mt_rand(1, 5) === 1) {
        try { $db->prepare("UPDATE " . DB_PREFIX . "ads SET gosterim = gosterim + 5 WHERE id = ?")->execute([$r['id']]); }
        catch (Throwable $e) {}
    }

    echo '<div class="reklam-slot slot-' . str_replace('_', '-', $konum) . '">';
    if (!in_array($konum, ['popup', 'mobil_sabit'], true)) {
        echo '<div class="reklam-etiket">Reklam</div>';
    }
    if ($konum === 'mobil_sabit') {
        echo '<a class="kapat" onclick="this.parentElement.style.display=\'none\';return false" href="#">×</a>';
    }

    if ($r['tip'] === 'kod' || $r['tip'] === 'adsense') {
        echo $r['kod'];
    } elseif ($r['tip'] === 'gorsel' && !empty($r['gorsel'])) {
        $resim = str_starts_with($r['gorsel'], 'http') ? $r['gorsel'] : url($r['gorsel']);
        $link  = !empty($r['hedef_url']) ? url('r.php?id=' . $r['id']) : '#';
        echo '<a href="' . h($link) . '" target="_blank" rel="noopener sponsored">';
        echo '<img src="' . h($resim) . '" alt="' . h($r['ad']) . '" loading="lazy">';
        echo '</a>';
    }
    echo '</div>';
}

/**
 * Makale icerigine N. paragraftan sonra reklam enjekte eder
 */
function reklam_icerige_enjekte(string $html, int $paragraf_sonra = 3): string {
    if (!ayar('reklam_aktif', '1')) return $html;
    global $db;
    $stmt = $db->prepare("SELECT id FROM " . DB_PREFIX . "ads WHERE konum = 'makale_ic' AND aktif = 1 LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) return $html;

    $parcalar = preg_split('/(<\/p>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $p = 0; $yeni = ''; $ekli = false;
    foreach ($parcalar as $parca) {
        $yeni .= $parca;
        if (strtolower($parca) === '</p>' && ++$p === $paragraf_sonra && !$ekli) {
            ob_start(); reklam_goster('makale_ic'); $yeni .= ob_get_clean();
            $ekli = true;
        }
    }
    return $yeni;
}

// =====================================================
// HTTP ISTEK (cURL)
// =====================================================

function http_getir(string $url, int $zaman_asimi = 15, array $ek_baslik = []): array {
    // Hedef sitenin origin'ini Referer olarak kullan (Cloudflare bot korumasını aşmaya yardımcı)
    $referer = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/';

    $baslik = array_merge([
        'Accept: application/rss+xml, application/atom+xml, application/xml;q=0.9, text/xml;q=0.9, */*;q=0.8',
        'Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding: gzip, deflate, br',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'DNT: 1',
        'Upgrade-Insecure-Requests: 1',
        'Referer: ' . $referer,
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: same-origin',
    ], $ek_baslik);

    // Retry: 503/429/502 geldiğinde bir kez daha dene (Cloudflare geçici limitleri)
    $kod = 0;
    $icerik = '';
    $hata = '';
    for ($deneme = 0; $deneme < 2; $deneme++) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $zaman_asimi,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER     => $baslik,
            CURLOPT_ENCODING       => '', // otomatik gzip/deflate decode
        ]);
        $icerik = curl_exec($ch);
        $kod    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hata   = curl_error($ch);
        curl_close($ch);
        // Kalici olmayan hatalarda (rate limit / geçici) tekrar dene
        if (!in_array($kod, [429, 502, 503, 504, 0], true)) break;
        if ($deneme === 0) sleep(2); // Cloudflare rate limit için kısa bekleme
    }
    return ['icerik' => $icerik, 'kod' => $kod, 'hata' => $hata];
}
