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
    telegram_sukses: number;
    telegram_gagal: number;
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
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Statistics Dashboard -->
            <TermsStatistics :stats="stats" />
            
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
