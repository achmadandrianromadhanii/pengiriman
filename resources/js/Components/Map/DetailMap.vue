<template>
  <div class="relative w-full h-full min-h-[420px] rounded-2xl overflow-hidden shadow-inner bg-slate-100 dark:bg-slate-900">
    <!-- Map Canvas Container -->
    <div ref="mapContainer" class="w-full h-full min-h-[420px] z-0" />

    <!-- Compass (Top Right) like in image.png -->
    <div class="absolute top-4 right-4 z-[400] pointer-events-auto">
      <div class="w-10 h-10 rounded-full bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200/80 dark:border-slate-700/80 shadow-md flex flex-col items-center justify-center text-[10px] font-black text-slate-800 dark:text-slate-200 select-none">
        <span class="text-blue-600 leading-none">N</span>
        <div class="w-0.5 h-2 bg-gradient-to-b from-blue-600 to-rose-500 my-0.5"></div>
        <span class="text-slate-400 leading-none text-[8px]">S</span>
      </div>
    </div>

    <!-- Map Quick Action Controls (Bottom Right) -->
    <div class="absolute bottom-4 right-4 z-[400] flex items-center gap-2 pointer-events-auto">
      <button
        type="button"
        @click="fitRoute"
        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white/95 dark:bg-slate-900/95 hover:bg-white dark:hover:bg-slate-800 active:scale-95 backdrop-blur-md border border-slate-200/80 dark:border-slate-700/80 rounded-xl shadow-md transition-all cursor-pointer"
        title="Tampilkan Seluruh Rute"
      >
        <Maximize2 class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
        <span>Rute Penuh</span>
      </button>

      <button
        type="button"
        @click="focusCourier"
        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white/95 dark:bg-slate-900/95 hover:bg-white dark:hover:bg-slate-800 active:scale-95 backdrop-blur-md border border-slate-200/80 dark:border-slate-700/80 rounded-xl shadow-md transition-all cursor-pointer"
        title="Fokus Kurir Simulasi"
      >
        <Truck class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
        <span>Fokus Kurir</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { Maximize2, Truck } from 'lucide-vue-next';

const mapContainer = ref(null);
let L = null;
let mapInstance = null;
let routeLine = null;
let courierMarker = null;

// Exact city center coordinates of Semarang matching image.png
const originCoords = [-6.9890, 110.4225];   // Jl. Ahmad Yani (Pusat Sortir)
const courierCoords = [-6.9820, 110.4140];  // Jl. Pemuda (Kurir Aktif)
const destCoords = [-6.9960, 110.4270];     // Menuju Sleman / Simpang Selatan

function fitRoute() {
  if (!mapInstance) return;
  if (routeLine) {
    mapInstance.fitBounds(routeLine.getBounds(), { padding: [50, 50], maxZoom: 15 });
  } else {
    mapInstance.setView([-6.988, 110.420], 14);
  }
}

function focusCourier() {
  if (!mapInstance || !courierMarker) return;
  mapInstance.setView(courierCoords, 16, { animate: true });
}

onMounted(async () => {
  await import('leaflet/dist/leaflet.css');
  const leafletModule = await import('leaflet');
  L = leafletModule.default;

  if (!mapContainer.value) return;

  // Initialize Map focused on Semarang
  mapInstance = L.map(mapContainer.value, {
    center: [-6.986, 110.420],
    zoom: 14,
    zoomControl: false,
    scrollWheelZoom: true,
    attributionControl: false,
  });

  // CartoDB Voyager Tile Layer for crisp, elegant 2D streets, buildings and water
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
    subdomains: 'abcd',
  }).addTo(mapInstance);

  // ── 1. Origin Marker & Card (Pusat Sortir Semarang) ────────────────────────
  const originIcon = L.divIcon({
    className: 'custom-card-icon',
    html: `
      <div style="position: relative; display: flex; flex-direction: column; align-items: flex-start;">
        <!-- Card -->
        <div style="background: rgba(255,255,255,0.96); backdrop-filter: blur(8px); border-radius: 12px; padding: 8px 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid rgba(226,232,240,0.9); font-family: inherit; min-width: 155px; pointer-events: auto;">
          <div style="font-weight: 700; font-size: 12px; color: #0f172a; line-height: 1.2;">Pusat Sortir Semarang</div>
          <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Jl. Ahmad Yani No. 12</div>
          <span style="display: inline-block; margin-top: 5px; padding: 2px 8px; font-size: 9px; font-weight: 700; color: #ffffff; background: #10B981; border-radius: 6px; text-transform: uppercase;">Asal</span>
        </div>
        <!-- Dot Anchor with pulse -->
        <div style="position: relative; margin-top: 4px; margin-left: 14px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">
          <div style="position: absolute; width: 22px; height: 22px; background: rgba(16, 185, 129, 0.35); border-radius: 50%; animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
          <div style="width: 14px; height: 14px; background: #10B981; border: 2.5px solid #ffffff; border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"></div>
        </div>
      </div>
    `,
    iconSize: [180, 85],
    iconAnchor: [24, 75],
  });
  L.marker(originCoords, { icon: originIcon }).addTo(mapInstance);

  // ── 2. Courier Marker & Card (Posisi Kurir Simulasi) ──────────────────────
  const courierIcon = L.divIcon({
    className: 'custom-card-icon',
    html: `
      <div style="position: relative; display: flex; flex-direction: column; align-items: flex-start;">
        <!-- Dark Navy Card -->
        <div style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(8px); border-radius: 12px; padding: 8px 12px; box-shadow: 0 12px 28px -4px rgba(0,0,0,0.35); border: 1px solid rgba(51, 65, 85, 0.9); font-family: inherit; color: #ffffff; min-width: 165px; pointer-events: auto;">
          <div style="font-weight: 700; font-size: 12px; color: #ffffff; line-height: 1.2;">Posisi Kurir (Simulasi)</div>
          <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Jl. Pemuda</div>
          <div style="font-size: 10px; color: #cbd5e1;">Semarang, Jawa Tengah</div>
          <div style="margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; font-size: 9px; font-weight: 600; color: #93c5fd; background: rgba(30, 58, 138, 0.6); padding: 2px 7px; border-radius: 6px; border: 1px solid rgba(59, 130, 246, 0.4);">
            <svg style="width: 10px; height: 10px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>5 menit yang lalu</span>
          </div>
        </div>
        <!-- Blue Vehicle Circle Anchor -->
        <div style="position: relative; margin-top: 4px; margin-left: 20px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
          <div style="position: absolute; width: 32px; height: 32px; background: rgba(37, 99, 235, 0.35); border-radius: 50%; animation: pulse 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite;"></div>
          <div style="width: 26px; height: 26px; background: #2563EB; border: 2.5px solid #ffffff; border-radius: 50%; box-shadow: 0 4px 10px rgba(37,99,235,0.45); display: flex; align-items: center; justify-content: center; color: white;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="3" width="15" height="13"></rect>
              <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
              <circle cx="5.5" cy="18.5" r="2.5"></circle>
              <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
          </div>
        </div>
      </div>
    `,
    iconSize: [190, 110],
    iconAnchor: [36, 95],
  });
  courierMarker = L.marker(courierCoords, { icon: courierIcon }).addTo(mapInstance);

  // ── 3. Destination Marker & Card (Tujuan Pengiriman) ───────────────────────
  const destIcon = L.divIcon({
    className: 'custom-card-icon',
    html: `
      <div style="position: relative; display: flex; flex-direction: column; align-items: flex-start;">
        <!-- Card -->
        <div style="background: rgba(255,255,255,0.96); backdrop-filter: blur(8px); border-radius: 12px; padding: 8px 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid rgba(226,232,240,0.9); font-family: inherit; min-width: 165px; pointer-events: auto;">
          <div style="font-weight: 700; font-size: 12px; color: #0f172a; line-height: 1.2;">Tujuan Pengiriman</div>
          <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Jl. Kaliurang No. 45</div>
          <div style="font-size: 10px; color: #94a3b8;">Sleman, D.I. Yogyakarta</div>
          <span style="display: inline-block; margin-top: 5px; padding: 2px 8px; font-size: 9px; font-weight: 700; color: #ffffff; background: #EF4444; border-radius: 6px; text-transform: uppercase;">Tujuan</span>
        </div>
        <!-- Dot Anchor with pulse -->
        <div style="position: relative; margin-top: 4px; margin-left: 18px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">
          <div style="position: absolute; width: 22px; height: 22px; background: rgba(239, 68, 68, 0.35); border-radius: 50%; animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
          <div style="width: 14px; height: 14px; background: #EF4444; border: 2.5px solid #ffffff; border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"></div>
        </div>
      </div>
    `,
    iconSize: [190, 95],
    iconAnchor: [28, 85],
  });
  L.marker(destCoords, { icon: destIcon }).addTo(mapInstance);

  // ── 4. Landmarks matching image.png ────────────────────────────────────────
  function createLandmarkBadge(title, subtitle, color, iconSvg) {
    return L.divIcon({
      className: 'landmark-badge',
      html: `
        <div style="display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.92); backdrop-filter: blur(4px); padding: 3px 8px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); border: 1px solid rgba(226,232,240,0.8); font-family: inherit; font-size: 10px; white-space: nowrap; pointer-events: none;">
          <div style="width: 16px; height: 16px; border-radius: 4px; background: ${color}; display: flex; align-items: center; justify-content: center; color: white;">
            ${iconSvg}
          </div>
          <div>
            <div style="font-weight: 700; color: #1e293b; line-height: 1.1;">${title}</div>
            ${subtitle ? `<div style="font-size: 8.5px; color: #3b82f6; font-weight: 600;">${subtitle}</div>` : ''}
          </div>
        </div>
      `,
      iconSize: [120, 26],
      iconAnchor: [60, 13],
    });
  }

  // UNNES Semarang
  L.marker([-6.9780, 110.4280], {
    icon: createLandmarkBadge('UNNES', 'Semarang', '#3B82F6', '<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9L12 15L21 10.09V17H23V9M5 13.18V17.18L12 21L19 17.18V13.18L12 17L5 13.18Z"/></svg>')
  }).addTo(mapInstance);

  // Stasiun Semarang Tawang
  L.marker([-6.9740, 110.4370], {
    icon: createLandmarkBadge('Stasiun', 'Semarang Tawang', '#0284C7', '<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="3" width="16" height="14" rx="2"/><circle cx="8" cy="13" r="1.5"/><circle cx="16" cy="13" r="1.5"/><path d="M7 21L9 17H15L17 21"/></svg>')
  }).addTo(mapInstance);

  // RSUP Dr. Kariadi
  L.marker([-6.9830, 110.4320], {
    icon: createLandmarkBadge('RSUP Dr. Kariadi', '', '#EF4444', '<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M19 10.5H13.5V5H10.5V10.5H5V13.5H10.5V19H13.5V13.5H19V10.5Z"/></svg>')
  }).addTo(mapInstance);

  // DP Mall Semarang
  L.marker([-6.9840, 110.4130], {
    icon: createLandmarkBadge('DP Mall', 'Semarang', '#2563EB', '<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6H16.82C16.4 4.84 15.3 4 14 4H10C8.7 4 7.6 4.84 7.18 6H5C3.9 6 3 6.9 3 8V18C3 19.1 3.9 20 5 20H19C20.1 20 21 19.1 21 18V8C21 6.9 20.1 6 19 6ZM10 6H14C14.55 6 15 6.45 15 7V8H9V7C9 6.45 9.45 6 10 6Z"/></svg>')
  }).addTo(mapInstance);

  // Paragon Mall Semarang
  L.marker([-6.9890, 110.4180], {
    icon: createLandmarkBadge('Paragon Mall', 'Semarang', '#4F46E5', '<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6H16.82C16.4 4.84 15.3 4 14 4H10C8.7 4 7.6 4.84 7.18 6H5C3.9 6 3 6.9 3 8V18C3 19.1 3.9 20 5 20H19C20.1 20 21 19.1 21 18V8C21 6.9 20.1 6 19 6Z"/></svg>')
  }).addTo(mapInstance);

  // ── 5. Blue Route Polyline tracing the city grid ───────────────────────────
  const routeWaypoints = [
    originCoords,
    [-6.9875, 110.4210],
    [-6.9860, 110.4190],
    [-6.9835, 110.4160],
    courierCoords,
    [-6.9850, 110.4130],
    [-6.9880, 110.4150],
    [-6.9910, 110.4190],
    [-6.9935, 110.4230],
    destCoords
  ];

  // Outer glow line
  L.polyline(routeWaypoints, {
    color: '#93C5FD',
    weight: 8,
    opacity: 0.5,
  }).addTo(mapInstance);

  // Core sharp line
  routeLine = L.polyline(routeWaypoints, {
    color: '#2563EB',
    weight: 4.5,
    opacity: 0.95,
  }).addTo(mapInstance);

  // Fit bounds cleanly on mount
  setTimeout(() => {
    if (mapInstance) {
      mapInstance.invalidateSize();
      fitRoute();
    }
  }, 250);
});

onBeforeUnmount(() => {
  if (mapInstance) {
    mapInstance.remove();
    mapInstance = null;
  }
});
</script>

<style scoped>
:deep(.leaflet-pane) {
  z-index: 10 !important;
}
</style>
