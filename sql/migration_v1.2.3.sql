-- ==========================================================
-- XNEWS v1.2.3 - Google AdSense ayarlari + ads.txt
-- ==========================================================

INSERT INTO `xn_settings` (`anahtar`, `deger`, `etiket`, `aciklama`, `tip`, `grup`, `sira`) VALUES
('adsense_client_id', '', 'AdSense Publisher ID', 'Google AdSense yayinci kimliginiz (ca-pub-xxxxxxxxxxxxxxxx)', 'metin', 'reklam', 50),
('adsense_auto_ads', '0', 'Otomatik Reklamlar Aktif', 'AdSense otomatik reklamlarini etkinlestirir (Client ID gerekli)', 'onoff', 'reklam', 51),
('ads_txt_icerik', '', 'ads.txt Dosyasi Icerigi', 'yoursite.com/ads.txt uzerinden yayinlanacak icerik. Google AdSense onayi icin gereklidir. Ornek: google.com, pub-xxx, DIRECT, f08c47fec0942fa0', 'metin-uzun', 'reklam', 52)
ON DUPLICATE KEY UPDATE etiket=VALUES(etiket), aciklama=VALUES(aciklama), tip=VALUES(tip), grup=VALUES(grup), sira=VALUES(sira);
