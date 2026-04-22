-- ==========================================================
-- XNEWS v1.0.7 - Genişletilmiş RSS Kaynakları
-- ==========================================================
-- 40 yeni RSS kaynağı. Her biri curl + XML parse ile test edilmiştir.
-- Mevcut slug ile çakışma olursa (ON DUPLICATE) o kayıt güncellenir.
-- ==========================================================

SET NAMES utf8mb4;

INSERT INTO `xn_sources`
    (`ad`, `slug`, `site_url`, `rss_url`, `varsayilan_kategori_id`, `aciklama`, `atfi_metin`, `cekim_sikligi`, `max_haber_adet`, `aktif`) VALUES

-- KATEGORİ: Gündem (ID 1)
('Hürriyet - Ana Sayfa', 'hurriyet-anasayfa', 'https://www.hurriyet.com.tr', 'https://www.hurriyet.com.tr/rss/anasayfa', 1, 'Hürriyet - Gündem kategorisi', 'Hürriyet', 10, 40, 1),
('Milliyet - Gündem', 'milliyet-gundem', 'https://www.milliyet.com.tr', 'https://www.milliyet.com.tr/rss/rssNew/gundemRss.xml', 1, 'Milliyet - Gündem kategorisi', 'Milliyet', 15, 30, 1),
('Sözcü - Gündem', 'sozcu-gundem', 'https://www.sozcu.com.tr', 'https://www.sozcu.com.tr/feeds-rss-category-gundem', 1, 'Sözcü - Gündem kategorisi', 'Sözcü', 15, 40, 1),
('A Haber - Gündem', 'ahaber-gundem', 'https://www.ahaber.com.tr', 'https://www.ahaber.com.tr/rss/gundem.xml', 1, 'A Haber - Gündem kategorisi', 'A Haber', 15, 40, 1),
('Evrensel', 'evrensel', 'https://www.evrensel.net', 'https://www.evrensel.net/rss/haber.xml', 1, 'Evrensel - Gündem kategorisi', 'Evrensel', 15, 40, 1),
('Diken', 'diken', 'https://www.diken.com.tr', 'https://www.diken.com.tr/feed/', 1, 'Diken - Gündem kategorisi', 'Diken', 15, 30, 1),
('Independent Türkçe', 'indyturk', 'https://www.indyturk.com', 'https://www.indyturk.com/rss.xml', 1, 'Independent Türkçe - Gündem kategorisi', 'Independent Türkçe', 15, 20, 1),
('Yeni Şafak - Gündem', 'yenisafak-gundem', 'https://www.yenisafak.com', 'https://www.yenisafak.com/Rss?xml=anasayfa', 1, 'Yeni Şafak - Gündem kategorisi', 'Yeni Şafak', 10, 20, 1),
('Mynet - Son Dakika', 'mynet-sondakika', 'https://www.mynet.com', 'https://www.mynet.com/haber/rss/sondakika', 1, 'Mynet - Gündem kategorisi', 'Mynet', 5, 40, 1),
('AA - Güncel', 'aa-guncel', 'https://www.aa.com.tr', 'https://www.aa.com.tr/tr/rss/default?cat=guncel', 1, 'Anadolu Ajansı - Gündem kategorisi', 'Anadolu Ajansı', 15, 30, 1),
('Gerçek Gündem', 'gercekgundem', 'https://www.gercekgundem.com', 'https://www.gercekgundem.com/rss', 1, 'Gerçek Gündem - Gündem kategorisi', 'Gerçek Gündem', 15, 40, 1),

-- KATEGORİ: Türkiye (ID 2)
('CNN Türk - Türkiye', 'cnnturk-turkiye', 'https://www.cnnturk.com', 'https://www.cnnturk.com/feed/rss/turkiye/news', 2, 'CNN Türk - Türkiye kategorisi', 'CNN Türk', 15, 30, 1),

-- KATEGORİ: Dünya (ID 3)
('Milliyet - Dünya', 'milliyet-dunya', 'https://www.milliyet.com.tr', 'https://www.milliyet.com.tr/rss/rssNew/dunyaRss.xml', 3, 'Milliyet - Dünya kategorisi', 'Milliyet', 15, 30, 1),
('AA - Dünya', 'aa-dunya', 'https://www.aa.com.tr', 'https://www.aa.com.tr/tr/rss/default?cat=dunya', 3, 'Anadolu Ajansı - Dünya kategorisi', 'Anadolu Ajansı', 15, 30, 1),
('NTV - Dünya', 'ntv-dunya', 'https://www.ntv.com.tr', 'https://www.ntv.com.tr/dunya.rss', 3, 'NTV - Dünya kategorisi', 'NTV', 15, 30, 1),
('CNN Türk - Dünya', 'cnnturk-dunya', 'https://www.cnnturk.com', 'https://www.cnnturk.com/feed/rss/dunya/news', 3, 'CNN Türk - Dünya kategorisi', 'CNN Türk', 15, 30, 1),
('Euronews Türkçe', 'euronews-tr', 'https://tr.euronews.com', 'https://tr.euronews.com/rss', 3, 'Euronews Türkçe - Dünya kategorisi', 'Euronews Türkçe', 15, 40, 1),

-- KATEGORİ: Ekonomi (ID 4)
('Sözcü - Ekonomi', 'sozcu-ekonomi', 'https://www.sozcu.com.tr', 'https://www.sozcu.com.tr/feeds-rss-category-ekonomi', 4, 'Sözcü - Ekonomi kategorisi', 'Sözcü', 15, 40, 1),
('AA - Ekonomi', 'aa-ekonomi', 'https://www.aa.com.tr', 'https://www.aa.com.tr/tr/rss/default?cat=ekonomi', 4, 'Anadolu Ajansı - Ekonomi kategorisi', 'Anadolu Ajansı', 15, 30, 1),
('Bloomberg HT', 'bloomberght', 'https://www.bloomberght.com', 'https://www.bloomberght.com/rss', 4, 'Bloomberg HT - Ekonomi kategorisi', 'Bloomberg HT', 15, 30, 1),
('Dünya Gazetesi', 'dunyagazetesi', 'https://www.dunya.com', 'https://www.dunya.com/rss?dunya', 4, 'Dünya Gazetesi - Ekonomi kategorisi', 'Dünya Gazetesi', 15, 30, 1),
('NTV - Ekonomi', 'ntv-ekonomi', 'https://www.ntv.com.tr', 'https://www.ntv.com.tr/ekonomi.rss', 4, 'NTV - Ekonomi kategorisi', 'NTV', 15, 30, 1),
('Milliyet - Ekonomi', 'milliyet-ekonomi', 'https://www.milliyet.com.tr', 'https://www.milliyet.com.tr/rss/rssNew/ekonomiRss.xml', 4, 'Milliyet - Ekonomi kategorisi', 'Milliyet', 15, 30, 1),

-- KATEGORİ: Spor (ID 5)
('Milliyet - Spor', 'milliyet-spor', 'https://www.milliyet.com.tr', 'https://www.milliyet.com.tr/rss/rssNew/sondakikaspor.xml', 5, 'Milliyet - Spor kategorisi', 'Milliyet', 5, 30, 1),
('Fotomaç', 'fotomac', 'https://www.fotomac.com.tr', 'https://www.fotomac.com.tr/rss/anasayfa.xml', 5, 'Fotomaç - Spor kategorisi', 'Fotomaç', 10, 40, 1),
('Fotomaç - Futbol', 'fotomac-futbol', 'https://www.fotomac.com.tr', 'https://www.fotomac.com.tr/rss/futbol.xml', 5, 'Fotomaç - Spor kategorisi', 'Fotomaç', 15, 40, 1),
('CNN Türk - Spor', 'cnnturk-spor', 'https://www.cnnturk.com', 'https://www.cnnturk.com/feed/rss/spor/news', 5, 'CNN Türk - Spor kategorisi', 'CNN Türk', 15, 30, 1),
('AA - Spor', 'aa-spor', 'https://www.aa.com.tr', 'https://www.aa.com.tr/tr/rss/default?cat=spor', 5, 'Anadolu Ajansı - Spor kategorisi', 'Anadolu Ajansı', 15, 30, 1),

-- KATEGORİ: Teknoloji (ID 6)
('Milliyet - Teknoloji', 'milliyet-tekno', 'https://www.milliyet.com.tr', 'https://www.milliyet.com.tr/rss/rssNew/teknolojiRss.xml', 6, 'Milliyet - Teknoloji kategorisi', 'Milliyet', 15, 30, 1),
('AA - Teknoloji', 'aa-tekno', 'https://www.aa.com.tr', 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji', 6, 'Anadolu Ajansı - Teknoloji kategorisi', 'Anadolu Ajansı', 15, 30, 1),
('ShiftDelete.Net', 'shiftdelete', 'https://shiftdelete.net', 'https://shiftdelete.net/feed', 6, 'ShiftDelete.Net - Teknoloji kategorisi', 'ShiftDelete.Net', 15, 30, 1),
('LOG', 'log', 'https://www.log.com.tr', 'https://www.log.com.tr/rss', 6, 'LOG - Teknoloji kategorisi', 'LOG', 15, 20, 1),
('NTV - Teknoloji', 'ntv-teknoloji', 'https://www.ntv.com.tr', 'https://www.ntv.com.tr/teknoloji.rss', 6, 'NTV - Teknoloji kategorisi', 'NTV', 15, 30, 1),

-- KATEGORİ: Magazin (ID 7)
('Milliyet - Magazin', 'milliyet-magazin', 'https://www.milliyet.com.tr', 'https://www.milliyet.com.tr/rss/rssNew/magazinRss.xml', 7, 'Milliyet - Magazin kategorisi', 'Milliyet', 15, 30, 1),

-- KATEGORİ: Sağlık (ID 8)
('AA - Sağlık', 'aa-saglik', 'https://www.aa.com.tr', 'https://www.aa.com.tr/tr/rss/default?cat=saglik', 8, 'Anadolu Ajansı - Sağlık kategorisi', 'Anadolu Ajansı', 15, 30, 1),
('NTV - Sağlık', 'ntv-saglik', 'https://www.ntv.com.tr', 'https://www.ntv.com.tr/saglik.rss', 8, 'NTV - Sağlık kategorisi', 'NTV', 15, 30, 1),

-- KATEGORİ: Eğitim (ID 9)
('AA - Eğitim', 'aa-egitim', 'https://www.aa.com.tr', 'https://www.aa.com.tr/tr/rss/default?cat=egitim', 9, 'Anadolu Ajansı - Eğitim kategorisi', 'Anadolu Ajansı', 15, 30, 1),

-- KATEGORİ: Kültür Sanat (ID 10)
('AA - Kültür Sanat', 'aa-kultur', 'https://www.aa.com.tr', 'https://www.aa.com.tr/tr/rss/default?cat=kultur', 10, 'Anadolu Ajansı - Kültür Sanat kategorisi', 'Anadolu Ajansı', 15, 30, 1),

-- KATEGORİ: Yaşam (ID 11)
('NTV - Yaşam', 'ntv-yasam', 'https://www.ntv.com.tr', 'https://www.ntv.com.tr/yasam.rss', 11, 'NTV - Yaşam kategorisi', 'NTV', 15, 30, 1),

-- KATEGORİ: Otomobil (ID 12)
('NTV - Otomobil', 'ntv-otomobil', 'https://www.ntv.com.tr', 'https://www.ntv.com.tr/otomobil.rss', 12, 'NTV - Otomobil kategorisi', 'NTV', 15, 30, 1)
ON DUPLICATE KEY UPDATE
    `ad` = VALUES(`ad`),
    `site_url` = VALUES(`site_url`),
    `rss_url` = VALUES(`rss_url`),
    `varsayilan_kategori_id` = VALUES(`varsayilan_kategori_id`),
    `atfi_metin` = VALUES(`atfi_metin`),
    `aktif` = VALUES(`aktif`);

-- Bilgi
SELECT CONCAT('XNEWS v1.0.7: Toplam ', COUNT(*), ' RSS kaynak aktif') AS durum
FROM `xn_sources` WHERE `aktif` = 1;
