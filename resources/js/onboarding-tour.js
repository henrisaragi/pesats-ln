import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

// Guided tour for the "lengkapi data entitas" onboarding wizard. Each wizard page
// carries data-tour="..." markers on its key elements; this builds a driver.js step
// list matching whichever page is currently loaded and can be re-run via the
// "Tur Panduan" button (id="onboarding-tour-trigger", added by the shared
// resources/views/components/onboarding-shell.blade.php shell).
//
// All popover text is translated server-side (see window.onboardingTourText, set by
// that same shell component via __()) rather than hardcoded here, matching the
// window.dataTableLanguage pattern used for DataTables i18n elsewhere in the app.

function text() {
    return window.onboardingTourText || {};
}

function stepsFor(pageKey) {
    const t = text();
    const common = [];

    if (document.querySelector('[data-tour="steps"]') && t.steps) {
        common.push({
            element: '[data-tour="steps"]',
            popover: t.steps,
        });
    }

    if (pageKey === 'type' && t.type) {
        return [
            ...common,
            { element: '[data-tour="type-options"]', popover: t.type.options },
            { element: '[data-tour="type-next"]', popover: t.type.next },
        ];
    }

    if (pageKey === 'entitas' && t.entitas) {
        return [
            ...common,
            { element: '[data-tour="entitas-name"]', popover: t.entitas.name },
            { element: '[data-tour="entitas-address"]', popover: t.entitas.address },
            { element: '[data-tour="entitas-region"]', popover: t.entitas.region },
            { element: '[data-tour="entitas-next"]', popover: t.entitas.next },
        ];
    }

    if (pageKey === 'detail' && t.detail) {
        return [
            ...common,
            { element: '[data-tour="detail-fields"]', popover: t.detail.fields },
            { element: '[data-tour="detail-next"]', popover: t.detail.next },
        ];
    }

    if (pageKey === 'complete' && t.complete) {
        return [{ element: '[data-tour="complete"]', popover: t.complete }];
    }

    return [];
}

function detectPageKey() {
    if (document.querySelector('[data-tour="type-form"]')) return 'type';
    if (document.querySelector('[data-tour="entitas-form"]')) return 'entitas';
    if (document.querySelector('[data-tour="detail-form"]')) return 'detail';
    if (document.querySelector('[data-tour="complete"]')) return 'complete';

    return null;
}

document.addEventListener('DOMContentLoaded', function () {
    const pageKey = detectPageKey();

    if (!pageKey) {
        return;
    }

    const steps = stepsFor(pageKey);

    if (steps.length === 0) {
        return;
    }

    const buttons = text().buttons || {};

    const startTour = () => {
        driver({
            showProgress: true,
            nextBtnText: buttons.next,
            prevBtnText: buttons.prev,
            doneBtnText: buttons.done,
            steps,
        }).drive();
    };

    document.getElementById('onboarding-tour-trigger')?.addEventListener('click', startTour);

    const seenKey = `onboarding-tour-seen-${pageKey}`;

    if (!localStorage.getItem(seenKey)) {
        localStorage.setItem(seenKey, '1');
        setTimeout(startTour, 400);
    }
});
