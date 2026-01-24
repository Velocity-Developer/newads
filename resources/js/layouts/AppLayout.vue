<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import 'vue-sonner/style.css'
import { Toaster } from '@/components/ui/sonner'
import { usePage } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const version = page.props.appInfo.version as string;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <Toaster />
        
        <footer style="margin-top:2rem; padding:0.5rem 1.5rem; color:#666; font-size:12px; text-align:right;">
            Versi aplikasi: {{ version }}
        </footer>
    </AppLayout>
</template>
