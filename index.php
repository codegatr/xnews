<?php
/**
 * XNEWS - Public Site (Tek Dosya)
 * Router + header/footer + tum sayfalar tek yerde
 * CODEGA pattern: single-file PHP
 */
define('XNEWS', true);
require_once __DIR__ . '/baglan.php';

// =====================================================
// ROUTER PARAMETRELERI
// =====================================================
$sayfa    = $_GET['sayfa']    ?? 'anasayfa';
$sayfa_no = max(1, (int)($_GET['sayfa_no'] ?? 1));
$slug     = $_GET['slug']     ?? '';
$hid      = (int)($_GET['id'] ?? 0);
$q        = trim($_GET['q']   ?? '');
$prefix   = DB_PREFIX;

// =====================================================
// ORTAK VERILER
// =====================================================
$menu_kategoriler = $db->query("SELECT id, ad, slug, renk FROM {$prefix}categories WHERE aktif = 1 AND ust_id IS NULL ORDER BY sira, ad")->fetchAll();

$sd_haberler = $db->query("SELECT id, baslik, slug FROM {$prefix}news WHERE son_dakika = 1 AND durum = 'yayinda' ORDER BY yayin_tarihi DESC LIMIT 8")->fetchAll();
if (empty($sd_haberler)) {
    $sd_haberler = $db->query("SELECT id, baslik, slug FROM {$prefix}news WHERE durum = 'yayinda' ORDER BY yayin_tarihi DESC LIMIT 5")->fetchAll();
}

$sosyal_liste = [
    'sm_facebook'  => ['Facebook',  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'],
    'sm_twitter'   => ['Twitter',   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'],
    'sm_instagram' => ['Instagram', '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>'],
    'sm_youtube'   => ['YouTube',   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>'],
    'sm_telegram'  => ['Telegram',  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>'],
];

// =====================================================
// SAYFA BASLA (HEAD + HEADER)
// =====================================================
function sayfa_basla(array $opt): void {
    global $menu_kategoriler, $sd_haberler, $sosyal_liste;
    $tr_ay  = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    $tr_gun = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
    $bugun  = $tr_gun[(int)date('w')] . ', ' . (int)date('j') . ' ' . $tr_ay[(int)date('n') - 1] . ' ' . date('Y');

    $baslik    = $opt['baslik']    ?? ayar('site_adi') . ' - ' . ayar('site_slogan');
    $aciklama  = $opt['aciklama']  ?? ayar('site_aciklama');
    $gorsel    = $opt['gorsel']    ?? url('assets/img/placeholder.svg');
    $aktif     = $opt['aktif_sayfa']    ?? '';
    $aktif_kat = $opt['aktif_kategori'] ?? null;
    $canonical = $opt['canonical']      ?? url($_SERVER['REQUEST_URI'] ?? '');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#c8102e">
<title><?= h($baslik) ?></title>
<meta name="description" content="<?= h(kisalt($aciklama, 155)) ?>">
<meta name="keywords" content="<?= h(ayar('site_anahtar_kelime', '')) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= h($canonical) ?>">
<meta property="og:type" content="<?= $aktif === 'haber' ? 'article' : 'website' ?>">
<meta property="og:site_name" content="<?= h(ayar('site_adi')) ?>">
<meta property="og:locale" content="tr_TR">
<meta property="og:title" content="<?= h($baslik) ?>">
<meta property="og:description" content="<?= h(kisalt($aciklama, 155)) ?>">
<meta property="og:image" content="<?= h($gorsel) ?>">
<meta property="og:url" content="<?= h($canonical) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/svg+xml" href="<?= url('favicon.svg') ?>">
<link rel="apple-touch-icon" href="<?= url('favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,600;0,8..60,700;1,8..60,400&family=IBM+Plex+Sans:wght@400;500;600;700&subset=latin-ext&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= h(ayar('mevcut_surum', '1.0.0')) ?>">
<link rel="alternate" type="application/rss+xml" title="<?= h(ayar('site_adi')) ?>" href="<?= url('rss') ?>">
<?php if ($gsc = ayar('google_site_verification')): ?><meta name="google-site-verification" content="<?= h($gsc) ?>"><?php endif; ?>
<?= ayar('google_analytics', '') ?>
<?= ayar('head_kod', '') ?>
</head>
<body class="sayfa-<?= h($aktif) ?>">

<?php if (!empty($sd_haberler)): ?>
<div class="son-dakika-serit"><div class="kapsayici">
    <span class="sd-etiket">Son Dakika</span>
    <div class="sd-kayar"><div class="sd-kayar-ic">
        <?php foreach ($sd_haberler as $sd): ?><a href="<?= h(haber_url($sd)) ?>"><?= h($sd['baslik']) ?></a><?php endforeach; ?>
    </div></div>
</div></div>
<?php endif; ?>

<div class="ust-bar"><div class="kapsayici">
    <div class="ust-tarih"><?= h($bugun) ?></div>
    <div class="ust-sosyal">
        <?php foreach ($sosyal_liste as $an => [$ad, $ik]): if ($url_sm = ayar($an)): ?>
            <a href="<?= h($url_sm) ?>" target="_blank" rel="noopener" title="<?= h($ad) ?>"><?= $ik ?></a>
        <?php endif; endforeach; ?>
    </div>
</div></div>

<header class="logo-bar"><div class="kapsayici">
    <div class="lb-sol"><div class="caps">Türkiye ve Dünya Haberleri</div></div>
    <a href="<?= url() ?>" class="logo-link">
        <div class="logo-metin"><span class="x">X</span>NEWS</div>
        <div class="logo-alt"><?= h(ayar('site_slogan', 'Haberin Hizli Adresi')) ?></div>
    </a>
    <div class="lb-sag">
        <button class="arama-ac" aria-label="Arama" onclick="xnews.aramaAc()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
        <button class="mobil-menu-buton" aria-label="Menu" onclick="xnews.mobilMenuAc()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </div>
</div></header>

<nav class="kat-menu"><div class="kapsayici">
    <ul class="kat-liste">
        <li class="<?= $aktif === 'anasayfa' ? 'aktif' : '' ?>"><a href="<?= url() ?>">Anasayfa</a></li>
        <?php foreach ($menu_kategoriler as $kat): ?>
            <li class="<?= ($aktif_kat && ($aktif_kat['id'] ?? 0) == $kat['id']) ? 'aktif' : '' ?>">
                <a href="<?= h(kategori_url($kat)) ?>"><?= h($kat['ad']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="kat-menu-sag">
        <span class="canli-rozet">Canli</span>
        <span class="mono"><?= date('H:i') ?></span>
    </div>
</div></nav>

<div class="mobil-karartmasi" onclick="xnews.mobilMenuKapat()"></div>
<aside class="mobil-menu-panel">
    <button class="kapat" onclick="xnews.mobilMenuKapat()" aria-label="Kapat">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <ul>
        <li><a href="<?= url() ?>">Anasayfa</a></li>
        <?php foreach ($menu_kategoriler as $kat): ?><li><a href="<?= h(kategori_url($kat)) ?>"><?= h($kat['ad']) ?></a></li><?php endforeach; ?>
        <li><a href="<?= url('hakkimizda') ?>">Hakkımızda</a></li>
        <li><a href="<?= url('iletisim') ?>">İletişim</a></li>
        <li><a href="<?= url('reklam') ?>">Reklam</a></li>
    </ul>
</aside>

<div class="arama-overlay" onclick="if(event.target===this)xnews.aramaKapat()">
    <button class="arama-kapat" onclick="xnews.aramaKapat()" aria-label="Kapat">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <form class="arama-kutu" method="get" action="<?= url() ?>">
        <input type="hidden" name="sayfa" value="arama">
        <input type="search" name="q" placeholder="NE ARIYORSUNUZ?" autocomplete="off" required>
    </form>
</div>

<?php reklam_goster('ust_banner'); ?>
<?php
}

// =====================================================
// SAYFA BITIS (FOOTER)
// =====================================================
function sayfa_bitis(string $aktif = ''): void {
    global $menu_kategoriler, $sosyal_liste;
    reklam_goster('alt_banner');
    reklam_goster('mobil_sabit');
?>
<footer><div class="kapsayici">
    <div class="footer-grid">
        <div class="footer-marka">
            <a href="<?= url() ?>" class="logo-link"><div class="logo-metin"><span class="x">X</span>NEWS</div></a>
            <p><?= h(ayar('site_aciklama', 'Türkiye ve dünyadan son dakika haberleri.')) ?></p>
            <div class="footer-sosyal">
                <?php foreach ($sosyal_liste as $an => [$ad, $ik]): if ($u = ayar($an)): ?>
                    <a href="<?= h($u) ?>" target="_blank" rel="noopener" title="<?= h($ad) ?>"><?= $ik ?></a>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <div>
            <h4>Kategoriler</h4>
            <ul>
                <?php foreach (array_slice($menu_kategoriler, 0, 6) as $kat): ?>
                    <li><a href="<?= h(kategori_url($kat)) ?>"><?= h($kat['ad']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Kurumsal</h4>
            <ul>
                <li><a href="<?= url('hakkimizda') ?>">Hakkımızda</a></li>
                <li><a href="<?= url('iletisim') ?>">İletişim</a></li>
                <li><a href="<?= url('reklam') ?>">Reklam Ver</a></li>
                <li><a href="<?= url('gizlilik') ?>">Gizlilik Politikasi</a></li>
                <li><a href="<?= url('kullanim-sartlari') ?>">Kullanim Sartlari</a></li>
            </ul>
        </div>
        <div>
            <h4>Abone Ol</h4>
            <p style="color:#aaa;font-size:13px;margin-bottom:12px">Son dakika haberlerini kacirmayin.</p>
            <a href="<?= url('rss') ?>" style="display:inline-flex;align-items:center;gap:8px;background:#c8102e;color:#fff;padding:10px 18px;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:.08em">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6.18 15.64a2.18 2.18 0 0 1 2.18 2.18C8.36 19 7.38 20 6.18 20C5 20 4 19 4 17.82a2.18 2.18 0 0 1 2.18-2.18M4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27zm0 5.66a9.9 9.9 0 0 1 9.9 9.9h-2.83A7.07 7.07 0 0 0 4 12.93z"/></svg>
                RSS Beslemesi
            </a>
        </div>
    </div>
    <div class="footer-alt">
        <div>© <?= date('Y') ?> <?= h(ayar('site_adi', 'XNEWS')) ?>. Tüm haklari saklidir.</div>
        <div><a href="https://codega.com.tr" target="_blank" rel="noopener">Powered by CODEGA</a></div>
    </div>
</div></footer>

<?php if ($aktif === 'haber'): ?><div class="ilerleme-cubugu" id="ilerlemeCubugu"></div><?php endif; ?>
<script src="<?= url('assets/js/main.js') ?>?v=<?= h(ayar('mevcut_surum', '1.0.0')) ?>" defer></script>
<?= ayar('body_kod', '') ?>
</body>
</html>
<?php
}

// =====================================================
// HABER KARTI RENDER YARDIMCI
// =====================================================
function haber_karti(array $h, string $tip = 'normal'): void {
?>
<article class="haber-kart <?= $tip === 'yatay' ? 'yatay' : '' ?>">
    <a href="<?= h(haber_url($h)) ?>" class="gorsel">
        <img src="<?= h(haber_gorsel($h['resim'] ?? null)) ?>" alt="<?= h($h['baslik']) ?>" loading="lazy">
    </a>
    <div class="metin">
        <?php if (!empty($h['kat_ad'])): ?>
            <a href="<?= h(url('kategori/' . $h['kat_slug'])) ?>" class="kat-etiket"><?= h($h['kat_ad']) ?></a>
        <?php endif; ?>
        <h3><a href="<?= h(haber_url($h)) ?>"><?= h($h['baslik']) ?></a></h3>
        <?php if ($tip !== 'yatay' && !empty($h['ozet'])): ?>
            <p class="ozet"><?= h(kisalt($h['ozet'], 120)) ?></p>
        <?php endif; ?>
        <div class="meta">
            <?php if (!empty($h['kaynak_ad'])): ?><span class="kaynak"><?= h($h['kaynak_ad']) ?></span><span>·</span><?php endif; ?>
            <span><?= h(goreceli_zaman($h['yayin_tarihi'])) ?></span>
        </div>
    </div>
</article>
<?php
}

// =====================================================
// ROUTER - SAYFA ISLEYICI
// =====================================================
try {
switch ($sayfa) {

// -----------------------------------------------------
// ANA SAYFA
// -----------------------------------------------------
case 'anasayfa':
    $manset_adet = (int)ayar('anasayfa_manset_adet', 5);

    $mansetler = $db->query("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug, kay.ad AS kaynak_ad
        FROM {$prefix}news h
        LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
        LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
        WHERE h.durum = 'yayinda' AND h.manset = 1
        ORDER BY h.yayin_tarihi DESC LIMIT {$manset_adet}")->fetchAll();

    if (count($mansetler) < $manset_adet) {
        $eksik = $manset_adet - count($mansetler);
        $ekstra = $db->query("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug, kay.ad AS kaynak_ad
            FROM {$prefix}news h
            LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
            LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
            WHERE h.durum = 'yayinda' AND h.manset = 0
            ORDER BY h.yayin_tarihi DESC LIMIT {$eksik}")->fetchAll();
        $mansetler = array_merge($mansetler, $ekstra);
    }

    $son_adet = (int)ayar('anasayfa_son_haber_adet', 20);
    $haric_ids = array_column($mansetler, 'id') ?: [0];
    $in = implode(',', array_fill(0, count($haric_ids), '?'));
    $stmt = $db->prepare("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug, kay.ad AS kaynak_ad
        FROM {$prefix}news h
        LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
        LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
        WHERE h.durum = 'yayinda' AND h.id NOT IN ($in)
        ORDER BY h.yayin_tarihi DESC LIMIT {$son_adet}");
    $stmt->execute($haric_ids);
    $son_haberler = $stmt->fetchAll();

    $pop = $db->query("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug
        FROM {$prefix}news h
        LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
        WHERE h.durum = 'yayinda' AND h.yayin_tarihi >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY h.okunma DESC LIMIT 5")->fetchAll();

    sayfa_basla(['aktif_sayfa' => 'anasayfa']);
?>
<main><div class="kapsayici">
    <?php if (empty($mansetler)): ?>
        <div class="bos-sonuc" style="padding:80px 20px">
            <div class="ikon" style="font-size:64px;margin-bottom:20px">📰</div>
            <h2 style="font-family:var(--font-manset,'Oswald',sans-serif);font-size:32px;margin-bottom:12px">Henüz haber yok</h2>
            <p style="max-width:540px;margin:0 auto 32px;color:#64748b;font-size:16px;line-height:1.6">
                Haberler RSS çekim motoruyla otomatik dolar. Aşağıdaki seçeneklerden biriyle başlayabilirsiniz:
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                <a href="<?= url('yonetim.php?sayfa=kaynaklar') ?>" style="display:inline-block;background:#c8102e;color:#fff;padding:14px 28px;font-weight:700;text-transform:uppercase;font-size:13px;letter-spacing:.1em;font-family:var(--font-ui);border-radius:3px">RSS Kaynakları (32 hazır)</a>
                <a href="<?= url('yonetim.php?sayfa=haberler&islem=ekle') ?>" style="display:inline-block;background:#0f172a;color:#fff;padding:14px 28px;font-weight:700;text-transform:uppercase;font-size:13px;letter-spacing:.1em;font-family:var(--font-ui);border-radius:3px">Manuel Haber Ekle</a>
            </div>
            <p style="margin-top:40px;color:#94a3b8;font-size:13px">
                <strong>İpucu:</strong> DirectAdmin Cron'dan her 10 dakikada bir
                <code style="background:#f1f5f9;padding:2px 6px;border-radius:3px;font-size:12px">cron.php</code> çalıştırın → haberler otomatik dolar.
            </p>
        </div>
    <?php else: ?>

    <section class="manset-grid">
        <?php $ilk = array_shift($mansetler); ?>
        <?php if ($ilk): ?>
        <a href="<?= h(haber_url($ilk)) ?>" class="manset-buyuk">
            <img src="<?= h(haber_gorsel($ilk['resim'])) ?>" alt="<?= h($ilk['baslik']) ?>">
            <div class="metin">
                <?php if (!empty($ilk['kat_ad'])): ?><span style="display:inline-block;background:#c8102e;color:#fff;padding:5px 12px;font-family:var(--font-ui);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;margin-bottom:10px"><?= h($ilk['kat_ad']) ?></span><?php endif; ?>
                <h1><?= h($ilk['baslik']) ?></h1>
                <?php if (!empty($ilk['ozet'])): ?><p class="ozet"><?= h(kisalt($ilk['ozet'], 140)) ?></p><?php endif; ?>
            </div>
        </a>
        <?php endif; ?>
        <?php foreach (array_slice($mansetler, 0, 4) as $m): ?>
        <a href="<?= h(haber_url($m)) ?>" class="manset-kucuk">
            <img src="<?= h(haber_gorsel($m['resim'])) ?>" alt="<?= h($m['baslik']) ?>" loading="lazy">
            <div class="metin"><h3><?= h(kisalt($m['baslik'], 80)) ?></h3></div>
        </a>
        <?php endforeach; ?>
    </section>

    <div class="icerik-sidebar-grid">
        <div>
            <section class="kategori-bolum">
                <div class="bolum-baslik">
                    <h2>Son Haberler</h2>
                </div>
                <div class="kat-grid">
                    <?php foreach (array_slice($son_haberler, 0, 8) as $h) haber_karti($h); ?>
                </div>
            </section>

            <?php foreach ($menu_kategoriler as $kat):
                $st = $db->prepare("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug, kay.ad AS kaynak_ad
                    FROM {$prefix}news h
                    LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
                    LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
                    WHERE h.durum = 'yayinda' AND h.kategori_id = ?
                    ORDER BY h.yayin_tarihi DESC LIMIT 4");
                $st->execute([$kat['id']]);
                $kat_haberleri = $st->fetchAll();
                if (empty($kat_haberleri)) continue;
            ?>
            <section class="kategori-bolum">
                <div class="bolum-baslik">
                    <h2><?= h($kat['ad']) ?></h2>
                    <a href="<?= h(kategori_url($kat)) ?>" class="tumu">Tümü →</a>
                </div>
                <div class="kat-grid">
                    <?php foreach ($kat_haberleri as $h) haber_karti($h); ?>
                </div>
            </section>
            <?php endforeach; ?>
        </div>

        <aside class="sidebar">
            <?php reklam_goster('sidebar_ust'); ?>
            <div class="sidebar-blok">
                <h3>En Çok Okunan</h3>
                <?php if (empty($pop)): ?>
                    <p style="color:var(--muted);font-size:13px;padding:12px 0">Henüz okunma verisi yok.</p>
                <?php else: $i = 1; foreach ($pop as $p): ?>
                <a href="<?= h(haber_url($p)) ?>" class="mini-kart">
                    <span class="numara"><?= $i++ ?></span>
                    <div>
                        <h4><?= h(kisalt($p['baslik'], 80)) ?></h4>
                        <div class="meta"><?= h(goreceli_zaman($p['yayin_tarihi'])) ?></div>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>
            <?php reklam_goster('sidebar_alt'); ?>
        </aside>
    </div>
    <?php endif; ?>
</div></main>
<?php
    sayfa_bitis('anasayfa');
    break;

// -----------------------------------------------------
// KATEGORI
// -----------------------------------------------------
case 'kategori':
    $st = $db->prepare("SELECT * FROM {$prefix}categories WHERE slug = ? AND aktif = 1");
    $st->execute([$slug]);
    $kat = $st->fetch();
    if (!$kat) { http_response_code(404); goto sayfa_404; }

    $per_page = (int)ayar('kategori_sayfa_adet', 15);
    $offset = ($sayfa_no - 1) * $per_page;

    $st = $db->prepare("SELECT COUNT(*) FROM {$prefix}news WHERE kategori_id = ? AND durum = 'yayinda'");
    $st->execute([$kat['id']]);
    $toplam = (int)$st->fetchColumn();
    $son_sayfa = max(1, (int)ceil($toplam / $per_page));

    $st = $db->prepare("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug, kay.ad AS kaynak_ad
        FROM {$prefix}news h
        LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
        LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
        WHERE h.kategori_id = ? AND h.durum = 'yayinda'
        ORDER BY h.yayin_tarihi DESC LIMIT {$per_page} OFFSET {$offset}");
    $st->execute([$kat['id']]);
    $haberler = $st->fetchAll();

    sayfa_basla([
        'aktif_sayfa' => 'kategori',
        'aktif_kategori' => $kat,
        'baslik' => $kat['ad'] . ' Haberleri - ' . ayar('site_adi'),
        'aciklama' => $kat['aciklama'] ?: "{$kat['ad']} kategorisindeki son haberler",
    ]);
?>
<main><div class="kapsayici">
    <div class="bolum-baslik"><h2><?= h($kat['ad']) ?></h2><span class="tumu"><?= $toplam ?> haber</span></div>
    <?php if (empty($haberler)): ?>
        <div class="bos-sonuc"><div class="ikon">📭</div><h2>Bu kategoride henüz haber yok</h2></div>
    <?php else: ?>
        <div class="icerik-sidebar-grid">
            <div>
                <div class="kat-grid-3"><?php foreach ($haberler as $h) haber_karti($h); ?></div>
                <?php if ($son_sayfa > 1): ?>
                <div class="sayfalama">
                    <?php if ($sayfa_no > 1): ?>
                        <a href="<?= h(url('kategori/' . $kat['slug'] . ($sayfa_no > 2 ? '/sayfa/' . ($sayfa_no - 1) : ''))) ?>">&larr;</a>
                    <?php else: ?><span class="devre-disi">&larr;</span><?php endif; ?>
                    <?php for ($i = max(1, $sayfa_no - 2); $i <= min($son_sayfa, $sayfa_no + 2); $i++): ?>
                        <?php if ($i == $sayfa_no): ?><span class="aktif"><?= $i ?></span>
                        <?php else: ?><a href="<?= h(url('kategori/' . $kat['slug'] . ($i > 1 ? '/sayfa/' . $i : ''))) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($sayfa_no < $son_sayfa): ?>
                        <a href="<?= h(url('kategori/' . $kat['slug'] . '/sayfa/' . ($sayfa_no + 1))) ?>">&rarr;</a>
                    <?php else: ?><span class="devre-disi">&rarr;</span><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <aside class="sidebar">
                <?php reklam_goster('sidebar_ust'); ?>
                <?php reklam_goster('sidebar_alt'); ?>
            </aside>
        </div>
    <?php endif; ?>
</div></main>
<?php
    sayfa_bitis('kategori');
    break;

// -----------------------------------------------------
// HABER DETAY
// -----------------------------------------------------
case 'haber':
    $st = $db->prepare("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug, k.renk AS kat_renk,
        kay.ad AS kaynak_ad, kay.site_url AS kaynak_url, kay.atfi_metin AS kaynak_atfi
        FROM {$prefix}news h
        LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
        LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
        WHERE h.id = ? AND h.durum = 'yayinda'");
    $st->execute([$hid]);
    $haber = $st->fetch();
    if (!$haber) { http_response_code(404); goto sayfa_404; }

    if (empty($_SESSION['okundu_' . $hid])) {
        $_SESSION['okundu_' . $hid] = true;
        $db->prepare("UPDATE {$prefix}news SET okunma = okunma + 1 WHERE id = ?")->execute([$hid]);
    }

    $st = $db->prepare("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug, kay.ad AS kaynak_ad
        FROM {$prefix}news h
        LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
        LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
        WHERE h.kategori_id = ? AND h.id != ? AND h.durum = 'yayinda'
        ORDER BY h.yayin_tarihi DESC LIMIT 4");
    $st->execute([$haber['kategori_id'], $hid]);
    $ilgili = $st->fetchAll();

    $icerik_final = reklam_icerige_enjekte($haber['icerik'] ?: '');

    sayfa_basla([
        'aktif_sayfa' => 'haber',
        'aktif_kategori' => ['id' => $haber['kategori_id']],
        'başlık' => ($haber['seo_baslik'] ?: $haber['baslik']) . ' - ' . ayar('site_adi'),
        'açıklama' => $haber['seo_aciklama'] ?: kisalt($haber['ozet'], 160),
        'gorsel' => haber_gorsel($haber['resim']),
    ]);
?>
<main><div class="kapsayici">
    <article class="haber-detay">
        <div class="hd-ust-bilgi">
            <?php if (!empty($haber['kat_ad'])): ?>
                <a href="<?= h(url('kategori/' . $haber['kat_slug'])) ?>" class="hd-kat" style="background:<?= h($haber['kat_renk'] ?? '#c8102e') ?>"><?= h($haber['kat_ad']) ?></a>
            <?php endif; ?>
            <h1 class="hd-baslik"><?= h($haber['baslik']) ?></h1>
            <?php if (!empty($haber['ozet'])): ?><p class="hd-ozet"><?= h($haber['ozet']) ?></p><?php endif; ?>
            <div class="hd-meta">
                <?php if (!empty($haber['kaynak_ad'])): ?><span class="kaynak"><?= h($haber['kaynak_ad']) ?></span><?php endif; ?>
                <span><?= h(tr_tarih($haber['yayin_tarihi'])) ?></span>
                <span><?= (int)$haber['okunma'] ?> okunma</span>
            </div>
        </div>

        <?php if (!empty($haber['resim'])): ?>
            <div class="hd-gorsel">
                <img src="<?= h(haber_gorsel($haber['resim'])) ?>" alt="<?= h($haber['resim_alt'] ?: $haber['baslik']) ?>">
                <?php if (!empty($haber['resim_alt'])): ?><div class="alt"><?= h($haber['resim_alt']) ?></div><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php reklam_goster('makale_ust'); ?>
        <div class="hd-icerik"><?= $icerik_final ?></div>

        <?php if (ayar('kaynak_goster', '1') && !empty($haber['kaynak_ad'])): ?>
        <div class="kaynak-atfi">
            <strong>Kaynak:</strong> <?= h($haber['kaynak_atfi'] ?: $haber['kaynak_ad']) ?>
            <?php if (!empty($haber['orijinal_url'])): ?>
                · <a href="<?= h($haber['orijinal_url']) ?>" target="_blank" rel="noopener nofollow">Orijinal haber &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="hd-paylas">
            <span>Paylas</span>
            <a href="#" onclick="xnews.paylas('facebook');return false" title="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
            <a href="#" onclick="xnews.paylas('twitter');return false" title="Twitter"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231z"/></svg></a>
            <a href="#" onclick="xnews.paylas('whatsapp');return false" title="WhatsApp"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg></a>
            <a href="#" onclick="xnews.paylas('telegram');return false" title="Telegram"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg></a>
            <a href="#" onclick="xnews.paylas('link');return false" title="Linki Kopyala"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></a>
        </div>

        <?php reklam_goster('makale_alt'); ?>
    </article>

    <?php if (!empty($ilgili)): ?>
    <section style="margin-top:60px;max-width:1080px;margin-left:auto;margin-right:auto">
        <div class="bolum-baslik"><h2>İlgili Haberler</h2></div>
        <div class="kat-grid"><?php foreach ($ilgili as $i) haber_karti($i); ?></div>
    </section>
    <?php endif; ?>
</div></main>
<?php
    sayfa_bitis('haber');
    break;

// -----------------------------------------------------
// ARAMA
// -----------------------------------------------------
case 'arama':
    $sonuc = []; $toplam = 0;
    if (mb_strlen($q, 'UTF-8') >= 2) {
        $per_page = 12;
        $offset = ($sayfa_no - 1) * $per_page;
        $like = '%' . $q . '%';

        $st = $db->prepare("SELECT h.*, k.ad AS kat_ad, k.slug AS kat_slug, kay.ad AS kaynak_ad
            FROM {$prefix}news h
            LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
            LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
            WHERE h.durum = 'yayinda' AND (h.baslik LIKE ? OR h.ozet LIKE ? OR h.icerik LIKE ?)
            ORDER BY h.yayin_tarihi DESC LIMIT {$per_page} OFFSET {$offset}");
        $st->execute([$like, $like, $like]);
        $sonuc = $st->fetchAll();

        $st = $db->prepare("SELECT COUNT(*) FROM {$prefix}news WHERE durum = 'yayinda' AND (baslik LIKE ? OR ozet LIKE ?)");
        $st->execute([$like, $like]);
        $toplam = (int)$st->fetchColumn();
    }
    sayfa_basla(['aktif_sayfa' => 'arama', 'baslik' => $q ? "'{$q}' arama sonuclari - " . ayar('site_adi') : 'Arama - ' . ayar('site_adi')]);
?>
<main><div class="kapsayici">
    <div class="bolum-baslik"><h2>Arama<?= $q ? ': ' . h($q) : '' ?></h2><span class="tumu"><?= $toplam ?> sonuc</span></div>
    <form method="get" action="<?= url() ?>" style="margin-bottom:30px;display:flex;gap:10px;max-width:600px">
        <input type="hidden" name="sayfa" value="arama">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Aramak istediginiz kelimeyi yazin..." style="flex:1;padding:14px 18px;border:2px solid var(--black);font-family:var(--font-ui);font-size:15px;outline:none" required autofocus>
        <button type="submit" style="padding:0 24px;background:var(--brand);color:#fff;border:none;font-weight:700;text-transform:uppercase;letter-spacing:.08em;font-size:13px;cursor:pointer">Ara</button>
    </form>
    <?php if (empty($sonuc) && $q): ?>
        <div class="bos-sonuc"><div class="ikon">🔍</div><h2>Sonuç Bulunamadı</h2><p>"<?= h($q) ?>" için haber bulunamadı.</p></div>
    <?php elseif (!empty($sonuc)): ?>
        <div class="kat-grid-3"><?php foreach ($sonuc as $h) haber_karti($h); ?></div>
    <?php endif; ?>
</div></main>
<?php
    sayfa_bitis('arama');
    break;

// -----------------------------------------------------
// STATIK SAYFALAR
// -----------------------------------------------------
case 'hakkimizda': case 'iletisim': case 'reklam': case 'gizlilik': case 'kullanim':
    $basliklar = ['hakkimizda' => 'Hakkımızda', 'iletisim' => 'İletişim', 'reklam' => 'Reklam Ver', 'gizlilik' => 'Gizlilik Politikasi', 'kullanim' => 'Kullanim Sartlari'];
    $baslik_st = $basliklar[$sayfa];
    sayfa_basla(['aktif_sayfa' => $sayfa, 'başlık' => $baslik_st . ' - ' . ayar('site_adi')]);
?>
<main><div class="kapsayici">
    <article class="haber-detay">
        <div class="hd-ust-bilgi"><h1 class="hd-baslik"><?= h($baslik_st) ?></h1></div>
        <div class="hd-icerik">
        <?php if ($sayfa === 'hakkimizda'): ?>
            <p><?= h(ayar('site_adi', 'XNEWS')) ?>, Türkiye ve dünyadan guncel haberleri hızlı ve tarafsiz bicimde okuyuculariyla bulusturan dijital bir haber platformudur.</p>
            <p>Platformumuz; Türkiye'nin onde gelen haber kaynaklarindan otomatik olarak haber akisi saglar, tum icerikler kaynak belirtilerek orijinal yayinciya link ile birlikte sunulur.</p>
            <h2>Misyonumuz</h2>
            <p>Okuyuculara hızlı, dogrudan ve yanli olmayan haber erisimi saglamak.</p>
            <h2>İletişim</h2>
            <p>Oneri, sikayet ve is birligi teklifleriniz için <a href="<?= url('iletisim') ?>">iletisim sayfasi</a>ni kullanabilirsiniz.</p>
        <?php elseif ($sayfa === 'iletisim'): ?>
            <p>Bizimle iletisime gecmek için asagidaki kanallardan ulasabilirsiniz.</p>
            <ul>
                <li><strong>E-posta:</strong> <a href="mailto:<?= h(ayar('iletisim_eposta', '')) ?>"><?= h(ayar('iletisim_eposta', '')) ?></a></li>
                <?php if ($tel = ayar('iletisim_telefon')): ?><li><strong>Telefon:</strong> <?= h($tel) ?></li><?php endif; ?>
                <?php if ($adr = ayar('iletisim_adres')): ?><li><strong>Adres:</strong> <?= h($adr) ?></li><?php endif; ?>
            </ul>
        <?php elseif ($sayfa === 'reklam'): ?>
            <p><?= h(ayar('site_adi')) ?> uzerinde reklam vermek için bizimle iletisime gecin.</p>
            <h2>Reklam Alanlarimiz</h2>
            <ul>
                <li>Üst Banner (970×90)</li>
                <li>Sidebar (300×250 / 300×600)</li>
                <li>Makale Ici ve Disi Banner (728×90)</li>
                <li>Alt Banner (970×90)</li>
                <li>Mobil Sabit (320×50)</li>
                <li>Google AdSense uyumlu ozel yerlestirmeler</li>
            </ul>
            <p>Teklif almak için: <a href="mailto:<?= h(ayar('iletisim_eposta')) ?>"><?= h(ayar('iletisim_eposta')) ?></a></p>
        <?php elseif ($sayfa === 'gizlilik'): ?>
            <p>Bu sayfa yönetim panelinden guncellenebilir.</p>
            <h2>1. Toplanan Bilgiler</h2>
            <p>Sitemizi ziyaret ettiginizde IP adresiniz, tarayici bilgileriniz anonim olarak kaydedilir.</p>
            <h2>2. Cerezler</h2>
            <p>Sitemiz oturum yonetimi ve analiz için cerez kullanir.</p>
        <?php elseif ($sayfa === 'kullanim'): ?>
            <p>Bu sayfa yönetim panelinden guncellenebilir.</p>
            <h2>İçerik Kullanimi</h2>
            <p>Sitemizde yer alan haberler kaynak belirtilmek suretiyle orijinal yayincilardan sunulmaktadir. Haberlerin telif hakki kaynak yayin kurulusuna aittir.</p>
        <?php endif; ?>
        </div>
    </article>
</div></main>
<?php
    sayfa_bitis($sayfa);
    break;

// -----------------------------------------------------
// 404
// -----------------------------------------------------
case '404':
default:
sayfa_404:
    http_response_code(404);
    sayfa_basla(['başlık' => '404 - Sayfa Bulunamadı - ' . ayar('site_adi'), 'aktif_sayfa' => '404']);
?>
<main><div class="kapsayici">
    <div class="hata-sayfa">
        <div class="numara">404</div>
        <h1>Sayfa Bulunamadı</h1>
        <p style="color:var(--muted);margin-bottom:28px">Aradiginiz sayfa silinmis, tasinmis veya hic var olmamis olabilir.</p>
        <a href="<?= url() ?>" style="display:inline-block;background:var(--black);color:#fff;padding:14px 32px;font-family:var(--font-ui);font-weight:700;text-transform:uppercase;letter-spacing:.1em;font-size:13px">Ana Sayfaya Don</a>
    </div>
</div></main>
<?php
    sayfa_bitis('404');
    break;

} // switch

} catch (Throwable $e) {
    if (defined('HATA_AYIKLAMA') && HATA_AYIKLAMA) {
        echo '<pre style="padding:20px;background:#fee;color:#900">' . h($e->getMessage()) . "\n" . h($e->getTraceAsString()) . '</pre>';
    } else {
        log_ekle('hata', 'index.php hatasi', $e->getMessage());
        http_response_code(500);
        echo '<div style="padding:60px;text-align:center;font-family:system-ui"><h1>Bir hata olustu</h1><p>Kısa bir sure sonra tekrar deneyiniz.</p></div>';
    }
}
