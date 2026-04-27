/**
 * Theme switcher with Light, Dark, and System modes.
 */

(function () {
    const normalizeTheme = theme => {
        if (theme === 'auto' || theme === 'system') {
            return 'system';
        }

        return theme === 'light' || theme === 'dark' ? theme : 'system';
    };

    const getStoredTheme = () => normalizeTheme(localStorage.getItem('theme'));
    const setStoredTheme = theme => localStorage.setItem('theme', normalizeTheme(theme));

    const resolveTheme = theme => {
        const normalizedTheme = normalizeTheme(theme);

        if (normalizedTheme !== 'system') {
            return normalizedTheme;
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    const updateThemeToggleUI = preferredTheme => {
        const iconClass = preferredTheme === 'light'
            ? 'bi bi-sun-fill'
            : preferredTheme === 'dark'
                ? 'bi bi-moon-stars-fill'
                : 'bi bi-circle-half';
        const title = preferredTheme === 'light'
            ? 'Theme: Light'
            : preferredTheme === 'dark'
                ? 'Theme: Dark'
                : 'Theme: System';

        document.querySelectorAll('[data-theme-toggle-icon]').forEach(icon => {
            icon.innerHTML = `<i class="${iconClass}"></i>`;
        });

        document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
            toggle.setAttribute('aria-label', title);
            toggle.setAttribute('title', title);
        });

        document.querySelectorAll('[data-theme-value]').forEach(option => {
            const isActive = option.getAttribute('data-theme-value') === preferredTheme;
            option.classList.toggle('active', isActive);
            option.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const setTheme = theme => {
        const preferredTheme = normalizeTheme(theme);
        const resolvedTheme = resolveTheme(preferredTheme);

        if (document.documentElement.getAttribute('data-bs-theme') !== resolvedTheme) {
            document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
        }

        updateThemeToggleUI(preferredTheme);
        window.dispatchEvent(new CustomEvent('theme:change', {
            detail: {
                theme: preferredTheme,
                resolvedTheme
            }
        }));
    };

    const syncTheme = () => {
        setTheme(getStoredTheme());
    };

    const subscribeToColorSchemeChanges = callback => {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', callback);
            return;
        }

        if (typeof mediaQuery.addListener === 'function') {
            mediaQuery.addListener(callback);
        }
    };

    document.addEventListener('click', event => {
        const option = event.target.closest('[data-theme-value]');

        if (!option) {
            return;
        }

        event.preventDefault();
        const theme = option.getAttribute('data-theme-value') || 'system';
        setStoredTheme(theme);
        setTheme(theme);
    });

    subscribeToColorSchemeChanges(() => {
        if (getStoredTheme() === 'system') {
            syncTheme();
        }
    });

    window.addEventListener('storage', event => {
        if (event.key === 'theme') {
            syncTheme();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncTheme, { once: true });
    }

    syncTheme();
})();
