import './bootstrap';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const createAddressMarker = (map, point) => L.marker(point, {
    draggable: true,
    icon: L.divIcon({
        className: 'address-map-marker',
        html: '<svg viewBox="0 0 32 40" aria-hidden="true"><path d="M16 1C7.72 1 1 7.72 1 16c0 10.55 15 23 15 23s15-12.45 15-23C31 7.72 24.28 1 16 1Z"/><circle cx="16" cy="15.5" r="5.25"/></svg>',
        iconSize: [32, 40],
        iconAnchor: [16, 39],
    }),
}).addTo(map);

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
    const locationButton = picker?.querySelector('[data-use-current-location]');
    const locationStatus = picker?.querySelector('[data-location-status]');
    const addressLookupStatus = picker?.querySelector('[data-address-lookup-status]');
    const reverseGeocodeUrl = picker?.querySelector('[data-reverse-geocode-url]')?.value;
    const addressForm = picker?.closest('form');
    const fullAddressInput = addressForm?.querySelector('[name="full_address"]');
    const labelInput = addressForm?.querySelector('[name="label"]');

    if (
        !latitudeInput
        || !longitudeInput
        || !locationButton
        || !locationStatus
        || !addressLookupStatus
        || !reverseGeocodeUrl
        || !fullAddressInput
    ) {
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
        ? createAddressMarker(map, [latitude, longitude])
        : null;
    let lookupController;
    let lookupTimeout;
    let lookupSequence = 0;

    const lookupAddress = (point) => {
        lookupController?.abort();
        window.clearTimeout(lookupTimeout);
        const currentSequence = ++lookupSequence;
        addressLookupStatus.classList.remove('text-rose-700', 'text-emerald-700');
        addressLookupStatus.textContent = 'Mencari alamat untuk titik ini…';

        lookupTimeout = window.setTimeout(async () => {
            lookupController = new AbortController();

            try {
                const response = await fetch(reverseGeocodeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        latitude: point.lat,
                        longitude: point.lng,
                    }),
                    signal: lookupController.signal,
                });
                const result = await response.json();

                if (currentSequence !== lookupSequence) {
                    return;
                }

                if (!response.ok) {
                    throw new Error(result.message || 'Alamat tidak dapat ditemukan.');
                }

                fullAddressInput.value = result.full_address;
                if (labelInput && !labelInput.value.trim() && result.label) {
                    labelInput.value = result.label;
                }
                addressLookupStatus.classList.add('text-emerald-700');
                addressLookupStatus.textContent = 'Alamat berhasil diisi otomatis. Silakan periksa kembali sebelum menyimpan.';
            } catch (error) {
                if (error.name === 'AbortError' || currentSequence !== lookupSequence) {
                    return;
                }

                addressLookupStatus.classList.add('text-rose-700');
                addressLookupStatus.textContent = error.message || 'Alamat tidak dapat ditemukan. Silakan isi secara manual.';
            }
        }, 1100);
    };

    const setCoordinates = (point) => {
        latitudeInput.value = point.lat.toFixed(7);
        longitudeInput.value = point.lng.toFixed(7);
    };

    const updateCoordinatesFromMarker = () => {
        const point = marker.getLatLng();
        setCoordinates(point);
        lookupAddress(point);
    };

    const placeMarker = (point) => {
        if (marker) {
            marker.setLatLng(point);
        } else {
            marker = createAddressMarker(map, point);
            marker.on('dragend', updateCoordinatesFromMarker);
        }

        setCoordinates(point);
        lookupAddress(point);
    };

    locationButton.addEventListener('click', () => {
        if (!navigator.geolocation) {
            locationStatus.textContent = 'Browser ini tidak mendukung pengambilan lokasi otomatis.';
            locationStatus.classList.add('text-rose-700');
            return;
        }

        locationButton.disabled = true;
        locationStatus.classList.remove('text-rose-700', 'text-emerald-700');
        locationStatus.textContent = 'Sedang mengambil lokasi perangkat…';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const point = L.latLng(position.coords.latitude, position.coords.longitude);
                placeMarker(point);
                map.setView(point, 17);
                locationButton.disabled = false;
                locationStatus.classList.remove('text-rose-700');
                locationStatus.classList.add('text-emerald-700');
                locationStatus.textContent = `Lokasi ditemukan (akurasi ±${Math.round(position.coords.accuracy)} m). Periksa marker sebelum menyimpan.`;
            },
            (error) => {
                const messages = {
                    [error.PERMISSION_DENIED]: 'Izin lokasi ditolak. Izinkan akses lokasi di browser, atau pilih titik pada peta.',
                    [error.POSITION_UNAVAILABLE]: 'Lokasi perangkat tidak tersedia. Periksa GPS atau koneksi, lalu coba lagi.',
                    [error.TIMEOUT]: 'Pengambilan lokasi terlalu lama. Coba lagi atau pilih titik pada peta.',
                };

                locationButton.disabled = false;
                locationStatus.classList.remove('text-emerald-700');
                locationStatus.classList.add('text-rose-700');
                locationStatus.textContent = messages[error.code] || 'Lokasi tidak dapat diambil. Pilih titik pada peta secara manual.';
            },
            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 15000,
            },
        );
    });

    if (marker) {
        marker.on('dragend', updateCoordinatesFromMarker);
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
                marker = createAddressMarker(map, point);
                marker.on('dragend', () => setCoordinates(marker.getLatLng()));
            }

            map.panTo(point);
            lookupAddress(point);
        }
    };

    latitudeInput.addEventListener('change', updateMarkerFromInputs);
    longitudeInput.addEventListener('change', updateMarkerFromInputs);
    container.dataset.mapInitialized = 'true';
    container._addressMap = map;
};

const initializeRouteMap = (container) => {
    let points;
    let geometry;

    try {
        points = JSON.parse(container.dataset.routeMapPoints || '[]');
        geometry = JSON.parse(container.dataset.routeMapGeometry || '[]');
    } catch (error) {
        console.error('Unable to parse OSRM route map data.', error);
        return;
    }

    const hasValidPoints = Array.isArray(points) && points.length > 0 && points.every((point) => (
        Number.isFinite(point.latitude)
        && point.latitude >= -90
        && point.latitude <= 90
        && Number.isFinite(point.longitude)
        && point.longitude >= -180
        && point.longitude <= 180
        && typeof point.label === 'string'
        && /^(D|[1-9]\d*)$/.test(point.label)
    ));

    if (!hasValidPoints) {
        console.error('OSRM route map received invalid waypoint coordinates.');
        return;
    }

    const map = L.map(container, {
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    points.forEach((point) => {
        const isDepot = point.label === 'D';
        const icon = L.divIcon({
            className: 'route-map-marker',
            html: `<span class="${isDepot ? 'route-map-marker-depot' : 'route-map-marker-stop'}">${point.label}</span>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        });

        L.marker([point.latitude, point.longitude], { icon })
            .bindTooltip(isDepot ? 'Depo' : `Pengantaran ${point.label}`)
            .addTo(map);
    });

    if (Array.isArray(geometry) && geometry.length >= 2) {
        const routeCoordinates = geometry.map(([longitude, latitude]) => [latitude, longitude]);
        const validGeometry = routeCoordinates.every(([latitude, longitude]) => (
            Number.isFinite(latitude)
            && latitude >= -90
            && latitude <= 90
            && Number.isFinite(longitude)
            && longitude >= -180
            && longitude <= 180
        ));

        if (!validGeometry) {
            console.error('OSRM route map received invalid route geometry.');
            map.remove();
            return;
        }

        const routeLine = L.polyline(routeCoordinates, {
            color: '#12377f',
            weight: 6,
            opacity: 0.85,
        }).addTo(map);

        const bounds = routeLine.getBounds();
        points.forEach((point) => bounds.extend([point.latitude, point.longitude]));
        map.fitBounds(bounds, { padding: [32, 32], maxZoom: 15 });
    } else {
        map.setView([points[0].latitude, points[0].longitude], 14);
    }
};

const initializeCourierRouteMap = (container) => {
    const destinationLatitude = Number.parseFloat(container.dataset.destinationLatitude);
    const destinationLongitude = Number.parseFloat(container.dataset.destinationLongitude);
    const routeButton = container.parentElement?.querySelector('[data-courier-route-button]');
    const status = container.parentElement?.querySelector('[data-courier-route-status]');

    if (
        !routeButton
        || !status
        || !container.dataset.routeUrl
        || !Number.isFinite(destinationLatitude)
        || !Number.isFinite(destinationLongitude)
        || Math.abs(destinationLatitude) > 90
        || Math.abs(destinationLongitude) > 180
    ) {
        console.error('Courier route map is missing valid destination data.');
        return;
    }

    const map = L.map(container, {
        scrollWheelZoom: false,
    }).setView([destinationLatitude, destinationLongitude], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const destination = L.circleMarker([destinationLatitude, destinationLongitude], {
        color: '#fff',
        fillColor: '#e05252',
        fillOpacity: 1,
        radius: 9,
        weight: 3,
    }).bindTooltip('Alamat tujuan').addTo(map);

    let courierMarker;
    let routeLine;

    routeButton.addEventListener('click', () => {
        if (!navigator.geolocation) {
            status.classList.add('text-rose-700');
            status.textContent = 'Browser ini tidak mendukung pengambilan lokasi. Buka alamat tujuan di aplikasi peta.';
            return;
        }

        routeButton.disabled = true;
        status.classList.remove('text-rose-700', 'text-emerald-700');
        status.textContent = 'Mengambil lokasi kurir saat ini…';

        navigator.geolocation.getCurrentPosition(async (position) => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            const currentLocation = L.latLng(latitude, longitude);
            courierMarker?.remove();
            courierMarker = L.circleMarker(currentLocation, {
                color: '#fff',
                fillColor: '#12377f',
                fillOpacity: 1,
                radius: 9,
                weight: 3,
            }).bindTooltip('Lokasi kurir').addTo(map);

            try {
                status.textContent = 'Menghitung rute jalan ke alamat tujuan…';
                const response = await fetch(container.dataset.routeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ latitude, longitude }),
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Rute jalan tidak dapat dihitung.');
                }

                if (
                    !Array.isArray(result.geometry)
                    || result.geometry.length < 2
                    || !Number.isFinite(result.distance_meters)
                    || !Number.isFinite(result.duration_seconds)
                ) {
                    throw new Error('Respons rute dari server tidak valid.');
                }

                const coordinates = result.geometry.map(([routeLongitude, routeLatitude]) => [
                    routeLatitude,
                    routeLongitude,
                ]);
                const validCoordinates = coordinates.every(([routeLatitude, routeLongitude]) => (
                    Number.isFinite(routeLatitude)
                    && Math.abs(routeLatitude) <= 90
                    && Number.isFinite(routeLongitude)
                    && Math.abs(routeLongitude) <= 180
                ));
                if (!validCoordinates) {
                    throw new Error('Koordinat rute dari server tidak valid.');
                }

                routeLine?.remove();
                routeLine = L.polyline(coordinates, {
                    color: '#12377f',
                    weight: 6,
                    opacity: 0.88,
                }).addTo(map);

                const bounds = routeLine.getBounds();
                bounds.extend(currentLocation);
                bounds.extend(destination.getLatLng());
                map.fitBounds(bounds, { padding: [32, 32], maxZoom: 16 });
                status.classList.add('text-emerald-700');
                status.textContent = `Rute jalan: ${(result.distance_meters / 1000).toFixed(1)} km · sekitar ${Math.max(1, Math.round(result.duration_seconds / 60))} menit dari lokasi yang baru diambil.`;
            } catch (error) {
                status.classList.add('text-rose-700');
                status.textContent = error.message || 'Rute tidak dapat dihitung. Periksa koneksi OSRM lalu coba lagi.';
            } finally {
                routeButton.disabled = false;
            }
        }, (error) => {
            const messages = {
                [error.PERMISSION_DENIED]: 'Izin lokasi ditolak. Izinkan akses lokasi browser untuk menghitung rute.',
                [error.POSITION_UNAVAILABLE]: 'Lokasi kurir tidak tersedia. Periksa GPS atau koneksi lalu coba lagi.',
                [error.TIMEOUT]: 'Pengambilan lokasi terlalu lama. Coba lagi.',
            };
            status.classList.add('text-rose-700');
            status.textContent = messages[error.code] || 'Lokasi kurir tidak dapat diambil.';
            routeButton.disabled = false;
        }, {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 15000,
        });
    });
};

document.querySelectorAll('[data-address-map]').forEach((container) => {
    initializeAddressMap(container);

    const details = container.closest('details');
    details?.addEventListener('toggle', () => initializeAddressMap(container));
});

document.querySelectorAll('[data-route-map]').forEach(initializeRouteMap);
document.querySelectorAll('[data-courier-route-map]').forEach(initializeCourierRouteMap);
