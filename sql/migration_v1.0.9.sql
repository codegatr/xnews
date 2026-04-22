-- ==========================================================
-- XNEWS v1.0.9 - Yasal Altyapı (KVKK, Takedown, Künye)
-- ==========================================================

SET NAMES utf8mb4;

-- =====================================================
-- TAKEDOWN (Kaldırma Talep) Tablosu
-- =====================================================
CREATE TABLE IF NOT EXISTS `xn_takedown` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `haber_id`        INT UNSIGNED NOT NULL,
    `talep_eden_ad`   VARCHAR(120) NOT NULL,
    `talep_eden_unvan` VARCHAR(120) DEFAULT NULL COMMENT 'Avukat, yayıncı, kişisel, vb.',
    `eposta`          VARCHAR(150) NOT NULL,
    `telefon`         VARCHAR(30) DEFAULT NULL,
    `kurum`           VARCHAR(150) DEFAULT NULL COMMENT 'Talep eden şirket/kurum',
    `iliski`          ENUM('hak_sahibi','yayin_sahibi','avukat','kisisel','diger') NOT NULL DEFAULT 'kisisel',
    `sebep`           ENUM('telif','kisilik','kvkk','yanlis','itibar','diger') NOT NULL DEFAULT 'diger',
    `aciklama`        TEXT NOT NULL,
    `kanit_url`       VARCHAR(500) DEFAULT NULL COMMENT 'Mahkeme karari, belge linki',
    `ip_adresi`       VARCHAR(45) DEFAULT NULL,
    `kullanici_ajan`  VARCHAR(500) DEFAULT NULL,
    `durum`           ENUM('beklemede','inceleniyor','kabul','red','iptal') NOT NULL DEFAULT 'beklemede',
    `yonetici_notu`   TEXT DEFAULT NULL,
    `islem_yapan`     INT UNSIGNED DEFAULT NULL,
    `islem_tarihi`    DATETIME DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_haber` (`haber_id`),
    KEY `idx_durum` (`durum`),
    KEY `idx_olusturma` (`olusturma_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- =====================================================
-- Yasal sayfa metinleri icin ayar anahtarlari
-- Yöneticiler bunlari paneldenden guncelleyebilir
-- =====================================================
INSERT INTO `xn_settings` (`anahtar`, `deger`, `etiket`, `aciklama`, `tip`, `grup`, `sira`) VALUES
('kunye_yayin_sahibi',     'Aksoy Grup / CODEGA', 'Yayın Sahibi', 'Basın Kanunu gereği yayın sahibi adı', 'metin', 'kunye', 1),
('kunye_sorumlu_mudur',    'Yunus Aksoy',         'Sorumlu Müdür', 'Basın Kanunu gereği sorumlu yazı işleri müdürü', 'metin', 'kunye', 2),
('kunye_yazi_isleri_md',   'Yunus Aksoy',         'Yazı İşleri Müdürü', 'Yazı işleri müdürü adı', 'metin', 'kunye', 3),
('kunye_ticaret_sicil',    '',                    'Ticaret Sicil No', 'Şirket ticaret sicil numarası', 'metin', 'kunye', 4),
('kunye_mersis',           '',                    'MERSİS No', 'Merkezi Sicil Kayıt Sistemi numarası', 'metin', 'kunye', 5),
('kunye_vergi_dairesi',    '',                    'Vergi Dairesi', 'Bağlı olunan vergi dairesi', 'metin', 'kunye', 6),
('kunye_vergi_no',         '',                    'Vergi No', 'Vergi numarası', 'metin', 'kunye', 7),
('kunye_yayin_turu',       'Süreli İnternet Yayını', 'Yayın Türü', 'Süreli / süresiz', 'metin', 'kunye', 8),
('kunye_yayin_sikligi',    'Günlük', 'Yayın Sıklığı', 'Günlük / haftalık', 'metin', 'kunye', 9),
('kvkk_veri_sorumlu',      'CODEGA',              'KVKK Veri Sorumlusu', 'KVKK uyarınca veri sorumlusu unvanı', 'metin', 'kvkk', 1),
('kvkk_basvuru_eposta',    '',                    'KVKK Başvuru E-posta', 'KVKK başvurularının iletileceği adres', 'metin', 'kvkk', 2),
('takedown_eposta',        '',                    'Kaldırma Talep E-posta', 'Haberle ilgili kaldırma taleplerinin iletildiği e-posta', 'metin', 'takedown', 1),
('takedown_yanit_suresi',  '72', 'Yanıt Süresi (saat)', 'Takedown taleplerine verilecek maksimum yanıt süresi', 'sayi', 'takedown', 2),
('cerez_bildirim_aktif',   '1', 'Çerez Bildirimi', 'Alt taraftaki çerez bildirimi banner gösterilsin mi?', 'evet_hayir', 'site', 50)
ON DUPLICATE KEY UPDATE `etiket` = VALUES(`etiket`);

-- Bildirim: yeni takedown kayitlari
SELECT CONCAT('XNEWS v1.0.9: xn_takedown tablosu + ', COUNT(*), ' ayar eklendi') AS durum
FROM `xn_settings` WHERE `grup` IN ('kunye','kvkk','takedown');
