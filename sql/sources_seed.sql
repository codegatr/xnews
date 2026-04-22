-- =====================================================
-- XNEWS - Varsayılan RSS Kaynakları
-- Tüm kaynaklar halka açık RSS besleme sunmaktadir.
-- Yasal Not: Haberler kaynak belirtilerek (atfi_metin) yayinlanmalidir.
-- =====================================================

SET NAMES utf8mb4;

-- Kategori ID'leri (schema.sql'den):
-- 1=Gündem, 2=Türkiye, 3=Dünya, 4=Ekonomi, 5=Spor, 6=Teknoloji,
-- 7=Magazin, 8=Sağlık, 9=Eğitim, 10=Kultur Sanat, 11=Yaşam, 12=Otomobil

INSERT INTO `xn_sources`
    (`ad`, `slug`, `site_url`, `rss_url`, `varsayilan_kategori_id`, `aciklama`, `atfi_metin`, `cekim_sikligi`, `max_haber_adet`, `aktif`) VALUES

-- ULUSAL HABER KAYNAKLARI
('TRT Haber - Manset', 'trthaber-manset', 'https://www.trthaber.com', 'https://www.trthaber.com/manset_articles.rss', 1, 'TRT Haber manset haberleri', 'TRT Haber', 10, 30, 1),
('TRT Haber - Son Dakika', 'trthaber-sondakika', 'https://www.trthaber.com', 'https://www.trthaber.com/sondakika_articles.rss', 1, 'TRT Haber son dakika', 'TRT Haber', 5, 50, 1),
('TRT Haber - Gündem', 'trthaber-gundem', 'https://www.trthaber.com', 'https://www.trthaber.com/gundem_articles.rss', 1, 'TRT Haber gundem', 'TRT Haber', 15, 30, 1),
('TRT Haber - Türkiye', 'trthaber-turkiye', 'https://www.trthaber.com', 'https://www.trthaber.com/turkiye_articles.rss', 2, 'TRT Haber Türkiye', 'TRT Haber', 15, 30, 1),
('TRT Haber - Dünya', 'trthaber-dunya', 'https://www.trthaber.com', 'https://www.trthaber.com/dunya_articles.rss', 3, 'TRT Haber dunya', 'TRT Haber', 15, 30, 1),
('TRT Haber - Ekonomi', 'trthaber-ekonomi', 'https://www.trthaber.com', 'https://www.trthaber.com/ekonomi_articles.rss', 4, 'TRT Haber ekonomi', 'TRT Haber', 15, 30, 1),
('TRT Haber - Spor', 'trthaber-spor', 'https://www.trthaber.com', 'https://www.trthaber.com/spor_articles.rss', 5, 'TRT Haber spor', 'TRT Haber', 15, 30, 1),
('TRT Haber - Bilim Teknoloji', 'trthaber-teknoloji', 'https://www.trthaber.com', 'https://www.trthaber.com/bilim_teknoloji_articles.rss', 6, 'TRT Haber bilim ve teknoloji', 'TRT Haber', 30, 20, 1),
('TRT Haber - Sağlık', 'trthaber-saglik', 'https://www.trthaber.com', 'https://www.trthaber.com/saglik_articles.rss', 8, 'TRT Haber saglik', 'TRT Haber', 30, 20, 1),
('TRT Haber - Eğitim', 'trthaber-egitim', 'https://www.trthaber.com', 'https://www.trthaber.com/egitim_articles.rss', 9, 'TRT Haber egitim', 'TRT Haber', 30, 20, 1),
('TRT Haber - Kultur Sanat', 'trthaber-kultur', 'https://www.trthaber.com', 'https://www.trthaber.com/kultur_sanat_articles.rss', 10, 'TRT Haber kültür sanat', 'TRT Haber', 30, 20, 1),
('TRT Haber - Yaşam', 'trthaber-yasam', 'https://www.trthaber.com', 'https://www.trthaber.com/yasam_articles.rss', 11, 'TRT Haber yasam', 'TRT Haber', 30, 20, 1),

-- HABERTURK
('Haberturk - Ana Sayfa', 'haberturk-anasayfa', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/anasayfa', 1, 'Haberturk ana sayfa', 'Haberturk', 10, 50, 1),
('Haberturk - Gündem', 'haberturk-gundem', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/kategori/gundem', 1, 'Haberturk gundem', 'Haberturk', 15, 30, 1),
('Haberturk - Dünya', 'haberturk-dunya', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/kategori/dunya', 3, 'Haberturk dunya', 'Haberturk', 15, 30, 1),
('Haberturk - Ekonomi', 'haberturk-ekonomi', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/kategori/ekonomi', 4, 'Haberturk ekonomi', 'Haberturk', 15, 30, 1),
('Haberturk - Spor', 'haberturk-spor', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/kategori/spor', 5, 'Haberturk spor', 'Haberturk', 15, 30, 1),

-- SABAH
('Sabah - Ana Sayfa', 'sabah-anasayfa', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/anasayfa.xml', 1, 'Sabah gazetesi', 'Sabah', 15, 50, 1),
('Sabah - Gündem', 'sabah-gundem', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/gundem.xml', 1, 'Sabah gundem', 'Sabah', 15, 30, 1),
('Sabah - Ekonomi', 'sabah-ekonomi', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/ekonomi.xml', 4, 'Sabah ekonomi', 'Sabah', 15, 30, 1),
('Sabah - Spor', 'sabah-spor', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/spor.xml', 5, 'Sabah spor', 'Sabah', 15, 30, 1),

-- CUMHURIYET
('Cumhuriyet - Ana Sayfa', 'cumhuriyet-ana', 'https://www.cumhuriyet.com.tr', 'https://www.cumhuriyet.com.tr/rss/1.xml', 1, 'Cumhuriyet gazetesi', 'Cumhuriyet', 15, 50, 1),

-- STAR
('Star - Son Dakika', 'star-sondakika', 'https://www.star.com.tr', 'https://www.star.com.tr/rss/rss.asp?cid=124', 1, 'Star son dakika', 'Star', 10, 50, 1),

-- HABER7
('Haber7 - Son Dakika', 'haber7-sondakika', 'https://www.haber7.com', 'https://i12.haber7.net/sondakika/new.rss', 1, 'Haber7 son dakika', 'Haber7', 10, 50, 1),

-- HABERLER.COM
('Haberler.com - Ana', 'haberler-ana', 'https://www.haberler.com', 'https://rss.haberler.com/rss.asp', 1, 'Haberler.com', 'Haberler.com', 15, 50, 1),

-- T24
('T24 - Haberler', 't24-haberler', 'https://t24.com.tr', 'https://t24.com.tr/rss/haberler', 1, 'T24 bagimsiz gazete', 'T24', 30, 30, 1),

-- TEKNOLOJI KAYNAKLARI
('Donanim Haber', 'donanim-haber', 'https://www.donanimhaber.com', 'https://www.donanimhaber.com/rss/tum/', 6, 'Donanim Haber', 'Donanim Haber', 30, 30, 1),
('Chip Online', 'chip-online', 'https://www.chip.com.tr', 'https://www.chip.com.tr/rss', 6, 'Chip Online teknoloji', 'Chip Online', 60, 20, 1),
('Webtekno', 'webtekno', 'https://www.webtekno.com', 'https://www.webtekno.com/rss.xml', 6, 'Webtekno teknoloji', 'Webtekno', 30, 30, 1),

-- RESMI KAYNAKLAR
('Disisleri Bakanligi', 'disisleri', 'https://www.mfa.gov.tr', 'https://www.mfa.gov.tr/rss.tr.mfa', 3, 'T.C. Disisleri Bakanligi', 'T.C. Disisleri Bakanligi', 60, 10, 1),

-- BBC TURKCE
('BBC News Türkçe', 'bbc-turkce', 'https://www.bbc.com/turkce', 'https://feeds.bbci.co.uk/turkce/rss.xml', 3, 'BBC News Türkçe', 'BBC News Türkçe', 30, 30, 1),

-- DW TURKCE
('DW Türkçe', 'dw-turkce', 'https://www.dw.com/tr', 'https://rss.dw.com/xml/rss-tur-all', 3, 'Deutsche Welle Türkçe', 'DW Türkçe', 60, 20, 1);


-- ==========================================================
-- v1.0.7: 40 yeni RSS kaynağı (NTV, Hürriyet, Milliyet, CNN Türk,
-- Sözcü, A Haber, Yeni Şafak, Mynet, AA, Bloomberg HT, Dünya
-- Gazetesi, Fotomaç, ShiftDelete, LOG, Evrensel, Diken, Indy Türkçe,
-- Euronews Türkçe, Gerçek Gündem)
-- ==========================================================
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


-- ==========================================================
-- v1.0.8: Eksik kategori kaynakları (Magazin/Sağlık/Yaşam/Eğitim)
-- ==========================================================
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

