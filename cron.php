<?php
/**
 * XNEWS - RSS Çekim Motoru (Cron)
 *
 * Kullanim:
 *   HTTP: https://xnews.com.tr/cron.php?anahtar=CRON_ANAHTARI
 *   CLI:  php cron.php CRON_ANAHTARI
 *
 * DirectAdmin Cron (her 10 dakikada):
 *   /usr/local/bin/php /home/USER/domains/xnews.com.tr/public_html/cron.php ANAHTAR
 *   veya
 *   wget -q -O /dev/null "https://xnews.com.tr/cron.php?anahtar=ANAHTAR"
 *
 * Manuel zorlama (sadece aktif admin oturumdan):
 *   ?anahtar=...&manuel=1&kaynak_id=5  (tek kaynak zorla)
 */
define('XNEWS', true);
require __DIR__ . '/baglan.php';

// CLI ise GET parametrelerini argv'den al
if (PHP_SAPI === 'cli') {
    $_GET['anahtar'] = $argv[1] ?? '';
    if (!empty($argv[2])) $_GET['kaynak_id'] = $argv[2];
}

// Anahtar doğrulama
if (empty($_GET['anahtar']) || !hash_equals(CRON_ANAHTARI, (string)$_GET['anahtar'])) {
    http_response_code(403);
    die('Erişim reddedildi.');
}

// Zaman limiti (DirectAdmin varsayilani 60sn, biz 300sn'ye cekiyoruz)
@set_time_limit(MAX_CEKIM_SURESI);
@ini_set('memory_limit', '256M');
ignore_user_abort(true);

$manuel        = !empty($_GET['manuel']);
$tek_kaynak_id = isset($_GET['kaynak_id']) ? (int)$_GET['kaynak_id'] : 0;
$gorsel_cikti  = !isset($_GET['sessiz']) && PHP_SAPI !== 'cli';

$baslangic_zamani = microtime(true);

// Ciktimi gormek istersen HTML, yoksa dump text
if ($gorsel_cikti) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset=utf-8><title>XNEWS Cron</title>';
    echo '<style>body{font-family:system-ui,monospace;background:#0f172a;color:#e2e8f0;padding:30px;font-size:13px;line-height:1.6}';
    echo '.k{color:#94a3b8}.b{color:#4ade80}.h{color:#f87171}.u{color:#fbbf24}.a{color:#60a5fa}h1{color:#fff;font-size:20px;margin-bottom:16px}';
    echo 'pre{background:#1e293b;padding:12px;border-radius:6px;white-space:pre-wrap;margin:8px 0}</style>';
    echo '<h1>🔄 XNEWS RSS Çekim Motoru</h1>';
}

// Cikti yardimcisi
function yaz(string $mesaj, string $sinif = 'k'): void {
    global $gorsel_cikti;
    if ($gorsel_cikti) {
        echo '<div class="' . $sinif . '">' . htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8') . '</div>';
        @ob_flush(); @flush();
    } else {
        echo $mesaj . "\n";
    }
}

// =====================================================
// RSS PARSER (SimpleXML tabanli)
// =====================================================

/**
 * RSS/Atom beslemesini cek ve ogeleri dondur
 */
function rss_cek(string $url): array {
    $r = http_getir($url, HTTP_TIMEOUT);
    if ($r['kod'] !== 200) {
        throw new RuntimeException('HTTP ' . $r['kod'] . ($r['hata'] ? ' - ' . $r['hata'] : ''));
    }
    if (empty($r['icerik'])) {
        throw new RuntimeException('Bos cevap');
    }

    // BOM varsa temizle
    $icerik = preg_replace('/^\xEF\xBB\xBF/', '', $r['icerik']);

    // XML parse
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($icerik, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    if ($xml === false) {
        $hatalar = array_map(fn($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        throw new RuntimeException('XML parse hatasi: ' . ($hatalar[0] ?? 'bilinmeyen'));
    }

    $ogeler = [];

    // RSS 2.0 format
    if (isset($xml->channel->item)) {
        foreach ($xml->channel->item as $item) {
            $ogeler[] = rss_oge_cikar($item, 'rss');
        }
    }
    // Atom format
    elseif (isset($xml->entry)) {
        foreach ($xml->entry as $entry) {
            $ogeler[] = rss_oge_cikar($entry, 'atom');
        }
    }
    // RDF format (RSS 1.0)
    elseif (isset($xml->item)) {
        foreach ($xml->item as $item) {
            $ogeler[] = rss_oge_cikar($item, 'rdf');
        }
    }

    return $ogeler;
}

/**
 * Tekil RSS/Atom ogesini normalize et
 */
function rss_oge_cikar(SimpleXMLElement $item, string $format): array {
    // Namespace'leri al
    $ns = $item->getNamespaces(true);
    $media = !empty($ns['media']) ? $item->children($ns['media']) : null;
    $dc    = !empty($ns['dc'])    ? $item->children($ns['dc'])    : null;
    $content_ns = !empty($ns['content']) ? $item->children($ns['content']) : null;

    // Başlık
    $baslik = (string)($item->title ?? '');

    // Link
    $link = '';
    if ($format === 'atom') {
        foreach ($item->link as $l) {
            $attr = $l->attributes();
            if (empty($attr['rel']) || (string)$attr['rel'] === 'alternate') {
                $link = (string)($attr['href'] ?? ''); break;
            }
        }
    } else {
        $link = (string)($item->link ?? '');
    }

    // Açıklama/Özet
    $ozet = '';
    if ($format === 'atom') {
        $ozet = (string)($item->summary ?? $item->content ?? '');
    } else {
        $ozet = (string)($item->description ?? '');
    }

    // Tam icerik (content:encoded)
    $icerik = '';
    if ($content_ns && !empty($content_ns->encoded)) {
        $icerik = (string)$content_ns->encoded;
    } elseif ($format === 'atom' && !empty($item->content)) {
        $icerik = (string)$item->content;
    } else {
        $icerik = $ozet; // Fallback
    }

    // Yayın tarihi
    $tarih = '';
    foreach (['pubDate', 'published', 'updated'] as $alan) {
        if (!empty($item->$alan)) { $tarih = (string)$item->$alan; break; }
    }
    if (empty($tarih) && $dc && !empty($dc->date)) { $tarih = (string)$dc->date; }
    if (empty($tarih)) { $tarih = date('Y-m-d H:i:s'); }
    $ts = strtotime($tarih);
    $tarih = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');

    // GUID
    $guid = (string)($item->guid ?? $item->id ?? $link ?? '');

    // Yazar
    $yazar = '';
    if ($dc && !empty($dc->creator)) { $yazar = (string)$dc->creator; }
    elseif (!empty($item->author)) {
        $yazar = $format === 'atom' ? (string)($item->author->name ?? '') : (string)$item->author;
    }

    // Kategori
    $kategori = '';
    if (!empty($item->category)) {
        $kategori = is_object($item->category) ? (string)$item->category : (string)$item->category[0];
    }

    // GORSEL CIKARMA (oncelik sirasi)
    $resim = '';

    // 1) media:content
    if ($media && !empty($media->content)) {
        foreach ($media->content as $mc) {
            $attr = $mc->attributes();
            if (!empty($attr['url'])) {
                $tip = (string)($attr['type'] ?? '');
                if (empty($tip) || str_starts_with($tip, 'image/')) {
                    $resim = (string)$attr['url']; break;
                }
            }
        }
    }
    // 2) media:thumbnail
    if (empty($resim) && $media && !empty($media->thumbnail)) {
        $attr = $media->thumbnail->attributes();
        if (!empty($attr['url'])) $resim = (string)$attr['url'];
    }
    // 3) enclosure (RSS)
    if (empty($resim) && !empty($item->enclosure)) {
        foreach ($item->enclosure as $enc) {
            $attr = $enc->attributes();
            $tip = (string)($attr['type'] ?? '');
            if (!empty($attr['url']) && (empty($tip) || str_starts_with($tip, 'image/'))) {
                $resim = (string)$attr['url']; break;
            }
        }
    }
    // 4) açıklama icindeki ilk <img src=...>
    if (empty($resim) && !empty($icerik)) {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $icerik, $m)) {
            $resim = $m[1];
        }
    }
    if (empty($resim) && !empty($ozet)) {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $ozet, $m)) {
            $resim = $m[1];
        }
    }

    return [
        'baslik'   => trim(html_entity_decode((string)$baslik, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        'link'     => trim($link),
        'ozet'     => html_entity_decode($ozet, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'icerik'   => html_entity_decode($icerik, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'tarih'    => $tarih,
        'guid'     => $guid,
        'yazar'    => trim(html_entity_decode((string)$yazar, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        'kategori' => trim($kategori),
        'resim'    => trim($resim),
    ];
}

// =====================================================
// ICERIK TEMIZLEME (XSS ve zararli etiketleri kaldir)
// =====================================================
function icerik_temizle(string $html): string {
    // Tehlikeli etiketleri kaldir
    $html = preg_replace('#<(script|iframe|object|embed|form|input|button|textarea|style)\b[^>]*>.*?</\1>#is', '', $html);
    $html = preg_replace('#<(script|iframe|object|embed|form|input|button|textarea|style|link|meta)\b[^>]*/?>#i', '', $html);
    // on* event attribute'larini kaldir
    $html = preg_replace('#\s+on[a-z]+\s*=\s*(["\'])[^"\']*\1#i', '', $html);
    // javascript: linkleri
    $html = preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1="#"', $html);
    // Gereksiz bosluklar
    $html = preg_replace('/\s+/', ' ', $html);
    $html = preg_replace('/>\s+</', '><', $html);
    // Bos paragraflari kaldir
    $html = preg_replace('#<p[^>]*>\s*(&nbsp;)?\s*</p>#i', '', $html);
    return trim($html);
}

// =====================================================
// ANA CEKIM DONGUSU
// =====================================================

try {
    // Çekilecek kaynaklari al
    $simdi = date('Y-m-d H:i:s');
    if ($tek_kaynak_id > 0) {
        $stmt = $db->prepare("SELECT * FROM " . DB_PREFIX . "sources WHERE id = ? AND aktif = 1");
        $stmt->execute([$tek_kaynak_id]);
    } elseif ($manuel) {
        // Manuel tetikte tum aktif kaynaklari zorla cek
        $stmt = $db->query("SELECT * FROM " . DB_PREFIX . "sources WHERE aktif = 1 ORDER BY son_cekim ASC");
    } else {
        // Normal cron: sadece cekim_sikligi dolanlar
        $stmt = $db->query("SELECT * FROM " . DB_PREFIX . "sources
            WHERE aktif = 1
              AND (son_cekim IS NULL OR TIMESTAMPDIFF(MINUTE, son_cekim, NOW()) >= cekim_sikligi)
            ORDER BY son_cekim ASC");
    }
    $kaynaklar = $stmt->fetchAll();

    if (empty($kaynaklar)) {
        yaz('Çekilecek kaynak yok.', 'u');
        yaz('Toplam sure: ' . round(microtime(true) - $baslangic_zamani, 2) . ' sn', 'a');
        if ($gorsel_cikti) echo '</body>';
        exit;
    }

    yaz(count($kaynaklar) . ' kaynak cekilecek. Mod: ' . ($manuel ? 'MANUEL' : 'CRON'), 'a');
    yaz(str_repeat('-', 60));

    $toplam_eklenen = 0;
    $toplam_atlanan = 0;
    $toplam_hatali  = 0;

    foreach ($kaynaklar as $kaynak) {
        // Zaman limitine yaklasiyor mu?
        if ((microtime(true) - $baslangic_zamani) > (MAX_CEKIM_SURESI - 20)) {
            yaz('Zaman siniri yaklaşti, diger kaynaklar bir sonraki cekimde alinacak.', 'u');
            break;
        }

        $k_baslangic = microtime(true);
        yaz('▸ [' . $kaynak['id'] . '] ' . $kaynak['ad'], 'a');

        $kaynak_eklenen = 0;
        $kaynak_atlanan = 0;
        $durum = 'başarılı';
        $hata_mesaj = null;

        try {
            $ogeler = rss_cek($kaynak['rss_url']);
            if (empty($ogeler)) {
                throw new RuntimeException('RSS boş veya parse edilemedi.');
            }

            yaz('  ' . count($ogeler) . ' öge bulundu, isleniyor...');
            $max = min(count($ogeler), (int)$kaynak['max_haber_adet']);

            for ($i = 0; $i < $max; $i++) {
                $oge = $ogeler[$i];
                if (empty($oge['baslik'])) { $kaynak_atlanan++; continue; }

                // Kategori belirle
                $kat_id = (int)($kaynak['varsayilan_kategori_id'] ?? 0);
                if (empty($kat_id)) {
                    // Kategori secili degilse ilk aktif kategoriyi kullan
                    $kat_id = (int)$db->query("SELECT id FROM " . DB_PREFIX . "categories WHERE aktif = 1 ORDER BY sira LIMIT 1")->fetchColumn();
                    if (empty($kat_id)) {
                        $kaynak_atlanan++; continue;
                    }
                }

                // Duplicate kontrol (hash)
                $hash = sha1(mb_strtolower($oge['baslik'], 'UTF-8') . '|' . $kat_id);
                $c = $db->prepare("SELECT id FROM " . DB_PREFIX . "news WHERE icerik_hash = ? LIMIT 1");
                $c->execute([$hash]);
                if ($c->fetch()) { $kaynak_atlanan++; continue; }

                // GUID ile de kontrol (ayni RSS'de ayni haber tekrar gelebilir)
                if (!empty($oge['guid'])) {
                    $c = $db->prepare("SELECT id FROM " . DB_PREFIX . "news WHERE guid = ? LIMIT 1");
                    $c->execute([$oge['guid']]);
                    if ($c->fetch()) { $kaynak_atlanan++; continue; }
                }

                // Slug
                $slug = benzersiz_slug($db, DB_PREFIX . 'news', slug_olustur($oge['baslik'], 200));

                // İçerik temizligi
                $icerik_temiz = icerik_temizle($oge['icerik']);
                if (empty($icerik_temiz) && !empty($oge['ozet'])) {
                    $icerik_temiz = '<p>' . strip_tags($oge['ozet']) . '</p>';
                }
                $ozet_temiz = trim(strip_tags($oge['ozet']));
                $ozet_temiz = mb_substr($ozet_temiz, 0, 500, 'UTF-8');

                // INSERT
                $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "news
                    (baslik, slug, ozet, icerik, resim, kaynak_id, kategori_id, yazar, orijinal_url,
                     guid, icerik_hash, durum, yayin_tarihi, olusturma)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'yayinda', ?, NOW())");
                $stmt->execute([
                    mb_substr($oge['baslik'], 0, 255, 'UTF-8'),
                    $slug,
                    $ozet_temiz,
                    $icerik_temiz,
                    $oge['resim'] ?: null,
                    $kaynak['id'],
                    $kat_id,
                    mb_substr($oge['yazar'], 0, 120, 'UTF-8'),
                    $oge['link'] ?: null,
                    mb_substr($oge['guid'], 0, 500, 'UTF-8'),
                    $hash,
                    $oge['tarih'],
                ]);
                $kaynak_eklenen++;
            }

            // Kaynagi guncelle
            $db->prepare("UPDATE " . DB_PREFIX . "sources
                SET son_cekim = NOW(), son_durum = ?, son_hata = NULL, toplam_haber = toplam_haber + ?
                WHERE id = ?")->execute(['basarili', $kaynak_eklenen, $kaynak['id']]);

            yaz('  ✓ ' . $kaynak_eklenen . ' yeni, ' . $kaynak_atlanan . ' atlandı.', 'b');

        } catch (Throwable $e) {
            $durum = 'hata';
            $hata_mesaj = $e->getMessage();
            $toplam_hatali++;
            yaz('  ✗ HATA: ' . $hata_mesaj, 'h');
            $db->prepare("UPDATE " . DB_PREFIX . "sources SET son_cekim = NOW(), son_durum = 'hata', son_hata = ? WHERE id = ?")
               ->execute([mb_substr($hata_mesaj, 0, 500, 'UTF-8'), $kaynak['id']]);
        }

        $k_sure_ms = (int)((microtime(true) - $k_baslangic) * 1000);

        // Cron gecmisine kaydet
        $db->prepare("INSERT INTO " . DB_PREFIX . "cron_history
            (kaynak_id, durum, eklenen, atlanan, toplam, sure_ms, hata_mesaj)
            VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$kaynak['id'], $durum, $kaynak_eklenen, $kaynak_atlanan,
                      $kaynak_eklenen + $kaynak_atlanan, $k_sure_ms, $hata_mesaj]);

        $toplam_eklenen += $kaynak_eklenen;
        $toplam_atlanan += $kaynak_atlanan;
    }

    // Genel ayar: son cron çalışması
    ayar_guncelle('cron_son_calisma', date('Y-m-d H:i:s'));

    $toplam_sure = round(microtime(true) - $baslangic_zamani, 2);

    yaz(str_repeat('-', 60));
    yaz('TOPLAM: ' . $toplam_eklenen . ' haber eklendi, ' . $toplam_atlanan . ' atlandı, ' . $toplam_hatali . ' kaynak hatali.', 'b');
    yaz('Süre: ' . $toplam_sure . ' sn', 'a');

    log_ekle('cron', 'RSS çekimi tamamlandi',
        "Eklenen: {$toplam_eklenen}, Atlanan: {$toplam_atlanan}, Hatali kaynak: {$toplam_hatali}, Süre: {$toplam_sure}sn");

} catch (Throwable $e) {
    log_ekle('hata', 'Cron global hata', $e->getMessage() . "\n" . $e->getTraceAsString());
    yaz('GLOBAL HATA: ' . $e->getMessage(), 'h');
    http_response_code(500);
}

if ($gorsel_cikti) echo '</body>';
