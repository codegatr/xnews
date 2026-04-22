-- =====================================================
-- XNEWS - Varsayilan RSS Kaynaklari
-- Tum kaynaklar halka acik RSS besleme sunmaktadir.
-- Yasal Not: Haberler kaynak belirtilerek (atfi_metin) yayinlanmalidir.
-- =====================================================

SET NAMES utf8mb4;

-- Kategori ID'leri (schema.sql'den):
-- 1=Gundem, 2=Turkiye, 3=Dunya, 4=Ekonomi, 5=Spor, 6=Teknoloji,
-- 7=Magazin, 8=Saglik, 9=Egitim, 10=Kultur Sanat, 11=Yasam, 12=Otomobil

INSERT INTO `xn_sources`
    (`ad`, `slug`, `site_url`, `rss_url`, `varsayilan_kategori_id`, `aciklama`, `atfi_metin`, `cekim_sikligi`, `max_haber_adet`, `aktif`) VALUES

-- ULUSAL HABER KAYNAKLARI
('TRT Haber - Manset', 'trthaber-manset', 'https://www.trthaber.com', 'https://www.trthaber.com/manset_articles.rss', 1, 'TRT Haber manset haberleri', 'TRT Haber', 10, 30, 1),
('TRT Haber - Son Dakika', 'trthaber-sondakika', 'https://www.trthaber.com', 'https://www.trthaber.com/sondakika_articles.rss', 1, 'TRT Haber son dakika', 'TRT Haber', 5, 50, 1),
('TRT Haber - Gundem', 'trthaber-gundem', 'https://www.trthaber.com', 'https://www.trthaber.com/gundem_articles.rss', 1, 'TRT Haber gundem', 'TRT Haber', 15, 30, 1),
('TRT Haber - Turkiye', 'trthaber-turkiye', 'https://www.trthaber.com', 'https://www.trthaber.com/turkiye_articles.rss', 2, 'TRT Haber Turkiye', 'TRT Haber', 15, 30, 1),
('TRT Haber - Dunya', 'trthaber-dunya', 'https://www.trthaber.com', 'https://www.trthaber.com/dunya_articles.rss', 3, 'TRT Haber dunya', 'TRT Haber', 15, 30, 1),
('TRT Haber - Ekonomi', 'trthaber-ekonomi', 'https://www.trthaber.com', 'https://www.trthaber.com/ekonomi_articles.rss', 4, 'TRT Haber ekonomi', 'TRT Haber', 15, 30, 1),
('TRT Haber - Spor', 'trthaber-spor', 'https://www.trthaber.com', 'https://www.trthaber.com/spor_articles.rss', 5, 'TRT Haber spor', 'TRT Haber', 15, 30, 1),
('TRT Haber - Bilim Teknoloji', 'trthaber-teknoloji', 'https://www.trthaber.com', 'https://www.trthaber.com/bilim_teknoloji_articles.rss', 6, 'TRT Haber bilim ve teknoloji', 'TRT Haber', 30, 20, 1),
('TRT Haber - Saglik', 'trthaber-saglik', 'https://www.trthaber.com', 'https://www.trthaber.com/saglik_articles.rss', 8, 'TRT Haber saglik', 'TRT Haber', 30, 20, 1),
('TRT Haber - Egitim', 'trthaber-egitim', 'https://www.trthaber.com', 'https://www.trthaber.com/egitim_articles.rss', 9, 'TRT Haber egitim', 'TRT Haber', 30, 20, 1),
('TRT Haber - Kultur Sanat', 'trthaber-kultur', 'https://www.trthaber.com', 'https://www.trthaber.com/kultur_sanat_articles.rss', 10, 'TRT Haber kultur sanat', 'TRT Haber', 30, 20, 1),
('TRT Haber - Yasam', 'trthaber-yasam', 'https://www.trthaber.com', 'https://www.trthaber.com/yasam_articles.rss', 11, 'TRT Haber yasam', 'TRT Haber', 30, 20, 1),

-- HABERTURK
('Haberturk - Ana Sayfa', 'haberturk-anasayfa', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/anasayfa', 1, 'Haberturk ana sayfa', 'Haberturk', 10, 50, 1),
('Haberturk - Gundem', 'haberturk-gundem', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/kategori/gundem', 1, 'Haberturk gundem', 'Haberturk', 15, 30, 1),
('Haberturk - Dunya', 'haberturk-dunya', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/kategori/dunya', 3, 'Haberturk dunya', 'Haberturk', 15, 30, 1),
('Haberturk - Ekonomi', 'haberturk-ekonomi', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/kategori/ekonomi', 4, 'Haberturk ekonomi', 'Haberturk', 15, 30, 1),
('Haberturk - Spor', 'haberturk-spor', 'https://www.haberturk.com', 'https://www.haberturk.com/rss/kategori/spor', 5, 'Haberturk spor', 'Haberturk', 15, 30, 1),

-- SABAH
('Sabah - Ana Sayfa', 'sabah-anasayfa', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/anasayfa.xml', 1, 'Sabah gazetesi', 'Sabah', 15, 50, 1),
('Sabah - Gundem', 'sabah-gundem', 'https://www.sabah.com.tr', 'https://www.sabah.com.tr/rss/gundem.xml', 1, 'Sabah gundem', 'Sabah', 15, 30, 1),
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
('BBC News Turkce', 'bbc-turkce', 'https://www.bbc.com/turkce', 'https://feeds.bbci.co.uk/turkce/rss.xml', 3, 'BBC News Turkce', 'BBC News Turkce', 30, 30, 1),

-- DW TURKCE
('DW Turkce', 'dw-turkce', 'https://www.dw.com/tr', 'https://rss.dw.com/xml/rss-tur-all', 3, 'Deutsche Welle Turkce', 'DW Turkce', 60, 20, 1);
