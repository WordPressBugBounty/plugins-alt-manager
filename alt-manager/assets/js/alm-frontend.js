(function() {
    'use strict';
    
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof almAltManager !== 'undefined') {
            const altText = almAltManager.altText || '';
            const titleText = almAltManager.titleText || '';

            // FORCE only-empty behavior: always only set attributes when missing/empty.
            document.querySelectorAll('img').forEach(function(img) {
                // Skip featured images, WPML flags, and logos
                const classes = img.className || '';
                if (classes.includes('wp-post-image') || classes.includes('wpml-ls-flag') || classes.includes('logo') || (img.src && img.src.includes('logo'))) {
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

