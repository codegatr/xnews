/* =====================================================
   XNEWS - Ana JavaScript
   ===================================================== */

window.xnews = (function() {
    'use strict';

    const $ = sel => document.querySelector(sel);
    const $$ = sel => document.querySelectorAll(sel);

    // Mobil menu
    function mobilMenuAc() {
        $('.mobil-menu-panel')?.classList.add('acik');
        $('.mobil-karartmasi')?.classList.add('acik');
        document.body.style.overflow = 'hidden';
    }
    function mobilMenuKapat() {
        $('.mobil-menu-panel')?.classList.remove('acik');
        $('.mobil-karartmasi')?.classList.remove('acik');
        document.body.style.overflow = '';
    }

    // Arama overlay
    function aramaAc() {
        const ov = $('.arama-overlay');
        if (!ov) return;
        ov.classList.add('acik');
        document.body.style.overflow = 'hidden';
        setTimeout(() => $('.arama-kutu input')?.focus(), 50);
    }
    function aramaKapat() {
        $('.arama-overlay')?.classList.remove('acik');
        document.body.style.overflow = '';
    }

    // ESC tuşu ile kapat
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            aramaKapat();
            mobilMenuKapat();
        }
    });

    // Scroll ilerleme cubugu (haber detay)
    function ilerlemeCubuguBaslat() {
        const cubuk = $('#ilerlemeCubugu');
        if (!cubuk) return;
        window.addEventListener('scroll', () => {
            const scrollTop = window.scrollY;
            const yukseklik = document.documentElement.scrollHeight - window.innerHeight;
            const oran = yukseklik > 0 ? (scrollTop / yukseklik) * 100 : 0;
            cubuk.style.width = oran + '%';
        }, { passive: true });
    }

    // Lazy load (eger tarayici desteklemiyorsa)
    function lazyLoadBaslat() {
        if ('loading' in HTMLImageElement.prototype) return; // Native destek var
        $$('img[loading="lazy"]').forEach(img => {
            if (img.dataset.src) { img.src = img.dataset.src; }
        });
    }

    // Paylasim butonlari
    function paylas(platform, url, baslik) {
        const siteUrl = encodeURIComponent(url || window.location.href);
        const siteBaslik = encodeURIComponent(baslik || document.title);
        let paylasUrl = '';
        switch (platform) {
            case 'facebook':
                paylasUrl = `https://www.facebook.com/sharer/sharer.php?u=${siteUrl}`; break;
            case 'twitter':
                paylasUrl = `https://twitter.com/intent/tweet?url=${siteUrl}&text=${siteBaslik}`; break;
            case 'whatsapp':
                paylasUrl = `https://wa.me/?text=${siteBaslik}%20${siteUrl}`; break;
            case 'telegram':
                paylasUrl = `https://t.me/share/url?url=${siteUrl}&text=${siteBaslik}`; break;
            case 'linkedin':
                paylasUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${siteUrl}`; break;
            case 'link':
                navigator.clipboard?.writeText(url || window.location.href);
                alert('Link panoya kopyalandi!');
                return;
        }
        if (paylasUrl) {
            window.open(paylasUrl, '_blank', 'width=600,height=500,noopener');
        }
    }

    // Oku-devam sistemli: kullanici scroll yaptikca "ilgili haber" kartlarini onbellekten doldur (opsiyonel - sonra)

    // Yil disinda tarih dogrulamasi (JS tarafinda Turkce tarih)
    function trTarihGuncelle() {
        $$('.tarih-dinamik').forEach(el => {
            const ts = parseInt(el.dataset.ts, 10);
            if (isNaN(ts)) return;
            const fark = Math.floor((Date.now() - ts * 1000) / 1000);
            if (fark < 60) el.textContent = 'az once';
            else if (fark < 3600) el.textContent = Math.floor(fark / 60) + ' dakika once';
            else if (fark < 86400) el.textContent = Math.floor(fark / 3600) + ' saat once';
            else if (fark < 604800) el.textContent = Math.floor(fark / 86400) + ' gun once';
        });
    }

    // ==================================================
    // ÇEREZ YÖNETIMI (KVKK uyumlu)
    // ==================================================
    const CEREZ_KEY = 'xn_cerez_onay';
    const CEREZ_VERSION = 2; // Politika değişirse arttır

    function cerezOku() {
        try {
            const c = document.cookie.split('; ').find(r => r.startsWith(CEREZ_KEY + '='));
            if (!c) return null;
            return JSON.parse(decodeURIComponent(c.split('=')[1]));
        } catch(e) { return null; }
    }
    function cerezYaz(data) {
        const val = encodeURIComponent(JSON.stringify({...data, v: CEREZ_VERSION, t: Date.now()}));
        document.cookie = CEREZ_KEY + '=' + val + '; path=/; max-age=' + (365*24*60*60) + '; SameSite=Lax';
    }
    function cerezBannerKontrol() {
        const banner = $('#cerezBanner');
        if (!banner) return;
        const mevcut = cerezOku();
        if (!mevcut || mevcut.v !== CEREZ_VERSION) {
            banner.style.display = 'block';
        } else {
            cerezUygula(mevcut);
        }
    }
    function cerezUygula(tercih) {
        // Analitik: Google Analytics yükle (varsa)
        if (tercih.analitik) {
            window.xnCerezAnalitikAktif = true;
            // Örnek: loadGA(); (admin GA key set ederse yüklenir)
        }
        if (tercih.reklam) {
            window.xnCerezReklamAktif = true;
            // AdSense consent
            if (window.adsbygoogle) {
                try { (adsbygoogle = window.adsbygoogle || []).push({}); } catch(e){}
            }
        }
    }
    function cerezKabul(tur) {
        const tercih = tur === 'tumu'
            ? { analitik: true, reklam: true }
            : { analitik: false, reklam: false };
        cerezYaz(tercih);
        cerezUygula(tercih);
        const b = $('#cerezBanner'); if (b) b.style.display = 'none';
        const m = $('#cerezModal'); if (m) m.style.display = 'none';
    }
    function cerezAyarlariAc() {
        const modal = $('#cerezModal');
        if (!modal) return;
        const mevcut = cerezOku() || {};
        if ($('#cerez_analitik')) $('#cerez_analitik').checked = !!mevcut.analitik;
        if ($('#cerez_reklam')) $('#cerez_reklam').checked = !!mevcut.reklam;
        modal.style.display = 'flex';
    }
    function cerezAyarlariKapat() {
        const m = $('#cerezModal'); if (m) m.style.display = 'none';
    }
    function cerezKaydet() {
        const tercih = {
            analitik: $('#cerez_analitik')?.checked || false,
            reklam:   $('#cerez_reklam')?.checked || false,
        };
        cerezYaz(tercih);
        cerezUygula(tercih);
        const m = $('#cerezModal'); if (m) m.style.display = 'none';
        const b = $('#cerezBanner'); if (b) b.style.display = 'none';
    }

    // ==================================================
    // FONT SIZE AYARI (haber detay)
    // ==================================================
    const FS_KEY = 'xn_fs';
    const FS_SIRA = ['fs-kucuk', '', 'fs-buyuk', 'fs-cok-buyuk'];
    function fontSizeUygula() {
        const mevcut = localStorage.getItem(FS_KEY) || '';
        // Temizle
        FS_SIRA.forEach(c => { if (c) document.body.classList.remove(c); });
        if (mevcut) document.body.classList.add(mevcut);
    }
    function fontSizeDegistir(yon) {
        let mevcut = localStorage.getItem(FS_KEY) || '';
        let idx = FS_SIRA.indexOf(mevcut);
        if (idx === -1) idx = 1; // varsayılan
        idx = Math.max(0, Math.min(FS_SIRA.length - 1, idx + yon));
        const yeni = FS_SIRA[idx];
        if (yeni) localStorage.setItem(FS_KEY, yeni);
        else localStorage.removeItem(FS_KEY);
        fontSizeUygula();
    }

    // ==================================================
    // DARK MODE (sadece manuel - default acik)
    // ==================================================
    const TEMA_KEY = 'xn_tema';
    function temaUygula() {
        // Default: acik. Sadece kullanici 'koyu' secerse koyu.
        const mevcut = localStorage.getItem(TEMA_KEY) || 'acik';
        document.body.classList.remove('tema-koyu', 'tema-acik');
        if (mevcut === 'koyu') document.body.classList.add('tema-koyu');
        else document.body.classList.add('tema-acik');
    }
    function temaDegistir() {
        const mevcut = localStorage.getItem(TEMA_KEY) || 'acik';
        const sonraki = mevcut === 'koyu' ? 'acik' : 'koyu';
        localStorage.setItem(TEMA_KEY, sonraki);
        temaUygula();
    }

    // ==================================================
    // YAZDIR
    // ==================================================
    function yazdir() { window.print(); }

    // ==================================================
    // HABERI DINLE (Web Speech API)
    // ==================================================
    let _konusma = null;
    function haberiDinle() {
        if (!('speechSynthesis' in window)) {
            alert('Tarayıcınız sesli okumayı desteklemiyor.');
            return;
        }
        if (_konusma && speechSynthesis.speaking) {
            speechSynthesis.cancel();
            _konusma = null;
            return;
        }
        const baslik = document.querySelector('.hd-baslik')?.innerText || '';
        const icerik = document.querySelector('.hd-icerik')?.innerText || '';
        const metin = (baslik + '. ' + icerik).substring(0, 5000);
        _konusma = new SpeechSynthesisUtterance(metin);
        _konusma.lang = 'tr-TR';
        _konusma.rate = 1.0;
        speechSynthesis.speak(_konusma);
    }

    // ==================================================
    // BÜYÜK SON DAKİKA SLIDER
    // ==================================================
    function sdSliderBaslat() {
        const slider = $('#sdSlider');
        if (!slider) return;
        const adet = parseInt(slider.dataset.adet || 0);
        if (adet < 2) return;

        const numaralar = slider.querySelectorAll('.sd-num');
        const slaytlar  = slider.querySelectorAll('.sd-slide');
        const ilerleme  = slider.querySelector('.sd-slider-ilerleme-dolgu');
        const sayac     = slider.querySelector('#sdAktifNo');
        const okPrev    = slider.querySelector('#sdOkPrev');
        const okNext    = slider.querySelector('#sdOkNext');
        const SURE = 7000;
        let aktifIdx = 0;
        let sayim = 0;
        let duraklatildi = false;

        function goster(idx) {
            idx = ((idx % adet) + adet) % adet; // negatif guvenlikli modulo
            numaralar.forEach((n, i) => n.classList.toggle('aktif', i === idx));
            slaytlar.forEach((s, i) => s.classList.toggle('aktif', i === idx));
            if (sayac) sayac.textContent = idx + 1;
            aktifIdx = idx;
            sayim = 0;
            if (ilerleme) ilerleme.style.width = '0%';
        }

        function sonraki() {
            if (duraklatildi) return;
            sayim += 100;
            if (ilerleme) ilerleme.style.width = (sayim / SURE * 100) + '%';
            if (sayim >= SURE) {
                goster(aktifIdx + 1);
            }
        }

        numaralar.forEach((n, i) => {
            n.addEventListener('click', (e) => { e.preventDefault(); goster(i); });
            n.addEventListener('mouseenter', () => goster(i));
        });

        if (okPrev) okPrev.addEventListener('click', (e) => { e.preventDefault(); goster(aktifIdx - 1); });
        if (okNext) okNext.addEventListener('click', (e) => { e.preventDefault(); goster(aktifIdx + 1); });

        slider.addEventListener('mouseenter', () => { duraklatildi = true; slider.classList.add('duraklatildi'); });
        slider.addEventListener('mouseleave', () => { duraklatildi = false; slider.classList.remove('duraklatildi'); });

        // Klavye (slider görünürse)
        document.addEventListener('keydown', (e) => {
            if (!slider.getBoundingClientRect().bottom > 0) return;
            if (e.key === 'ArrowRight' && document.activeElement.tagName !== 'INPUT') goster(aktifIdx + 1);
            else if (e.key === 'ArrowLeft' && document.activeElement.tagName !== 'INPUT') goster(aktifIdx - 1);
        });

        setInterval(sonraki, 100);
    }

    // ==================================================
    // MOBIL SABIT BANNER (kapat)
    // ==================================================
    function mobilBannerKapat() {
        const b = $('.reklam-mobil-sabit');
        if (b) b.style.display = 'none';
        sessionStorage.setItem('xn_msb_kapali', '1');
    }
    function mobilBannerKontrol() {
        const b = $('.reklam-mobil-sabit');
        if (!b) return;
        if (sessionStorage.getItem('xn_msb_kapali')) return;
        b.classList.add('aktif');
    }

    // Baslangic
    document.addEventListener('DOMContentLoaded', () => {
        ilerlemeCubuguBaslat();
        lazyLoadBaslat();
        trTarihGuncelle();
        setInterval(trTarihGuncelle, 60000);

        // Manset haberlerini tikla
        $$('.haber-kart, .manset-buyuk, .manset-kucuk, .mini-kart').forEach(kart => {
            const link = kart.querySelector('a[href]');
            if (link && !kart.closest('a')) {
                kart.style.cursor = 'pointer';
                kart.addEventListener('click', (e) => {
                    if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                        window.location = link.href;
                    }
                });
            }
        });

        // Cerez + Tema + Font size uygula
        temaUygula();
        fontSizeUygula();
        setTimeout(cerezBannerKontrol, 800); // Sayfa yuklendikten sonra bildir
        mobilBannerKontrol();
        sdSliderBaslat();
    });

    return {
        mobilMenuAc, mobilMenuKapat,
        aramaAc, aramaKapat,
        paylas,
        cerezKabul, cerezAyarlariAc, cerezAyarlariKapat, cerezKaydet,
        fontSizeDegistir, temaDegistir, yazdir, haberiDinle,
        mobilBannerKapat,
    };
})();
