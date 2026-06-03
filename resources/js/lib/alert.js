import Swal from 'sweetalert2';

function isDark() {
    return document.documentElement.classList.contains('dark');
}

export const toast = Swal.mixin({
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

export function fireToast(icon, title) {
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
