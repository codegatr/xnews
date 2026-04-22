<?php
/**
 * XNEWS - GitHub Guncelleme Sistemi
 *
 * Sadece admin yetkili kullanicilar erisebilir.
 * Kullanim: https://xnews.com.tr/guncelle.php
 *
 * Akis:
 *   1. manifest.json'dan mevcut surum okunur
 *   2. GitHub API'den son release bilgisi alinir
 *   3. Yeni surum varsa admin'e "Guncelle" butonu gosterilir
 *   4. Butona basinca ZIP indirilir -> yedek alinir -> dosyalar degistirilir
 *   5. config.php, uploads/, install.lock korunur (manifest'teki "korunan_dosyalar")
 *
 * GitHub Public repo icin token gerekmez. Private icin config.php'deki GITHUB_TOKEN kullanilir.
 */
define('XNEWS', true);
require __DIR__ . '/baglan.php';

// Yetki
$yonetici = admin_zorunlu();

// Guncelleme aktif mi?
if (!defined('GUNCELLEME_AKTIF') || !GUNCELLEME_AKTIF) {
    die('Guncelleme sistemi devre disi (config.php).');
}

$islem     = $_GET['islem'] ?? '';
$mesaj     = '';
$mesaj_tip = '';

// =====================================================
// YARDIMCI FONKSIYONLAR
// =====================================================

function manifest_oku(): array {
    $yol = __DIR__ . '/manifest.json';
    if (!file_exists($yol)) throw new RuntimeException('manifest.json bulunamadi.');
    $veri = json_decode(file_get_contents($yol), true);
    if (!$veri) throw new RuntimeException('manifest.json gecersiz.');
    return $veri;
}

function github_son_release(string $repo): array {
    $url = "https://api.github.com/repos/{$repo}/releases/latest";
    $ek_baslik = ['User-Agent: XNEWS-Updater/1.0', 'Accept: application/vnd.github+json'];
    if (defined('GITHUB_TOKEN') && !empty(GITHUB_TOKEN)) {
        $ek_baslik[] = 'Authorization: Bearer ' . GITHUB_TOKEN;
    }
    $r = http_getir($url, 15, $ek_baslik);
    if ($r['kod'] === 404) {
        throw new RuntimeException('Repo bulunamadi veya henuz release yok: ' . $repo);
    }
    if ($r['kod'] !== 200) {
        throw new RuntimeException('GitHub API hatasi: HTTP ' . $r['kod']);
    }
    $veri = json_decode($r['icerik'], true);
    if (empty($veri['tag_name'])) {
        throw new RuntimeException('Release bilgisi okunamadi.');
    }
    return $veri;
}

function surum_karsilastir(string $mevcut, string $yeni): int {
    // v onekini temizle
    $mevcut = ltrim($mevcut, 'vV');
    $yeni   = ltrim($yeni, 'vV');
    return version_compare($yeni, $mevcut, '>') ? 1 : ($yeni === $mevcut ? 0 : -1);
}

function zip_indir(string $url, string $hedef): void {
    $ek_baslik = ['User-Agent: XNEWS-Updater/1.0'];
    if (defined('GITHUB_TOKEN') && !empty(GITHUB_TOKEN)) {
        $ek_baslik[] = 'Authorization: Bearer ' . GITHUB_TOKEN;
    }
    $r = http_getir($url, 60, $ek_baslik);
    if ($r['kod'] !== 200) {
        throw new RuntimeException('ZIP indirilemedi: HTTP ' . $r['kod']);
    }
    if (empty($r['icerik'])) {
        throw new RuntimeException('Bos ZIP dosyasi.');
    }
    if (file_put_contents($hedef, $r['icerik']) === false) {
        throw new RuntimeException('ZIP yazilamadi: ' . $hedef);
    }
}

/**
 * Guncelleme oncesi mevcut dosyalari yedekle
 */
function yedek_al(array $korunan): string {
    $zaman = date('Y-m-d_H-i-s');
    $yedek_dizin = __DIR__ . '/uploads/yedek/' . $zaman;
    if (!mkdir($yedek_dizin, 0755, true) && !is_dir($yedek_dizin)) {
        throw new RuntimeException('Yedek dizini olusturulamadi.');
    }

    // Kok dizindeki tum PHP dosyalarini yedekle
    foreach (glob(__DIR__ . '/*.php') as $f) {
        copy($f, $yedek_dizin . '/' . basename($f));
    }
    // Assets yedekle
    if (is_dir(__DIR__ . '/assets')) {
        klasor_kopyala(__DIR__ . '/assets', $yedek_dizin . '/assets');
    }
    // manifest.json, .htaccess da yedekle
    foreach (['manifest.json', '.htaccess', 'robots.txt'] as $f) {
        if (file_exists(__DIR__ . '/' . $f)) {
            copy(__DIR__ . '/' . $f, $yedek_dizin . '/' . $f);
        }
    }

    return $yedek_dizin;
}

function klasor_kopyala(string $kaynak, string $hedef): void {
    if (!is_dir($hedef)) mkdir($hedef, 0755, true);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($kaynak, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $item) {
        $d = $hedef . DIRECTORY_SEPARATOR . $iter->getSubPathname();
        if ($item->isDir()) {
            if (!is_dir($d)) mkdir($d, 0755, true);
        } else {
            copy($item->getRealPath(), $d);
        }
    }
}

/**
 * ZIP'i ac ve korunan dosyalari atlayarak uygula
 */
function guncelleme_uygula(string $zip_yolu, array $korunan): int {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive eklentisi yok.');
    }
    $zip = new ZipArchive();
    if ($zip->open($zip_yolu) !== true) {
        throw new RuntimeException('ZIP acilamadi.');
    }

    $gecici = __DIR__ . '/uploads/guncelleme_gecici';
    if (is_dir($gecici)) klasor_sil($gecici);
    if (!mkdir($gecici, 0755, true)) {
        throw new RuntimeException('Gecici klasor olusturulamadi.');
    }

    $zip->extractTo($gecici);
    $zip->close();

    // GitHub ZIP'i genelde "repo-adi-hash/" seklinde bir ust klasor iceriyor
    $icerik = glob($gecici . '/*', GLOB_ONLYDIR);
    $kok_dizin = (count($icerik) === 1 && is_dir($icerik[0])) ? $icerik[0] : $gecici;

    // Korunan dosya/klasor kontrol fonksiyonu
    $korunuyor_mu = function(string $rel) use ($korunan): bool {
        foreach ($korunan as $k) {
            $k = trim($k, '/');
            if ($rel === $k) return true;
            if (str_ends_with($k, '/') || is_dir(__DIR__ . '/' . $k)) {
                if (str_starts_with($rel, rtrim($k, '/') . '/')) return true;
            }
        }
        return false;
    };

    $sayac = 0;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($kok_dizin, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $item) {
        $rel = str_replace('\\', '/', $iter->getSubPathname());
        // Korunan dosya?
        if ($korunuyor_mu($rel)) continue;
        // install.lock ve yedek klasoru atla
        if ($rel === 'install.lock' || str_starts_with($rel, 'uploads/')) continue;

        $hedef = __DIR__ . '/' . $rel;
        if ($item->isDir()) {
            if (!is_dir($hedef)) @mkdir($hedef, 0755, true);
        } else {
            $hedef_dir = dirname($hedef);
            if (!is_dir($hedef_dir)) @mkdir($hedef_dir, 0755, true);
            if (@copy($item->getRealPath(), $hedef)) $sayac++;
        }
    }

    // Gecici klasoru temizle
    klasor_sil($gecici);
    @unlink($zip_yolu);

    return $sayac;
}

function klasor_sil(string $yol): void {
    if (!is_dir($yol)) return;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($yol, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iter as $item) {
        $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
    }
    @rmdir($yol);
}

// =====================================================
// ISLEMLER
// =====================================================

$manifest       = manifest_oku();
$mevcut_surum   = $manifest['surum'];
$repo           = $manifest['github_repo'] ?? '';
$korunan        = $manifest['korunan_dosyalar'] ?? ['config.php', 'uploads/'];
$release_bilgi  = null;
$yeni_surum_var = false;

try {
    if ($islem === 'kontrol' || $islem === 'guncelle') {
        if (empty($repo)) throw new RuntimeException('manifest.json icinde github_repo tanimli degil.');
        $release_bilgi  = github_son_release($repo);
        $yeni_surum     = ltrim($release_bilgi['tag_name'], 'vV');
        $yeni_surum_var = surum_karsilastir($mevcut_surum, $yeni_surum) === 1;
        ayar_guncelle('son_guncelleme_kontrol', date('Y-m-d H:i:s'));
    }

    if ($islem === 'guncelle' && post()) {
        if (!csrf_dogrula($_POST['_csrf'] ?? '')) {
            throw new RuntimeException('Guvenlik dogrulamasi basarisiz.');
        }
        if (!$yeni_surum_var) {
            throw new RuntimeException('Yeni surum bulunamadi.');
        }

        $zip_url = $release_bilgi['zipball_url'] ?? '';
        // Onceliği release asset'lerde (xnews-X.Y.Z.zip gibi) varsa onu al
        if (!empty($release_bilgi['assets'])) {
            foreach ($release_bilgi['assets'] as $asset) {
                if (str_ends_with($asset['name'], '.zip')) {
                    $zip_url = $asset['browser_download_url'];
                    break;
                }
            }
        }
        if (empty($zip_url)) throw new RuntimeException('Release icinde ZIP bulunamadi.');

        @set_time_limit(300);
        @ini_set('memory_limit', '256M');

        $zip_yolu = __DIR__ . '/uploads/guncelleme_' . time() . '.zip';
        zip_indir($zip_url, $zip_yolu);

        $yedek_yolu = yedek_al($korunan);
        log_ekle('islem', 'Guncelleme yedegi alindi', $yedek_yolu, $yonetici['id']);

        $dosya_sayisi = guncelleme_uygula($zip_yolu, $korunan);

        // manifest.json'un yeni surumu yansittigini dogrula
        $yeni_manifest = manifest_oku();
        ayar_guncelle('mevcut_surum', $yeni_manifest['surum']);

        log_ekle('islem', 'Guncelleme tamamlandi',
            'v' . $mevcut_surum . ' -> v' . $yeni_manifest['surum'] . ' (' . $dosya_sayisi . ' dosya)', $yonetici['id']);

        $mesaj = 'Guncelleme basariyla uygulandi. v' . $mevcut_surum . ' → v' . $yeni_manifest['surum'] . ' (' . $dosya_sayisi . ' dosya). Yedek: ' . basename($yedek_yolu);
        $mesaj_tip = 'basari';
        $mevcut_surum = $yeni_manifest['surum'];
        $yeni_surum_var = false;
    }
} catch (Throwable $e) {
    $mesaj = $e->getMessage();
    $mesaj_tip = 'hata';
    log_ekle('hata', 'Guncelleme hatasi', $e->getMessage(), $yonetici['id']);
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Guncelleme - XNEWS</title>
<link rel="icon" type="image/svg+xml" href="<?= url('favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
<style>
body { background: #f5f7fa; padding: 40px 20px; }
.gun-kapsayici { max-width: 760px; margin: 0 auto; }
.gun-bas { background: #fff; border-radius: 12px; padding: 32px 40px; box-shadow: var(--shadow); margin-bottom: 20px; }
.gun-bas h1 { font-size: 24px; margin-bottom: 6px; }
.gun-bas .alt { color: var(--muted); font-size: 14px; }
.surum-kart { display: grid; grid-template-columns: 1fr auto 1fr; gap: 20px; align-items: center; margin: 24px 0; padding: 24px; background: linear-gradient(135deg, #f8fafc, #eef1f6); border-radius: 10px; border: 1px solid var(--border); }
.surum-kutu { text-align: center; }
.surum-kutu .etiket { font-size: 12px; text-transform: uppercase; letter-spacing: .1em; color: var(--muted); margin-bottom: 8px; }
.surum-kutu .numara { font-family: 'Oswald', sans-serif; font-size: 40px; font-weight: 700; color: var(--ink); line-height: 1; }
.surum-kutu.yeni .numara { color: var(--brand); }
.ok-ikon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background: var(--brand); color: #fff; border-radius: 50%; font-size: 24px; }
.release-detay { background: #0f172a; color: #e2e8f0; padding: 20px; border-radius: 8px; margin-top: 20px; font-size: 13px; }
.release-detay h3 { color: #fff; margin-bottom: 12px; font-size: 15px; }
.release-detay pre { background: transparent; padding: 0; color: #cbd5e1; white-space: pre-wrap; font-family: 'IBM Plex Mono', monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
.bilgi-liste { background: #f8fafc; padding: 16px 22px; border-radius: 8px; margin: 16px 0; font-size: 13px; line-height: 1.7; }
.bilgi-liste li { padding: 2px 0; }
.back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--muted); margin-bottom: 20px; }
.back:hover { color: var(--brand); }
</style>
</head>
<body>
<div class="gun-kapsayici">
    <a href="<?= url('yonetim.php') ?>" class="back">← Yonetim Paneline Don</a>

    <div class="gun-bas">
        <h1>🔄 Guncelleme Sistemi</h1>
        <p class="alt">GitHub Releases tabanli otomatik guncelleme · Repo: <code><?= h($repo) ?></code></p>
    </div>

    <?php if ($mesaj): ?>
        <div class="alert alert-<?= h($mesaj_tip) ?>" style="margin-bottom:20px"><?= h($mesaj) ?></div>
    <?php endif; ?>

    <?php if ($islem === '' || ($islem === 'kontrol' && empty($release_bilgi))): ?>
        <div class="gun-bas">
            <h2 style="font-size:18px;margin-bottom:8px">Mevcut Surum</h2>
            <p style="color:var(--muted);margin-bottom:20px">Sunucunuzda yuklu olan XNEWS surumu.</p>
            <div class="surum-kart">
                <div class="surum-kutu">
                    <div class="etiket">Yuklu</div>
                    <div class="numara">v<?= h($mevcut_surum) ?></div>
                </div>
                <div></div>
                <div class="surum-kutu" style="opacity:.4">
                    <div class="etiket">?</div>
                    <div class="numara">—</div>
                </div>
            </div>
            <form method="get" action="" style="text-align:center">
                <input type="hidden" name="islem" value="kontrol">
                <button type="submit" class="buton" style="padding:12px 32px;font-size:14px">GitHub'dan Guncelleme Kontrol Et</button>
            </form>
            <div class="bilgi-liste">
                <strong>Son kontrol:</strong> <?= ayar('son_guncelleme_kontrol') ? h(ayar('son_guncelleme_kontrol')) : 'Henuz kontrol edilmedi' ?>
            </div>
        </div>

    <?php elseif (!empty($release_bilgi) && $yeni_surum_var): ?>
        <div class="gun-bas">
            <h2 style="font-size:20px;color:var(--brand);margin-bottom:8px">🎉 Yeni Surum Mevcut!</h2>
            <p style="color:var(--muted)">Guvenli guncelleme icin yedek otomatik alinir.</p>

            <div class="surum-kart">
                <div class="surum-kutu">
                    <div class="etiket">Mevcut</div>
                    <div class="numara">v<?= h($mevcut_surum) ?></div>
                </div>
                <div class="ok-ikon">→</div>
                <div class="surum-kutu yeni">
                    <div class="etiket">Yeni</div>
                    <div class="numara"><?= h($release_bilgi['tag_name']) ?></div>
                </div>
            </div>

            <?php if (!empty($release_bilgi['body'])): ?>
            <div class="release-detay">
                <h3>📝 <?= h($release_bilgi['name'] ?? $release_bilgi['tag_name']) ?></h3>
                <pre><?= h($release_bilgi['body']) ?></pre>
            </div>
            <?php endif; ?>

            <div class="bilgi-liste">
                <strong>Guncelleme yapilirken:</strong>
                <ul style="margin-left:20px">
                    <li>✓ Mevcut dosyalarin tamami otomatik yedeklenir (<code>uploads/yedek/</code>)</li>
                    <li>✓ <code>config.php</code> ve <code>uploads/</code> korunur</li>
                    <li>✓ Veritabani degismez</li>
                    <li>⚠ Islem sirasinda site kisa bir sure erisilemeyebilir</li>
                </ul>
            </div>

            <form method="post" action="?islem=guncelle" onsubmit="return confirm('Guncellemeyi baslatmak istediginize emin misiniz?\n\nBu islem birkac dakika surer. Tamamlanmadan sayfayi kapatmayin.')">
                <?= csrf_input() ?>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
                    <a href="<?= url('guncelle.php') ?>" class="buton ikincil">Iptal</a>
                    <button type="submit" class="buton" style="padding:12px 28px">Guncellemeyi Baslat</button>
                </div>
            </form>
        </div>

    <?php elseif (!empty($release_bilgi)): ?>
        <div class="gun-bas">
            <div class="surum-kart">
                <div class="surum-kutu yeni">
                    <div class="etiket">Yuklu Surum</div>
                    <div class="numara">v<?= h($mevcut_surum) ?></div>
                </div>
                <div class="ok-ikon" style="background:var(--success)">✓</div>
                <div class="surum-kutu">
                    <div class="etiket">GitHub</div>
                    <div class="numara" style="color:var(--success)"><?= h($release_bilgi['tag_name']) ?></div>
                </div>
            </div>
            <p style="text-align:center;color:var(--success);font-weight:600;font-size:16px;margin:10px 0">
                ✓ En guncel surumu kullaniyorsunuz.
            </p>
            <p style="text-align:center;color:var(--muted);font-size:13px">
                Son kontrol: <?= date('d.m.Y H:i') ?>
            </p>
            <div style="text-align:center;margin-top:20px">
                <a href="<?= url('guncelle.php') ?>" class="buton ikincil">Tekrar Kontrol Et</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Manifest bilgileri -->
    <details style="margin-top:20px;background:#fff;border-radius:8px;padding:16px 20px;border:1px solid var(--border)">
        <summary style="cursor:pointer;font-weight:600;font-size:13px;color:var(--muted)">Teknik Bilgiler</summary>
        <div style="margin-top:12px;font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--ink-muted)">
            <div>Proje: <?= h($manifest['proje']) ?></div>
            <div>Repo: <?= h($repo) ?></div>
            <div>PHP: <?= PHP_VERSION ?> (minimum: <?= h($manifest['php_minimum'] ?? '?') ?>)</div>
            <div>Korunan dosyalar: <?= h(implode(', ', $korunan)) ?></div>
            <div>Token: <?= !empty(GITHUB_TOKEN) ? 'Tanimli (private repo destekli)' : 'Yok (public repo)' ?></div>
        </div>
    </details>
</div>
</body>
</html>
