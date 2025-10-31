<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import AppLogo from '@/components/AppLogo.vue';
import TermsStatistics from '@/components/TermsStatistics.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Stats {
    total: number;
    ai_relevan: number;
    ai_negative: number;
    ai_null: number;
    google_sukses: number;
    google_gagal: number;
    google_error: number;
    google_null: number;
    telegram_sukses: number;
    telegram_gagal: number;
    telegram_null: number;
    // Frasa stats
    frasa_total: number;
    frasa_ai_indonesia: number;
    frasa_ai_luar: number;
    frasa_ai_null: number;
    frasa_google_sukses: number;
    frasa_google_gagal: number;
    frasa_google_error: number;
    frasa_google_null: number;
    frasa_telegram_sukses: number;
    frasa_telegram_gagal: number;
    frasa_telegram_null: number;
}

interface Props {
    stats: Stats;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: '/dashboard' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Statistics Dashboard -->
            <TermsStatistics :stats="stats" />
        </div>
    </AppLayout>
</template>
