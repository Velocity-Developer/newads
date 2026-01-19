<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Eye, Trash, Filter } from 'lucide-vue-next';
import KirimKonversiForm from '@/components/KirimKonversiForm.vue';
import KirimKonversiGetUpdate from '@/components/KirimKonversiGetUpdate.vue';

interface RekapForm {
    id: number;
    nama: string;
    cek_konversi_nominal: boolean;
    tanggal: string;
    created_at: string;
    source_id: string | null;
}

interface KirimKonversi {
    id: number;
    gclid: string;
    jobid: string | null;
    waktu: string | null;
    status: string | null;
    response: string | null;
    source: string | null;
    rekap_form_id: string | null;
    rekap_form_source: string | null;
    created_at: string;
    updated_at: string;
    //relasi dengan model RekapForm
    rekap_form: RekapForm | null;
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

const urlParams = new URLSearchParams(window.location.search);

const filters = {
    search: props.filters.search || urlParams.get('search') || '',
    status: props.filters.status || urlParams.get('status') || 'all',
    source: props.filters.source || urlParams.get('source') || 'all',
    sort_by: props.filters.sort_by || urlParams.get('sort_by') || 'created_at',
    sort_order: props.filters.sort_order || urlParams.get('sort_order') || 'desc',
    date_from: props.filters.date_from || urlParams.get('date_from') || '',
    date_to: props.filters.date_to || urlParams.get('date_to') || '',
};

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
    const params: Record<string, any> = {};

    if (filters.search) params.search = filters.search;
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (filters.source && filters.source !== 'all') params.source = filters.source;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    params.sort_by = filters.sort_by;
    params.sort_order = filters.sort_order;

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
    const params: Record<string, any> = {};

    if (filters.search) params.search = filters.search;
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (filters.source && filters.source !== 'all') params.source = filters.source;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    params.sort_by = filters.sort_by;
    params.sort_order = filters.sort_order;
    params.per_page = perPage;

    router.get('/kirim-konversi', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changeSort = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const sortBy = target.value;
    const params: Record<string, any> = {};

    if (filters.search) params.search = filters.search;
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (filters.source && filters.source !== 'all') params.source = filters.source;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    params.sort_by = sortBy;
    params.sort_order = filters.sort_order;

    router.get('/kirim-konversi', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changeSortOrder = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const sortOrder = target.value;
    const params: Record<string, any> = {};

    if (filters.search) params.search = filters.search;
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (filters.source && filters.source !== 'all') params.source = filters.source;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    params.sort_by = filters.sort_by;
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

    const params: Record<string, any> = {};

    if (filters.search) params.search = filters.search;
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (filters.source && filters.source !== 'all') params.source = filters.source;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    params.sort_by = filters.sort_by;
    params.sort_order = filters.sort_order;
    params.per_page = props.kirimKonversis.per_page;

    router.delete(`/kirim-konversi/${item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.get('/kirim-konversi', params, {
                preserveScroll: true,
            });
        },
    });
};

// Update data response when form is submitted
const updateDataResponse = (data: any) => {
    //reload data
    const params: Record<string, any> = {};

    if (filters.search) params.search = filters.search;
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (filters.source && filters.source !== 'all') params.source = filters.source;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    params.sort_by = filters.sort_by;
    params.sort_order = filters.sort_order;
    params.per_page = props.kirimKonversis.per_page;

    router.get('/kirim-konversi', params, {
        preserveScroll: true,
    });
}



</script>

<template>
    <Head title="Kirim Konversi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            
            <div class="mt-2 md:mt-0 flex items-center justify-end gap-1">
                <KirimKonversiForm @update="updateDataResponse" />
                <KirimKonversiGetUpdate @update="updateDataResponse" />                            
            </div>

            <!-- Kirim Konversi Table -->
            <Card>
                <CardHeader>
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <CardTitle>Riwayat Kirim Konversi</CardTitle>
                    </div>
                    
                    <!-- Per Page Selector -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between mt-5">
                        <div class="flex items-center justify-end md:justify-start gap-4 mb-2 md:mb-0">                       
                            <select v-model="kirimKonversis.per_page" @change="changePerPage($event)" class="border rounded text-sm px-3 py-2">
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <Dialog>                                
                                <DialogTrigger as-child>
                                    <Button variant="outline">
                                        <Filter /> Filters
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-xl">
                                    <DialogHeader>
                                        <DialogTitle>Filters</DialogTitle>
                                    </DialogHeader>
                                    <!-- Filters Section -->
           
                                    <div class="grid grid-cols-1 gap-4">
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
                                            <select id="status" name="status" placeholder="Select Status" v-model="filters.status" class="border p-2 rounded text-sm w-full">
                                                <option value="all">All Status</option>
                                                <option value="success">Success</option>
                                                <option value="failed">Failed</option>
                                                <option value="pending">Pending</option>
                                                <option value="error">Error</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Source Filter -->
                                        <div>
                                            <label class="text-sm font-medium mb-2 block">Source</label>
                                            <select id="source" name="source" placeholder="Select Status" v-model="filters.source" class="border p-2 rounded text-sm w-full">
                                                <option value="">All Sources</option>
                                                <option value="greetingads">Greeting Ads</option>
                                                <option value="manual">Manual</option>
                                            </select>
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
                                        <div class="flex items-end justify-end gap-2">   
                                            <Button variant="outline" @click="clearFilters">Clear</Button>                                         
                                            <DialogClose as-child>                                                
                                                <Button @click="applyFilters()">Apply Filters</Button>
                                            </DialogClose>
                                        </div>
                                    </div>

                                </DialogContent>
                            </Dialog>
                            <Button v-if="filters.search || filters.status || filters.source || filters.date_from || filters.date_to" variant="outline" @click="clearFilters">Clear</Button>
                        </div>
                        <div class="flex justify-end">
                            <Button @click="applyFilters">Reload</Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-muted/50">
                                <tr class="border-b">
                                    <th class="text-left p-2 font-medium">No</th>
                                    <th class="text-left p-2 font-medium">Nama</th>
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
                                        <Link :href="`/rekap-form/${item.rekap_form_id}`" class="hover:underline">                                            
                                            <template v-if="item?.rekap_form?.nama">
                                                {{ item.rekap_form.nama }}
                                            </template>
                                            <template v-else-if="item?.rekap_form?.nama == '' && item.rekap_form_source == 'tidio'">
                                                {{ item?.rekap_form?.source_id }}
                                            </template>
                                            <template v-else-if="item?.rekap_form_id">
                                                {{ item.rekap_form_id }}
                                            </template>
                                            <template v-else>
                                                -
                                            </template>
                                        </Link>
                                    </td>
                                    <td class="p-2">
                                        <div :title="item.gclid" class="font-mono text-sm max-w-40 overflow-hidden text-ellipsis whitespace-nowrap">
                                            {{ item.gclid }}
                                        </div>
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
                                                <Link :href="`/kirim-konversi/${item.id}`">
                                                    <Eye/>
                                                </Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="deleteKirimKonversi(item)">                                                
                                                <Trash/>
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
                    <div v-if="kirimKonversis.last_page > 1" class="flex items-center justify-between gap-4 mt-4">
                        <div class="text-sm text-muted-foreground">
                            Page {{ kirimKonversis.current_page }} of {{ kirimKonversis.last_page }}                            
                            <div class="text-sm text-end md:text-start text-muted-foreground">
                                Showing {{ kirimKonversis.from }} to {{ kirimKonversis.to }} of {{ kirimKonversis.total }} results
                            </div>
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