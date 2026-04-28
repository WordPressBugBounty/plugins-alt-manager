(function() {
    'use strict';

    function isInsideLoopContext(img) {
        if (!img || !img.closest) {
            return false;
        }

        return Boolean(
            img.closest('article') ||
            img.closest('[data-post-id]') ||
            img.closest('[id^="post-"]') ||
            img.closest('[class*=" post-"]') ||
            img.closest('[class^="post-"]') ||
            img.closest('.elementor-post') ||
            img.closest('.e-loop-item') ||
            img.closest('.post') ||
            img.closest('.product')
        );
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof almAltManager !== 'undefined') {
            const altText = almAltManager.altText || '';
            const titleText = almAltManager.titleText || '';

            // This is a last-resort fallback for unresolved images only.
            document.querySelectorAll('img').forEach(function(img) {
                // Skip featured images, WPML flags, and logos
                const classes = img.className || '';
                if (classes.includes('wp-post-image') || classes.includes('wpml-ls-flag') || classes.includes('logo') || (img.src && img.src.includes('logo'))) {
                    return;
                }

                // Skip loop/archive/card items. Those must use per-item PHP context instead of one global fallback.
                if (isInsideLoopContext(img)) {
                    return;
                }

                // Alt: set only when altText provided AND current alt is missing/empty
                const currentAlt = img.getAttribute('alt');
                const altIsEmpty = !currentAlt || currentAlt.trim() === '';
                if (altText && altIsEmpty) {
                    img.setAttribute('alt', altText);
                }

                // Title: set only when titleText provided AND current title is missing/empty
                const currentTitle = img.getAttribute('title');
                const titleIsEmpty = !currentTitle || currentTitle.trim() === '';
                if (titleText && titleIsEmpty) {
                    img.setAttribute('title', titleText);
                }
            });
        }
    });
})();

