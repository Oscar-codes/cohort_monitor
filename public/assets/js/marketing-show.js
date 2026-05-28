/**
 * Marketing workflow interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const selects = Array.from(document.querySelectorAll('.js-risk-status'));

    if (!selects.length) {
        return;
    }

    const toggleRiskPanel = function (select) {
        const targetId = select.dataset.riskTarget;
        if (!targetId) {
            return;
        }

        const panel = document.getElementById(targetId);
        if (!panel) {
            return;
        }

        panel.classList.toggle('d-none', select.value !== 'at_risk');
    };

    selects.forEach((select) => {
        toggleRiskPanel(select);
        select.addEventListener('change', function () {
            toggleRiskPanel(select);
        });
    });
});
