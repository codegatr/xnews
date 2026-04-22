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
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url" content="<?= h($canonical) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($baslik) ?>">
<meta name="twitter:description" content="<?= h(kisalt($aciklama, 155)) ?>">
<meta name="twitter:image" content="<?= h($gorsel) ?>">
<?php if ($tw = ayar('twitter_kullanici')): ?><meta name="twitter:site" content="@<?= h(ltrim($tw, '@')) ?>"><?php endif; ?>
<?php if ($aktif === 'haber' && !empty($opt['haber'])): $hb = $opt['haber']; ?>
<meta property="article:published_time" content="<?= h(date('c', strtotime($hb['yayin_tarihi'] ?? $hb['olusturma_tarihi'] ?? 'now'))) ?>">
<?php if (!empty($hb['guncellenme_tarihi'])): ?><meta property="article:modified_time" content="<?= h(date('c', strtotime($hb['guncellenme_tarihi']))) ?>"><?php endif; ?>
<?php if (!empty($hb['kat_ad'])): ?><meta property="article:section" content="<?= h($hb['kat_ad']) ?>"><?php endif; ?>
<?php if (!empty($hb['yazar'])): ?><meta property="article:author" content="<?= h($hb['yazar']) ?>"><?php endif; ?>
<!-- Schema.org NewsArticle JSON-LD -->
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $hb['baslik'] ?? $baslik,
    'description' => kisalt($aciklama, 155),
    'image' => [$gorsel],
    'datePublished' => date('c', strtotime($hb['yayin_tarihi'] ?? $hb['olusturma_tarihi'] ?? 'now')),
    'dateModified' => date('c', strtotime($hb['guncellenme_tarihi'] ?? $hb['yayin_tarihi'] ?? 'now')),
    'author' => ['@type' => 'Organization', 'name' => $hb['kaynak_atfi'] ?? $hb['kaynak_ad'] ?? ayar('site_adi')],
    'publisher' => ['@type' => 'Organization', 'name' => ayar('site_adi'), 'logo' => ['@type' => 'ImageObject', 'url' => url('favicon.svg')]],
    'articleSection' => $hb['kat_ad'] ?? null,
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>
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
        <div class="logo-alt"><?= h(ayar('site_slogan', 'Haberin Hızlı Adresi')) ?></div>
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
        <li class="<?= $aktif === 'anasayfa' ? 'aktif' : '' ?>"><a href="<?= url() ?>">Ana Sayfa</a></li>
        <?php foreach ($menu_kategoriler as $kat): ?>
            <li class="<?= ($aktif_kat && ($aktif_kat['id'] ?? 0) == $kat['id']) ? 'aktif' : '' ?>">
                <a href="<?= h(kategori_url($kat)) ?>"><?= h($kat['ad']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="kat-menu-sag">
        <span class="canli-rozet">CANLI</span>
        <span class="mono"><?= date('H:i') ?></span>
    </div>
</div></nav>

<div class="mobil-karartmasi" onclick="xnews.mobilMenuKapat()"></div>
<aside class="mobil-menu-panel">
    <button class="kapat" onclick="xnews.mobilMenuKapat()" aria-label="Kapat">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <ul>
        <li><a href="<?= url() ?>">Ana Sayfa</a></li>
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
                <li><a href="<?= url('kunye') ?>">Künye</a></li>
                <li><a href="<?= url('iletisim') ?>">İletişim</a></li>
                <li><a href="<?= url('reklam') ?>">Reklam Ver</a></li>
                <li><a href="<?= url('yayin-ilkeleri') ?>">Yayın İlkelerimiz</a></li>
            </ul>
        </div>
        <div>
            <h4>Yasal</h4>
            <ul>
                <li><a href="<?= url('kvkk') ?>">KVKK Aydınlatma</a></li>
                <li><a href="<?= url('gizlilik') ?>">Gizlilik Politikası</a></li>
                <li><a href="<?= url('cerez') ?>">Çerez Politikası</a></li>
                <li><a href="<?= url('kullanim') ?>">Kullanım Şartları</a></li>
                <li><a href="<?= url('kaldirma-talebi') ?>">Kaldırma Talebi</a></li>
            </ul>
        </div>
        <div>
            <h4>Abone Ol</h4>
            <p style="color:#aaa;font-size:13px;margin-bottom:12px">Son dakika haberlerini kaçırmayın.</p>
            <a href="<?= url('rss') ?>" style="display:inline-flex;align-items:center;gap:8px;background:#c8102e;color:#fff;padding:10px 18px;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:.08em">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6.18 15.64a2.18 2.18 0 0 1 2.18 2.18C8.36 19 7.38 20 6.18 20C5 20 4 19 4 17.82a2.18 2.18 0 0 1 2.18-2.18M4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27zm0 5.66a9.9 9.9 0 0 1 9.9 9.9h-2.83A7.07 7.07 0 0 0 4 12.93z"/></svg>
                RSS Beslemesi
            </a>
        </div>
    </div>
    <div class="footer-alt">
        <div>© <?= date('Y') ?> <?= h(ayar('site_adi', 'XNEWS')) ?>. Tüm hakları saklıdır.</div>
        <div><a href="https://codega.com.tr" target="_blank" rel="noopener">Powered by CODEGA</a></div>
    </div>
<?php reklam_goster('alt_banner'); ?>

</div></footer>

<!-- Mobil Sabit Banner (alta yapışık) -->
<div class="reklam-mobil-sabit">
    <button class="kapat" onclick="xnews.mobilBannerKapat()" aria-label="Kapat">×</button>
    <?php reklam_goster('mobil_sabit'); ?>
</div>

<?php if (ayar('cerez_bildirim_aktif', '1') === '1'): ?>
<!-- Çerez Bildirim Paneli -->
<div id="cerezBanner" class="cerez-banner" style="display:none" role="dialog" aria-label="Çerez Tercihleri">
    <div class="cerez-banner-i">
        <div class="cerez-banner-metin">
            <strong>🍪 Çerez Kullanımı</strong>
            <p>Sitemizde deneyiminizi iyileştirmek için çerezler kullanıyoruz. <strong>Zorunlu çerezler</strong> sitenin çalışması için gereklidir. <strong>Analitik ve reklam çerezleri</strong> tercihlerinize bağlıdır.
            <a href="<?= url('cerez') ?>" style="color:#c8102e;text-decoration:underline">Detaylı bilgi</a>.</p>
        </div>
        <div class="cerez-banner-btn">
            <button onclick="xnews.cerezKabul('tumu')" class="cb-btn cb-btn-ana">Tümünü Kabul Et</button>
            <button onclick="xnews.cerezKabul('zorunlu')" class="cb-btn cb-btn-alt">Sadece Zorunlu</button>
            <button onclick="xnews.cerezAyarlariAc()" class="cb-btn cb-btn-icon" aria-label="Özel tercihler">⚙</button>
        </div>
    </div>
</div>

<!-- Çerez Ayarlar Modal -->
<div id="cerezModal" class="cerez-modal" style="display:none">
    <div class="cerez-modal-i">
        <div class="cerez-modal-ust">
            <h3>Çerez Tercihleri</h3>
            <button onclick="xnews.cerezAyarlariKapat()" class="cerez-modal-kapat" aria-label="Kapat">&times;</button>
        </div>
        <div class="cerez-modal-icerik">
            <div class="cerez-kategori">
                <label>
                    <input type="checkbox" checked disabled>
                    <strong>Zorunlu Çerezler</strong>
                    <span class="cerez-zorunlu">Her zaman aktif</span>
                </label>
                <p>Sitenin temel işlevleri için gerekli. Oturum yönetimi, güvenlik (CSRF), çerez tercihleriniz.</p>
            </div>
            <div class="cerez-kategori">
                <label>
                    <input type="checkbox" id="cerez_analitik">
                    <strong>Analitik Çerezler</strong>
                </label>
                <p>Site kullanımını anonim olarak analiz eder (Google Analytics). Hangi sayfaların popüler olduğunu anlamamıza yardımcı olur.</p>
            </div>
            <div class="cerez-kategori">
                <label>
                    <input type="checkbox" id="cerez_reklam">
                    <strong>Reklam Çerezleri</strong>
                </label>
                <p>Kişiselleştirilmiş reklam göstermek için kullanılır (Google AdSense, vb.). Kapatırsanız genel reklamlar görürsünüz.</p>
            </div>
        </div>
        <div class="cerez-modal-alt">
            <button onclick="xnews.cerezKaydet()" class="cb-btn cb-btn-ana">Tercihlerimi Kaydet</button>
            <button onclick="xnews.cerezKabul('tumu')" class="cb-btn cb-btn-alt">Tümünü Kabul Et</button>
        </div>
    </div>
</div>
<?php endif; ?>

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

    // Büyük Son Dakika Slider: resmi OLAN son dakika haberlerden 6 tane
    $sd_slider = $db->query("SELECT h.id, h.baslik, h.slug, h.ozet, h.resim, h.yayin_tarihi,
            k.ad AS kat_ad, k.slug AS kat_slug, k.renk AS kat_renk, kay.ad AS kaynak_ad
        FROM {$prefix}news h
        LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
        LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
        WHERE h.durum = 'yayinda' AND h.son_dakika = 1
          AND h.resim IS NOT NULL AND h.resim != ''
        ORDER BY h.yayin_tarihi DESC LIMIT 6")->fetchAll();
    // Yeterli son dakika yoksa, resimli en son haberlerle tamamla
    if (count($sd_slider) < 6) {
        $eksik_ids = array_column($sd_slider, 'id') ?: [0];
        $in_sd = implode(',', array_fill(0, count($eksik_ids), '?'));
        $sd_ek_st = $db->prepare("SELECT h.id, h.baslik, h.slug, h.ozet, h.resim, h.yayin_tarihi,
                k.ad AS kat_ad, k.slug AS kat_slug, k.renk AS kat_renk, kay.ad AS kaynak_ad
            FROM {$prefix}news h
            LEFT JOIN {$prefix}categories k ON k.id = h.kategori_id
            LEFT JOIN {$prefix}sources kay ON kay.id = h.kaynak_id
            WHERE h.durum = 'yayinda' AND h.id NOT IN ($in_sd)
              AND h.resim IS NOT NULL AND h.resim != ''
            ORDER BY h.yayin_tarihi DESC LIMIT " . (6 - count($sd_slider)));
        $sd_ek_st->execute($eksik_ids);
        $sd_slider = array_merge($sd_slider, $sd_ek_st->fetchAll());
    }

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

    <?php if (!empty($sd_slider) && count($sd_slider) >= 2): ?>
    <!-- BÜYÜK SON DAKİKA SLIDER -->
    <section class="sd-slider" id="sdSlider" data-adet="<?= count($sd_slider) ?>">
        <div class="sd-slider-numaralar">
            <?php foreach ($sd_slider as $i => $h): ?>
                <button class="sd-num <?= $i === 0 ? 'aktif' : '' ?>" data-slide="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"><?= $i + 1 ?></button>
            <?php endforeach; ?>
        </div>
        <div class="sd-slider-panel">
            <div class="sd-slider-etiket">
                <span class="sd-dot"></span>
                SON DAKİKA
            </div>
            <?php foreach ($sd_slider as $i => $h): ?>
            <a href="<?= h(haber_url($h)) ?>" class="sd-slide <?= $i === 0 ? 'aktif' : '' ?>" data-slide="<?= $i ?>">
                <div class="sd-slide-gorsel">
                    <img src="<?= h(haber_gorsel($h['resim'])) ?>" alt="<?= h($h['baslik']) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                    <div class="sd-slide-overlay"></div>
                </div>
                <div class="sd-slide-metin">
                    <?php if (!empty($h['kat_ad'])): ?>
                        <span class="sd-slide-kat" style="background:<?= h($h['kat_renk'] ?? '#c8102e') ?>"><?= h($h['kat_ad']) ?></span>
                    <?php endif; ?>
                    <h2 class="sd-slide-baslik"><?= h($h['baslik']) ?></h2>
                    <?php if (!empty($h['ozet'])): ?>
                        <p class="sd-slide-ozet"><?= h(kisalt($h['ozet'], 140)) ?></p>
                    <?php endif; ?>
                    <div class="sd-slide-meta">
                        <?php if (!empty($h['kaynak_ad'])): ?><span class="sd-slide-kaynak"><?= h($h['kaynak_ad']) ?></span> · <?php endif; ?>
                        <span><?= h(goreceli_zaman($h['yayin_tarihi'])) ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="sd-slider-ilerleme"><div class="sd-slider-ilerleme-dolgu"></div></div>
    </section>
    <?php endif; ?>

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
                <h3>🔥 En Çok Okunan</h3>
                <?php if (empty($pop)): ?>
                    <p style="color:var(--muted);font-size:13px;padding:12px 0">Henüz okunma verisi yok.</p>
                <?php else: ?>
                <div class="populer-liste">
                    <?php $i = 1; foreach ($pop as $p): ?>
                    <div class="populer-oge">
                        <div class="populer-numara"><?= $i++ ?></div>
                        <div class="populer-icerik">
                            <a href="<?= h(haber_url($p)) ?>"><?= h(kisalt($p['baslik'], 75)) ?></a>
                            <div class="populer-meta"><?= h(goreceli_zaman($p['yayin_tarihi'])) ?> · <?= (int)($p['okunma'] ?? 0) ?> okunma</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php
            // Trend etiketler - son 7 gundeki en populer etiketler
            $trend_etiketler = $db->query("SELECT e.ad, e.slug FROM {$prefix}tags e
                INNER JOIN {$prefix}news_tags ht ON ht.tag_id = e.id
                INNER JOIN {$prefix}news h ON h.id = ht.news_id
                WHERE h.durum = 'yayinda' AND h.yayin_tarihi > DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY e.id ORDER BY COUNT(*) DESC LIMIT 12")->fetchAll();
            ?>
            <?php if (!empty($trend_etiketler)): ?>
            <div class="sidebar-blok">
                <h3>🏷 Trend Konular</h3>
                <div class="trend-etiketler" style="padding-top:12px">
                    <?php foreach ($trend_etiketler as $e): ?>
                        <a href="<?= url('etiket/' . $e['slug']) ?>" class="trend-etiket"><?= h($e['ad']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

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
    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Kırıntı yolu">
        <a href="<?= url() ?>">Ana Sayfa</a>
        <?php if (!empty($haber['kat_ad'])): ?>
            <span class="sep">›</span>
            <a href="<?= h(url('kategori/' . $haber['kat_slug'])) ?>"><?= h($haber['kat_ad']) ?></a>
        <?php endif; ?>
        <span class="sep">›</span>
        <span class="son"><?= h(mb_substr($haber['baslik'], 0, 60, 'UTF-8')) . (mb_strlen($haber['baslik'], 'UTF-8') > 60 ? '...' : '') ?></span>
    </nav>

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

        <!-- Araç çubuğu: font size, yazdır, dinle, tema -->
        <div class="hd-araclar">
            <div class="hd-araclar-sol">
                <span>Yazı Boyutu</span>
                <button class="hd-arac-btn" onclick="xnews.fontSizeDegistir(-1)" aria-label="Küçült">A-</button>
                <button class="hd-arac-btn" onclick="xnews.fontSizeDegistir(1)" aria-label="Büyüt">A+</button>
            </div>
            <div class="hd-araclar-sag">
                <button class="hd-arac-btn" onclick="xnews.haberiDinle()" title="Sesli Oku">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>
                    Dinle
                </button>
                <button class="hd-arac-btn" onclick="xnews.temaDegistir()" title="Tema Değiştir">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    Tema
                </button>
                <button class="hd-arac-btn" onclick="xnews.yazdir()" title="Yazdır">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Yazdır
                </button>
            </div>
        </div>

        <?php reklam_goster('makale_ust'); ?>
        <div class="hd-icerik"><?= $icerik_final ?></div>

        <?php if (ayar('kaynak_goster', '1') && !empty($haber['kaynak_ad'])): ?>
        <div class="kaynak-atfi">
            <strong>Kaynak:</strong> <?= h($haber['kaynak_atfi'] ?: $haber['kaynak_ad']) ?>
            <?php if (!empty($haber['orijinal_url'])): ?>
                · <a href="<?= h($haber['orijinal_url']) ?>" target="_blank" rel="noopener nofollow">Orijinal haber &rarr;</a>
            <?php endif; ?>
            <div style="margin-top:10px;padding-top:10px;border-top:1px dashed rgba(0,0,0,.15);font-size:13px">
                <span style="color:var(--muted)">Hak sahibi misiniz? </span>
                <a href="<?= url('kaldirma-talebi') ?>?haber=<?= (int)$haber['id'] ?>" style="color:var(--brand);font-weight:600">Bu haberin kaldırılmasını talep et &rarr;</a>
            </div>
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
    <div class="bolum-baslik"><h2>Arama<?= $q ? ': ' . h($q) : '' ?></h2><span class="tumu"><?= $toplam ?> sonuç</span></div>
    <form method="get" action="<?= url() ?>" style="margin-bottom:30px;display:flex;gap:10px;max-width:600px">
        <input type="hidden" name="sayfa" value="arama">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Aramak istediğiniz kelimeyi yazin..." style="flex:1;padding:14px 18px;border:2px solid var(--black);font-family:var(--font-ui);font-size:15px;outline:none" required autofocus>
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
case 'kvkk': case 'cerez': case 'kunye': case 'yayin-ilkeleri':
    $basliklar = [
        'hakkimizda'      => 'Hakkımızda',
        'iletisim'        => 'İletişim',
        'reklam'          => 'Reklam Ver',
        'gizlilik'        => 'Gizlilik Politikası',
        'kullanim'        => 'Kullanım Şartları',
        'kvkk'            => 'KVKK Aydınlatma Metni',
        'cerez'           => 'Çerez Politikası',
        'kunye'           => 'Künye',
        'yayin-ilkeleri'  => 'Yayın İlkelerimiz',
    ];
    $baslik_st = $basliklar[$sayfa];
    sayfa_basla(['aktif_sayfa' => $sayfa, 'baslik' => $baslik_st . ' - ' . ayar('site_adi')]);
?>
<main><div class="kapsayici">
    <article class="haber-detay">
        <div class="hd-ust-bilgi"><h1 class="hd-baslik"><?= h($baslik_st) ?></h1></div>
        <div class="hd-icerik">
        <?php if ($sayfa === 'hakkimizda'): ?>
            <p><?= h(ayar('site_adi', 'XNEWS')) ?>, Türkiye ve dünyadan güncel haberleri hızlı ve tarafsız biçimde okuyucularıyla buluşturan dijital bir haber platformudur.</p>
            <p>Platformumuz; Türkiye'nin önde gelen haber kaynaklarından otomatik olarak haber akışı sağlar, tüm içerikler kaynak belirtilerek orijinal yayıncıya link ile birlikte sunulur.</p>
            <h2>Misyonumuz</h2>
            <p>Okuyuculara hızlı, doğrudan ve yanlı olmayan haber erişimi sağlamak.</p>
            <h2>İletişim</h2>
            <p>Öneri, şikayet ve iş birliği teklifleriniz için <a href="<?= url('iletisim') ?>">iletişim sayfasını</a> kullanabilirsiniz.</p>
        <?php elseif ($sayfa === 'iletisim'): ?>
            <p>Bizimle iletişime geçmek için aşağıdaki kanallardan ulaşabilirsiniz.</p>
            <ul>
                <?php if ($ep = ayar('iletisim_eposta')): ?><li><strong>E-posta:</strong> <a href="mailto:<?= h($ep) ?>"><?= h($ep) ?></a></li><?php endif; ?>
                <?php if ($tel = ayar('iletisim_telefon')): ?><li><strong>Telefon:</strong> <?= h($tel) ?></li><?php endif; ?>
                <?php if ($adr = ayar('iletisim_adres')): ?><li><strong>Adres:</strong> <?= h($adr) ?></li><?php endif; ?>
            </ul>
        <?php elseif ($sayfa === 'reklam'): ?>
            <p><?= h(ayar('site_adi')) ?> üzerinde reklam vermek için bizimle iletişime geçin.</p>
            <h2>Reklam Alanlarımız</h2>
            <ul>
                <li>Üst Banner (970×90)</li>
                <li>Sidebar (300×250 / 300×600)</li>
                <li>Makale İçi ve Dışı Banner (728×90)</li>
                <li>Alt Banner (970×90)</li>
                <li>Mobil Sabit (320×50)</li>
                <li>Google AdSense uyumlu özel yerleştirmeler</li>
            </ul>
            <p>Teklif almak için: <a href="mailto:<?= h(ayar('iletisim_eposta')) ?>"><?= h(ayar('iletisim_eposta')) ?></a></p>
        <?php elseif ($sayfa === 'gizlilik'): ?>
            <p>Bu sayfa yönetim panelinden güncellenebilir.</p>
            <h2>1. Toplanan Bilgiler</h2>
            <p>Sitemizi ziyaret ettiğinizde IP adresiniz ve tarayıcı bilgileriniz anonim olarak kaydedilir.</p>
            <h2>2. Çerezler</h2>
            <p>Sitemiz oturum yönetimi ve analiz için çerez kullanır.</p>
            <h2>3. Üçüncü Taraf Hizmetleri</h2>
            <p>Google Analytics ve Google AdSense gibi üçüncü taraf hizmetleri kullanılıyorsa, ilgili şirketlerin gizlilik politikaları da geçerlidir.</p>
        <?php elseif ($sayfa === 'kullanim'): ?>
            <p>Bu sayfa yönetim panelinden güncellenebilir.</p>
            <h2>1. İçerik Kullanımı</h2>
            <p>Sitemizde yer alan haberler kaynak belirtilmek suretiyle orijinal yayıncılardan sunulmaktadır. Haberlerin telif hakkı kaynak yayın kuruluşuna aittir.</p>
            <h2>2. Sorumluluk Reddi</h2>
            <p>Sitemizde yayımlanan haberlerin doğruluğu, güncel olup olmadığı ve içeriği kaynak yayıncının sorumluluğundadır.</p>
            <h2>3. İçerik Kaldırma Talebi</h2>
            <p>Haber sahibi olan yayıncılar, hak sahipleri veya haber içeriğinde adı geçen kişiler, içeriğin kaldırılmasını talep edebilirler. Talepte bulunmak için <a href="<?= url('kaldirma-talebi') ?>">Kaldırma Talebi</a> sayfasını kullanabilirsiniz.</p>
            <h2>4. Değişiklikler</h2>
            <p>Bu kullanım şartları önceden haber verilmeksizin değiştirilebilir. Güncel sürümü bu sayfada yayımlanır.</p>

        <?php elseif ($sayfa === 'kvkk'): ?>
            <p><strong>6698 Sayılı Kişisel Verilerin Korunması Kanunu (KVKK) Uyarınca Aydınlatma Metni</strong></p>
            <p>İşbu aydınlatma metni, <?= h(ayar('kvkk_veri_sorumlu', ayar('site_adi'))) ?> ("Veri Sorumlusu") tarafından, 6698 sayılı Kişisel Verilerin Korunması Kanunu'nun 10. maddesi uyarınca, kişisel verilerinizin işlenmesine ilişkin usul ve esaslar hakkında sizleri bilgilendirmek amacıyla hazırlanmıştır.</p>

            <h2>1. Veri Sorumlusunun Kimliği</h2>
            <p>Unvan: <?= h(ayar('kvkk_veri_sorumlu', ayar('site_adi'))) ?><br>
            <?php if ($adr = ayar('iletisim_adres')): ?>Adres: <?= h($adr) ?><br><?php endif; ?>
            <?php if ($ep = ayar('kvkk_basvuru_eposta', ayar('iletisim_eposta'))): ?>E-posta: <?= h($ep) ?><br><?php endif; ?>
            <?php if ($tel = ayar('iletisim_telefon')): ?>Telefon: <?= h($tel) ?><?php endif; ?></p>

            <h2>2. İşlenen Kişisel Veriler</h2>
            <p>Sitemizi ziyaretiniz ve hizmetlerimizden yararlanmanız sırasında aşağıdaki kişisel verileriniz işlenebilir:</p>
            <ul>
                <li><strong>Kimlik verileri:</strong> Ad, soyad (yalnızca iletişim/kaldırma talebi için)</li>
                <li><strong>İletişim verileri:</strong> E-posta, telefon (yalnızca kendi talebinizle)</li>
                <li><strong>İşlem güvenliği verileri:</strong> IP adresi, tarayıcı bilgileri, çerez bilgileri, erişim kayıtları</li>
                <li><strong>Pazarlama verileri:</strong> Çerez ve analitik bilgiler (yalnızca izin verdiğiniz takdirde)</li>
            </ul>

            <h2>3. Kişisel Verilerin İşlenme Amaçları</h2>
            <ul>
                <li>Haber sunumu ve hizmetin sağlanması</li>
                <li>Site güvenliğinin sağlanması ve kötüye kullanımın önlenmesi</li>
                <li>Yasal yükümlülüklerin yerine getirilmesi (5651 Sayılı Kanun, KVKK, Basın Kanunu)</li>
                <li>Kullanıcı geri bildirimlerinin ve kaldırma taleplerinin değerlendirilmesi</li>
                <li>Site performansının ölçülmesi ve iyileştirilmesi (analitik)</li>
            </ul>

            <h2>4. Kişisel Verilerin Aktarılması</h2>
            <p>Kişisel verileriniz; yasal yükümlülükler gereği yetkili kamu kurum ve kuruluşlarına, hizmet sağlayıcılarımıza (barındırma, analitik, reklam) gerektiği ölçüde aktarılabilir. Yurt dışına veri aktarımı yalnızca açık rızanızla veya KVKK madde 9 kapsamında gerçekleşir.</p>

            <h2>5. Veri İşlemenin Hukuki Sebepleri</h2>
            <ul>
                <li>Kanunlarda açıkça öngörülmesi (KVKK md.5/2-a)</li>
                <li>Bir hakkın tesisi, kullanılması veya korunması için zorunluluk (md.5/2-e)</li>
                <li>Veri sorumlusunun meşru menfaati (md.5/2-f)</li>
                <li>İlgili kişinin açık rızası (md.5/1) — yalnızca pazarlama çerezleri için</li>
            </ul>

            <h2>6. KVKK Madde 11 Kapsamındaki Haklarınız</h2>
            <p>Veri sahibi olarak aşağıdaki haklara sahipsiniz:</p>
            <ul>
                <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
                <li>İşlenmişse buna ilişkin bilgi talep etme</li>
                <li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme</li>
                <li>Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme</li>
                <li>Eksik veya yanlış işlenmişse düzeltilmesini isteme</li>
                <li>KVKK md.7'de öngörülen şartlar çerçevesinde silinmesini veya yok edilmesini isteme</li>
                <li>Otomatik sistemler vasıtasıyla analiz sonucu aleyhinize bir sonucun ortaya çıkmasına itiraz etme</li>
                <li>Zarara uğramanız hâlinde tazminat talep etme</li>
            </ul>

            <h2>7. Başvuru Yöntemi</h2>
            <?php $kvkk_ep = ayar('kvkk_basvuru_eposta', ayar('iletisim_eposta')); ?>
            <p>KVKK kapsamındaki haklarınızı kullanmak için başvurularınızı yazılı olarak veya e-posta yoluyla iletebilirsiniz:</p>
            <ul>
                <?php if ($kvkk_ep): ?><li>E-posta: <a href="mailto:<?= h($kvkk_ep) ?>"><?= h($kvkk_ep) ?></a></li><?php endif; ?>
                <?php if ($adr = ayar('iletisim_adres')): ?><li>Posta: <?= h($adr) ?></li><?php endif; ?>
            </ul>
            <p>Başvurunuz en geç <strong>30 gün</strong> içinde ücretsiz olarak sonuçlandırılır.</p>

        <?php elseif ($sayfa === 'cerez'): ?>
            <p>Bu Çerez Politikası, <?= h(ayar('site_adi')) ?> web sitesinde kullanılan çerezler hakkında sizleri bilgilendirmek amacıyla hazırlanmıştır.</p>

            <h2>1. Çerez Nedir?</h2>
            <p>Çerez (cookie), web sitelerinin kullanıcı deneyimini iyileştirmek amacıyla tarayıcınıza yerleştirdiği küçük metin dosyalarıdır. Çerezler, kişisel bilgisayarınızda veya mobil cihazınızda saklanır ve sitemize her ziyaretinizde bize gönderilir.</p>

            <h2>2. Kullandığımız Çerez Türleri</h2>

            <h3>2.1 Zorunlu Çerezler</h3>
            <p>Sitemizin temel işlevlerini yerine getirmesi için gerekli çerezlerdir. Bu çerezler olmadan site düzgün çalışmaz. Genellikle tarafınızca yapılan bir işleme (oturum açma, form doldurma gibi) yanıt olarak ayarlanır. <strong>Onayınıza gerek yoktur.</strong></p>
            <ul>
                <li><code>PHPSESSID</code> — Oturum tanımlama</li>
                <li><code>xn_cerez_onay</code> — Çerez tercihlerinizi hatırlar</li>
                <li><code>xn_csrf</code> — Güvenlik (CSRF koruması)</li>
            </ul>

            <h3>2.2 Performans ve Analitik Çerezler</h3>
            <p>Sitemizi nasıl kullandığınız hakkında anonim bilgi toplar. Hangi sayfaların popüler olduğunu, kullanıcıların sitede nasıl gezindiğini anlamamıza yardımcı olur. <strong>İzninize bağlıdır.</strong></p>
            <ul>
                <li>Google Analytics (<code>_ga, _gid</code>) — kullanıldığı takdirde</li>
            </ul>

            <h3>2.3 Reklam Çerezleri</h3>
            <p>Size daha ilgili reklamlar göstermek ve reklam kampanyalarının etkinliğini ölçmek için kullanılır. <strong>İzninize bağlıdır.</strong></p>
            <ul>
                <li>Google AdSense (<code>__gads, __gpi</code>) — kullanıldığı takdirde</li>
            </ul>

            <h2>3. Çerezleri Nasıl Yönetebilirsiniz?</h2>
            <p>Tarayıcı ayarlarınızdan çerezleri silebilir veya engelleyebilirsiniz. Ancak bu durumda sitemizin bazı bölümleri düzgün çalışmayabilir.</p>
            <ul>
                <li><strong>Chrome:</strong> Ayarlar → Gizlilik ve güvenlik → Çerezler</li>
                <li><strong>Firefox:</strong> Ayarlar → Gizlilik ve Güvenlik → Çerezler ve Site Verileri</li>
                <li><strong>Safari:</strong> Tercihler → Gizlilik → Çerezleri Yönet</li>
                <li><strong>Edge:</strong> Ayarlar → Çerezler ve site izinleri</li>
            </ul>

            <h2>4. Çerez Tercihlerinizi Değiştirin</h2>
            <p>Sitemizdeki çerez bildirim panelinden tercihlerinizi güncelleyebilirsiniz. <button onclick="xnews.cerezAyarlariAc()" style="background:#c8102e;color:#fff;border:none;padding:10px 18px;cursor:pointer;font-weight:600">Çerez Ayarlarını Aç</button></p>

            <h2>5. Yasal Dayanak</h2>
            <p>Bu politika 6698 Sayılı KVKK, Elektronik Ticaretin Düzenlenmesi Hakkında Kanun ve ilgili mevzuat çerçevesinde hazırlanmıştır.</p>

        <?php elseif ($sayfa === 'kunye'): ?>
            <p><strong>5187 Sayılı Basın Kanunu gereği künye bilgileri:</strong></p>
            <table style="width:100%;border-collapse:collapse;margin:20px 0">
                <tbody>
                    <tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;width:40%;background:#faf8f3">Yayının Adı</td><td style="padding:12px 8px"><?= h(ayar('site_adi')) ?></td></tr>
                    <tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Yayın Türü</td><td style="padding:12px 8px"><?= h(ayar('kunye_yayin_turu', 'Süreli İnternet Yayını')) ?></td></tr>
                    <tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Yayın Sıklığı</td><td style="padding:12px 8px"><?= h(ayar('kunye_yayin_sikligi', 'Günlük')) ?></td></tr>
                    <tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Yayın Sahibi</td><td style="padding:12px 8px"><?= h(ayar('kunye_yayin_sahibi', ayar('site_adi'))) ?></td></tr>
                    <tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Sorumlu Müdür</td><td style="padding:12px 8px"><?= h(ayar('kunye_sorumlu_mudur', '-')) ?></td></tr>
                    <tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Yazı İşleri Müdürü</td><td style="padding:12px 8px"><?= h(ayar('kunye_yazi_isleri_md', ayar('kunye_sorumlu_mudur', '-'))) ?></td></tr>
                    <?php if ($ts = ayar('kunye_ticaret_sicil')): ?><tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Ticaret Sicil No</td><td style="padding:12px 8px"><?= h($ts) ?></td></tr><?php endif; ?>
                    <?php if ($m = ayar('kunye_mersis')): ?><tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">MERSİS No</td><td style="padding:12px 8px"><?= h($m) ?></td></tr><?php endif; ?>
                    <?php if ($vd = ayar('kunye_vergi_dairesi')): ?><tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Vergi Dairesi / No</td><td style="padding:12px 8px"><?= h($vd) ?><?php if ($vn = ayar('kunye_vergi_no')): ?> / <?= h($vn) ?><?php endif; ?></td></tr><?php endif; ?>
                    <?php if ($adr = ayar('iletisim_adres')): ?><tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Yönetim Yeri</td><td style="padding:12px 8px"><?= h($adr) ?></td></tr><?php endif; ?>
                    <?php if ($tel = ayar('iletisim_telefon')): ?><tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Telefon</td><td style="padding:12px 8px"><?= h($tel) ?></td></tr><?php endif; ?>
                    <?php if ($ep = ayar('iletisim_eposta')): ?><tr style="border-bottom:1px solid #e5e5e5"><td style="padding:12px 8px;font-weight:600;background:#faf8f3">E-posta</td><td style="padding:12px 8px"><a href="mailto:<?= h($ep) ?>"><?= h($ep) ?></a></td></tr><?php endif; ?>
                    <tr><td style="padding:12px 8px;font-weight:600;background:#faf8f3">Web Sitesi</td><td style="padding:12px 8px"><a href="<?= url() ?>"><?= h(ayar('site_url', 'xnews.com.tr')) ?></a></td></tr>
                </tbody>
            </table>
            <p><small>İşbu künye 5187 sayılı Basın Kanunu ve 5651 sayılı İnternet Ortamında Yapılan Yayınların Düzenlenmesi Hakkında Kanun çerçevesinde yayımlanmıştır.</small></p>

        <?php elseif ($sayfa === 'yayin-ilkeleri'): ?>
            <p><strong><?= h(ayar('site_adi')) ?> editöryal ilkeleri ve yayın standartları:</strong></p>

            <h2>1. Doğruluk ve Güvenilirlik</h2>
            <ul>
                <li>Yayımlanan tüm haberler güvenilir kaynaklardan alınır ve <strong>orijinal yayıncıya link ile atıf yapılır</strong>.</li>
                <li>Haber başlıkları, içerikle uyumlu olmak ve yanıltıcı/clickbait olmamak zorundadır.</li>
                <li>Bilgi doğrulanamayan konularda tahmin yerine "iddia edildi", "bildirildi" gibi belirsiz ifadeler kullanılır.</li>
            </ul>

            <h2>2. Tarafsızlık</h2>
            <ul>
                <li>Haberlerde tarafsızlık esastır. Siyasi, dini, etnik ayrımcılık yapılmaz.</li>
                <li>Köşe yazıları ve görüşler, haber olarak sunulmaz.</li>
                <li>Tartışmalı konularda tüm taraflara eşit söz hakkı tanınmaya çalışılır.</li>
            </ul>

            <h2>3. Özel Hayat ve Kişilik Hakları</h2>
            <ul>
                <li>Kamuya mal olmayan kişilerin özel hayatları haber konusu yapılmaz.</li>
                <li>Çocukların kimliği, suç şüphelilerinin tam adı (mahkeme kararı olmaksızın) korunur.</li>
                <li>Şiddet, istismar mağdurlarının kimliği açıklanmaz.</li>
            </ul>

            <h2>4. Etik Kurallar</h2>
            <ul>
                <li>Basın İlan Kurumu ve Türkiye Gazeteciler Cemiyeti meslek ilkelerine uyulur.</li>
                <li>Chiff, Kaynak, İntihal, Manipülasyon gibi etik ihlallere izin verilmez.</li>
                <li>Intihal ve kaynaksız alıntı yapılmaz.</li>
            </ul>

            <h2>5. Düzeltme Hakkı</h2>
            <ul>
                <li>Yanlış bilgi tespit edilmesi halinde 24 saat içinde düzeltme yapılır.</li>
                <li>İlgili kişiler Tekzip Hakkı'nı kullanmak için <a href="<?= url('iletisim') ?>">bize ulaşabilir</a>.</li>
                <li>Haberin kaldırılması talebi için <a href="<?= url('kaldirma-talebi') ?>">Kaldırma Talebi</a> formu kullanılabilir.</li>
            </ul>

            <h2>6. Reklam Ayrımı</h2>
            <ul>
                <li>Sponsorlu içerikler "Reklam" veya "Sponsorlu" etiketiyle açıkça belirtilir.</li>
                <li>Reklam ve editöryel içerik karıştırılmaz.</li>
            </ul>

            <h2>7. Yasal Yükümlülükler</h2>
            <ul>
                <li><?= h(ayar('site_adi')) ?>, 5651 sayılı İnternet Kanunu, 5187 sayılı Basın Kanunu ve 6698 sayılı KVKK'ya uygun faaliyet gösterir.</li>
                <li>Erişim engelleme kararları ve yasal tebligatlara 24 saat içinde yanıt verilir.</li>
            </ul>
        <?php endif; ?>
        </div>
    </article>
</div></main>
<?php
    sayfa_bitis($sayfa);
    break;

// -----------------------------------------------------
// KALDIRMA TALEBI (Takedown Request)
// -----------------------------------------------------
case 'kaldirma-talebi':
    $haber_id_q = (int)($_GET['haber'] ?? 0);
    $haber_info = null;
    if ($haber_id_q > 0) {
        $st = $db->prepare("SELECT h.id, h.baslik, h.slug, k.slug AS kat_slug
                            FROM {$prefix}news h LEFT JOIN {$prefix}categories k ON h.kategori_id = k.id
                            WHERE h.id = ? AND h.durum = 'yayinda'");
        $st->execute([$haber_id_q]);
        $haber_info = $st->fetch();
    }

    $mesaj_kt = '';
    $mesaj_kt_tip = '';

    if (post()) {
        if (!csrf_dogrula($_POST['_csrf'] ?? '')) {
            $mesaj_kt = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.';
            $mesaj_kt_tip = 'hata';
        } else {
            // Math CAPTCHA dogrulamasi
            $captcha_cevap = (int)($_POST['captcha_cevap'] ?? 0);
            $captcha_dogru = (int)($_POST['captcha_dogru'] ?? -1);
            if ($captcha_cevap !== $captcha_dogru) {
                $mesaj_kt = 'Güvenlik sorusu yanlış. Lütfen tekrar deneyin.';
                $mesaj_kt_tip = 'hata';
            } else {
                $hid  = (int)($_POST['haber_id'] ?? 0);
                $ad   = trim($_POST['ad'] ?? '');
                $unv  = trim($_POST['unvan'] ?? '');
                $ep   = trim($_POST['eposta'] ?? '');
                $tel  = trim($_POST['telefon'] ?? '');
                $krm  = trim($_POST['kurum'] ?? '');
                $ilsk = $_POST['iliski'] ?? 'kisisel';
                $seb  = $_POST['sebep']  ?? 'diger';
                $ack  = trim($_POST['aciklama'] ?? '');
                $knt  = trim($_POST['kanit_url'] ?? '');

                // Basit dogrulama
                if ($hid <= 0) {
                    $mesaj_kt = 'Geçerli bir haber seçilmedi.';
                    $mesaj_kt_tip = 'hata';
                } elseif (mb_strlen($ad) < 3 || mb_strlen($ad) > 120) {
                    $mesaj_kt = 'Lütfen geçerli bir ad soyad girin (3-120 karakter).';
                    $mesaj_kt_tip = 'hata';
                } elseif (!filter_var($ep, FILTER_VALIDATE_EMAIL)) {
                    $mesaj_kt = 'Geçerli bir e-posta adresi girin.';
                    $mesaj_kt_tip = 'hata';
                } elseif (mb_strlen($ack) < 30) {
                    $mesaj_kt = 'Lütfen talebinizi en az 30 karakter açıklayın.';
                    $mesaj_kt_tip = 'hata';
                } elseif (!in_array($ilsk, ['hak_sahibi','yayin_sahibi','avukat','kisisel','diger'])) {
                    $mesaj_kt = 'Geçersiz ilişki türü.';
                    $mesaj_kt_tip = 'hata';
                } elseif (!in_array($seb, ['telif','kisilik','kvkk','yanlis','itibar','diger'])) {
                    $mesaj_kt = 'Geçersiz sebep.';
                    $mesaj_kt_tip = 'hata';
                } else {
                    // Haber gercekten var mi?
                    $st = $db->prepare("SELECT id FROM {$prefix}news WHERE id = ?");
                    $st->execute([$hid]);
                    if (!$st->fetchColumn()) {
                        $mesaj_kt = 'Haber bulunamadı.';
                        $mesaj_kt_tip = 'hata';
                    } else {
                        // Kayit
                        $st = $db->prepare("INSERT INTO {$prefix}takedown
                            (haber_id, talep_eden_ad, talep_eden_unvan, eposta, telefon, kurum, iliski, sebep, aciklama, kanit_url, ip_adresi, kullanici_ajan, durum)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'beklemede')");
                        $st->execute([
                            $hid, $ad, $unv ?: null, $ep, $tel ?: null, $krm ?: null,
                            $ilsk, $seb, $ack, $knt ?: null,
                            $_SERVER['REMOTE_ADDR'] ?? null,
                            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                        ]);
                        log_ekle('islem', 'Kaldırma talebi alındı', "Haber ID: $hid | Ad: $ad | E-posta: $ep");
                        $mesaj_kt = 'Talebiniz alınmıştır. En geç ' . (int)ayar('takedown_yanit_suresi', 72) . ' saat içinde size dönüş yapılacaktır.';
                        $mesaj_kt_tip = 'basari';
                        $haber_info = null; // formu temizle
                    }
                }
            }
        }
    }

    // Math CAPTCHA uret
    $cap_a = random_int(2, 9);
    $cap_b = random_int(2, 9);
    $cap_dogru = $cap_a + $cap_b;

    sayfa_basla(['aktif_sayfa' => 'kaldirma-talebi', 'baslik' => 'Kaldırma Talebi - ' . ayar('site_adi')]);
?>
<main><div class="kapsayici">
    <article class="haber-detay" style="max-width:800px">
        <div class="hd-ust-bilgi"><h1 class="hd-baslik">Kaldırma Talebi</h1>
            <p class="hd-ozet">Hak sahibi, yayıncı veya içerikte bahsi geçen kişiler, haber içeriğinin kaldırılmasını talep edebilir.</p>
        </div>

        <?php if ($mesaj_kt): ?>
            <div style="padding:16px 20px;margin:20px 0;border-radius:4px;<?= $mesaj_kt_tip === 'basari' ? 'background:#d1fae5;border:1px solid #10b981;color:#064e3b' : 'background:#fee2e2;border:1px solid #ef4444;color:#7f1d1d' ?>">
                <?= h($mesaj_kt) ?>
            </div>
        <?php endif; ?>

        <?php if ($mesaj_kt_tip !== 'basari'): ?>
        <div class="hd-icerik">
            <div style="padding:14px 18px;background:#faf8f3;border-left:3px solid #c8102e;margin:20px 0;font-size:14px;line-height:1.7">
                <strong>Önemli:</strong> Kaldırma talebiniz incelendikten sonra size <strong><?= (int)ayar('takedown_yanit_suresi', 72) ?> saat içinde</strong> dönüş yapılır. Hukuki belge/mahkeme kararı varsa eklemeniz yanıt süresini kısaltır. <strong>Gerçeğe aykırı talepler cezai sorumluluk doğurur.</strong>
            </div>

            <form method="post" style="display:flex;flex-direction:column;gap:18px;margin-top:30px">
                <?= csrf_input() ?>
                <input type="hidden" name="captcha_dogru" value="<?= $cap_dogru ?>">

                <!-- Haber Bilgisi -->
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">Hangi haberin kaldırılmasını talep ediyorsunuz? *</label>
                    <?php if ($haber_info): ?>
                        <div style="padding:14px;background:#f1f5f9;border-radius:4px;border:1px solid #cbd5e1">
                            <div style="font-weight:600"><?= h($haber_info['baslik']) ?></div>
                            <div style="font-size:12px;color:#64748b;margin-top:4px">ID: <?= $haber_info['id'] ?></div>
                        </div>
                        <input type="hidden" name="haber_id" value="<?= (int)$haber_info['id'] ?>">
                    <?php else: ?>
                        <input type="number" name="haber_id" required min="1" placeholder="Haber ID'si veya URL" value="<?= h($_POST['haber_id'] ?? '') ?>" style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px">
                        <div style="font-size:12px;color:#64748b;margin-top:4px">Haberin URL'sinden ID bulabilirsiniz: /haber/<strong>123</strong>-baslik</div>
                    <?php endif; ?>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">Ad Soyad *</label>
                        <input type="text" name="ad" required minlength="3" maxlength="120" value="<?= h($_POST['ad'] ?? '') ?>" style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px">
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">Unvan / Görev</label>
                        <input type="text" name="unvan" maxlength="120" placeholder="Avukat, Yayın Sahibi, vb." value="<?= h($_POST['unvan'] ?? '') ?>" style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">E-posta *</label>
                        <input type="email" name="eposta" required value="<?= h($_POST['eposta'] ?? '') ?>" style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px">
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">Telefon</label>
                        <input type="tel" name="telefon" maxlength="30" value="<?= h($_POST['telefon'] ?? '') ?>" style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px">
                    </div>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">Kurum / Şirket Adı</label>
                    <input type="text" name="kurum" maxlength="150" value="<?= h($_POST['kurum'] ?? '') ?>" style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">İçerikle İlişkiniz *</label>
                        <?php $il_s = $_POST['iliski'] ?? ''; ?>
                        <select name="iliski" required style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px;background:#fff">
                            <option value="hak_sahibi" <?= $il_s==='hak_sahibi'?'selected':'' ?>>Hak sahibi (eser sahibi, telif)</option>
                            <option value="yayin_sahibi" <?= $il_s==='yayin_sahibi'?'selected':'' ?>>Orijinal yayın sahibi</option>
                            <option value="avukat" <?= $il_s==='avukat'?'selected':'' ?>>Avukat (vekâleten)</option>
                            <option value="kisisel" <?= $il_s==='kisisel'?'selected':'' ?>>Kişisel (haberde adım geçiyor)</option>
                            <option value="diger" <?= $il_s==='diger'?'selected':'' ?>>Diğer</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">Kaldırma Sebebi *</label>
                        <?php $s_s = $_POST['sebep'] ?? ''; ?>
                        <select name="sebep" required style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px;background:#fff">
                            <option value="telif" <?= $s_s==='telif'?'selected':'' ?>>Telif hakkı ihlali</option>
                            <option value="kisilik" <?= $s_s==='kisilik'?'selected':'' ?>>Kişilik hakkı ihlali</option>
                            <option value="kvkk" <?= $s_s==='kvkk'?'selected':'' ?>>KVKK - Kişisel veri</option>
                            <option value="yanlis" <?= $s_s==='yanlis'?'selected':'' ?>>Yanlış / asılsız bilgi</option>
                            <option value="itibar" <?= $s_s==='itibar'?'selected':'' ?>>İtibar / hakaret</option>
                            <option value="diger" <?= $s_s==='diger'?'selected':'' ?>>Diğer</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">Açıklama * <small style="font-weight:normal;color:#64748b">(en az 30 karakter)</small></label>
                    <textarea name="aciklama" required minlength="30" rows="6" style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px;font-family:inherit;resize:vertical"><?= h($_POST['aciklama'] ?? '') ?></textarea>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px">Kanıt / Belge URL</label>
                    <input type="url" name="kanit_url" maxlength="500" placeholder="https://... (mahkeme kararı, telif belgesi, vb.)" value="<?= h($_POST['kanit_url'] ?? '') ?>" style="padding:12px;border:1px solid #cbd5e1;border-radius:4px;width:100%;font-size:14px">
                </div>

                <!-- Math CAPTCHA -->
                <div style="padding:16px;background:#fef3c7;border:1px solid #fbbf24;border-radius:4px">
                    <label style="display:block;font-weight:600;margin-bottom:8px;font-size:13px">Güvenlik Doğrulama: <?= $cap_a ?> + <?= $cap_b ?> = ?</label>
                    <input type="number" name="captcha_cevap" required style="padding:10px;border:1px solid #cbd5e1;border-radius:4px;width:120px;font-size:14px">
                </div>

                <div style="font-size:12px;color:#64748b;line-height:1.6;padding:14px;background:#faf8f3;border-radius:4px">
                    <strong>KVKK Bilgilendirme:</strong> Bu form aracılığıyla ilettiğiniz kişisel veriler (ad, e-posta, telefon, IP adresi), talebinizin değerlendirilmesi amacıyla işlenir ve yasal saklama sürelerine uygun olarak saklanır.
                    <a href="<?= url('kvkk') ?>" target="_blank">Detaylı bilgi için KVKK Aydınlatma Metni</a>.
                </div>

                <button type="submit" style="background:#c8102e;color:#fff;border:none;padding:16px 32px;font-weight:700;text-transform:uppercase;font-size:14px;letter-spacing:.08em;cursor:pointer;border-radius:4px;font-family:inherit">
                    Talebi Gönder
                </button>
            </form>
        </div>
        <?php endif; ?>
    </article>
</div></main>
<?php
    sayfa_bitis('kaldirma-talebi');
    break;

// -----------------------------------------------------
// 404
// -----------------------------------------------------
case '404':
default:
sayfa_404:
    http_response_code(404);
    sayfa_basla(['baslik' => '404 - Sayfa Bulunamadı - ' . ayar('site_adi'), 'aktif_sayfa' => '404']);
?>
<main><div class="kapsayici">
    <div class="hata-sayfa">
        <div class="numara">404</div>
        <h1>Sayfa Bulunamadı</h1>
        <p style="color:var(--muted);margin-bottom:28px">Aradığınız sayfa silinmiş, taşınmış veya hiç var olmamış olabilir.</p>
        <a href="<?= url() ?>" style="display:inline-block;background:var(--black);color:#fff;padding:14px 32px;font-family:var(--font-ui);font-weight:700;text-transform:uppercase;letter-spacing:.1em;font-size:13px">Ana Sayfaya Dön</a>
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
        echo '<div style="padding:60px;text-align:center;font-family:system-ui"><h1>Bir hata oluştu</h1><p>Kısa bir süre sonra tekrar deneyiniz.</p></div>';
    }
}
