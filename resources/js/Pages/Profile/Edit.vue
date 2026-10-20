<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
        import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import { ref } from 'vue';
    import Swal from 'sweetalert2';

    const page = usePage();
    const activeTab = ref('profile');

    const photoInput = ref(null);
    const photoPreview = ref(null);

    const selectNewPhoto = () => {
        photoInput.value.click();
    };

    const updatePhotoPreview = () => {
        const photo = photoInput.value.files[0];
        if (!photo) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };
        reader.readAsDataURL(photo);
        
        router.post(route('profile.update'), {
            _method: 'patch',
            foto: photo,
            nama: page.props.auth.user.nama || page.props.auth.user.name,
            email: page.props.auth.user.email
        }, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                photoPreview.value = null;
                Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Foto profil diperbarui' });
            },
            onError: (err) => {
                Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'error', title: err.foto || 'Gagal mengunggah foto' });
            }
        });
    };

    defineOptions({ layout: AppLayout });

    defineProps({
        mustVerifyEmail: {
            type: Boolean,
        },
        status: {
            type: String,
        },
    });
</script>

<template>
    <Head title="Profile" />

    <div class="space-y-6 animate-fade-in relative">
        <!-- Background semi-circle elegan -->
        <div class="absolute top-0 inset-x-0 h-[250px] bg-gradient-to-br from-[#1E3A8A] to-[#312E81] rounded-b-[40px] opacity-10 -z-10 pattern-diagonal-lines"></div>
        <!-- Fungsi: Avatar Hero Section (Premium UI) -->
        <!-- Cara Kerja: Membentuk visualisasi aplikasi iOS dengan Avatar pengguna di atas secara elegan (khusus mobile style). -->
        <div
            class="card md:p-8 p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border-none bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 relative overflow-hidden flex flex-col items-center md:items-start text-center md:text-left"
        >
            <div
                class="absolute -right-10 -top-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl"
            ></div>

            <div
                class="w-24 h-24 mb-4 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 p-1 shadow-lg shadow-blue-500/30"
            >
                <div
                    @click="selectNewPhoto"
                    class="w-full h-full rounded-full bg-white dark:bg-gray-800 flex items-center justify-center border-4 border-white dark:border-gray-800 overflow-hidden relative group cursor-pointer"
                >
                    <input type="file" ref="photoInput" class="hidden" accept="image/jpeg, image/png, image/webp" @change="updatePhotoPreview" />
                    <img
                        v-if="photoPreview"
                        :src="photoPreview"
                        class="w-full h-full object-cover object-center"
                    />
                    <img
                        v-else-if="$page.props.auth.user.foto"
                        :src="'/storage/' + $page.props.auth.user.foto"
                        class="w-full h-full object-cover object-center"
                    />
                    <img
                        v-else
                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent($page.props.auth.user.name)}&background=f3f4f6&color=111827&size=200`"
                        class="w-full h-full object-cover object-center"
                    />
                    <!-- Hover overlay -->
                    <div
                        class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <i class="bi bi-camera text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div>
                <!-- [UPDATE: NAMA & BADGE TERVERIFIKASI] -->
                <!-- Fungsi: Menampilkan nama dengan badge biru verifikasi untuk kesan profesional -->
                <div class="flex items-center justify-center md:justify-start gap-2">
                    <h1
                        class="font-heading font-extrabold text-2xl md:text-3xl text-gray-900 dark:text-white tracking-tight"
                    >
                        {{ $page.props.auth.user.name }}
                    </h1>
                    <!-- Ikon Centang Biru (Verified) murni bawaan Bootstrap Icons -->
                    <i
                        class="bi bi-patch-check-fill text-blue-500 text-xl"
                        title="Verified Account"
                    ></i>
                </div>

                <!-- [UPDATE: EMAIL & STATUS CHIP] -->
                <!-- Fungsi: Menampilkan email beserta chip status "Administrator" -->
                <div class="mt-2 flex flex-col md:flex-row items-center gap-2 md:gap-3">
                    <p
                        class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1.5"
                    >
                        <i class="bi bi-envelope-at text-gray-400"></i>
                        {{ $page.props.auth.user.email }}
                    </p>

                    <!-- Chip Status (Super Ringan, murni Tailwind CSS) -->
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        Administrator
                    </span>
                </div>
            </div>
        </div>

        <!-- [UPDATE: NAVIGASI TAB KHUSUS MOBILE] -->
        <!-- Fungsi: Menghilangkan scroll panjang di HP dengan menu tab interaktif. Tidak tampil di desktop (md:hidden). -->
        

        <!-- Wrapper Kartu Premium untuk form Profil -->
        <div
            :class="[
                'card md:p-8 p-5 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:bg-card-dark border-none max-w-xl transition duration-300',
                activeTab === 'profile' ? 'block animate-fade-in' : 'hidden md:block',
            ]"
        >
            <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
        </div>

        

        
    </div>
</template>
