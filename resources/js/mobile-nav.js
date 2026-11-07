function syncMobileNavIcons(root, isOpen) {
    const openIcon = root.querySelector('[data-mobile-nav-icon="open"]');
    const closeIcon = root.querySelector('[data-mobile-nav-icon="close"]');

    openIcon?.classList.toggle('hidden', isOpen);
    closeIcon?.classList.toggle('hidden', !isOpen);
}

function closeMobileNav(root) {
    root.dataset.open = 'false';

    const drawer = root.querySelector('[data-mobile-nav-drawer]');
    const toggle = root.querySelector('[data-mobile-nav-toggle]');

    if (drawer) {
        drawer.hidden = true;
    }

    if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open menu');
    }

    syncMobileNavIcons(root, false);
}

function openMobileNav(root) {
    root.dataset.open = 'true';

    const drawer = root.querySelector('[data-mobile-nav-drawer]');
    const toggle = root.querySelector('[data-mobile-nav-toggle]');

    if (drawer) {
        drawer.hidden = false;
    }

    if (toggle) {
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close menu');
    }

    syncMobileNavIcons(root, true);
}

function toggleMobileNav(root) {
    if (root.dataset.open === 'true') {
        closeMobileNav(root);
    } else {
        openMobileNav(root);
    }
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-mobile-nav-toggle]');

    if (toggle) {
        const root = toggle.closest('[data-mobile-nav]');

        if (root) {
            toggleMobileNav(root);
        }

        return;
    }

    const close = event.target.closest('[data-mobile-nav-close]');

    if (close) {
        const root = close.closest('[data-mobile-nav]');

        if (root) {
            closeMobileNav(root);
        }

        return;
    }

    const link = event.target.closest('[data-mobile-nav-drawer] a');

    if (link) {
        const root = link.closest('[data-mobile-nav]');

        if (root) {
            closeMobileNav(root);
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('[data-mobile-nav][data-open="true"]').forEach((root) => {
        closeMobileNav(root);
    });
});
