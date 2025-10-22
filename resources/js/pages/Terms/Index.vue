<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import TermsFilters from '@/components/TermsFilters.vue';
import { Button } from '@/components/ui/button';

interface Term {
    id: number;
    terms: string;
    hasil_cek_ai: 'relevan' | 'negatif' | null;
    status_input_google: 'sukses' | 'gagal' | 'error' | null;
    notif_telegram: 'sukses' | 'gagal' | null;
    retry_count: number;
    created_at: string;
    updated_at: string;
    frasa_negatives_count?: number;
}



interface Props {
    terms: {
        data: Term[];
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
        ai_result?: string;
        google_status?: string;
        telegram_notif?: string;
        sort_by?: string;
        sort_order?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Terms Negative Management',
        href: '/terms',
    },
];

// Badge variants for different statuses
const getAiBadgeVariant = (status: string) => {
    switch (status) {
        case 'relevan':
            return 'default';
        case 'negatif':
            return 'destructive';
        default:
            return 'secondary';
    }
};

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

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString();
};

const changePerPage = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const perPage = parseInt(target.value);
    const params: Record<string, any> = { ...props.filters };

    // Add per_page parameter
    params.per_page = perPage;
    
    router.get('/terms', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteTerm = (term: Term) => {
    if (!confirm(`Hapus term "${term.terms}"? Data frasa terkait tidak akan dihapus.`)) {
        return;
    }

    router.delete(`/terms/${term.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // reload current page
            router.get('/terms', { ...props.filters, per_page: props.terms.per_page }, {
                preserveScroll: true,
            });
        },
    });
};
</script>

<template>
    <Head title="Terms Negative Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Filters Section -->
            <TermsFilters :filters="filters" />

            <!-- Terms Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Terms Data</CardTitle>
                    
                    <!-- Per Page Selector -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">Items per page:</span>
                            <select
                                :value="terms.per_page"
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
                            Showing {{ terms.from }} to {{ terms.to }} of {{ terms.total }} results
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-muted/50">
                                <tr class="border-b">
                                    <th class="text-left p-2 font-medium">ID</th>
                                    <th class="text-left p-2 font-medium">Terms</th>
                                    <th class="text-left p-2 font-medium">AI Result</th>
                                    <th class="text-left p-2 font-medium">Input Google Ads</th>
                                    <th class="text-left p-2 font-medium">Notif Telegram</th>
                                    <th class="text-left p-2 font-medium">Retry Count</th>
                                    <th class="text-left p-2 font-medium">Created At</th>
                                    <th class="text-left p-2 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="term in terms.data" :key="term.id" class="border-b hover:bg-muted/50">
                                    <td class="p-2">
                                        <div class="font-medium">{{ term.id }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="font-medium">{{ term.terms }}</div>
                                        <div v-if="term.frasa_negatives_count" class="text-sm text-muted-foreground">
                                            {{ term.frasa_negatives_count }} negative phrases
                                        </div>
                                    </td>
                                    <td class="p-2">
                                        <Badge :variant="getAiBadgeVariant(term.hasil_cek_ai)">
                                            {{ term.hasil_cek_ai }}
                                        </Badge>
                                    </td>
                                    <td class="p-2">
                                        <Badge :variant="getGoogleBadgeVariant(term.status_input_google)">
                                            {{ term.status_input_google }}
                                        </Badge>
                                    </td>
                                    <td class="p-2">
                                        <Badge :variant="getTelegramBadgeVariant(term.notif_telegram)">
                                            {{ term.notif_telegram }}
                                        </Badge>
                                    </td>
                                    <td class="p-2">
                                        <span class="text-sm">{{ term.retry_count }}</span>
                                    </td>
                                    <td class="p-2">
                                        <span class="text-sm text-muted-foreground">
                                            {{ formatDate(term.created_at) }}
                                        </span>
                                    </td>
                                    <td class="p-2">
                                        <Button variant="destructive" size="sm" @click="deleteTerm(term)">
                                            Delete
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="terms.last_page > 1" class="flex items-center justify-center gap-4 mt-4">
                        <div class="text-sm text-muted-foreground">
                            Page {{ terms.current_page }} of {{ terms.last_page }}
                        </div>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in terms.links"
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