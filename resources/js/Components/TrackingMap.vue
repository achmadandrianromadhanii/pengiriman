<script setup>
    import { onMounted, onUnmounted, ref, computed, nextTick } from 'vue';

    const props = defineProps({
        kotaAsal: { type: Object, required: true },
        kotaTujuan: { type: Object, required: true },
        status: { type: String, required: true },
        pengirim: { type: String, required: true },
        penerima: { type: String, required: true },
    });

    const mapRef = ref(null);

    let map = null;
    let truckMarker = null;
    let animInterval = null;

    function toNumber(v) {
        if (v === null || v === undefined) return null;
        const s = String(v).trim().replace(',', '.'); // handle "-6,2088"
        if (!s) return null;
        const n = Number.parseFloat(s);
        return Number.isFinite(n) ? n : null;
    }

    function safeLatLng(obj) {
        // support key latitude/longitude atau lat/lng (biar fleksibel)
        const lat = toNumber(obj?.latitude ?? obj?.lat);
        const lng = toNumber(obj?.longitude ?? obj?.lng ?? obj?.lon);

        // 0,0 biasanya data salah → anggap invalid
        if (lat === 0 || lng === 0) return { lat: null, lng: null };
        return { lat, lng };
    }

    const asal = computed(() => safeLatLng(props.kotaAsal));
    const tujuan = computed(() => safeLatLng(props.kotaTujuan));

    const hasAsal = computed(() => asal.value.lat !== null && asal.value.lng !== null);
    const hasTujuan = computed(() => tujuan.value.lat !== null && tujuan.value.lng !== null);

    const asalName = computed(
        () => props.kotaAsal?.nama ?? props.kotaAsal?.nama_kota ?? 'Kota Asal',
    );
    const tujuanName = computed(
        () => props.kotaTujuan?.nama ?? props.kotaTujuan?.nama_kota ?? 'Kota Tujuan',
    );

    const mapWarning = computed(() => {
        if (hasAsal.value && hasTujuan.value) return '';
        if (!hasAsal.value && !hasTujuan.value)
            return 'Koordinat kota asal & tujuan belum valid. Peta ditampilkan tanpa pin.';
        if (!hasAsal.value)
            return 'Koordinat kota asal belum valid. Peta ditampilkan tanpa pin asal.';
        return 'Koordinat kota tujuan belum valid. Peta ditampilkan tanpa pin tujuan.';
    });

    onMounted(async () => {
        // [UPDATE: LEAFLET CSS LAZY-LOAD]
        // Fungsi: Memuat CSS Leaflet hanya di halaman yang membutuhkan peta.
        // Alasan: Sebelumnya leaflet.css (~15KB) dimuat di SETIAP halaman via app.css global.
        //         Sekarang hanya dimuat saat TrackingMap di-render (halaman Tracking saja).
        // Hasil: Semua halaman lain (Dashboard, Pengiriman, dsb) lebih ringan 15KB CSS.
        await import('leaflet/dist/leaflet.css');
        const L = (await import('leaflet')).default;
        await nextTick();

        // Map selalu dibuat (biar tidak blank)
        map = L.map(mapRef.value, { zoomControl: true });

        // Menggunakan Tile Premium CartoDB Voyager agar terlihat berkelas, elegan, dan mirip aplikasi modern
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap & CartoDB',
            maxZoom: 18,
        }).addTo(map);

        const indonesiaCenter = [-2.5, 118.0];

        if (hasAsal.value && hasTujuan.value) {
            map.fitBounds(
                [
                    [asal.value.lat, asal.value.lng],
                    [tujuan.value.lat, tujuan.value.lng],
                ],
                { padding: [60, 60] },
            );
        } else if (hasAsal.value) {
            map.setView([asal.value.lat, asal.value.lng], 10);
        } else if (hasTujuan.value) {
            map.setView([tujuan.value.lat, tujuan.value.lng], 10);
        } else {
            map.setView(indonesiaCenter, 4);
        }

        // penting untuk transisi inertia/animasi
        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 150);

        // Marker asal (Desain Dot Elegan)
        if (hasAsal.value) {
            const iconAsal = L.divIcon({
                className: 'custom-icon',
                html: '<div style="background-color: #10B981; width: 22px; height: 22px; border-radius: 50%; border: 4px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.4);"></div>',
                iconSize: [22, 22],
                iconAnchor: [11, 11],
            });

            L.marker([asal.value.lat, asal.value.lng], { icon: iconAsal })
                .addTo(map)
                .bindPopup(`<b>${asalName.value}</b><br>Pengirim: ${props.pengirim}`);
        }

        // Marker tujuan (Desain Dot Elegan)
        if (hasTujuan.value) {
            const iconTujuan = L.divIcon({
                className: 'custom-icon',
                html: '<div style="background-color: #EF4444; width: 22px; height: 22px; border-radius: 50%; border: 4px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.4);"></div>',
                iconSize: [22, 22],
                iconAnchor: [11, 11],
            });

            L.marker([tujuan.value.lat, tujuan.value.lng], { icon: iconTujuan })
                .addTo(map)
                .bindPopup(`<b>${tujuanName.value}</b><br>Penerima: ${props.penerima}`);
        }

        // Garis Rute (Jalan Raya Nyata OSRM) + Micro Offset
        if (hasAsal.value && hasTujuan.value) {
            let latA = asal.value.lat;
            let lngA = asal.value.lng;
            let latB = tujuan.value.lat;
            let lngB = tujuan.value.lng;

            // [Trik Micro-Offset]: Jika kota asal & tujuan sama persis kordinatnya (Pengiriman Dalam Kota),
            // kita geser titik B sedikit (sekitar 3-5 KM) agar OSRM bisa menggambar jalan lokal.
            if (latA === latB && lngA === lngB) {
                latB += 0.035;
                lngB += 0.025;
            }

            const asalArr = [latA, lngA];
            const tujuanArr = [latB, lngB];

            try {
                // Ambil data rute jalan raya dari OSRM secara Asynchronous (Tanpa API Key, gratis & cepat)
                const response = await fetch(
                    `https://router.project-osrm.org/route/v1/driving/${lngA},${latA};${lngB},${latB}?overview=full&geometries=geojson`,
                );
                const data = await response.json();

                let routeCoordinates = [];
                if (data.routes && data.routes.length > 0) {
                    // GeoJSON dari OSRM formatnya [lng, lat], Leaflet butuh [lat, lng]
                    routeCoordinates = data.routes[0].geometry.coordinates.map((c) => [c[1], c[0]]);

                    // Gambar rute jalan raya biru meliuk-liuk
                    L.polyline(routeCoordinates, {
                        color: '#3B82F6', // Warna biru jalan tol
                        weight: 5,
                        opacity: 0.8,
                    }).addTo(map);
                } else {
                    // Fallback jika OSRM gagal (tarik garis putus-putus biasa)
                    routeCoordinates = [asalArr, tujuanArr];
                    L.polyline(routeCoordinates, {
                        color: '#6366F1',
                        dashArray: '10,8',
                        weight: 3,
                        opacity: 0.8,
                    }).addTo(map);
                }

                // Animasi Ikon Truk Murni Berdasarkan Status
                renderTruckBasedOnStatus(L, asalArr, tujuanArr, routeCoordinates);
            } catch (err) {
                console.error('OSRM Routing Error:', err);
                // Fallback aman
                const fallbackCoords = [asalArr, tujuanArr];
                L.polyline(fallbackCoords, {
                    color: '#6366F1',
                    dashArray: '10,8',
                    weight: 3,
                    opacity: 0.8,
                }).addTo(map);
                renderTruckBasedOnStatus(L, asalArr, tujuanArr, fallbackCoords);
            }
        }
    });

    function renderTruckBasedOnStatus(L, asalArr, tujuanArr, routeCoordinates) {
        const htmlTruk = `<div style="background-color: #4F46E5; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4); border: 2px solid white;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5v-7zm1.294 7.456A1.999 1.999 0 0 1 4.732 11h5.536a2.01 2.01 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456zM12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12v4zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg></div>`;
        const htmlCentang = `<div style="background-color: #10B981; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4); border: 2px solid white;"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg></div>`;

        const ikonTruk = L.divIcon({
            className: '',
            html: htmlTruk,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        });
        const ikonCentang = L.divIcon({
            className: '',
            html: htmlCentang,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        });

        if (['pending', 'diproses'].includes(props.status)) {
            truckMarker = L.marker(asalArr, { icon: ikonTruk }).addTo(map);
            return;
        }

        if (props.status === 'dalam_perjalanan') {
            // Truk berjalan menyusuri array kordinat jalan raya satu persatu
            truckMarker = L.marker(asalArr, { icon: ikonTruk }).addTo(map);

            if (routeCoordinates.length > 2) {
                let i = 0;
                // Kecepatan animasi disesuaikan dengan jumlah titik agar halus
                const speed = Math.max(50, 2000 / routeCoordinates.length);

                animInterval = setInterval(() => {
                    i = (i + 1) % routeCoordinates.length;
                    truckMarker.setLatLng(routeCoordinates[i]);
                }, speed);
            } else {
                // Fallback animasi lurus
                let t = 0;
                animInterval = setInterval(() => {
                    t = (t + 0.005) % 1;
                    const lat = asalArr[0] + (tujuanArr[0] - asalArr[0]) * t;
                    const lng = asalArr[1] + (tujuanArr[1] - asalArr[1]) * t;
                    truckMarker.setLatLng([lat, lng]);
                }, 100);
            }
            return;
        }

        if (
            ['tiba_di_kota_tujuan', 'sedang_diantar', 'gagal', 'dibatalkan'].includes(props.status)
        ) {
            truckMarker = L.marker(tujuanArr, { icon: ikonTruk }).addTo(map);
            return;
        }

        if (props.status === 'terkirim') {
            truckMarker = L.marker(tujuanArr, { icon: ikonCentang }).addTo(map);
            return;
        }

        truckMarker = L.marker(asalArr, { icon: ikonTruk }).addTo(map);
    }

    onUnmounted(() => {
        if (animInterval) clearInterval(animInterval);
        animInterval = null;

        if (map) {
            map.remove();
            map = null;
        }

        truckMarker = null;
    });
</script>

<template>
    <div>
        <div ref="mapRef" class="leaflet-map-container"></div>

        <div
            v-if="mapWarning"
            class="mt-3 text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900/40 rounded-xl p-3"
        >
            <i class="bi bi-exclamation-triangle-fill mr-2"></i>
            {{ mapWarning }}
        </div>
    </div>
</template>
