-- ==========================================================
-- XNEWS v1.2.6 - Bozuk RSS URL'lerini duzeltme + kullanici rolleri
-- ==========================================================

-- Haberturk yeni URL formati: /rss/kategori/X -> /rss/X.xml
UPDATE `xn_sources` SET `rss_url` = 'https://www.haberturk.com/rss/anasayfa.xml' WHERE `rss_url` = 'https://www.haberturk.com/rss/anasayfa';
UPDATE `xn_sources` SET `rss_url` = 'https://www.haberturk.com/rss/dunya.xml' WHERE `rss_url` = 'https://www.haberturk.com/rss/kategori/dunya';
UPDATE `xn_sources` SET `rss_url` = 'https://www.haberturk.com/rss/ekonomi.xml' WHERE `rss_url` = 'https://www.haberturk.com/rss/kategori/ekonomi';
UPDATE `xn_sources` SET `rss_url` = 'https://www.haberturk.com/rss/gundem.xml' WHERE `rss_url` = 'https://www.haberturk.com/rss/kategori/gundem';
UPDATE `xn_sources` SET `rss_url` = 'https://www.haberturk.com/rss/spor.xml' WHERE `rss_url` = 'https://www.haberturk.com/rss/kategori/spor';
UPDATE `xn_sources` SET `rss_url` = 'https://www.haberturk.com/rss/yasam.xml' WHERE `rss_url` = 'https://www.haberturk.com/rss/kategori/yasam';
UPDATE `xn_sources` SET `rss_url` = 'https://www.haberturk.com/rss/saglik.xml' WHERE `rss_url` = 'https://www.haberturk.com/rss/kategori/saglik';
UPDATE `xn_sources` SET `rss_url` = 'https://www.haberturk.com/rss/magazin.xml' WHERE `rss_url` = 'https://www.haberturk.com/rss/kategori/magazin';

-- Haber7 yeni URL
UPDATE `xn_sources` SET `rss_url` = 'https://i12.haber7.net/sondakika/newsstand/latest.xml' WHERE `rss_url` = 'https://i12.haber7.net/sondakika/new.rss';

-- T24 yeni URL
UPDATE `xn_sources` SET `rss_url` = 'https://t24.com.tr/service/rss' WHERE `rss_url` = 'https://t24.com.tr/rss/haberler';

-- Euronews ise UA sorunuydu, URL zaten dogru - UA fix'le calisir

-- =====================================================
-- KULLANICI ROL SISTEMI (Yoneticiler/Editor/Haber Sorumlusu)
-- =====================================================

-- users tablosuna rol_id sutunu ekle (ON DUPLICATE KEY yerine manuel kontrol)
ALTER TABLE `xn_users` ADD COLUMN IF NOT EXISTS `rol` VARCHAR(40) DEFAULT 'yonetici' AFTER `eposta`;
ALTER TABLE `xn_users` ADD COLUMN IF NOT EXISTS `telefon` VARCHAR(40) DEFAULT NULL AFTER `rol`;
ALTER TABLE `xn_users` ADD COLUMN IF NOT EXISTS `gorev_tanimi` VARCHAR(200) DEFAULT NULL AFTER `telefon`;

-- Mevcut kullanicilari yonetici yap
UPDATE `xn_users` SET `rol` = 'yonetici' WHERE `rol` IS NULL OR `rol` = '';

-- Kunye bilgi guncellemesi (site yonetim kadrosu icin yeni ayarlar)
INSERT INTO `xn_settings` (`anahtar`, `deger`, `etiket`, `aciklama`, `tip`, `grup`, `sira`) VALUES
('kadro_editorler_goster', '1', 'Editor Kadrosunu Kunyede Goster', 'Kullanicilar tablosundaki editorler kunye sayfasinda listelenir', 'onoff', 'kunye', 20)
ON DUPLICATE KEY UPDATE etiket=VALUES(etiket);
