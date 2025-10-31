<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import FrasaFilters from '@/components/FrasaFilters.vue';

interface FrasaItem {
    id: number;
    frasa: string;
    parent_term_id: number;
    parent_term?: {
        id: number;
        terms: string;
    } | null;
    status_input_google: 'sukses' | 'gagal' | 'error' | null;
    notif_telegram: 'sukses' | 'gagal' | null;
    hasil_cek_ai: 'indonesia' | 'luar' | null;
    retry_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    frasa: {
        data: FrasaItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        google_status?: string;
        telegram_notif?: string;
        sort_by?: string;
        sort_order?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Frasa Negative Management', href: '/frasa' },
];

const getGoogleBadgeVariant = (status: string) => {
    switch (status) {
        case 'sukses':
            return 'default';
        case 'gagal':
        case 'error':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const getTelegramBadgeVariant = (status: string) => {
    switch (status) {
        case 'sukses':
            return 'default';
        case 'gagal':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const getAiBadgeVariant = (hasil: string) => {
    switch (hasil) {
        case 'indonesia':
            return 'default';
        case 'luar':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString();
};

const changePerPage = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const perPage = parseInt(target.value);
    const params: Record<string, any> = { ...props.filters };
    params.per_page = perPage;
    router.get('/frasa', params, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head title="Frasa Negative Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <FrasaFilters :filters="filters" />

            <Card>
                <CardHeader>
                    <CardTitle>Frasa Data</CardTitle>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">Items per page:</span>
                            <select
                                :value="frasa.per_page"
                                @change="changePerPage($event)"
                                class="flex h-8 w-20 rounded-md border border-input bg-background px-2 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Showing {{ frasa.from }} to {{ frasa.to }} of {{ frasa.total }} results
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-muted/50">
                                <tr class="border-b">
                                    <th class="text-left p-2 font-medium">ID</th>
                                    <th class="text-left p-2 font-medium">Frasa</th>
                                    <th class="text-left p-2 font-medium">Parent Term</th>
                                    <th class="text-left p-2 font-medium">AI Result</th>
                                    <th class="text-left p-2 font-medium">Input Google Ads</th>
                                    <th class="text-left p-2 font-medium">Notif Telegram</th>
                                    <th class="text-left p-2 font-medium">Retry Count</th>
                                    <th class="text-left p-2 font-medium">Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in frasa.data" :key="item.id" class="border-b hover:bg-muted/50">
                                    <td class="p-2">
                                        <div class="font-medium">{{ item.id }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="font-medium">{{ item.frasa }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-sm">
                                            <span v-if="item.parent_term">{{ item.parent_term.terms }}</span>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </div>
                                    </td>
                                    <td class="p-2">
                                        <Badge :variant="getAiBadgeVariant(item.hasil_cek_ai as any)">
                                            {{ item.hasil_cek_ai }}
                                        </Badge>
                                    </td>
                                    <td class="p-2">
                                        <Badge :variant="getGoogleBadgeVariant(item.status_input_google as any)">
                                            {{ item.status_input_google }}
                                        </Badge>
                                    </td>
                                    <td class="p-2">
                                        <Badge :variant="getTelegramBadgeVariant(item.notif_telegram as any)">
                                            {{ item.notif_telegram }}
                                        </Badge>
                                    </td>
                                    <td class="p-2">
                                        <span class="text-sm">{{ item.retry_count }}</span>
                                    </td>
                                    <td class="p-2">
                                        <span class="text-sm text-muted-foreground">{{ formatDate(item.created_at) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="frasa.last_page > 1" class="flex items-center justify-center gap-4 mt-4">
                        <div class="text-sm text-muted-foreground">Page {{ frasa.current_page }} of {{ frasa.last_page }}</div>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in frasa.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="px-3 py-1 rounded border text-sm"
                                :class="{
                                    'bg-primary text-primary-foreground border-primary': link.active,
                                    'bg-background text-foreground border-input': !link.active,
                                    'opacity-50 pointer-events-none': !link.url,
                                }"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>