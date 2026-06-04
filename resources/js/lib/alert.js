// lib/alert.js
// [UPDATE: LAZY-LOAD SWEETALERT2]
// Fungsi: Menunda pemuatan SweetAlert2 (~50KB) hingga benar-benar dipanggil.
// Alasan: Sebelumnya library ini dimuat langsung di 10+ file (static import),
//         menyebabkan browser HP Android harus mengunduh + mengurai ~50KB JS
//         bahkan sebelum user melihat notifikasi apapun.
// Cara Kerja: Fungsi getSwal() menggunakan dynamic import() — hanya di-download
//             saat pertama kali ada toast/alert yang dipanggil. Setelah itu di-cache.
// Hasil: Halaman Login, Dashboard, dsb langsung tampil tanpa menunggu SweetAlert.

function isDark() {
    return document.documentElement.classList.contains('dark');
}

// Cache — SweetAlert hanya di-download 1x, lalu disimpan di memori
let _swal = null;
async function getSwal() {
    if (!_swal) {
        const mod = await import('sweetalert2');
        _swal = mod.default;
    }
    return _swal;
}

export async function fireToast(icon, title) {
    const Swal = await getSwal();
    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1800,
        timerProgressBar: true,
        didOpen: (t) => {
            t.addEventListener('mouseenter', Swal.stopTimer);
            t.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });
    return toast.fire({
        icon,
        title,
        background: isDark() ? '#0B1220' : '#ffffff',
        color: isDark() ? '#E5E7EB' : '#111827',
    });
}

export async function confirmAction({
    title = 'Konfirmasi',
    text = 'Lanjutkan aksi ini?',
    confirmText = 'Ya',
    cancelText = 'Batal',
    icon = 'question',
} = {}) {
    const Swal = await getSwal();
    const res = await Swal.fire({
        icon,
        title,
        text,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
        background: isDark() ? '#0B1220' : '#ffffff',
        color: isDark() ? '#E5E7EB' : '#111827',
    });
    return res.isConfirmed;
}

export async function confirmDelete(text = 'Data akan dihapus permanen.') {
    return confirmAction({
        icon: 'warning',
        title: 'Hapus data?',
        text,
        confirmText: 'Ya, hapus',
        cancelText: 'Batal',
    });
}

export async function confirmLogout() {
    return confirmAction({
        icon: 'warning',
        title: 'Logout?',
        text: 'Kamu akan keluar dari aplikasi.',
        confirmText: 'Ya, logout',
        cancelText: 'Batal',
    });
}
