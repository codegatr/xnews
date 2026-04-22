-- ==========================================================
-- XNEWS v1.0.4 - Veritabanı Migration
-- ==========================================================
-- Mevcut kategori adlarını ASCII'den Türkçe'ye çevirir.
-- SADECE DISPLAY TEXT değişir, slug'lar ASCII kalır.
--
-- phpMyAdmin veya DirectAdmin SQL Manager'dan xnewsccg_2027
-- veritabanını seçip bu komutları çalıştırın.
-- ==========================================================

-- Kategori adları (slug ASCII kalır, sadece 'ad' kolonu değişir)
UPDATE `xn_categories` SET `ad` = 'Gündem' WHERE `slug` = 'gundem';
UPDATE `xn_categories` SET `ad` = 'Türkiye' WHERE `slug` = 'turkiye';
UPDATE `xn_categories` SET `ad` = 'Dünya' WHERE `slug` = 'dunya';
UPDATE `xn_categories` SET `ad` = 'Sağlık' WHERE `slug` = 'saglik';
UPDATE `xn_categories` SET `ad` = 'Eğitim' WHERE `slug` = 'egitim';
UPDATE `xn_categories` SET `ad` = 'Yaşam' WHERE `slug` = 'yasam';
UPDATE `xn_categories` SET `ad` = 'Kültür Sanat' WHERE `slug` = 'kultur-sanat';
UPDATE `xn_categories` SET `ad` = 'Teknoloji' WHERE `slug` = 'teknoloji';
-- Magazin, Otomobil, Spor, Ekonomi zaten Türkçe uyumlu

-- Kategori açıklamaları
UPDATE `xn_categories` SET `aciklama` = 'Türkiye gündemi ve son dakika haberleri' WHERE `slug` = 'gundem';
UPDATE `xn_categories` SET `aciklama` = 'Türkiye''den güncel haberler' WHERE `slug` = 'turkiye';
UPDATE `xn_categories` SET `aciklama` = 'Dünyadan son dakika haberleri ve analizler' WHERE `slug` = 'dunya';
UPDATE `xn_categories` SET `aciklama` = 'Ekonomi, finans ve borsa haberleri' WHERE `slug` = 'ekonomi';
UPDATE `xn_categories` SET `aciklama` = 'Spor haberleri ve güncel sonuçlar' WHERE `slug` = 'spor';
UPDATE `xn_categories` SET `aciklama` = 'Teknoloji ve bilim haberleri' WHERE `slug` = 'teknoloji';
UPDATE `xn_categories` SET `aciklama` = 'Magazin ve ünlüler haberleri' WHERE `slug` = 'magazin';
UPDATE `xn_categories` SET `aciklama` = 'Sağlık haberleri ve uzman görüşleri' WHERE `slug` = 'saglik';
UPDATE `xn_categories` SET `aciklama` = 'Eğitim haberleri ve sınav sonuçları' WHERE `slug` = 'egitim';
UPDATE `xn_categories` SET `aciklama` = 'Kültür ve sanat dünyasından haberler' WHERE `slug` = 'kultur-sanat';
UPDATE `xn_categories` SET `aciklama` = 'Yaşam, moda ve güncel içerikler' WHERE `slug` = 'yasam';
UPDATE `xn_categories` SET `aciklama` = 'Otomobil haberleri ve test sürüşleri' WHERE `slug` = 'otomobil';

-- Ayar etiketleri (yönetim paneli için Türkçe)
UPDATE `xn_settings` SET `etiket` = 'Site Adı' WHERE `anahtar` = 'site_adi';
UPDATE `xn_settings` SET `etiket` = 'Site Sloganı' WHERE `anahtar` = 'site_slogan';
UPDATE `xn_settings` SET `etiket` = 'Site Açıklaması' WHERE `anahtar` = 'site_aciklama';
UPDATE `xn_settings` SET `etiket` = 'Dil' WHERE `anahtar` = 'site_dil';
UPDATE `xn_settings` SET `etiket` = 'Sayfa Başına Haber Sayısı' WHERE `anahtar` = 'sayfa_basina_haber';
UPDATE `xn_settings` SET `etiket` = 'Manşet Haber Sayısı' WHERE `anahtar` = 'manset_sayisi';
UPDATE `xn_settings` SET `etiket` = 'Öne Çıkan Haber Sayısı' WHERE `anahtar` = 'one_cikan_sayisi';
UPDATE `xn_settings` SET `etiket` = 'Son Dakika Bant Aktif' WHERE `anahtar` = 'son_dakika_bant_aktif';
UPDATE `xn_settings` SET `etiket` = 'Reklam Aktif' WHERE `anahtar` = 'reklam_aktif';

-- Log tipleri zaten ASCII ENUM (dokunulmaz): bilgi, uyari, hata, guvenlik, cron, islem

SELECT 'Migration v1.0.4 tamamlandı.' AS durum;
