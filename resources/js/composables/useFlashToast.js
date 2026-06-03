import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { fireToast } from '@/lib/alert';

export default function useFlashToast() {
    const page = usePage();

    watch(
        () => page.props.flash,
        (flash) => {
            if (!flash) return;

            if (flash.success) fireToast('success', flash.success);
            if (flash.error) fireToast('error', flash.error);
            if (flash.info) fireToast('info', flash.info);
            if (flash.warning) fireToast('warning', flash.warning);
        },
        { immediate: true, deep: true },
    );
}
