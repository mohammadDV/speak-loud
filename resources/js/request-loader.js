const BAR_DELAY_MS = 150;
const OVERLAY_DELAY_MS = 400;
const NAV_SAFETY_MS = 10000;
const ROOT = () => document.documentElement;

let pending = 0;
let navigating = false;
let barTimer = null;
let overlayTimer = null;
let navSafetyTimer = null;

function clearSplashClasses() {
    ROOT().classList.remove('app-splash-visible', 'app-splash-leaving');
}

function splashIsVisible() {
    // Only treat splash as active when the splash node is still in the DOM.
    // SPA navigations can leave stale html classes after the body is swapped.
    return ROOT().classList.contains('app-splash-visible')
        && document.querySelector('.app-splash') !== null;
}

function syncAria() {
    const loader = document.querySelector('[data-app-request-loader]');
    const overlayVisible = ROOT().classList.contains('app-request-loading-overlay');

    if (!loader) {
        return;
    }

    loader.setAttribute('aria-hidden', overlayVisible ? 'false' : 'true');
    ROOT().toggleAttribute('aria-busy', overlayVisible || ROOT().classList.contains('app-request-loading-bar'));
}

function clearTimers() {
    if (barTimer !== null) {
        clearTimeout(barTimer);
        barTimer = null;
    }

    if (overlayTimer !== null) {
        clearTimeout(overlayTimer);
        overlayTimer = null;
    }
}

function clearNavSafetyTimer() {
    if (navSafetyTimer !== null) {
        clearTimeout(navSafetyTimer);
        navSafetyTimer = null;
    }
}

function hideLoaders() {
    clearTimers();
    ROOT().classList.remove('app-request-loading-bar', 'app-request-loading-overlay');
    syncAria();
}

function showLoaders() {
    if (splashIsVisible()) {
        return;
    }

    // SPA navigations already use Livewire's branded top progress bar.
    if (!navigating && barTimer === null && !ROOT().classList.contains('app-request-loading-bar')) {
        barTimer = setTimeout(() => {
            barTimer = null;

            if (pending > 0 && !navigating && !splashIsVisible()) {
                ROOT().classList.add('app-request-loading-bar');
                syncAria();
            }
        }, BAR_DELAY_MS);
    }

    if (overlayTimer === null && !ROOT().classList.contains('app-request-loading-overlay')) {
        overlayTimer = setTimeout(() => {
            overlayTimer = null;

            if (pending > 0 && !splashIsVisible()) {
                ROOT().classList.add('app-request-loading-overlay');
                syncAria();
            }
        }, OVERLAY_DELAY_MS);
    }
}

function beginWait() {
    pending += 1;
    showLoaders();
}

function endWait() {
    pending = Math.max(0, pending - 1);

    if (pending === 0) {
        hideLoaders();
    }
}

function resetWaits() {
    pending = 0;
    navigating = false;
    clearNavSafetyTimer();
    hideLoaders();
}

function bindLivewire() {
    if (!window.Livewire || window.__slmRequestLoaderBound) {
        return;
    }

    window.__slmRequestLoaderBound = true;

    if (typeof Livewire.interceptRequest === 'function') {
        Livewire.interceptRequest(({ onResponse, onSuccess, onError, onFailure }) => {
            beginWait();

            let finished = false;
            const finish = () => {
                if (finished) {
                    return;
                }

                finished = true;
                endWait();
            };

            onResponse(finish);
            onSuccess(finish);
            onError(finish);
            onFailure(finish);
        });
    }

    document.addEventListener('livewire:navigating', () => {
        // Drop stale splash classes left on <html> after a body swap.
        clearSplashClasses();
        navigating = true;
        beginWait();

        // Livewire cancels failed navigations without firing `livewire:navigated`.
        clearNavSafetyTimer();
        navSafetyTimer = setTimeout(() => {
            if (navigating) {
                resetWaits();
            }
        }, NAV_SAFETY_MS);
    });

    document.addEventListener('livewire:navigated', () => {
        clearSplashClasses();
        resetWaits();
    });
}

function boot() {
    // Heal any leftover splash classes from a previous SPA body swap.
    if (ROOT().classList.contains('app-splash-visible') && document.querySelector('.app-splash') === null) {
        clearSplashClasses();
    }

    if (!document.querySelector('[data-app-request-loader]')) {
        return;
    }

    syncAria();

    if (window.Livewire) {
        bindLivewire();
    } else {
        document.addEventListener('livewire:init', bindLivewire, { once: true });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
