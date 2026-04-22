<?php
/**
 * XNEWS - Kurulum Sihirbazi
 * Kullanim: tarayicidan xnews.com.tr/kurulum.php
 */

define('XNEWS', true);
define('KURULUM_MODU', true);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Zaten kuruluysa engelle
if (file_exists(__DIR__ . '/install.lock')) {
    die('<div style="font-family:system-ui;padding:40px;max-width:600px;margin:50px auto;background:#fef3c7;border:1px solid #f59e0b;border-radius:12px;color:#78350f">
    <h2 style="margin:0 0 12px">Kurulum zaten tamamlanmis</h2>
    <p>Yeniden kurmak icin sunucudan <code>install.lock</code> dosyasini silin.</p>
    <p><a href="yonetim.php">Yonetim paneline git &rarr;</a></p></div>');
}

session_start();
$adim = (int)($_GET['adim'] ?? 1);
$hata = '';
$basari = '';

// -----------------------------------------------------
// ADIM 4: Tablolari olustur ve admin ekle
// -----------------------------------------------------
if ($adim === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $k = $_SESSION['kurulum'] ?? [];
        if (empty($k)) { throw new Exception('Oturum verileri kayboldu. Basa donun.'); }

        // Admin form bilgileri
        $admin_ad = trim($_POST['admin_ad'] ?? '');
        $admin_kullanici = trim($_POST['admin_kullanici'] ?? '');
        $admin_eposta = trim($_POST['admin_eposta'] ?? '');
        $admin_sifre = $_POST['admin_sifre'] ?? '';
        $site_adi = trim($_POST['site_adi'] ?? 'XNEWS');
        $site_url = rtrim(trim($_POST['site_url'] ?? ''), '/');

        if (strlen($admin_kullanici) < 4) throw new Exception('Kullanici adi en az 4 karakter olmali.');
        if (!filter_var($admin_eposta, FILTER_VALIDATE_EMAIL)) throw new Exception('Gecerli bir e-posta girin.');
        if (strlen($admin_sifre) < 8) throw new Exception('Sifre en az 8 karakter olmali.');
        if (empty($site_url)) throw new Exception('Site URL zorunlu.');

        // DB'ye baglan
        $dsn = "mysql:host={$k['db_host']};dbname={$k['db_name']};charset=utf8mb4";
        $db = new PDO($dsn, $k['db_user'], $k['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);

        // Semayi uygula
        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        if (!$schema) throw new Exception('sql/schema.sql okunamadi.');
        // Prefix'i uygula
        $prefix = preg_replace('/[^a-z0-9_]/i', '', $k['db_prefix']);
        if ($prefix !== 'xn_') { $schema = str_replace('xn_', $prefix, $schema); }
        $db->exec($schema);

        // Kaynaklari ekle
        $sources = file_get_contents(__DIR__ . '/sql/sources_seed.sql');
        if ($sources) {
            if ($prefix !== 'xn_') { $sources = str_replace('xn_', $prefix, $sources); }
            $db->exec($sources);
        }

        // Admin kullaniciyi ekle
        $stmt = $db->prepare("INSERT INTO `{$prefix}users` (kullanici_adi, eposta, sifre_hash, ad_soyad, rol) VALUES (?,?,?,?,'admin')");
        $stmt->execute([
            $admin_kullanici, $admin_eposta,
            password_hash($admin_sifre, PASSWORD_BCRYPT),
            $admin_ad ?: $admin_kullanici,
        ]);

        // Site URL ve adi ayarlara yaz
        $db->prepare("UPDATE `{$prefix}settings` SET deger = ? WHERE anahtar = 'site_adi'")->execute([$site_adi]);

        // config.php uret
        $guvenlik = bin2hex(random_bytes(32));
        $csrf = bin2hex(random_bytes(32));
        $cron = bin2hex(random_bytes(16));

        $config = "<?php\n";
        $config .= "/**\n * XNEWS - Konfigurasyon\n * Bu dosya kurulumda otomatik olusturuldu: " . date('Y-m-d H:i:s') . "\n */\n";
        $config .= "if (!defined('XNEWS')) { http_response_code(403); die('Erisim reddedildi.'); }\n\n";
        $config .= "// VERITABANI\n";
        $config .= "define('DB_HOST', " . var_export($k['db_host'], true) . ");\n";
        $config .= "define('DB_NAME', " . var_export($k['db_name'], true) . ");\n";
        $config .= "define('DB_USER', " . var_export($k['db_user'], true) . ");\n";
        $config .= "define('DB_PASS', " . var_export($k['db_pass'], true) . ");\n";
        $config .= "define('DB_CHARSET', 'utf8mb4');\n";
        $config .= "define('DB_PREFIX', " . var_export($prefix, true) . ");\n\n";
        $config .= "// SITE\n";
        $config .= "define('SITE_URL', " . var_export($site_url, true) . ");\n";
        $config .= "define('SITE_ADI', " . var_export($site_adi, true) . ");\n";
        $config .= "define('SITE_SLOGAN', 'Haberin Hizli Adresi');\n\n";
        $config .= "// GUVENLIK\n";
        $config .= "define('GUVENLIK_ANAHTARI', " . var_export($guvenlik, true) . ");\n";
        $config .= "define('CSRF_ANAHTARI', " . var_export($csrf, true) . ");\n";
        $config .= "define('OTURUM_SURESI', 7200);\n\n";
        $config .= "// GITHUB GUNCELLEME\n";
        $config .= "define('GITHUB_TOKEN', '');\n";
        $config .= "define('GUNCELLEME_AKTIF', true);\n\n";
        $config .= "// RSS / CRON\n";
        $config .= "define('CRON_ANAHTARI', " . var_export($cron, true) . ");\n";
        $config .= "define('MAX_CEKIM_SURESI', 300);\n";
        $config .= "define('HTTP_TIMEOUT', 15);\n\n";
        $config .= "// UPLOAD\n";
        $config .= "define('UPLOAD_DIZINI', __DIR__ . '/uploads');\n";
        $config .= "define('UPLOAD_URL', SITE_URL . '/uploads');\n";
        $config .= "define('MAX_UPLOAD_BOYUT', 5 * 1024 * 1024);\n";
        $config .= "define('IZIN_VERILEN_UZANTI', 'jpg,jpeg,png,webp,gif,svg');\n\n";
        $config .= "date_default_timezone_set('Europe/Istanbul');\n";
        $config .= "define('HATA_AYIKLAMA', false);\n";
        $config .= "if (!HATA_AYIKLAMA) { error_reporting(0); ini_set('display_errors','0'); }\n";

        if (!file_put_contents(__DIR__ . '/config.php', $config)) {
            throw new Exception('config.php yazilamadi. Dosya izinlerini kontrol edin (755/644).');
        }
        @chmod(__DIR__ . '/config.php', 0644);

        // install.lock olustur
        file_put_contents(__DIR__ . '/install.lock', date('Y-m-d H:i:s') . "\n" . $site_url);

        // uploads/.htaccess (PHP calistirma engeli)
        @file_put_contents(__DIR__ . '/uploads/.htaccess',
            "<FilesMatch \"\\.(php|phtml|phar)$\">\nOrder allow,deny\nDeny from all\n</FilesMatch>\n");

        // Oturumu temizle
        unset($_SESSION['kurulum']);
        $_SESSION['kurulum_bitti'] = true;

        header('Location: kurulum.php?adim=5');
        exit;
    } catch (Throwable $e) {
        $hata = 'Kurulum hatasi: ' . $e->getMessage();
    }
}

// -----------------------------------------------------
// ADIM 3: DB bilgilerini test et
// -----------------------------------------------------
if ($adim === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';
    $prefix = trim($_POST['db_prefix'] ?? 'xn_');
    if (!preg_match('/^[a-z0-9_]+$/i', $prefix)) { $hata = 'Prefix sadece harf/rakam/altcizgi icerebilir.'; }
    else {
        try {
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            $test = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
            $test = null;
            $_SESSION['kurulum'] = compact('host','name','user','pass','prefix');
            $_SESSION['kurulum']['db_host'] = $host;
            $_SESSION['kurulum']['db_name'] = $name;
            $_SESSION['kurulum']['db_user'] = $user;
            $_SESSION['kurulum']['db_pass'] = $pass;
            $_SESSION['kurulum']['db_prefix'] = $prefix;
            header('Location: kurulum.php?adim=4');
            exit;
        } catch (PDOException $e) {
            $hata = 'Baglanti basarisiz: ' . $e->getMessage();
        }
    }
}

// -----------------------------------------------------
// ADIM 2: Gereksinim kontrolu
// -----------------------------------------------------
function ger_kontrol(): array {
    $r = [];
    $r[] = ['PHP 8.1+', version_compare(PHP_VERSION, '8.1.0', '>='), 'Mevcut: ' . PHP_VERSION];
    $r[] = ['PDO MySQL', extension_loaded('pdo_mysql'), 'PDO MySQL eklentisi gerekli'];
    $r[] = ['cURL',      extension_loaded('curl'),      'RSS cekimi icin'];
    $r[] = ['mbstring',  extension_loaded('mbstring'),  'Cok dilli metinler icin'];
    $r[] = ['SimpleXML', extension_loaded('SimpleXML'), 'RSS ayristirma icin'];
    $r[] = ['ZipArchive',class_exists('ZipArchive'),    'Guncelleme sistemi icin'];
    $r[] = ['JSON',      extension_loaded('json'),      'JSON islemleri icin'];
    $r[] = ['GD veya Imagick', extension_loaded('gd') || extension_loaded('imagick'), 'Gorsel isleme icin'];
    $r[] = ['Yazma izni (kok)', is_writable(__DIR__), 'config.php yazimi icin'];
    $r[] = ['Yazma izni (uploads/)', is_writable(__DIR__ . '/uploads') || @chmod(__DIR__ . '/uploads', 0755), 'Yukleme klasoru'];
    return $r;
}

// -----------------------------------------------------
// ADIM 5: Tamamlandi - admin temizlik
// -----------------------------------------------------
if ($adim === 5 && empty($_SESSION['kurulum_bitti'])) {
    header('Location: kurulum.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>XNEWS Kurulum</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #0f172a, #1e293b); min-height: 100vh; color: #e2e8f0; padding: 40px 16px; }
.kutu { max-width: 720px; margin: 0 auto; background: #fff; color: #0f172a; border-radius: 16px; box-shadow: 0 30px 60px rgba(0,0,0,.4); overflow: hidden; }
.baslik { padding: 32px 40px; background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; }
.baslik h1 { font-size: 28px; font-weight: 800; letter-spacing: -.5px; }
.baslik p { opacity: .9; margin-top: 6px; font-size: 14px; }
.adimlar { display: flex; background: #f1f5f9; padding: 0; border-bottom: 1px solid #e2e8f0; }
.adim { flex: 1; padding: 16px; text-align: center; font-size: 13px; color: #64748b; position: relative; }
.adim.aktif { color: #dc2626; font-weight: 700; background: #fff; }
.adim.tamam { color: #16a34a; }
.adim:not(:last-child)::after { content: '›'; position: absolute; right: -6px; top: 50%; transform: translateY(-50%); color: #cbd5e1; font-size: 18px; }
.icerik { padding: 40px; }
h2 { font-size: 22px; margin-bottom: 8px; color: #0f172a; }
.alt { color: #64748b; margin-bottom: 28px; font-size: 14px; }
.grup { margin-bottom: 18px; }
label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: #334155; }
input[type=text], input[type=password], input[type=email], input[type=url] { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: border .2s; }
input:focus { outline: none; border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.1); }
.ipucu { font-size: 12px; color: #94a3b8; margin-top: 4px; }
.buton { display: inline-block; padding: 12px 28px; background: #dc2626; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .2s; }
.buton:hover { background: #b91c1c; transform: translateY(-1px); }
.buton.ikincil { background: #f1f5f9; color: #334155; }
.buton.ikincil:hover { background: #e2e8f0; }
.butonlar { margin-top: 30px; display: flex; gap: 12px; justify-content: space-between; }
.hata { background: #fee2e2; border-left: 4px solid #dc2626; padding: 14px 18px; border-radius: 8px; color: #991b1b; margin-bottom: 20px; font-size: 14px; }
.basari { background: #dcfce7; border-left: 4px solid #16a34a; padding: 14px 18px; border-radius: 8px; color: #166534; margin-bottom: 20px; font-size: 14px; }
.kontrol { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.kontrol .satir { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; }
.kontrol .satir:last-child { border-bottom: none; }
.kontrol .ad { font-weight: 600; font-size: 14px; }
.kontrol .aciklama { color: #94a3b8; font-size: 12px; margin-top: 2px; }
.rozet { padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
.rozet.ok { background: #dcfce7; color: #166534; }
.rozet.hata { background: #fee2e2; color: #991b1b; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } .icerik { padding: 28px 20px; } .baslik { padding: 24px; } }
.bitti { text-align: center; padding: 20px 0; }
.bitti .tik { font-size: 64px; margin-bottom: 16px; }
.bitti h2 { font-size: 28px; color: #16a34a; margin-bottom: 12px; }
.bilgi-kart { background: #f1f5f9; padding: 18px; border-radius: 10px; margin-top: 20px; text-align: left; font-size: 13px; line-height: 1.7; }
.bilgi-kart code { background: #fff; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #dc2626; }
</style>
</head>
<body>
<div class="kutu">
    <div class="baslik">
        <h1>XNEWS Kurulum Sihirbazi</h1>
        <p>Surum 1.0.0 · CODEGA · PHP <?= PHP_VERSION ?></p>
    </div>
    <div class="adimlar">
        <div class="adim <?= $adim === 1 ? 'aktif' : ($adim > 1 ? 'tamam' : '') ?>">1. Karsilama</div>
        <div class="adim <?= $adim === 2 ? 'aktif' : ($adim > 2 ? 'tamam' : '') ?>">2. Gereksinim</div>
        <div class="adim <?= $adim === 3 ? 'aktif' : ($adim > 3 ? 'tamam' : '') ?>">3. Veritabani</div>
        <div class="adim <?= $adim === 4 ? 'aktif' : ($adim > 4 ? 'tamam' : '') ?>">4. Yonetici</div>
        <div class="adim <?= $adim === 5 ? 'aktif tamam' : '' ?>">5. Bitti</div>
    </div>
    <div class="icerik">
        <?php if ($hata): ?><div class="hata">⚠ <?= htmlspecialchars($hata) ?></div><?php endif; ?>

        <?php if ($adim === 1): ?>
            <h2>XNEWS'e Hosgeldiniz</h2>
            <p class="alt">Bu sihirbaz XNEWS kurulumunu 4 adimda tamamlayacaktir.</p>
            <div style="line-height:1.8;font-size:14px;color:#475569">
                <p><strong>Baslamadan once hazirlayin:</strong></p>
                <ul style="margin-left:20px;margin-top:8px">
                    <li>DirectAdmin veya hosting panelinizden olusturulmus bir MySQL veritabani</li>
                    <li>Veritabani kullanici adi ve sifresi</li>
                    <li>Site URL (ornek: https://xnews.com.tr)</li>
                    <li>Yonetici paneli icin kullanici adi/sifre</li>
                </ul>
            </div>
            <div class="butonlar"><span></span><a href="?adim=2" class="buton">Baslayalim &rarr;</a></div>

        <?php elseif ($adim === 2): ?>
            <h2>Sunucu Gereksinimleri</h2>
            <p class="alt">Kuruluma devam edebilmek icin asagidaki gereksinimlerin hepsi OK olmali.</p>
            <div class="kontrol">
                <?php $hepsi_ok = true; foreach (ger_kontrol() as [$ad, $durum, $acik]): ?>
                <div class="satir">
                    <div>
                        <div class="ad"><?= htmlspecialchars($ad) ?></div>
                        <div class="aciklama"><?= htmlspecialchars($acik) ?></div>
                    </div>
                    <span class="rozet <?= $durum ? 'ok' : 'hata' ?>"><?= $durum ? '✓ OK' : '✗ EKSIK' ?></span>
                </div>
                <?php if (!$durum) $hepsi_ok = false; endforeach; ?>
            </div>
            <div class="butonlar">
                <a href="?adim=1" class="buton ikincil">← Geri</a>
                <?php if ($hepsi_ok): ?>
                    <a href="?adim=3" class="buton">Devam →</a>
                <?php else: ?>
                    <span style="color:#dc2626;font-size:13px;align-self:center">Eksikleri gidermeden devam edilemez</span>
                <?php endif; ?>
            </div>

        <?php elseif ($adim === 3): ?>
            <h2>Veritabani Bilgileri</h2>
            <p class="alt">DirectAdmin panelinden olusturdugunuz MySQL bilgilerini girin.</p>
            <form method="post">
                <div class="grid-2">
                    <div class="grup">
                        <label>Sunucu</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                        <div class="ipucu">Genellikle: localhost</div>
                    </div>
                    <div class="grup">
                        <label>Tablo Onek (Prefix)</label>
                        <input type="text" name="db_prefix" value="<?= htmlspecialchars($_POST['db_prefix'] ?? 'xn_') ?>" pattern="[a-z0-9_]+" required>
                        <div class="ipucu">Onerilen: xn_</div>
                    </div>
                </div>
                <div class="grup">
                    <label>Veritabani Adi</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
                    <div class="ipucu">Ornek: kullanici_xnews</div>
                </div>
                <div class="grid-2">
                    <div class="grup">
                        <label>Kullanici Adi</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                    </div>
                    <div class="grup">
                        <label>Sifre</label>
                        <input type="password" name="db_pass" value="">
                    </div>
                </div>
                <div class="butonlar">
                    <a href="?adim=2" class="buton ikincil">← Geri</a>
                    <button type="submit" class="buton">Baglantiyi Test Et →</button>
                </div>
            </form>

        <?php elseif ($adim === 4): ?>
            <?php if (empty($_SESSION['kurulum'])) { header('Location: ?adim=3'); exit; } ?>
            <h2>Yonetici Hesabi ve Site Bilgileri</h2>
            <p class="alt">Bu bilgilerle yonetim paneline giriş yapacaksınız.</p>
            <form method="post">
                <div class="grid-2">
                    <div class="grup">
                        <label>Site Adi</label>
                        <input type="text" name="site_adi" value="XNEWS" required>
                    </div>
                    <div class="grup">
                        <label>Site URL</label>
                        <input type="url" name="site_url" value="<?= htmlspecialchars((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']) ?>" required>
                        <div class="ipucu">Sonunda / yok!</div>
                    </div>
                </div>
                <hr style="border:none;border-top:1px dashed #e2e8f0;margin:24px 0">
                <div class="grup">
                    <label>Ad Soyad</label>
                    <input type="text" name="admin_ad" value="<?= htmlspecialchars($_POST['admin_ad'] ?? '') ?>" required>
                </div>
                <div class="grid-2">
                    <div class="grup">
                        <label>Kullanici Adi</label>
                        <input type="text" name="admin_kullanici" value="<?= htmlspecialchars($_POST['admin_kullanici'] ?? '') ?>" minlength="4" required>
                    </div>
                    <div class="grup">
                        <label>E-posta</label>
                        <input type="email" name="admin_eposta" value="<?= htmlspecialchars($_POST['admin_eposta'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="grup">
                    <label>Sifre (en az 8 karakter)</label>
                    <input type="password" name="admin_sifre" minlength="8" required>
                </div>
                <div class="butonlar">
                    <a href="?adim=3" class="buton ikincil">← Geri</a>
                    <button type="submit" class="buton">Kurulumu Tamamla →</button>
                </div>
            </form>

        <?php elseif ($adim === 5): ?>
            <div class="bitti">
                <div class="tik">✅</div>
                <h2>Kurulum Tamamlandi!</h2>
                <p style="color:#475569">XNEWS artik kullanima hazir.</p>
                <div class="bilgi-kart">
                    <strong>⚠ Guvenlik icin simdi yapmaniz gerekenler:</strong>
                    <ol style="margin-left:20px;margin-top:10px">
                        <li><code>kurulum.php</code> dosyasini FTP'den silin</li>
                        <li><code>config.php</code> izinlerini 644 yapin (yazmaya kapali)</li>
                        <li>DirectAdmin'den cron job ekleyin:<br>
                            <code style="font-size:11px">*/10 * * * * wget -q -O /dev/null "https://xnews.com.tr/cron.php?anahtar=CRON_ANAHTARI"</code>
                        </li>
                    </ol>
                </div>
                <div class="butonlar" style="justify-content:center">
                    <a href="yonetim.php" class="buton">Yonetim Paneline Giris Yap →</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
