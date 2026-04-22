-- ==========================================================
-- XNEWS v1.0.8 - Eksik Kategori RSS Kaynakları
-- ==========================================================
-- Zayıf kategoriler için 5 yeni test edilmiş kaynak:
--   Magazin: 1 -> 3 (Sabah, Takvim)
--   Sağlık:  2 -> 3 (Sabah)
--   Yaşam:   1 -> 2 (Sabah)
--   Eğitim:  1 -> 2 (Sabah)
-- ==========================================================

SET NAMES utf8mb4;

INSERT INTO `xn_sources`
    (`ad`, `slug`, `site_url`, `rss_url`, `varsayilan_kategori_id`, `aciklama`, `atfi_metin`, `cekim_sikligi`, `max_haber_adet`, `aktif`) VALUES

-- KATEGORİ: Magazin (ID 7)
('Sabah - Magazin', 'sabah-magazin', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/magazin.xml', 7, 'Sabah - Magazin', 'Sabah', 15, 20, 1),
('Takvim - Magazin', 'takvim-magazin', 'https://www.takvim.com.tr', 'https://www.takvim.com.tr/rss/magazin', 7, 'Takvim - Magazin', 'Takvim', 15, 40, 1),

-- KATEGORİ: Sağlık (ID 8)
('Sabah - Sağlık', 'sabah-saglik', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/saglik.xml', 8, 'Sabah - Sağlık', 'Sabah', 15, 20, 1),

-- KATEGORİ: Eğitim (ID 9)
('Sabah - Eğitim', 'sabah-egitim', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/egitim.xml', 9, 'Sabah - Eğitim', 'Sabah', 15, 20, 1),

-- KATEGORİ: Yaşam (ID 11)
('Sabah - Yaşam', 'sabah-yasam', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/yasam.xml', 11, 'Sabah - Yaşam', 'Sabah', 15, 20, 1)
ON DUPLICATE KEY UPDATE
    `ad` = VALUES(`ad`),
    `rss_url` = VALUES(`rss_url`),
    `aktif` = VALUES(`aktif`);

SELECT CONCAT('XNEWS v1.0.8: Toplam ', COUNT(*), ' RSS kaynak aktif') AS durum
FROM `xn_sources` WHERE `aktif` = 1;
