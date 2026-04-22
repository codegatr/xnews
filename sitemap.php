<?php
/**
 * XNEWS - Dinamik sitemap.xml ureticisi
 * URL: https://xnews.com.tr/sitemap.xml (htaccess ile yonlendirilir)
 *      Dogrudan: https://xnews.com.tr/sitemap.php
 *
 * Google News sitemap standardina uygun.
 */
define('XNEWS', true);
require __DIR__ . '/baglan.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // 1 saat cache
$prefix = DB_PREFIX;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Yardimci - URL yaz
function su(string $loc, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5'): void {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
    if ($lastmod) echo '    <lastmod>' . date('c', strtotime($lastmod)) . "</lastmod>\n";
    echo '    <changefreq>' . $changefreq . "</changefreq>\n";
    echo '    <priority>' . $priority . "</priority>\n";
    echo "  </url>\n";
}

// 1) Ana sayfa
su(url(), null, 'hourly', '1.0');

// 2) Statik sayfalar
foreach (['hakkimizda', 'iletisim', 'reklam', 'gizlilik', 'kullanim-sartlari'] as $s) {
    su(url('?sayfa=' . $s), null, 'monthly', '0.3');
}

// 3) Kategoriler
$kategoriler = $db->query("SELECT slug, (SELECT MAX(yayin_tarihi) FROM {$prefix}news n WHERE n.kategori_id = k.id) AS son_haber
    FROM {$prefix}categories k WHERE aktif = 1")->fetchAll();
foreach ($kategoriler as $k) {
    su(url('kategori/' . $k['slug']), $k['son_haber'], 'daily', '0.8');
}

// 4) Haberler (son 5000)
$stmt = $db->query("SELECT id, slug, yayin_tarihi, guncelleme
    FROM {$prefix}news
    WHERE durum = 'yayinda'
    ORDER BY yayin_tarihi DESC LIMIT 5000");
foreach ($stmt as $h) {
    $url = url('haber/' . $h['id'] . '-' . $h['slug']);
    $lastmod = $h['guncelleme'] ?: $h['yayin_tarihi'];
    // Son 48 saat icindeyse yüksek oncelik
    $oncelik = (time() - strtotime($h['yayin_tarihi'])) < 172800 ? '0.9' : '0.6';
    su($url, $lastmod, 'weekly', $oncelik);
}

// 5) Etiketler (opsiyonel - kullanim > 3 olanlar)
$etiketler = $db->query("SELECT slug FROM {$prefix}tags WHERE kullanim > 2")->fetchAll();
foreach ($etiketler as $t) {
    su(url('etiket/' . $t['slug']), null, 'weekly', '0.4');
}

// 6) Kaynaklar
$kaynaklar = $db->query("SELECT slug FROM {$prefix}sources WHERE aktif = 1")->fetchAll();
foreach ($kaynaklar as $kay) {
    su(url('kaynak/' . $kay['slug']), null, 'weekly', '0.3');
}

echo '</urlset>';
