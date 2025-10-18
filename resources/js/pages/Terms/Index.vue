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

interface Term {
    id: number;
    terms: string;
    hasil_cek_ai: 'positive' | 'negative' | 'pending';
    status_input_google: 'success' | 'failed' | 'pending';
    notif_telegram: 'sent' | 'failed' | 'pending';
    retry_count: number;
    created_at: string;
    updated_at: string;
    frasa_negatives_count?: number;
}

interface Stats {
    total: number;
    ai_positive: number;
    ai_negative: number;
    ai_pending: number;
    google_success: number;
    google_failed: number;
    google_pending: number;
    telegram_sent: number;
    telegram_failed: number;
    telegram_pending: number;
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

// Reactive filters
const searchQuery = ref(props.filters.search || '');
const aiResultFilter = ref(props.filters.ai_result || '');
const googleStatusFilter = ref(props.filters.google_status || '');
const telegramNotifFilter = ref(props.filters.telegram_notif || '');
const sortBy = ref(props.filters.sort_by || 'created_at');
const sortOrder = ref(props.filters.sort_order || 'desc');

// Apply filters
const applyFilters = () => {
    const params: Record<string, any> = {};
    
    if (searchQuery.value) params.search = searchQuery.value;
    if (aiResultFilter.value) params.ai_result = aiResultFilter.value;
    if (googleStatusFilter.value) params.google_status = googleStatusFilter.value;
    if (telegramNotifFilter.value) params.telegram_notif = telegramNotifFilter.value;
    if (sortBy.value !== 'created_at') params.sort_by = sortBy.value;
    if (sortOrder.value !== 'desc') params.sort_order = sortOrder.value;

    router.get('/terms', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear filters
const clearFilters = () => {
    searchQuery.value = '';
    aiResultFilter.value = '';
    googleStatusFilter.value = '';
    telegramNotifFilter.value = '';
    sortBy.value = 'created_at';
    sortOrder.value = 'desc';
    
    router.get('/terms', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Badge variants for different statuses
const getAiBadgeVariant = (status: string) => {
    switch (status) {
        case 'positive': return 'default';
        case 'negative': return 'destructive';
        case 'pending': return 'secondary';
        default: return 'secondary';
    }
};

const getGoogleBadgeVariant = (status: string) => {
    switch (status) {
        case 'success': return 'default';
        case 'failed': return 'destructive';
        case 'pending': return 'secondary';
        default: return 'secondary';
    }
};

const getTelegramBadgeVariant = (status: string) => {
    switch (status) {
        case 'sent': return 'default';
        case 'failed': return 'destructive';
        case 'pending': return 'secondary';
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
            <!-- Statistics Dashboard -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Terms</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total.toLocaleString() }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">AI Analysis</CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.ai_positive }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.ai_negative }} negative, {{ stats.ai_pending }} pending
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Google Ads</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ stats.google_success }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.google_failed }} failed, {{ stats.google_pending }} pending
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Telegram Notifications</CardTitle>
                        <AlertCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-purple-600">{{ stats.telegram_sent }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.telegram_failed }} failed, {{ stats.telegram_pending }} pending
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters Section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filters & Search
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Search -->
                        <div class="space-y-2">
                            <Label for="search">Search Terms</Label>
                            <div class="relative">
                                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="search"
                                    v-model="searchQuery"
                                    placeholder="Search terms..."
                                    class="pl-8"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <!-- AI Result Filter -->
                        <div class="space-y-2">
                            <Label for="ai-result">AI Result</Label>
                            <select
                                id="ai-result"
                                v-model="aiResultFilter"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All AI Results</option>
                                <option value="positive">Positive</option>
                                <option value="negative">Negative</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>

                        <!-- Google Status Filter -->
                        <div class="space-y-2">
                            <Label for="google-status">Google Ads Status</Label>
                            <select
                                id="google-status"
                                v-model="googleStatusFilter"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All Google Status</option>
                                <option value="success">Success</option>
                                <option value="failed">Failed</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>

                        <!-- Telegram Notification Filter -->
                        <div class="space-y-2">
                            <Label for="telegram-notif">Telegram Notification</Label>
                            <select
                                id="telegram-notif"
                                v-model="telegramNotifFilter"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All Telegram Status</option>
                                <option value="sent">Sent</option>
                                <option value="failed">Failed</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button @click="applyFilters" class="flex items-center gap-2">
                            <Search class="h-4 w-4" />
                            Apply Filters
                        </Button>
                        <Button variant="outline" @click="clearFilters">
                            Clear Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

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
                                    <th class="text-left p-2 font-medium">Terms</th>
                                    <th class="text-left p-2 font-medium">AI Result</th>
                                    <th class="text-left p-2 font-medium">Google Ads</th>
                                    <th class="text-left p-2 font-medium">Telegram</th>
                                    <th class="text-left p-2 font-medium">Retry Count</th>
                                    <th class="text-left p-2 font-medium">Created</th>
                                    <th class="text-left p-2 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="term in terms.data" :key="term.id" class="border-b hover:bg-muted/50">
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
                                        <Link
                                            :href="`/terms/${term.id}`"
                                            class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800"
                                        >
                                            <Eye class="h-4 w-4" />
                                            View
                                        </Link>
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