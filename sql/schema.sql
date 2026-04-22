-- =====================================================
-- XNEWS - Veritabani Semasi
-- MySQL 5.7+ / MariaDB 10.3+
-- Karakter Seti: utf8mb4_unicode_ci
-- =====================================================
--
-- ONEMLI: Bu dosyayi CALISTIRMADAN ONCE veritabanini SECIN!
--
-- phpMyAdmin'de:
--   1) Sol panelden veritabani adina tiklayin (ornek: kullanici_xnews)
--   2) Üst menuden "SQL" veya "Import" sekmesine gecin
--   3) Sorguyu yapistirin veya dosyayi import edin
--
-- Ya da asagidaki satirin basindaki -- isaretini kaldirip
-- kendi veritabani adinizi yazin:
--
-- USE `kullanici_xnews`;
--
-- En kolay yol: kurulum.php sihirbazini kullanin - her seyi otomatik yapar.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Tablo: xn_users (Yönetici kullanicilar)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kullanici_adi` VARCHAR(60) NOT NULL,
    `eposta` VARCHAR(150) NOT NULL,
    `sifre_hash` VARCHAR(255) NOT NULL,
    `ad_soyad` VARCHAR(120) NOT NULL,
    `rol` ENUM('admin','editor','yazar') NOT NULL DEFAULT 'editor',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `son_giris` DATETIME DEFAULT NULL,
    `son_ip` VARCHAR(45) DEFAULT NULL,
    `durum` TINYINT(1) NOT NULL DEFAULT 1,
    `hatirla_token` VARCHAR(128) DEFAULT NULL,
    `sifirlama_token` VARCHAR(128) DEFAULT NULL,
    `sifirlama_son` DATETIME DEFAULT NULL,
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_kullanici_adi` (`kullanici_adi`),
    UNIQUE KEY `uk_eposta` (`eposta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_categories (Haber kategorileri)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad` VARCHAR(80) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `açıklama` VARCHAR(255) DEFAULT NULL,
    `renk` VARCHAR(7) NOT NULL DEFAULT '#dc2626',
    `ikon` VARCHAR(40) DEFAULT 'newspaper',
    `ust_id` INT UNSIGNED DEFAULT NULL,
    `sıra` SMALLINT NOT NULL DEFAULT 0,
    `seo_baslik` VARCHAR(160) DEFAULT NULL,
    `seo_aciklama` VARCHAR(255) DEFAULT NULL,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `ix_sira` (`sıra`),
    KEY `ix_ust_id` (`ust_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_sources (RSS kaynaklari)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_sources` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad` VARCHAR(120) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `site_url` VARCHAR(255) NOT NULL,
    `rss_url` VARCHAR(500) NOT NULL,
    `varsayilan_kategori_id` INT UNSIGNED DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `açıklama` TEXT,
    `dil` VARCHAR(5) NOT NULL DEFAULT 'tr',
    `atfi_metin` VARCHAR(120) DEFAULT NULL COMMENT 'Kaynak: X yazisi',
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `son_cekim` DATETIME DEFAULT NULL,
    `son_durum` ENUM('başarılı','hata','bekliyor') NOT NULL DEFAULT 'bekliyor',
    `son_hata` TEXT,
    `toplam_haber` INT UNSIGNED NOT NULL DEFAULT 0,
    `cekim_sikligi` SMALLINT NOT NULL DEFAULT 10 COMMENT 'Dakika',
    `max_haber_adet` SMALLINT NOT NULL DEFAULT 50 COMMENT 'Bir seferde max',
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `ix_aktif_son_cekim` (`aktif`, `son_cekim`),
    KEY `ix_varsayilan_kategori` (`varsayilan_kategori_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_news (Haberler)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_news` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `başlık` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(280) NOT NULL,
    `ozet` TEXT,
    `icerik` MEDIUMTEXT,
    `resim` VARCHAR(500) DEFAULT NULL,
    `resim_alt` VARCHAR(255) DEFAULT NULL,
    `kaynak_id` INT UNSIGNED DEFAULT NULL,
    `kategori_id` INT UNSIGNED NOT NULL,
    `yazar` VARCHAR(120) DEFAULT NULL,
    `yazar_id` INT UNSIGNED DEFAULT NULL,
    `orijinal_url` VARCHAR(500) DEFAULT NULL,
    `guid` VARCHAR(500) DEFAULT NULL COMMENT 'RSS tekil kimlik',
    `icerik_hash` CHAR(40) DEFAULT NULL COMMENT 'SHA1 duplicate kontrol',
    `okunma` INT UNSIGNED NOT NULL DEFAULT 0,
    `manset` TINYINT(1) NOT NULL DEFAULT 0,
    `one_cikan` TINYINT(1) NOT NULL DEFAULT 0,
    `son_dakika` TINYINT(1) NOT NULL DEFAULT 0,
    `video_url` VARCHAR(500) DEFAULT NULL,
    `durum` ENUM('yayinda','taslak','arsiv','beklemede') NOT NULL DEFAULT 'yayinda',
    `seo_baslik` VARCHAR(200) DEFAULT NULL,
    `seo_aciklama` VARCHAR(300) DEFAULT NULL,
    `yayin_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    UNIQUE KEY `uk_icerik_hash` (`icerik_hash`),
    KEY `ix_durum_yayin` (`durum`, `yayin_tarihi`),
    KEY `ix_kategori_yayin` (`kategori_id`, `durum`, `yayin_tarihi`),
    KEY `ix_kaynak` (`kaynak_id`),
    KEY `ix_manset` (`manset`, `yayin_tarihi`),
    KEY `ix_one_cikan` (`one_cikan`, `yayin_tarihi`),
    KEY `ix_okunma` (`okunma`),
    FULLTEXT KEY `ft_arama` (`başlık`, `ozet`, `icerik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_tags (Etiketler)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_tags` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad` VARCHAR(80) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `kullanim` INT UNSIGNED NOT NULL DEFAULT 0,
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `ix_kullanim` (`kullanim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_news_tags (Haber-etiket iliskisi)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_news_tags` (
    `news_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`news_id`, `tag_id`),
    KEY `ix_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_ads (Reklam slotlari)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_ads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad` VARCHAR(120) NOT NULL,
    `konum` ENUM('ust_banner','sidebar_ust','sidebar_alt','makale_ust','makale_ic','makale_alt','alt_banner','mobil_sabit','popup') NOT NULL,
    `tip` ENUM('kod','gorsel','adsense') NOT NULL DEFAULT 'gorsel',
    `kod` MEDIUMTEXT COMMENT 'HTML/AdSense/script kodu',
    `gorsel` VARCHAR(500) DEFAULT NULL,
    `hedef_url` VARCHAR(500) DEFAULT NULL,
    `genislik` SMALLINT DEFAULT NULL,
    `yukseklik` SMALLINT DEFAULT NULL,
    `gosterim` INT UNSIGNED NOT NULL DEFAULT 0,
    `tiklanma` INT UNSIGNED NOT NULL DEFAULT 0,
    `baslangic` DATE DEFAULT NULL,
    `bitis` DATE DEFAULT NULL,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_konum_aktif` (`konum`, `aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_settings (Site ayarlari - key/value)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_settings` (
    `anahtar` VARCHAR(80) NOT NULL,
    `değer` TEXT,
    `tip` ENUM('metin','sayi','html','json','boolean','gorsel') NOT NULL DEFAULT 'metin',
    `grup` VARCHAR(40) NOT NULL DEFAULT 'genel',
    `etiket` VARCHAR(120) DEFAULT NULL,
    `açıklama` VARCHAR(255) DEFAULT NULL,
    `sıra` SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`anahtar`),
    KEY `ix_grup` (`grup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_logs (Sistem loglari)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tip` ENUM('bilgi','uyari','hata','guvenlik','cron','islem') NOT NULL DEFAULT 'bilgi',
    `başlık` VARCHAR(255) NOT NULL,
    `detay` TEXT,
    `kullanici_id` INT UNSIGNED DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `url` VARCHAR(500) DEFAULT NULL,
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_tip_tarih` (`tip`, `olusturma`),
    KEY `ix_kullanici` (`kullanici_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_cron_history (RSS çekim gecmisi)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_cron_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kaynak_id` INT UNSIGNED DEFAULT NULL,
    `durum` ENUM('başarılı','hata','kismi') NOT NULL,
    `eklenen` SMALLINT NOT NULL DEFAULT 0,
    `atlanan` SMALLINT NOT NULL DEFAULT 0,
    `toplam` SMALLINT NOT NULL DEFAULT 0,
    `sure_ms` INT UNSIGNED NOT NULL DEFAULT 0,
    `hata_mesaj` TEXT,
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_kaynak_tarih` (`kaynak_id`, `olusturma`),
    KEY `ix_tarih` (`olusturma`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tablo: xn_visitors (Istatistik)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `xn_visitors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_hash` CHAR(40) NOT NULL,
    `haber_id` INT UNSIGNED DEFAULT NULL,
    `sayfa` VARCHAR(255) DEFAULT NULL,
    `referer_host` VARCHAR(120) DEFAULT NULL,
    `tarayici` VARCHAR(40) DEFAULT NULL,
    `cihaz` ENUM('masaustu','mobil','tablet','bot') DEFAULT 'masaustu',
    `ulke` VARCHAR(2) DEFAULT NULL,
    `olusturma` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_haber_tarih` (`haber_id`, `olusturma`),
    KEY `ix_tarih` (`olusturma`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- VARSAYILAN AYARLAR
-- -----------------------------------------------------
INSERT INTO `xn_settings` (`anahtar`, `değer`, `tip`, `grup`, `etiket`, `sıra`) VALUES
-- Genel
('site_adi', 'XNEWS', 'metin', 'genel', 'Site Adi', 1),
('site_slogan', 'Haberin Hizli Adresi', 'metin', 'genel', 'Slogan', 2),
('site_aciklama', 'Türkiye ve dünyadan son dakika haberleri, guncel gelismeler ve analizler', 'metin', 'genel', 'Site Aciklamasi', 3),
('site_anahtar_kelime', 'haber, son dakika, turkiye, dunya, ekonomi, spor, teknoloji', 'metin', 'genel', 'Anahtar Kelimeler', 4),
('logo', '', 'gorsel', 'genel', 'Logo', 5),
('favicon', '', 'gorsel', 'genel', 'Favicon', 6),
('iletisim_eposta', 'iletisim@xnews.com.tr', 'metin', 'genel', 'İletişim E-posta', 7),
('iletisim_telefon', '', 'metin', 'genel', 'Telefon', 8),
('iletisim_adres', '', 'metin', 'genel', 'Adres', 9),

-- Sosyal medya
('sm_facebook', '', 'metin', 'sosyal', 'Facebook URL', 1),
('sm_twitter', '', 'metin', 'sosyal', 'X (Twitter) URL', 2),
('sm_instagram', '', 'metin', 'sosyal', 'Instagram URL', 3),
('sm_youtube', '', 'metin', 'sosyal', 'YouTube URL', 4),
('sm_telegram', '', 'metin', 'sosyal', 'Telegram URL', 5),
('sm_whatsapp', '', 'metin', 'sosyal', 'WhatsApp', 6),

-- SEO
('google_analytics', '', 'html', 'seo', 'Google Analytics Kodu', 1),
('google_site_verification', '', 'metin', 'seo', 'Google Search Console', 2),
('yandex_verification', '', 'metin', 'seo', 'Yandex Webmaster', 3),
('head_kod', '', 'html', 'seo', 'Head icine ek kod', 4),
('body_kod', '', 'html', 'seo', 'Body sonuna ek kod', 5),

-- Goruntuleme
('anasayfa_manset_adet', '5', 'sayi', 'goruntuleme', 'Manset haber adedi', 1),
('anasayfa_son_haber_adet', '20', 'sayi', 'goruntuleme', 'Son haber listesi adedi', 2),
('kategori_sayfa_adet', '15', 'sayi', 'goruntuleme', 'Kategori sayfa basina haber', 3),
('varsayilan_resim', '', 'gorsel', 'goruntuleme', 'Varsayılan haber gorseli', 4),
('tema_renk', '#dc2626', 'metin', 'goruntuleme', 'Ana tema rengi', 5),

-- RSS / Cron
('cron_aktif', '1', 'boolean', 'cron', 'Otomatik cekim aktif', 1),
('cron_son_calisma', '', 'metin', 'cron', 'Son cron çalışması', 2),
('otomatik_yayin', '1', 'boolean', 'cron', 'RSS haberleri otomatik yayinla', 3),
('duplicate_kontrol', '1', 'boolean', 'cron', 'Tekrar eden haberleri engelle', 4),
('kaynak_goster', '1', 'boolean', 'cron', 'Haber altinda kaynak göster (YASAL ZORUNLU)', 5),

-- Reklam
('reklam_aktif', '1', 'boolean', 'reklam', 'Reklamlari göster', 1),
('adsense_kimlik', '', 'metin', 'reklam', 'Google AdSense yayinci ID', 2),

-- Güncelleme
('son_guncelleme_kontrol', '', 'metin', 'sistem', 'Son guncelleme kontrolü', 1),
('mevcut_surum', '1.0.0', 'metin', 'sistem', 'Yuklu sürüm', 2);

-- -----------------------------------------------------
-- VARSAYILAN KATEGORILER
-- -----------------------------------------------------
INSERT INTO `xn_categories` (`ad`, `slug`, `açıklama`, `renk`, `ikon`, `sıra`) VALUES
('Gündem', 'gundem', 'Gündem ve son dakika haberleri', '#dc2626', 'flame', 1),
('Türkiye', 'turkiye', 'Türkiye haberleri', '#b91c1c', 'flag', 2),
('Dünya', 'dunya', 'Dünya haberleri', '#2563eb', 'globe', 3),
('Ekonomi', 'ekonomi', 'Ekonomi, finans ve is dunyasi', '#16a34a', 'trending-up', 4),
('Spor', 'spor', 'Spor haberleri', '#ea580c', 'trophy', 5),
('Teknoloji', 'teknoloji', 'Teknoloji ve bilim haberleri', '#7c3aed', 'cpu', 6),
('Magazin', 'magazin', 'Magazin ve sanat haberleri', '#ec4899', 'star', 7),
('Sağlık', 'saglik', 'Sağlık ve yaşam haberleri', '#0891b2', 'heart-pulse', 8),
('Eğitim', 'egitim', 'Eğitim haberleri', '#0369a1', 'graduation-cap', 9),
('Kültür Sanat', 'kultur-sanat', 'Kultur, sanat ve edebiyat', '#9333ea', 'palette', 10),
('Yaşam', 'yasam', 'Yaşam, seyahat ve rehber', '#059669', 'sun', 11),
('Otomobil', 'otomobil', 'Otomotiv sektoru', '#4b5563', 'car', 12);

SET FOREIGN_KEY_CHECKS = 1;
