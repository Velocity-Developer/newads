<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Search, Filter, Eye, TrendingUp, TrendingDown, Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import TermsFilters from '@/components/TermsFilters.vue';

interface Term {
    id: number;
    terms: string;
    hasil_cek_ai: 'relevan' | 'negative' | null;
    status_input_google: 'sukses' | 'gagal' | 'error' | null;
    notif_telegram: 'sukses' | 'gagal' | null;
    retry_count: number;
    created_at: string;
    updated_at: string;
    frasa_negatives_count?: number;
}

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
    stats: Stats;
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
        title: 'Terms Management',
        href: '/terms',
    },
];

// Badge variants for different statuses
const getAiBadgeVariant = (status: string) => {
    switch (status) {
        case null: return 'outline';
        case 'relevan': return 'success';
        case 'negative': return 'destructive';
        default: return 'secondary';
    }
};

const getGoogleBadgeVariant = (status: string) => {
    switch (status) {
        case null: return 'outline';
        case 'sukses': return 'success';
        case 'gagal': return 'destructive';
        case 'error': return 'destructive';
        default: return 'secondary';
    }
};

const getTelegramBadgeVariant = (status: string) => {
    switch (status) {
        case null: return 'outline';
        case 'sukses': return 'success';
        case 'gagal': return 'destructive';
        default: return 'secondary';
    }
};

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Terms Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Filters Section -->
            <TermsFilters :filters="filters" />

            <!-- Terms Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Terms Data</CardTitle>
                    <CardDescription>
                        Showing {{ terms.from }} to {{ terms.to }} of {{ terms.total }} terms
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-2 font-medium">ID Terms</th>
                                    <th class="text-left p-2 font-medium">Terms</th>
                                    <th class="text-left p-2 font-medium">AI Result</th>
                                    <th class="text-left p-2 font-medium">Google Ads</th>
                                    <th class="text-left p-2 font-medium">Notif Telegram</th>
                                    <th class="text-left p-2 font-medium">Retry Count</th>
                                    <th class="text-left p-2 font-medium">Created</th>
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
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="terms.last_page > 1" class="flex items-center justify-between mt-4">
                        <div class="text-sm text-muted-foreground">
                            Page {{ terms.current_page }} of {{ terms.last_page }}
                        </div>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in terms.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm border rounded',
                                    link.active
                                        ? 'bg-primary text-primary-foreground border-primary'
                                        : 'bg-background hover:bg-muted border-input',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>