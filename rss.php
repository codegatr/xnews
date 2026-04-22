<?php
/**
 * XNEWS - Public RSS Cikis Beslemesi
 * Kullanim:
 *   https://xnews.com.tr/rss.php                 (tum haberler)
 *   https://xnews.com.tr/rss.php?kategori=gundem (kategori bazli)
 *   https://xnews.com.tr/rss.php?kaynak=trthaber (kaynak bazli)
 */
define('XNEWS', true);
require __DIR__ . '/baglan.php';

header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=600'); // 10 dk cache

$prefix = DB_PREFIX;

// Filtre
$kategori_slug = preg_replace('/[^a-z0-9-]/i', '', $_GET['kategori'] ?? '');
$kaynak_slug   = preg_replace('/[^a-z0-9-]/i', '', $_GET['kaynak'] ?? '');
$baslik_ek     = '';
$where         = "n.durum = 'yayinda'";
$params        = [];

if ($kategori_slug) {
    $k = $db->prepare("SELECT id, ad FROM {$prefix}categories WHERE slug = ? LIMIT 1");
    $k->execute([$kategori_slug]);
    $kat = $k->fetch();
    if ($kat) { $where .= ' AND n.kategori_id = ?'; $params[] = $kat['id']; $baslik_ek = ' - ' . $kat['ad']; }
}
if ($kaynak_slug) {
    $k = $db->prepare("SELECT id, ad FROM {$prefix}sources WHERE slug = ? LIMIT 1");
    $k->execute([$kaynak_slug]);
    $kay = $k->fetch();
    if ($kay) { $where .= ' AND n.kaynak_id = ?'; $params[] = $kay['id']; $baslik_ek = ' - ' . $kay['ad']; }
}

// Haberleri cek
$sql = "SELECT n.id, n.baslik, n.slug, n.ozet, n.icerik, n.resim, n.yayin_tarihi, n.yazar,
               k.ad AS kategori_ad, s.ad AS kaynak_ad
        FROM {$prefix}news n
        LEFT JOIN {$prefix}categories k ON k.id = n.kategori_id
        LEFT JOIN {$prefix}sources s ON s.id = n.kaynak_id
        WHERE {$where}
        ORDER BY n.yayin_tarihi DESC LIMIT 50";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$haberler = $stmt->fetchAll();

$site_adi = ayar('site_adi', 'XNEWS');
$site_aciklama = ayar('site_aciklama', '');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
    <title><?= htmlspecialchars($site_adi . $baslik_ek, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></title>
    <link><?= htmlspecialchars(url(), ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></link>
    <description><?= htmlspecialchars($site_aciklama, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></description>
    <language>tr-TR</language>
    <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
    <generator>XNEWS (CODEGA)</generator>
    <atom:link href="<?= htmlspecialchars(url('rss.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')), ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>" rel="self" type="application/rss+xml"/>
    <image>
        <url><?= htmlspecialchars(url('favicon.svg'), ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></url>
        <title><?= htmlspecialchars($site_adi, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></title>
        <link><?= htmlspecialchars(url(), ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></link>
    </image>

<?php foreach ($haberler as $h):
    $haber_url = url('haber/' . $h['id'] . '-' . $h['slug']);
    $icerik = !empty($h['resim']) ? '<img src="' . htmlspecialchars(haber_gorsel($h['resim']), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" alt=""/><br/>' : '';
    $icerik .= $h['icerik'] ?: '<p>' . htmlspecialchars($h['ozet'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</p>';
?>
    <item>
        <title><?= htmlspecialchars($h['baslik'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></title>
        <link><?= htmlspecialchars($haber_url, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars($haber_url, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></guid>
        <pubDate><?= date(DATE_RSS, strtotime($h['yayin_tarihi'])) ?></pubDate>
        <description><?= htmlspecialchars(kisalt($h['ozet'] ?: $h['icerik'], 300), ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></description>
        <content:encoded><![CDATA[<?= $icerik ?>]]></content:encoded>
<?php if ($h['kategori_ad']): ?>
        <category><?= htmlspecialchars($h['kategori_ad'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></category>
<?php endif; if ($h['yazar']): ?>
        <dc:creator><?= htmlspecialchars($h['yazar'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></dc:creator>
<?php endif; if ($h['kaynak_ad']): ?>
        <source url="<?= htmlspecialchars(url('kaynak/' . $kaynak_slug), ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($h['kaynak_ad'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></source>
<?php endif; ?>
    </item>
<?php endforeach; ?>
</channel>
</rss>
