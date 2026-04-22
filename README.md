# XNEWS - Haber Toplama Platformu

**Surum:** 1.0.0
**PHP:** 8.1+
**MySQL:** 5.7+ / MariaDB 10.3+
**Hosting:** DirectAdmin, cPanel, Plesk

Public RSS beslemeleri uzerinden 30+ Turk haber kaynagindan otomatik haber toplayan, yonetilebilir ve reklam destekli haber portali.

## Ozellikler

- Coklu RSS kaynagi yonetimi (TRT Haber, Haberturk, Sabah, Cumhuriyet, Star, Haber7, Haberler.com, T24, BBC Turkce, DW Turkce, teknoloji siteleri vb.)
- Otomatik duplicate kontrolu (SHA1 hash)
- 12 hazir kategori, etiketleme
- 9 farkli konumda reklam slotu (AdSense destekli)
- GitHub Releases tabanli otomatik guncelleme sistemi
- Tam mobil uyumlu modern tasarim
- SEO dostu URL'ler (Turkce karakter yok)
- Gorsel otomatik cekimi (enclosure, media:content, og:image)
- Ziyaretci istatistikleri
- Kategori/etiket/arama sayfalari, sitemap.xml, RSS beslemesi

## Kurulum

1. Dosyalari sunucunun `public_html` klasorune yukleyin (ZIP cozerek)
2. DirectAdmin'den MySQL veritabani olusturun
3. Tarayicidan `https://xnews.com.tr/kurulum.php` adresine gidin
4. Sihirbazi takip edin (4 adim)
5. Bitince `kurulum.php` dosyasini silin

## Cron Job

DirectAdmin > Cron Jobs'dan asagidaki satiri ekleyin (her 10 dakikada bir):

```
*/10 * * * * wget -q -O /dev/null "https://xnews.com.tr/cron.php?anahtar=BURAYA_CRON_ANAHTARINIZ"
```

Cron anahtarinizi `config.php` icinde `CRON_ANAHTARI` sabitinde bulabilirsiniz.

## Dosya Yapisi

```
/
├── index.php           Public site router
├── yonetim.php         Yonetim paneli
├── cron.php            RSS cekici (cron tetikler)
├── kurulum.php         Kurulum sihirbazi (kurulum sonrasi silinir)
├── guncelle.php        GitHub uzerinden guncelleme
├── baglan.php          DB + ortak fonksiyonlar
├── config.php          Yapilandirma (git-ignored, guncellemeye dahil degil)
├── sitemap.php         sitemap.xml uretici
├── rss.php             RSS cikis beslemesi
├── manifest.json       Surum bilgisi
├── .htaccess           Apache/LiteSpeed ayarlari
├── sql/
│   ├── schema.sql      Tablo semasi
│   └── sources_seed.sql  Hazir RSS kaynaklari
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
└── uploads/            Yuklenen gorseller (+ hata.log)
```

## GitHub Guncelleme Sistemi

- `manifest.json` icindeki `surum` alani mevcut yuklu surumdur
- `yonetim/guncelle` sayfasi GitHub API'den son releasei kontrol eder
- Guncelleme secilirse ZIP indirilir, config.php korunur, dosyalar degistirilir
- Yedek `uploads/yedek/` dizinine atilir

## Reklam Alanlari

- `ust_banner` - Header ustu (970x90)
- `sidebar_ust` - Sag kolon ust (300x250)
- `sidebar_alt` - Sag kolon alt (300x600)
- `makale_ust` - Makale ustu (728x90)
- `makale_ic` - Makale icinde (paragrafa gomulu)
- `makale_alt` - Makale alti (728x90)
- `alt_banner` - Footer ustu (970x90)
- `mobil_sabit` - Mobilde sabit alt (320x50)
- `popup` - Acilir pencere

## Yasal Notlar

Tum haberler kaynak atfi ile birlikte yayinlanir ve orijinal kaynaga link verilir. RSS beslemeleri kamuya acik servis olup haber sitelerinin resmi duyurularla desteklenmistir. Ticari kullanim oncesi her kaynaktan ek izin alinmasi tavsiye edilir.

## Destek

CODEGA - https://codega.com.tr
