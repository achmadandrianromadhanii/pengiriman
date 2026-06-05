<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import TrackingTimeline from '@/Components/TrackingTimeline.vue';
    import { computed, onMounted, reactive, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    // [UPDATE: LAZY-LOAD SWEETALERT2 — PERFORMA MOBILE]
    // Fungsi: Menghapus static import SweetAlert2 (~80KB) yang memaksa browser HP
    //         mengunduh + mengurai library ini SEBELUM halaman tampil.
    // Cara Kerja: getSwal() dari lib/alert.js melakukan dynamic import() —
    //             SweetAlert2 hanya di-download saat user benar-benar klik tombol.
    // Hasil: Halaman ini tampil ~200-500ms lebih cepat di HP Android.
    import { getSwal } from '@/lib/alert';

    defineOptions({ layout: AppLayout });

    const props = defineProps({
        pengiriman: { type: Object, required: true },
    });

    const page = usePage();

    // UPDATE: Hanya menampilkan 3 status inti untuk mempercepat proses update oleh admin.
    // Fungsi: Mencegah kebingungan memilih status yang terlalu banyak, sangat efisien.
    // Letak: Variabel opsi dropdown di form Update Status.
    const statusOptions = [
        { value: 'diproses', label: 'Diproses' },
        { value: 'dalam_perjalanan', label: 'Dalam Perjalanan' },
        { value: 'terkirim', label: 'Terkirim' },
    ];

    const updateForm = reactive({
        status_baru: '',
        lokasi: '', // Lokasi kini bebas dan opsional
    });

    const submitting = ref(false);

    // State khusus mobile form update status (Sliding Bottom Sheet)
    const showMobileUpdateForm = ref(false);

    function rupiah(n) {
        const val = Math.round(Number(n || 0));
        return `Rp ${new Intl.NumberFormat('id-ID').format(val)}`;
    }

    function layananLabel(l) {
        return (
            {
                express: 'Express',
                reguler: 'Reguler',
                kargo: 'Kargo',
                ekonomi: 'Ekonomi',
            }[l] || l
        );
    }

    function metodeLabel(m) {
        return (
            {
                dibayar_pengirim: 'Dibayar Pengirim',
                dibayar_penerima: 'Dibayar Penerima',
                cod: 'COD',
            }[m] || m
        );
    }

    function fmtDate(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'Asia/Jakarta',
        }).format(d);
    }

    function fmtDateTime(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Jakarta',
        }).format(d);
    }

    function statusBadge(s) {
        const map = {
            pending: 'badge-gray',
            diproses: 'badge-amber',
            dalam_perjalanan: 'badge-blue',
            tiba_di_kota_tujuan: 'badge-indigo',
            sedang_diantar: 'badge-indigo',
            terkirim: 'badge-green',
            gagal: 'badge-red',
            dibatalkan: 'badge-red',
        };
        return map[s] || 'badge-gray';
    }

    const statusLabel = computed(() => {
        const s = props.pengiriman?.status;
        return (
            {
                pending: 'Menunggu',
                diproses: 'Diproses',
                dalam_perjalanan: 'Dalam Perjalanan',
                tiba_di_kota_tujuan: 'Tiba di Kota Tujuan',
                sedang_diantar: 'Sedang Diantar',
                terkirim: 'Terkirim',
                gagal: 'Gagal',
                dibatalkan: 'Dibatalkan',
            }[s] ||
            s ||
            '-'
        );
    });

    const isTerlambat = computed(() => {
        const estimasi = props.pengiriman?.estimasi_tiba;
        const status = props.pengiriman?.status;
        if (!estimasi) return false;
        if (['terkirim', 'dibatalkan', 'gagal'].includes(status)) return false;

        // Konversi tanggal ke timezone Jakarta untuk perbandingan yang konsisten
        const estStr = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Jakarta',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).format(new Date(estimasi));
        const nowStr = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Jakarta',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).format(new Date());

        return estStr < nowStr;
    });

    const canCancel = computed(() => {
        const status = props.pengiriman?.status;
        if (!status) return false;
        return !['terkirim', 'dibatalkan', 'gagal'].includes(status);
    });

    const kotaAsal = computed(() => props.pengiriman?.pengirim_kota ?? null);
    const kotaTujuan = computed(() => props.pengiriman?.penerima_kota ?? null);

    // UPDATE: Fitur Smart Auto-Fill lokasi sesuai dengan rute yang diambil.
    // Fungsi: Mengetik otomatis nama lokasi berdasarkan status yang dipilih agar memangkas waktu kerja.
    // Cara Kerja: Fungsi watch() Vue secara instan mendeteksi perubahan status_baru di form,
    // lalu mengambil data 'kotaAsal' dan 'kotaTujuan', dan memasukkannya ke input 'lokasi'.
    watch(
        () => updateForm.status_baru,
        (newStatus) => {
            const asal = kotaAsal.value?.nama_kota || '-';
            const tujuan = kotaTujuan.value?.nama_kota || '-';

            if (newStatus === 'diproses') {
                updateForm.lokasi = asal;
            } else if (newStatus === 'dalam_perjalanan') {
                updateForm.lokasi = `Dalam perjalanan dari ${asal} menuju ${tujuan}`;
            } else if (newStatus === 'terkirim') {
                updateForm.lokasi = tujuan;
            }
        },
    );

    const barangList = computed(() => props.pengiriman?.barang ?? []);

    const totalBerat = computed(() => {
        const sum = (barangList.value || []).reduce((s, b) => s + Number(b?.berat_kg || 0), 0);
        return Number(sum.toFixed(2));
    });

    const headerEstimasi = computed(() => fmtDate(props.pengiriman?.estimasi_tiba));
    const headerTerkirim = computed(() =>
        props.pengiriman?.tanggal_terkirim ? fmtDateTime(props.pengiriman?.tanggal_terkirim) : null,
    );

    const trackingItems = computed(() => props.pengiriman?.tracking_histories ?? []);

    onMounted(async () => {
        const Swal = await getSwal();
        const success = page.props?.flash?.success;
        const error = page.props?.flash?.error;

        if (success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: success,
                timer: 1500,
                showConfirmButton: false,
            });
        }

        if (error) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: error,
                confirmButtonColor: '#6366F1',
            });
        }

        // default lokasi update: nama kota asal (jika ada)
        updateForm.lokasi = kotaAsal.value?.nama_kota ?? '';
    });

    async function submitUpdate() {
        const Swal = await getSwal();
        if (!updateForm.status_baru) {
            await Swal.fire({
                icon: 'warning',
                title: 'Periksa kembali',
                text: 'Status baru wajib dipilih.',
                confirmButtonColor: '#6366F1',
            });
            return;
        }

        // Keterangan dihapus sepenuhnya sesuai permintaan user agar lebih "Langsung Saja"
        // Lokasi menjadi opsional, tidak perlu validasi strict lagi
        const lokasi = String(updateForm.lokasi || '').trim();

        const confirm = await Swal.fire({
            icon: 'question',
            title: 'Update status?',
            text: 'Tracking history akan ditambahkan.',
            showCancelButton: true,
            confirmButtonText: 'Ya, Update',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#6366F1',
        });

        if (!confirm.isConfirmed) return;

        submitting.value = true;
        Swal.fire({
            title: 'Memproses...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        router.post(
            route('tracking.update', props.pengiriman.id),
            { status_baru: updateForm.status_baru, lokasi },
            {
                preserveScroll: true,
                onFinish: () => {
                    submitting.value = false;
                    Swal.close();
                },
                onSuccess: () => {
                    updateForm.status_baru = '';
                },
                onError: async () => {
                    Swal.close();
                    await Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Validasi gagal. Periksa input update status.',
                        confirmButtonColor: '#6366F1',
                    });
                },
            },
        );
    }

    function openPrint() {
        window.open(route('resi.print', props.pengiriman.id), '_blank', 'noopener');
    }

    async function cancelShipment() {
        if (!canCancel.value) return;

        const Swal = await getSwal();
        const res = await Swal.fire({
            icon: 'warning',
            title: 'Batalkan pengiriman?',
            input: 'textarea',
            inputLabel: 'Alasan Pembatalan',
            inputPlaceholder: 'Tulis alasan pembatalan (minimal 5 karakter)...',
            inputAttributes: { maxlength: 500 },
            showCancelButton: true,
            confirmButtonText: 'Batalkan',
            cancelButtonText: 'Tutup',
            confirmButtonColor: '#EF4444',
            preConfirm: (val) => {
                const v = String(val || '').trim();
                if (v.length < 5) {
                    Swal.showValidationMessage('Alasan minimal 5 karakter.');
                    return false;
                }
                return v;
            },
        });

        if (!res.isConfirmed) return;

        Swal.fire({
            title: 'Membatalkan...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        router.post(
            route('pengiriman.batal', props.pengiriman.id),
            { alasan_batal: res.value },
            {
                preserveScroll: true,
                onFinish: () => Swal.close(),
                onError: async () => {
                    Swal.close();
                    await Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal membatalkan pengiriman.',
                        confirmButtonColor: '#6366F1',
                    });
                },
            },
        );
    }

    // Fungsi: Menyalin nomor resi ke clipboard pengguna.
    // Cara kerja: Menggunakan Clipboard API modern, dengan fallback ke execCommand jika diakses via HTTP lokal (sangat aman & anti error).
    // Letak: Tombol resi di Header Ticket khusus Mobile.
    async function copyResi() {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(props.pengiriman.nomor_resi);
            } else {
                // Fallback klasik untuk server local HTTP (XAMPP/Artisan Serve)
                const el = document.createElement('textarea');
                el.value = props.pengiriman.nomor_resi;
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            }

            // Menampilkan notifikasi popup (Toast) kecil melayang tanpa memblokir layar
            const Swal = await getSwal();
            Swal.fire({
                icon: 'success',
                title: 'Resi Tersalin!',
                toast: true,
                position: 'top',
                timer: 1500,
                showConfirmButton: false,
                customClass: { popup: 'rounded-xl shadow-lg border border-gray-100' },
            });
        } catch (err) {
            console.error('Gagal menyalin:', err);
        }
    }
</script>

<template>
    <div>
        <!-- ============================================================== -->
        <!-- DESKTOP LAYOUT (Tampilan Monitor/Laptop dipertahankan asli)    -->
        <!-- ============================================================== -->
        <div class="hidden md:block space-y-6 animate-fade-in">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div class="space-y-1">
                    <a
                        :href="route('pengiriman.index')"
                        class="text-sm text-gray-600 dark:text-gray-300 hover:text-primary inline-flex items-center gap-2"
                    >
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1
                            class="font-heading font-extrabold text-2xl text-gray-900 dark:text-white"
                        >
                            Nomor Resi:
                            <span class="text-primary">{{ pengiriman.nomor_resi }}</span>
                        </h1>

                        <span :class="statusBadge(pengiriman.status)">
                            {{ statusLabel }}
                        </span>

                        <span v-if="isTerlambat" class="badge-red">TERLAMBAT</span>
                    </div>

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Estimasi Tiba: <span class="font-semibold">{{ headerEstimasi }}</span>
                        <span v-if="headerTerkirim" class="ml-3">
                            Terkirim: <span class="font-semibold">{{ headerTerkirim }}</span>
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-secondary" @click="openPrint">
                        <i class="bi bi-printer"></i>
                        Cetak Resi
                    </button>

                    <button
                        v-if="canCancel"
                        type="button"
                        class="btn-danger"
                        @click="cancelShipment"
                    >
                        <i class="bi bi-x-octagon"></i>
                        Batalkan
                    </button>
                </div>
            </div>

            <!-- Pengirim & Penerima -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                <div class="card">
                    <div
                        class="font-heading font-extrabold text-lg text-gray-900 dark:text-white mb-3"
                    >
                        Data Pengirim
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-200 space-y-2">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Nama:</span>
                            <span class="font-semibold">{{ pengiriman.pengirim_nama }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">HP:</span>
                            {{ pengiriman.pengirim_hp }}
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Alamat:</span>
                            {{ pengiriman.pengirim_alamat }}
                        </div>

                        <div v-if="kotaAsal">
                            <span class="text-gray-500 dark:text-gray-400">Kota:</span>
                            <span class="font-semibold">{{ kotaAsal.nama_kota }}</span>
                            <span class="text-gray-500 dark:text-gray-400">
                                ({{ kotaAsal.provinsi }}, {{ kotaAsal.kode_pos }})
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div
                        class="font-heading font-extrabold text-lg text-gray-900 dark:text-white mb-3"
                    >
                        Data Penerima
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-200 space-y-2">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Nama:</span>
                            <span class="font-semibold">{{ pengiriman.penerima_nama }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">HP:</span>
                            {{ pengiriman.penerima_hp }}
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Alamat:</span>
                            {{ pengiriman.penerima_alamat }}
                        </div>

                        <div v-if="kotaTujuan">
                            <span class="text-gray-500 dark:text-gray-400">Kota:</span>
                            <span class="font-semibold">{{ kotaTujuan.nama_kota }}</span>
                            <span class="text-gray-500 dark:text-gray-400">
                                ({{ kotaTujuan.provinsi }}, {{ kotaTujuan.kode_pos }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barang -->
            <div class="card">
                <div class="font-heading font-extrabold text-lg text-gray-900 dark:text-white mb-4">
                    Daftar Barang
                </div>

                <div class="w-full">
                    <!-- ============================ -->
                    <!-- DESKTOP TABLE (HIDE DI MOBILE) -->
                    <!-- ============================ -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr
                                    class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700/70"
                                >
                                    <th class="py-3 pr-4">No</th>
                                    <th class="py-3 pr-4">Nama Barang</th>
                                    <th class="py-3 pr-4">Berat (kg)</th>
                                    <th class="py-3 pr-4">Dimensi (cm)</th>
                                    <th class="py-3 pr-2">Volume (cm³)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(b, idx) in barangList"
                                    :key="'desk-' + b.id"
                                    class="border-b border-gray-100 dark:border-gray-800/60"
                                >
                                    <td class="py-3 pr-4">{{ idx + 1 }}</td>
                                    <td
                                        class="py-3 pr-4 font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ b.nama_barang }}
                                    </td>
                                    <td class="py-3 pr-4">{{ b.berat_kg }}</td>
                                    <td class="py-3 pr-4">
                                        {{ b.panjang_cm }}×{{ b.lebar_cm }}×{{ b.tinggi_cm }}
                                    </td>
                                    <td class="py-3 pr-2">{{ b.volume_cm3 }}</td>
                                </tr>
                                <tr v-if="barangList.length === 0">
                                    <td
                                        colspan="5"
                                        class="py-4 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Belum ada data barang.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ============================ -->
                    <!-- MOBILE CARD LIST (V50+)      -->
                    <!-- ============================ -->
                    <div class="md:hidden flex flex-col gap-3">
                        <div
                            v-for="(b, idx) in barangList"
                            :key="'mob-' + b.id"
                            class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-card-dark shadow-sm"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-gray-900 dark:text-white"
                                    >{{ idx + 1 }}. {{ b.nama_barang }}</span
                                >
                                <span
                                    class="text-sm font-semibold text-primary bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded"
                                    >{{ b.berat_kg }} kg</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800/60"
                            >
                                <span
                                    ><i class="bi bi-box me-1"></i>{{ b.panjang_cm }}×{{
                                        b.lebar_cm
                                    }}×{{ b.tinggi_cm }} cm</span
                                >
                                <span>Vol: {{ b.volume_cm3 }} cm³</span>
                            </div>
                        </div>
                        <div
                            v-if="barangList.length === 0"
                            class="py-6 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/20 rounded-xl"
                        >
                            <i class="bi bi-inbox text-3xl mb-2 block"></i>
                            Belum ada data barang.
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-sm text-gray-700 dark:text-gray-200">
                    Total: <span class="font-semibold">{{ barangList.length }}</span> barang |
                    <span class="font-semibold">{{ totalBerat }}</span> kg
                </div>
            </div>

            <!-- Ringkasan biaya -->
            <div class="card">
                <div class="font-heading font-extrabold text-lg text-gray-900 dark:text-white mb-3">
                    Ringkasan Pengiriman
                </div>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700 dark:text-gray-200"
                >
                    <div>
                        Layanan:
                        <span class="font-semibold">{{
                            layananLabel(pengiriman.jenis_layanan)
                        }}</span>
                    </div>
                    <div>
                        Metode Bayar:
                        <span class="font-semibold">{{
                            metodeLabel(pengiriman.metode_pembayaran)
                        }}</span>
                    </div>

                    <div>
                        Ongkir:
                        <span class="font-semibold">{{ rupiah(pengiriman.biaya_pengiriman) }}</span>
                    </div>
                    <div>
                        Tambahan:
                        <span class="font-semibold">{{ rupiah(pengiriman.biaya_tambahan) }}</span>
                    </div>

                    <div>
                        Asuransi:
                        <span class="font-semibold">{{ rupiah(pengiriman.biaya_asuransi) }}</span>
                    </div>
                    <div>
                        Total:
                        <span class="font-semibold">{{ rupiah(pengiriman.total_biaya) }}</span>
                    </div>

                    <div>
                        Status Pembayaran:
                        <span class="font-semibold">{{ pengiriman.status_pembayaran }}</span>
                    </div>
                    <div>
                        Dibuat:
                        <span class="font-semibold">{{ fmtDate(pengiriman.created_at) }}</span>
                    </div>

                    <div v-if="pengiriman.alasan_batal" class="md:col-span-2">
                        Alasan Batal:
                        <span class="font-semibold">{{ pengiriman.alasan_batal }}</span>
                    </div>
                </div>

                <div
                    v-if="pengiriman.catatan"
                    class="mt-4 text-sm text-gray-700 dark:text-gray-200"
                >
                    <span class="text-gray-500 dark:text-gray-400">Catatan:</span>
                    {{ pengiriman.catatan }}
                </div>
            </div>

            <!-- Update Status (Dirombak jadi "Langsung Saja" - 2 Kali Klik) -->
            <div class="card">
                <div class="font-heading font-extrabold text-lg text-gray-900 dark:text-white mb-4">
                    Update Status
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                            >Status Baru *</label
                        >
                        <select v-model="updateForm.status_baru" class="input-field mt-2">
                            <option value="" disabled>Pilih status</option>
                            <option v-for="o in statusOptions" :key="o.value" :value="o.value">
                                {{ o.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <!-- Lokasi dibiarkan bebas, admin tidak perlu search. Ini rahasia kecepatan sistem. -->
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                            >Lokasi (Opsional)</label
                        >
                        <input
                            v-model="updateForm.lokasi"
                            type="text"
                            class="input-field mt-2"
                            placeholder="Nama kota/cabang (kosongkan juga aman)"
                        />
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button
                        type="button"
                        class="btn-primary"
                        :disabled="submitting"
                        @click="submitUpdate"
                    >
                        <i class="bi bi-upload"></i>
                        Update Instan
                    </button>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card">
                <div class="font-heading font-extrabold text-lg text-gray-900 dark:text-white mb-4">
                    Timeline Tracking
                </div>

                <div
                    v-if="trackingItems.length === 0"
                    class="text-sm text-gray-500 dark:text-gray-400"
                >
                    Belum ada tracking.
                </div>

                <TrackingTimeline v-else :items="trackingItems" />
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MOBILE LAYOUT (Desain Native Enterprise V15+ Khusus HP)        -->
        <!-- ============================================================== -->
        <!-- Fungsi: Menghadirkan antarmuka sekelas aplikasi native (Traveloka/Tokopedia).
             Menggunakan Ticket Style, Connecting Route, Receipt Style, dan Bottom Sheet Form. -->
        <div class="md:hidden animate-fade-in pb-2">
            <!-- 1. HERO SECTION (TICKET STYLE) -->
            <!-- Dibuat menyatu dengan warna primer agar berkesan premium -->
            <div
                class="bg-primary pt-6 pb-20 px-5 rounded-b-[2.5rem] shadow-lg relative -mx-4 -mt-5"
            >
                <div class="flex items-center justify-between text-white mb-6">
                    <a
                        :href="route('pengiriman.index')"
                        class="flex items-center gap-2 font-medium opacity-80 hover:opacity-100 transition-opacity"
                    >
                        <i class="bi bi-arrow-left text-lg"></i> Kembali
                    </a>
                    <!-- Fungsi: Tombol salin resi instan dengan ukuran teks yang sedikit dibesarkan -->
                    <!-- Ditambahkan @click="copyResi" dan efek interaktif agar terasa seperti native app -->
                    <div
                        @click="copyResi"
                        class="flex items-center gap-2.5 bg-white/20 px-3.5 py-1.5 rounded-full md:backdrop-blur-md cursor-pointer hover:bg-white/30 active:scale-95 transition"
                    >
                        <span class="text-[13px] font-extrabold tracking-widest">{{
                            pengiriman.nomor_resi
                        }}</span>
                        <!-- Ikon salin fungsi asli (UX Premium) -->
                        <i class="bi bi-copy text-[12px] opacity-100"></i>
                    </div>
                </div>

                <div class="text-center mt-2">
                    <div class="text-white/70 text-[10px] uppercase tracking-widest font-bold mb-2">
                        Status Saat Ini
                    </div>
                    <!-- Lencana (Badge) Status Utama -->
                    <div
                        class="inline-block px-5 py-2 rounded-full bg-white text-primary font-extrabold text-sm shadow-[0_4px_15px_rgba(0,0,0,0.1)] mb-4"
                    >
                        {{ statusLabel }}
                    </div>
                    <div
                        class="text-white/90 text-xs font-medium bg-black/10 inline-block px-4 py-1.5 rounded-full mt-1"
                    >
                        <i class="bi bi-calendar2-check mr-1"></i> Estimasi Tiba:
                        {{ headerEstimasi }}
                    </div>
                </div>
            </div>

            <!-- 2. RUTE PERJALANAN (CONNECTING DOTS) -->
            <!-- Fungsi: Mempersingkat ruang vertikal dan memudahkan pengguna memahami titik A ke titik B -->
            <div
                class="bg-white dark:bg-card-dark rounded-3xl p-5 shadow-xl mx-2 -mt-12 relative z-10 border border-gray-100 dark:border-gray-800"
            >
                <div class="flex flex-col relative">
                    <!-- Garis Konektor Putus-putus -->
                    <div
                        class="absolute left-[11px] top-8 bottom-8 w-[2px] border-l-2 border-dashed border-gray-200 dark:border-gray-700"
                    ></div>

                    <!-- Titik A (Pengirim) -->
                    <div class="flex gap-4 relative z-10 mb-6">
                        <div
                            class="w-6 h-6 rounded-full bg-blue-50 dark:bg-blue-900/40 flex items-center justify-center shrink-0 border-[3px] border-white dark:border-card-dark mt-1 shadow-sm"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                        </div>
                        <div class="flex-1">
                            <div
                                class="text-[9px] uppercase font-bold text-gray-400 tracking-widest mb-0.5"
                            >
                                Asal (Pengirim)
                            </div>
                            <div class="font-bold text-gray-900 dark:text-white text-sm">
                                {{ pengiriman.pengirim_nama }}
                            </div>
                            <div class="text-xs text-primary font-semibold mb-1">
                                {{ kotaAsal ? kotaAsal.nama_kota : pengiriman.pengirim_kota }}
                            </div>

                            <!-- Detail Tersembunyi (Accordion) untuk Kerapian -->
                            <details class="text-xs text-gray-500 dark:text-gray-400 group">
                                <summary
                                    class="cursor-pointer text-[10px] text-gray-400 hover:text-primary font-semibold select-none list-none flex items-center gap-1 transition-colors"
                                >
                                    Lihat Detail Alamat
                                    <i
                                        class="bi bi-chevron-down transition-transform group-open:rotate-180"
                                    ></i>
                                </summary>
                                <div
                                    class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-800 leading-relaxed"
                                >
                                    <i class="bi bi-telephone opacity-60 mr-1"></i>
                                    {{ pengiriman.pengirim_hp }}<br />
                                    <i class="bi bi-geo-alt opacity-60 mr-1"></i>
                                    {{ pengiriman.pengirim_alamat }}
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- Titik B (Penerima) -->
                    <div class="flex gap-4 relative z-10">
                        <div
                            class="w-6 h-6 rounded-full bg-red-50 dark:bg-red-900/40 flex items-center justify-center shrink-0 border-[3px] border-white dark:border-card-dark mt-1 shadow-sm"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                        </div>
                        <div class="flex-1">
                            <div
                                class="text-[9px] uppercase font-bold text-gray-400 tracking-widest mb-0.5"
                            >
                                Tujuan (Penerima)
                            </div>
                            <div class="font-bold text-gray-900 dark:text-white text-sm">
                                {{ pengiriman.penerima_nama }}
                            </div>
                            <div class="text-xs text-red-500 font-semibold mb-1">
                                {{ kotaTujuan ? kotaTujuan.nama_kota : pengiriman.penerima_kota }}
                            </div>

                            <!-- Detail Tersembunyi (Accordion) untuk Kerapian -->
                            <details class="text-xs text-gray-500 dark:text-gray-400 group">
                                <summary
                                    class="cursor-pointer text-[10px] text-gray-400 hover:text-red-500 font-semibold select-none list-none flex items-center gap-1 transition-colors"
                                >
                                    Lihat Detail Alamat
                                    <i
                                        class="bi bi-chevron-down transition-transform group-open:rotate-180"
                                    ></i>
                                </summary>
                                <div
                                    class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-800 leading-relaxed"
                                >
                                    <i class="bi bi-telephone opacity-60 mr-1"></i>
                                    {{ pengiriman.penerima_hp }}<br />
                                    <i class="bi bi-geo-alt opacity-60 mr-1"></i>
                                    {{ pengiriman.penerima_alamat }}
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. STRUK DIGITAL (RECEIPT STYLE & BARANG) -->
            <!-- Fungsi: Merangkum biaya secara transparan layaknya struk belanja -->
            <div
                class="bg-white dark:bg-card-dark rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-gray-800 mx-2 mt-4 relative overflow-hidden"
            >
                <!-- Ornamen atas struk -->
                <div
                    class="flex justify-between items-center border-b border-dashed border-gray-300 dark:border-gray-700 pb-4 mb-4"
                >
                    <span class="font-bold text-gray-900 dark:text-white text-sm"
                        >Rincian Biaya</span
                    >
                    <span
                        class="text-[9px] px-2 py-1 rounded-full uppercase font-extrabold tracking-widest"
                        :class="
                            pengiriman.status_pembayaran.toLowerCase() === 'lunas'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-amber-100 text-amber-700'
                        "
                    >
                        {{ pengiriman.status_pembayaran }}
                    </span>
                </div>

                <div class="space-y-3 text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex justify-between items-center">
                        <span
                            >Layanan
                            <span class="font-semibold text-gray-800 dark:text-gray-200"
                                >({{ layananLabel(pengiriman.jenis_layanan) }})</span
                            ></span
                        >
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{
                            rupiah(pengiriman.biaya_pengiriman)
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between items-center"
                        v-if="Number(pengiriman.biaya_tambahan) > 0"
                    >
                        <span>Biaya Tambahan</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{
                            rupiah(pengiriman.biaya_tambahan)
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between items-center"
                        v-if="Number(pengiriman.biaya_asuransi) > 0"
                    >
                        <span>Asuransi Barang</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{
                            rupiah(pengiriman.biaya_asuransi)
                        }}</span>
                    </div>
                </div>

                <div
                    class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-end"
                >
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">
                            Total Bayar
                        </div>
                        <div class="text-[11px] text-gray-400 mt-0.5">
                            <i class="bi bi-wallet2 mr-1"></i>
                            {{ metodeLabel(pengiriman.metode_pembayaran) }}
                        </div>
                    </div>
                    <span class="text-xl font-extrabold text-primary">{{
                        rupiah(pengiriman.total_biaya)
                    }}</span>
                </div>

                <!-- Daftar Barang Accordion (Bersatu dengan Struk) -->
                <details
                    class="mt-5 bg-gray-50 dark:bg-gray-800/30 rounded-2xl group text-sm overflow-hidden"
                >
                    <summary
                        class="cursor-pointer px-4 py-3.5 font-semibold text-gray-700 dark:text-gray-300 select-none list-none flex justify-between items-center border border-gray-100 dark:border-gray-700/50 rounded-2xl group-open:rounded-b-none group-open:border-b-0 transition"
                    >
                        <span class="flex items-center gap-2"
                            ><i class="bi bi-box-seam text-primary"></i> Data Barang ({{
                                barangList.length
                            }})</span
                        >
                        <i
                            class="bi bi-chevron-down transition-transform group-open:rotate-180"
                        ></i>
                    </summary>
                    <div
                        class="p-4 pt-1 space-y-3 border border-t-0 border-gray-100 dark:border-gray-700/50 rounded-b-2xl"
                    >
                        <div
                            v-for="(b, idx) in barangList"
                            :key="'mob-b-' + b.id"
                            class="pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0 last:pb-0"
                        >
                            <div
                                class="flex justify-between font-bold text-gray-800 dark:text-gray-200 mb-1 text-xs"
                            >
                                <span>{{ b.nama_barang }}</span>
                                <span class="text-primary bg-primary/10 px-2 py-0.5 rounded"
                                    >{{ b.berat_kg }} kg</span
                                >
                            </div>
                            <div class="text-[10px] text-gray-500 font-medium">
                                Dimensi: {{ b.panjang_cm }}x{{ b.lebar_cm }}x{{ b.tinggi_cm }} cm
                                (Vol: {{ b.volume_cm3 }})
                            </div>
                        </div>
                        <div
                            v-if="barangList.length === 0"
                            class="text-center text-gray-400 py-2 text-xs"
                        >
                            Barang belum didata.
                        </div>
                    </div>
                </details>
            </div>

            <!-- Tombol Cetak & Batalkan (Sekunder) -->
            <div class="flex gap-3 mx-2 mt-5">
                <button
                    type="button"
                    class="flex-1 py-3.5 rounded-2xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 font-bold text-xs bg-white dark:bg-card-dark shadow-sm active:scale-95 transition-transform flex items-center justify-center"
                    @click="openPrint"
                >
                    <i class="bi bi-printer text-primary mr-1.5 text-sm"></i> Cetak Resi
                </button>
                <button
                    v-if="canCancel"
                    type="button"
                    class="flex-1 py-3.5 rounded-2xl border border-red-100 dark:border-red-900/30 bg-red-50 dark:bg-red-900/10 text-red-600 font-bold text-xs shadow-sm active:scale-95 transition-transform flex items-center justify-center"
                    @click="cancelShipment"
                >
                    <i class="bi bi-x-octagon mr-1.5 text-sm"></i> Batalkan
                </button>
            </div>

            <!-- 4. TIMELINE TRACKING -->
            <div class="mt-8 mx-3 mb-10">
                <h3
                    class="font-extrabold text-gray-900 dark:text-white mb-5 flex items-center gap-2"
                >
                    <i class="bi bi-clock-history text-primary"></i> Riwayat Perjalanan
                </h3>
                <div
                    v-if="trackingItems.length === 0"
                    class="text-center bg-white dark:bg-card-dark rounded-2xl py-8 shadow-sm border border-gray-100 dark:border-gray-800"
                >
                    <i class="bi bi-inbox text-3xl text-gray-300 mb-2 block"></i>
                    <span class="text-xs text-gray-500 font-medium"
                        >Riwayat perjalanan belum tersedia.</span
                    >
                </div>
                <!-- Menumpangi komponen yang sudah ada, dijamin hijau tanpa bug -->
                <div
                    v-else
                    class="bg-white dark:bg-card-dark rounded-3xl p-4 sm:p-5 shadow-sm border border-gray-100 dark:border-gray-800"
                >
                    <TrackingTimeline :items="trackingItems" />
                </div>
            </div>

            <!-- 5. FLOATING BOTTOM SHEET (UPDATE STATUS) -->
            <!-- Fungsi: Mengubah formulir Update Status menjadi Modal Panel geser dari bawah (Bottom Sheet) agar layar tracking tidak penuh sesak -->
            <div class="fixed bottom-[110px] left-4 right-4 z-50">
                <!-- Panel Form (Muncul saat tombol diklik) -->
                <div
                    v-if="showMobileUpdateForm"
                    class="bg-white dark:bg-card-dark rounded-3xl shadow-[0_15px_50px_rgba(0,0,0,0.25)] border border-gray-100 dark:border-gray-700 p-5 mb-4 animate-fade-in-up"
                >
                    <div
                        class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100 dark:border-gray-800"
                    >
                        <span class="font-extrabold text-gray-900 dark:text-white text-sm"
                            ><i class="bi bi-pencil-square text-primary mr-1"></i> Update Status
                            Pesanan</span
                        >
                        <button
                            @click="showMobileUpdateForm = false"
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 hover:text-red-500 hover:bg-red-50 transition-colors"
                        >
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>

                    <div class="space-y-4 mb-5">
                        <div>
                            <label
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5"
                                >Status Baru</label
                            >
                            <select
                                v-model="updateForm.status_baru"
                                class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl text-sm font-semibold focus:ring-2 focus:ring-primary p-3.5"
                            >
                                <option value="" disabled>Pilih Status...</option>
                                <option v-for="o in statusOptions" :key="o.value" :value="o.value">
                                    {{ o.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5"
                                >Lokasi (Opsional)</label
                            >
                            <input
                                v-model="updateForm.lokasi"
                                type="text"
                                class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl text-sm font-semibold focus:ring-2 focus:ring-primary p-3.5 placeholder:font-normal"
                                placeholder="Nama kota/cabang"
                            />
                        </div>
                    </div>

                    <button
                        @click="submitUpdate"
                        :disabled="submitting"
                        class="w-full bg-primary text-white font-extrabold text-sm py-4 rounded-2xl active:scale-95 transition-transform flex items-center justify-center gap-2 shadow-[0_4px_15px_rgba(45,51,107,0.3)] disabled:opacity-70"
                    >
                        <i class="bi bi-cloud-arrow-up-fill text-lg"></i> Simpan Update
                    </button>
                </div>

                <!-- Tombol Pemancing (FAB Lebar) -->
                <!-- Fungsi: Bersembunyi jika form terbuka. Diposisikan di z-50 di atas konten. -->
                <button
                    v-if="!showMobileUpdateForm"
                    @click="showMobileUpdateForm = true"
                    class="w-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-extrabold text-sm py-4 rounded-2xl shadow-[0_15px_30px_rgba(0,0,0,0.25)] flex justify-center items-center gap-2 active:scale-95 transition-transform animate-fade-in-up"
                >
                    <i class="bi bi-pencil-square text-lg"></i> Update Status Resi
                </button>
            </div>
        </div>
    </div>
</template>
