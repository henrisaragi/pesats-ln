import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

// "Take Tour" di dashboard: menjelaskan menu sidebar yang tampil untuk user ini. Item menu yang
// tidak diizinkan memang tidak dirender (lihat layouts/partials/menu.blade.php), jadi step tour
// cukup dibangun dari elemen [data-sidebar-tour] yang ada di DOM. Teks tombol berasal dari
// data-*-text pada tombol pemicu (diterjemahkan server-side via __()).

const html = document.documentElement;

// Layar kecil: sidebar berupa offcanvas yang tersembunyi - buka dulu lewat tombol toggle topbar
// supaya elemen menu bisa disorot.
function ensureSidebarVisible() {
    if (html.getAttribute('data-sidenav-size') === 'offcanvas' && !html.classList.contains('sidenav-enable')) {
        document.getElementById('button-toggle-menu')?.click();
    }
}

function buildSteps() {
    return Array.from(document.querySelectorAll('#app-menu [data-sidebar-tour]')).map((element) => ({
        element,
        popover: {
            title: element.dataset.tourTitle,
            description: element.dataset.tourDescription,
            side: 'right',
            align: 'start',
        },
    }));
}

document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.getElementById('dashboard-tour-trigger');

    if (!trigger) {
        return;
    }

    const steps = buildSteps();

    if (steps.length === 0) {
        trigger.classList.add('hidden');

        return;
    }

    trigger.addEventListener('click', () => {
        ensureSidebarVisible();

        driver({
            showProgress: true,
            nextBtnText: trigger.dataset.nextText,
            prevBtnText: trigger.dataset.prevText,
            doneBtnText: trigger.dataset.doneText,
            steps,
        }).drive();
    });
});
