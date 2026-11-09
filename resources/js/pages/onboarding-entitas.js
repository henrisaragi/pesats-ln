// Fills the onboarding "Data Entitas" latitude/longitude inputs from the browser
// Geolocation API. Status messages are translated server-side via data-* attributes.

const button = document.getElementById('onboarding-entitas-locate');
const latitude = document.getElementById('onboarding-entitas-latitude');
const longitude = document.getElementById('onboarding-entitas-longitude');
const status = document.getElementById('onboarding-entitas-locate-status');

function setStatus(key, isError = false) {
    status.textContent = status.dataset[key];
    status.classList.toggle('text-danger', isError);
    status.classList.toggle('text-default-500', !isError);
}

if (button && latitude && longitude && status) {
    button.addEventListener('click', () => {
        if (!('geolocation' in navigator)) {
            setStatus('unsupported', true);
            return;
        }

        button.disabled = true;
        setStatus('locating');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitude.value = position.coords.latitude.toFixed(7);
                longitude.value = position.coords.longitude.toFixed(7);
                setStatus('success');
                button.disabled = false;
            },
            () => {
                setStatus('denied', true);
                button.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
    });
}
