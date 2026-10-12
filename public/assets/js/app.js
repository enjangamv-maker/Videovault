(() => {
    'use strict';

    const THEME_KEY = 'vv-theme';
    const root = document.documentElement;

    function setTheme(t) {
        if (t !== 'light' && t !== 'dark') return;
        root.setAttribute('data-theme', t);
        try { localStorage.setItem(THEME_KEY, t); } catch(e) {}
    }
    function currentTheme() { return root.getAttribute('data-theme') || 'dark'; }
    function bindToggle(btn) {
        if (!btn || btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', (e) => {
            e.preventDefault(); e.stopPropagation();
            setTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    }
    document.querySelectorAll('#themeToggle, [data-theme-toggle]').forEach(bindToggle);

    // Hamburger
    const burger = document.getElementById('navBurger');
    const menu = document.getElementById('navMenu');
    if (burger && menu) {
        burger.addEventListener('click', () => {
            const open = menu.classList.toggle('is-open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Toast
    const toastHost = document.getElementById('toasts');
    window.toast = function (msg, type = 'info', ttl = 4000) {
        if (!toastHost) return;
        const el = document.createElement('div');
        el.className = `toast toast--${type}`;
        el.innerHTML = `<span>${msg}</span><button class="toast__close" aria-label="Tutup">✕</button>`;
        toastHost.appendChild(el);
        const close = () => {
            el.style.transition = 'opacity .3s, transform .3s';
            el.style.opacity = '0'; el.style.transform = 'translateY(6px)';
            setTimeout(() => el.remove(), 300);
        };
        el.querySelector('.toast__close').addEventListener('click', close);
        if (ttl > 0) setTimeout(close, ttl);
    };

    // Alerts → toast
    document.querySelectorAll('.alert').forEach((a) => {
        const txt = a.textContent.trim();
        const type = a.classList.contains('alert--ok') ? 'ok'
                   : a.classList.contains('alert--err') ? 'err' : 'info';
        if (txt) window.toast(txt, type);
        a.remove();
    });

    // Keyboard
    document.addEventListener('keydown', (e) => {
        const typing = ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName);
        if (e.key === '/' && !typing) {
            const s = document.querySelector('input[type="search"]');
            if (s) { e.preventDefault(); s.focus(); }
        }
        if ((e.key === 't' || e.key === 'T') && !typing) {
            setTheme(currentTheme() === 'dark' ? 'light' : 'dark');
            window.toast(`Mode: ${currentTheme()}`, 'info', 1500);
        }
        const v = document.querySelector('.detail__player video');
        if (v && !typing) {
            if (e.key === ' ') { e.preventDefault(); v.paused ? v.play() : v.pause(); }
            if (e.key === 'ArrowRight') v.currentTime += 5;
            if (e.key === 'ArrowLeft')  v.currentTime -= 5;
            if (e.key === 'f' || e.key === 'F') {
                document.fullscreenElement ? document.exitFullscreen() : v.requestFullscreen?.();
            }
        }
    });

    // Reveal
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) { entry.target.classList.add('is-in'); io.unobserve(entry.target); }
        });
    }, { threshold: 0.08 });
    document.querySelectorAll('.card, .stat').forEach((el) => io.observe(el));

    // Confirm forms
    document.querySelectorAll('form[data-confirm]').forEach((f) => {
        f.addEventListener('submit', (e) => {
            if (!confirm(f.dataset.confirm)) { e.preventDefault(); return; }
            const btn = f.querySelector('button[type="submit"], button');
            if (btn) { btn.disabled = true; btn.textContent = '…'; }
        });
    });

    // Upload progress
    document.querySelectorAll('form[enctype="multipart/form-data"]').forEach((f) => {
        let bar = f.querySelector('.upload-progress__bar');
        if (!bar) {
            const wrap = document.createElement('div');
            wrap.className = 'upload-progress';
            wrap.innerHTML = '<div class="upload-progress__bar"></div>';
            f.appendChild(wrap);
            bar = wrap.querySelector('.upload-progress__bar');
        }
        f.addEventListener('submit', (e) => {
            e.preventDefault();
            const data = new FormData(f);
            const xhr = new XMLHttpRequest();
            xhr.open(f.method || 'POST', f.action || location.href, true);
            xhr.upload.addEventListener('progress', (ev) => {
                if (ev.lengthComputable) {
                    const pct = Math.round(ev.loaded / ev.total * 100);
                    bar.style.width = pct + '%';
                }
            });
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 400) {
                    window.toast('Upload berhasil. Memuat ulang…', 'ok', 1500);
                    setTimeout(() => location.href = f.dataset.redirect || xhr.responseURL || location.href, 800);
                } else {
                    window.toast('Upload gagal.', 'err');
                    bar.style.width = '0%';
                }
            });
            xhr.addEventListener('error', () => {
                window.toast('Koneksi error.', 'err');
                bar.style.width = '0%';
            });
            xhr.send(data);
        });
    });

    // Drag & drop
    document.querySelectorAll('input[type="file"]').forEach((input) => {
        const wrap = input.closest('label') || input.parentElement;
        if (!wrap) return;
        wrap.addEventListener('dragover', (e) => { e.preventDefault(); wrap.style.borderColor = 'var(--amber)'; });
        wrap.addEventListener('dragleave', () => { wrap.style.borderColor = ''; });
        wrap.addEventListener('drop', (e) => {
            e.preventDefault(); wrap.style.borderColor = '';
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });

    // Sync theme antar tab
    window.addEventListener('storage', (e) => {
        if (e.key === THEME_KEY && e.newValue) root.setAttribute('data-theme', e.newValue);
    });

    console.log('%c VideoVault v2.1.0 ', 'background:#e8a13c;color:#1a1206;font-weight:800;padding:2px 6px;border-radius:4px;');
})();
