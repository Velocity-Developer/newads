<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

interface KirimKonversi {
    id: number;
    gclid: string;
    jobid: string | null;
    waktu: string | null;
    status: string | null;
    response: string | null;
    source: string | null;
    rekap_form_id: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    kirimKonversis: {
        data: KirimKonversi[];
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
        status?: string;
        source?: string;
        sort_by?: string;
        sort_order?: string;
        date_from?: string;
        date_to?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kirim Konversi',
        href: '/kirim-konversi',
    },
];

const getStatusBadgeVariant = (status: string | null) => {
    switch (status) {
        case 'success':
        case 'sukses':
            return 'default';
        case 'failed':
        case 'gagal':
        case 'error':
            return 'destructive';
        case 'pending':
        case 'menunggu':
            return 'secondary';
        default:
            return 'outline';
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID');
};

const formatText = (text: string | null, maxLength: number = 50) => {
    if (!text) return '-';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

const applyFilters = () => {
    const params: Record<string, any> = { ...props.filters };
    router.get('/kirim-konversi', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    router.get('/kirim-konversi', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changePerPage = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const perPage = parseInt(target.value);
    const params: Record<string, any> = { ...props.filters };
    params.per_page = perPage;
    
    router.get('/kirim-konversi', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changeSort = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const sortBy = target.value;
    const params: Record<string, any> = { ...props.filters };
    params.sort_by = sortBy;
    
    router.get('/kirim-konversi', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changeSortOrder = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const sortOrder = target.value;
    const params: Record<string, any> = { ...props.filters };
    params.sort_order = sortOrder;
    
    router.get('/kirim-konversi', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteKirimKonversi = (item: KirimKonversi) => {
    if (!confirm(`Hapus data Kirim Konversi dengan ID ${item.id}?`)) {
        return;
    }

    router.delete(`/kirim-konversi/${item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.get('/kirim-konversi', { ...props.filters, per_page: props.kirimKonversis.per_page }, {
                preserveScroll: true,
            });
        },
    });
};
</script>

<template>
    <Head title="Kirim Konversi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Filters Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                    <CardDescription>Filter data Kirim Konversi</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Search</label>
                            <Input
                                v-model="filters.search"
                                placeholder="Search GCLID, Job ID, Status, Source..."
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Status</label>
                            <Select v-model="filters.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="success">Success</SelectItem>
                                    <SelectItem value="failed">Failed</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="error">Error</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <!-- Source Filter -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Source</label>
                            <Select v-model="filters.source">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Source" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Sources</SelectItem>
                                    <SelectItem value="greetingads">Greeting Ads</SelectItem>
                                    <SelectItem value="manual">Manual</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <!-- Date From -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Date From</label>
                            <Input
                                v-model="filters.date_from"
                                type="date"
                            />
                        </div>
                        
                        <!-- Date To -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Date To</label>
                            <Input
                                v-model="filters.date_to"
                                type="date"
                            />
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex items-end gap-2">
                            <Button @click="applyFilters">Apply Filters</Button>
                            <Button variant="outline" @click="clearFilters">Clear</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Kirim Konversi Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Kirim Konversi Data</CardTitle>
                    
                    <!-- Per Page Selector -->
                    <div class="md:flex items-center justify-between mt-5">
                        <div class="flex items-center justify-end md:justify-start gap-4 mb-2 md:mb-0">
                            <div class="flex items-center gap-2">                                
                                <Select v-model="kirimKonversis.per_page" @update:modelValue="(value) => changePerPage({ target: { value } })">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Items per page" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="15">15</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="flex items-center gap-2">
                                <Select v-model="filters.sort_by" @change="changeSort($event)">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Sort by" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="created_at">Created At</SelectItem>
                                        <SelectItem value="gclid">GCLID</SelectItem>
                                        <SelectItem value="jobid">Job ID</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select v-model="filters.sort_order" @change="changeSortOrder($event)">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Sort order" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="desc">Descending</SelectItem>
                                        <SelectItem value="asc">Ascending</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="text-sm text-end md:text-start text-muted-foreground">
                            Showing {{ kirimKonversis.from }} to {{ kirimKonversis.to }} of {{ kirimKonversis.total }} results
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-muted/50">
                                <tr class="border-b">
                                    <th class="text-left p-2 font-medium">No</th>
                                    <th class="text-left p-2 font-medium">GCLID</th>
                                    <th class="text-left p-2 font-medium">Job ID</th>
                                    <th class="text-left p-2 font-medium">Waktu</th>
                                    <th class="text-left p-2 font-medium">Status</th>
                                    <th class="text-left p-2 font-medium">Source</th>
                                    <th class="text-left p-2 font-medium">Created At</th>
                                    <th class="text-left p-2 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in kirimKonversis.data" :key="item.id" class="border-b hover:bg-muted/50">
                                    <td class="p-2">
                                        <div class="font-medium">{{ kirimKonversis.data.indexOf(item) + kirimKonversis.from }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="font-mono text-sm">{{ item.gclid }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="font-mono text-sm">{{ item.jobid || '-' }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-sm">{{ item.waktu || '-' }}</div>
                                    </td>
                                    <td class="p-2">
                                        <Badge v-if="item.status" :variant="getStatusBadgeVariant(item.status)">
                                            {{ item.status }}
                                        </Badge>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="p-2">
                                        <Badge v-if="item.source" variant="outline">
                                            {{ item.source }}
                                        </Badge>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="p-2">
                                        <span class="text-sm text-muted-foreground">
                                            {{ formatDate(item.created_at) }}
                                        </span>
                                    </td>
                                    <td class="p-2">
                                        <div class="flex gap-2">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="`/kirim-konversi/${item.id}`">View</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="deleteKirimKonversi(item)">
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="kirimKonversis.data.length === 0" class="text-center py-8">
                        <p class="text-muted-foreground">No data found</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="kirimKonversis.last_page > 1" class="flex items-center justify-center gap-4 mt-4">
                        <div class="text-sm text-muted-foreground">
                            Page {{ kirimKonversis.current_page }} of {{ kirimKonversis.last_page }}
                        </div>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in kirimKonversis.links"
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