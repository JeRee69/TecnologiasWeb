(() => {
    const sidebar = document.getElementById('app-sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!sidebar || !toggle) return;
    const mobile = window.matchMedia('(max-width: 767px)');
    const setOpen = open => {
        sidebar.classList.toggle('is-open', open);
        sidebar.inert = mobile.matches && !open;
        backdrop.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) sidebar.querySelector('a').focus();
    };
    toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('is-open')));
    backdrop.addEventListener('click', () => { setOpen(false); toggle.focus(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) { setOpen(false); toggle.focus(); }
        if (event.key === 'Tab' && mobile.matches && sidebar.classList.contains('is-open')) {
            const links = [...sidebar.querySelectorAll('a[href],button:not([disabled])')];
            if (event.shiftKey && document.activeElement === links[0]) { event.preventDefault(); links.at(-1).focus(); }
            else if (!event.shiftKey && document.activeElement === links.at(-1)) { event.preventDefault(); links[0].focus(); }
        }
    });
    mobile.addEventListener('change', () => setOpen(false));
    setOpen(false);
})();
