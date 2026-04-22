/* XNEWS Admin - JavaScript */
window.xadmin = (function () {
    'use strict';
    const $ = s => document.querySelector(s);

    // Sidebar (mobil)
    function sidebarAc() {
        $('.sidebar')?.classList.add('acik');
        $('.karartma')?.classList.add('acik');
        document.body.style.overflow = 'hidden';
    }
    function sidebarKapat() {
        $('.sidebar')?.classList.remove('acik');
        $('.karartma')?.classList.remove('acik');
        document.body.style.overflow = '';
    }

    // Silme onayi
    function silOnayla(mesaj) {
        return confirm(mesaj || 'Silmek istediginizden emin misiniz? Bu islem geri alinamaz.');
    }

    // Kopyalama
    function kopyala(metin, buton) {
        navigator.clipboard?.writeText(metin).then(() => {
            if (buton) {
                const eskiMetin = buton.textContent;
                buton.textContent = 'Kopyalandi!';
                setTimeout(() => buton.textContent = eskiMetin, 1500);
            }
        });
    }

    // Ad -> Slug otomatik uretim (form-icinde)
    function slugOlustur(inputId, hedefId) {
        const kaynak = document.getElementById(inputId);
        const hedef  = document.getElementById(hedefId);
        if (!kaynak || !hedef) return;
        kaynak.addEventListener('input', () => {
            if (hedef.dataset.dokunuldu === '1') return;
            const tr = { 'ı':'i', 'İ':'i', 'ğ':'g', 'Ğ':'g', 'ü':'u', 'Ü':'u', 'ş':'s', 'Ş':'s', 'ö':'o', 'Ö':'o', 'ç':'c', 'Ç':'c' };
            hedef.value = kaynak.value
                .replace(/[ıİğĞüÜşŞöÖçÇ]/g, c => tr[c] || c)
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s-]+/g, '-')
                .replace(/^-|-$/g, '');
        });
        hedef.addEventListener('input', () => hedef.dataset.dokunuldu = '1');
    }

    // Flash mesajlarini otomatik kaldir
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.alert[data-otomatik-kaldir]').forEach(el => {
            setTimeout(() => {
                el.style.transition = 'opacity .3s, transform .3s';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-8px)';
                setTimeout(() => el.remove(), 300);
            }, 4000);
        });
    });

    return { sidebarAc, sidebarKapat, silOnayla, kopyala, slugOlustur };
})();
