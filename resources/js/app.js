import './bootstrap';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const initializeAddressMap = (container) => {
    if (container.dataset.mapInitialized === 'true') {
        const map = container._addressMap;
        window.setTimeout(() => map?.invalidateSize(), 0);

        return;
    }

    const details = container.closest('details');

    if (details && !details.open) {
        return;
    }

    const picker = container.closest('[data-address-picker]');
    const latitudeInput = picker?.querySelector('input[name="latitude"]');
    const longitudeInput = picker?.querySelector('input[name="longitude"]');

    if (!latitudeInput || !longitudeInput) {
        return;
    }

    const defaultCenter = [-7.318, 112.768];
    const latitude = Number.parseFloat(latitudeInput.value);
    const longitude = Number.parseFloat(longitudeInput.value);
    const hasCoordinates = Number.isFinite(latitude) && Number.isFinite(longitude);
    const map = L.map(container, {
        scrollWheelZoom: false,
    }).setView(hasCoordinates ? [latitude, longitude] : defaultCenter, hasCoordinates ? 16 : 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    let marker = hasCoordinates
        ? L.marker([latitude, longitude], { draggable: true }).addTo(map)
        : null;

    const setCoordinates = (point) => {
        latitudeInput.value = point.lat.toFixed(7);
        longitudeInput.value = point.lng.toFixed(7);
    };

    const placeMarker = (point) => {
        if (marker) {
            marker.setLatLng(point);
        } else {
            marker = L.marker(point, { draggable: true }).addTo(map);
            marker.on('dragend', () => setCoordinates(marker.getLatLng()));
        }

        setCoordinates(point);
    };

    if (marker) {
        marker.on('dragend', () => setCoordinates(marker.getLatLng()));
    }

    map.on('click', (event) => placeMarker(event.latlng));

    const updateMarkerFromInputs = () => {
        const nextLatitude = Number.parseFloat(latitudeInput.value);
        const nextLongitude = Number.parseFloat(longitudeInput.value);

        if (
            Number.isFinite(nextLatitude)
            && Number.isFinite(nextLongitude)
            && nextLatitude >= -90
            && nextLatitude <= 90
            && nextLongitude >= -180
            && nextLongitude <= 180
        ) {
            const point = L.latLng(nextLatitude, nextLongitude);

            if (marker) {
                marker.setLatLng(point);
            } else {
                marker = L.marker(point, { draggable: true }).addTo(map);
                marker.on('dragend', () => setCoordinates(marker.getLatLng()));
            }

            map.panTo(point);
        }
    };

    latitudeInput.addEventListener('change', updateMarkerFromInputs);
    longitudeInput.addEventListener('change', updateMarkerFromInputs);
    container.dataset.mapInitialized = 'true';
    container._addressMap = map;
};

document.querySelectorAll('[data-address-map]').forEach((container) => {
    initializeAddressMap(container);

    const details = container.closest('details');
    details?.addEventListener('toggle', () => initializeAddressMap(container));
});
