-- ==========================================================
-- XNEWS v1.2.9 - Footer otomatik guncelleme bildirimi
-- ==========================================================

INSERT INTO `xn_settings` (`anahtar`, `deger`, `etiket`, `aciklama`, `tip`, `grup`, `sira`) VALUES
('otomatik_guncelleme_dk', '10', 'Otomatik Guncelleme Dakikasi', 'Footer bildirim metninde gorunecek dakika degeri (cron sikligi ile uyumlu olmali). Ornek: 5, 10, 15, 30, 60', 'sayi', 'genel', 30)
ON DUPLICATE KEY UPDATE etiket=VALUES(etiket), aciklama=VALUES(aciklama), tip=VALUES(tip);
