<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();

import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const branding = computed(() => (page.props as any).branding);
const sidebarIconUrl = computed(() => branding.value?.sidebarIconUrl || null);
const hasError = ref(false);
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="dashboard()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-9 w-9 items-center justify-center rounded-md"
                        >
                            <img
                                v-if="sidebarIconUrl && !hasError"
                                :src="sidebarIconUrl"
                                alt="Brand Icon"
                                class="size-9 object-contain"
                                @error="hasError = true"
                            />
                            <AppLogoIcon
                                v-else
                                class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
