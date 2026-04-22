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

    // Baslangic
    document.addEventListener('DOMContentLoaded', () => {
        ilerlemeCubuguBaslat();
        lazyLoadBaslat();
        trTarihGuncelle();
        setInterval(trTarihGuncelle, 60000); // Her dakikada bir guncelle

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
    });

    return {
        mobilMenuAc, mobilMenuKapat,
        aramaAc, aramaKapat,
        paylas,
    };
})();
