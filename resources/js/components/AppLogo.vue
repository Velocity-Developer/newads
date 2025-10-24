<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const branding = page.props.branding as {
    sidebarTitle?: string;
    sidebarIconUrl?: string | null;
};
const hasError = ref(false);
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <img
            v-if="branding?.sidebarIconUrl && !hasError"
            :src="branding.sidebarIconUrl"
            alt="Logo"
            class="size-6 object-contain"
            @error="hasError = true"
        />
        <AppLogoIcon v-else class="size-8" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">
            {{ branding?.sidebarTitle ?? 'Laravel Starter Kit' }}
        </span>
    </div>
</template>
