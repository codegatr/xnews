<?php
/**
 * XNEWS - Konfigurasyon Dosyasi
 * ----------------------------------
 * Bu dosya KURULUM sirasinda otomatik olusturulur.
 * GitHub guncellemelerinde ASLA UZERINE YAZILMAZ.
 *
 * Manuel kurulum icin: config.sample.php -> config.php kopyalayin ve duzenleyin.
 */

if (!defined('XNEWS')) { http_response_code(403); die('Erisim reddedildi.'); }

// ==========================================
// VERITABANI AYARLARI
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'kullanici_xnews');
define('DB_USER', 'kullanici_xnews');
define('DB_PASS', 'SIFRE_BURAYA');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', 'xn_');

// ==========================================
// SITE AYARLARI
// ==========================================
define('SITE_URL', 'https://xnews.com.tr');
define('SITE_ADI', 'XNEWS');
define('SITE_SLOGAN', 'Haberin Hizli Adresi');

// ==========================================
// GUVENLIK
// ==========================================
// Bu anahtar kurulumda OTOMATIK uretilir - degistirmeyin
define('GUVENLIK_ANAHTARI', 'KURULUMDA_URETILECEK');
define('CSRF_ANAHTARI', 'KURULUMDA_URETILECEK');

// Yonetim paneli zaman asimi (saniye)
define('OTURUM_SURESI', 7200); // 2 saat

// ==========================================
// GITHUB GUNCELLEME
// ==========================================
// GitHub Personal Access Token (ozel repo icin). Public repo icin bos birakin.
define('GITHUB_TOKEN', '');
define('GUNCELLEME_AKTIF', true);

// ==========================================
// RSS CEKIM AYARLARI
// ==========================================
define('CRON_ANAHTARI', 'KURULUMDA_URETILECEK'); // cron.php?anahtar=... icin
define('MAX_CEKIM_SURESI', 300); // 5 dakika (saniye)
define('HTTP_TIMEOUT', 15); // RSS istegi zaman asimi

// ==========================================
// GORSEL / UPLOAD
// ==========================================
define('UPLOAD_DIZINI', __DIR__ . '/uploads');
define('UPLOAD_URL', SITE_URL . '/uploads');
define('MAX_UPLOAD_BOYUT', 5 * 1024 * 1024); // 5 MB
define('IZIN_VERILEN_UZANTI', 'jpg,jpeg,png,webp,gif,svg');

// ==========================================
// ZAMAN DILIMI
// ==========================================
date_default_timezone_set('Europe/Istanbul');

// ==========================================
// HATA MODU (production: false)
// ==========================================
define('HATA_AYIKLAMA', false);

if (HATA_AYIKLAMA) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/uploads/hata.log');
}
