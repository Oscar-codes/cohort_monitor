/**
 * Applies preferred density mode before app shell paint.
 */

(function () {
    'use strict';

    try {
        if (localStorage.getItem('app-density') === 'compact') {
            document.documentElement.classList.add('app-density-compact');
        }
    } catch (e) {
        // Ignore storage availability errors.
    }
})();
