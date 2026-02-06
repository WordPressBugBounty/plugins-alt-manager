(function() {
    'use strict';
    
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof almAltManager !== 'undefined') {
            const altText = almAltManager.altText || '';
            const titleText = almAltManager.titleText || '';

            if (altText.length > 0 || titleText.length > 0) {
                document.querySelectorAll("img").forEach(function(img) {
                    if (altText.length > 0 &&
                        (!img.hasAttribute("alt") || img.getAttribute("alt").trim() === "")) {
                        img.setAttribute("alt", altText);
                    }
                    if (titleText.length > 0 &&
                        (!img.hasAttribute("title") || img.getAttribute("title").trim() === "")) {
                        img.setAttribute("title", titleText);
                    }
                });
            }
        }
    });
})();

