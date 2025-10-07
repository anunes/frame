/**
 * Theme switcher with Light and Dark modes
 */

(function() {
    // Get stored theme or default to 'light'
    const getStoredTheme = () => localStorage.getItem('theme') || 'light';

    // Apply theme to document
    const setTheme = theme => {
        document.documentElement.setAttribute('data-bs-theme', theme);
    };

    // Initialize theme immediately (before DOMContentLoaded to prevent flash)
    setTheme(getStoredTheme());
})();
