-- ==========================================================
-- XNEWS v1.3.4 - Disisleri Bakanligi RSS duzeltmesi
-- ==========================================================
-- Yanlis URL: mfa.gov.tr/rss.tr.mfa (bulunamiyor)
-- Dogru URL:  mfa.gov.tr/tr.rss.mfa?UUID (6 farkli feed)

-- Eski yanlis kaydi sil
DELETE FROM `xn_sources` WHERE `rss_url` = 'https://www.mfa.gov.tr/rss.tr.mfa' OR `slug` = 'disisleri';

-- Yeni 6 feed ekle
INSERT IGNORE INTO `xn_sources`
    (`ad`, `slug`, `site_url`, `rss_url`, `varsayilan_kategori_id`, `aciklama`, `atfi_metin`, `cekim_sikligi`, `max_haber_adet`, `aktif`)
VALUES
('MFA - Güncel Gelişmeler',     'mfa-gelismeler',   'https://www.mfa.gov.tr', 'https://www.mfa.gov.tr/tr.rss.mfa?978045a8-225a-487d-8fd8-8d371874e8ec', (SELECT id FROM `xn_categories` WHERE slug='dunya' LIMIT 1), 'T.C. Dışişleri Bakanlığı - Güncel Gelişmeler',               'T.C. Dışişleri Bakanlığı', 15,  50, 1),
('MFA - Bakanlık Açıklamaları', 'mfa-aciklamalar',  'https://www.mfa.gov.tr', 'https://www.mfa.gov.tr/tr.rss.mfa?3fc6582e-a37b-40d1-847a-6914dc12fb60', (SELECT id FROM `xn_categories` WHERE slug='dunya' LIMIT 1), 'T.C. Dışişleri Bakanlığı - Bakanlık Açıklamaları',          'T.C. Dışişleri Bakanlığı', 15,  50, 1),
('MFA - Güncel Duyurular',      'mfa-duyurular',    'https://www.mfa.gov.tr', 'https://www.mfa.gov.tr/tr.rss.mfa?259fca5b-ebee-47d1-b850-ec53936ab32e', (SELECT id FROM `xn_categories` WHERE slug='dunya' LIMIT 1), 'T.C. Dışişleri Bakanlığı - Güncel Duyurular',                'T.C. Dışişleri Bakanlığı', 30,  30, 1),
('MFA - Diğer Metinler',        'mfa-diger',        'https://www.mfa.gov.tr', 'https://www.mfa.gov.tr/tr.rss.mfa?8a5e254e-533a-4b3d-84db-9f95be1207ff', (SELECT id FROM `xn_categories` WHERE slug='dunya' LIMIT 1), 'T.C. Dışişleri Bakanlığı - Diğer Metinler ve Bağlantılar',   'T.C. Dışişleri Bakanlığı', 60,  30, 1),
('MFA - Enerji ve Çevre',       'mfa-enerji',       'https://www.mfa.gov.tr', 'https://www.mfa.gov.tr/tr.rss.mfa?a2aa6dde-f503-4a54-ba92-d960969a9a84', (SELECT id FROM `xn_categories` WHERE slug='dunya' LIMIT 1), 'T.C. Dışişleri Bakanlığı - Enerji, Su Kaynakları ve Çevre', 'T.C. Dışişleri Bakanlığı', 60,  25, 1),
('MFA - Enerji Arşiv',          'mfa-enerji-arsiv', 'https://www.mfa.gov.tr', 'https://www.mfa.gov.tr/tr.rss.mfa?a2bb6dde-f503-4a54-ba92-d960969a9984', (SELECT id FROM `xn_categories` WHERE slug='dunya' LIMIT 1), 'T.C. Dışişleri Bakanlığı - Enerji Arşiv',                    'T.C. Dışişleri Bakanlığı', 120, 20, 0);
-- Son kayit (Arsiv) varsayilan PASIF (nadiren guncellenir)
