-- ==========================================================
-- XNEWS v1.3.5 - Haberturk RSS kaynaklarinin zenginlestirilmesi
-- ==========================================================
-- Sorun: haberturk.com/rss/anasayfa.xml 404 donuyordu.
-- Cozum: Eski kaydın URL'ini duzelt (rss/ direkt kullanilabiliyor) +
--        7 yeni calisan kategori feed'i ekle.

-- 1. Ana sayfa feed URL duzeltme (anasayfa.xml -> /rss/)
UPDATE `xn_sources` SET
    `rss_url` = 'https://www.haberturk.com/rss/',
    `son_hata` = NULL,
    `son_durum` = 'bekliyor',
    `ad` = 'Haberturk'
WHERE `rss_url` = 'https://www.haberturk.com/rss/anasayfa.xml'
   OR `slug` = 'haberturk-ana-sayfa';

-- 2. Yeni kategori feed'leri
INSERT IGNORE INTO `xn_sources`
    (`ad`, `slug`, `site_url`, `rss_url`, `varsayilan_kategori_id`, `aciklama`, `atfi_metin`, `cekim_sikligi`, `max_haber_adet`, `aktif`)
VALUES
('Haberturk - Saglik',        'haberturk-saglik',      'https://www.haberturk.com', 'https://www.haberturk.com/rss/saglik.xml',       (SELECT id FROM `xn_categories` WHERE slug='saglik'       LIMIT 1), 'Haberturk - Saglik kategorisi',        'Haberturk', 30, 30, 1),
('Haberturk - Magazin',       'haberturk-magazin',     'https://www.haberturk.com', 'https://www.haberturk.com/rss/magazin.xml',      (SELECT id FROM `xn_categories` WHERE slug='magazin'      LIMIT 1), 'Haberturk - Magazin kategorisi',       'Haberturk', 30, 30, 1),
('Haberturk - Yasam',         'haberturk-yasam',       'https://www.haberturk.com', 'https://www.haberturk.com/rss/yasam.xml',        (SELECT id FROM `xn_categories` WHERE slug='yasam'        LIMIT 1), 'Haberturk - Yasam kategorisi',         'Haberturk', 30, 30, 1),
('Haberturk - Teknoloji',     'haberturk-teknoloji',   'https://www.haberturk.com', 'https://www.haberturk.com/rss/teknoloji.xml',    (SELECT id FROM `xn_categories` WHERE slug='teknoloji'    LIMIT 1), 'Haberturk - Teknoloji kategorisi',     'Haberturk', 30, 30, 1),
('Haberturk - Kultur Sanat',  'haberturk-kultur',      'https://www.haberturk.com', 'https://www.haberturk.com/rss/kultur-sanat.xml', (SELECT id FROM `xn_categories` WHERE slug='kultur-sanat' LIMIT 1), 'Haberturk - Kültür Sanat kategorisi',  'Haberturk', 60, 30, 1),
('Haberturk - Otomobil',      'haberturk-otomobil',    'https://www.haberturk.com', 'https://www.haberturk.com/rss/otomobil.xml',     (SELECT id FROM `xn_categories` WHERE slug='otomobil'     LIMIT 1), 'Haberturk - Otomobil kategorisi',      'Haberturk', 60, 30, 1),
('Haberturk - Is Yasam',      'haberturk-is-yasam',    'https://www.haberturk.com', 'https://www.haberturk.com/rss/is-yasam.xml',     (SELECT id FROM `xn_categories` WHERE slug='ekonomi'      LIMIT 1), 'Haberturk - Is Yasam (Kariyer/Ekonomi)','Haberturk', 60, 30, 1),
('Haberturk - Video',         'haberturk-video',       'https://www.haberturk.com', 'https://www.haberturk.com/rss/video.xml',        (SELECT id FROM `xn_categories` WHERE slug='gundem'       LIMIT 1), 'Haberturk - Video haber feed',         'Haberturk', 60, 30, 0);
-- Video PASIF varsayilan (ana sayfaya kari semayli video baskisi olmasin diye)
