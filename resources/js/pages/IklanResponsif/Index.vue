<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Search } from 'lucide-vue-next';

type IklanResponsif = {
    id: number;
    group_iklan: string;
    kata_kunci: string;
    search_term_id?: number;
    nomor_group_iklan?: string;
    nomor_kata_kunci?: string;
    status?: string;
    created_at: string;
    updated_at: string;
    search_term?: {
        id: number;
        term: string;
    };
};

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    items: Paginator<IklanResponsif>;
    filters: {
        q?: string;
        status?: string;
        sort_by?: string;
        sort_order?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.q || '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Iklan Responsif', href: '/iklan-responsif' },
];

const handleSearch = () => {
    router.get('/iklan-responsif', {
        ...props.filters,
        q: search.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changePerPage = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const perPage = parseInt(target.value);
    
    router.get('/iklan-responsif', {
        ...props.filters,
        per_page: perPage,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(search, (value) => {
    // Debounce search if needed, but for now simple enter or button click is fine.
    // Actually let's just rely on Enter key or button for explicit search
});

</script>

<template>
    <Head title="Iklan Responsif" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            
            <div class="flex items-center gap-4">
                <div class="relative w-full max-w-sm items-center">
                    <Input 
                        v-model="search" 
                        placeholder="Search..." 
                        class="pl-10" 
                        @keyup.enter="handleSearch"
                    />
                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                        <Search class="size-4 text-muted-foreground" />
                    </span>
                </div>
                <Button @click="handleSearch">Search</Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Data Iklan Responsif</CardTitle>
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">Items per page:</span>
                            <select
                                :value="items.per_page"
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
                            Showing {{ items.from }} to {{ items.to }} of {{ items.total }} results
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-muted/50">
                                <tr class="border-b">
                                    <th class="text-left p-2 font-medium">ID</th>
                                    <th class="text-left p-2 font-medium">Group Iklan</th>
                                    <th class="text-left p-2 font-medium">Kata Kunci</th>
                                    <th class="text-left p-2 font-medium">Asal Search Term</th>
                                    <th class="text-left p-2 font-medium">Nomor Group</th>
                                    <th class="text-left p-2 font-medium">Nomor Kata Kunci</th>
                                    <th class="text-left p-2 font-medium">Status</th>
                                    <th class="text-left p-2 font-medium">Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="items.data.length === 0">
                                    <td colspan="7" class="p-4 text-center text-muted-foreground">
                                        No data found.
                                    </td>
                                </tr>
                                <tr v-for="item in items.data" :key="item.id" class="border-b hover:bg-muted/50">
                                    <td class="p-2">{{ item.id }}</td>
                                    <td class="p-2">{{ item.group_iklan }}</td>
                                    <td class="p-2">{{ item.kata_kunci }}</td>
                                    <td class="p-2">
                                        <span v-if="item.search_term" class="text-xs bg-muted px-2 py-1 rounded">
                                            {{ item.search_term.term }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="p-2">{{ item.nomor_group_iklan || '-' }}</td>
                                    <td class="p-2">{{ item.nomor_kata_kunci || '-' }}</td>
                                    <td class="p-2">{{ item.status || '-' }}</td>
                                    <td class="p-2">{{ new Date(item.created_at).toLocaleString() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-4 flex justify-end gap-2" v-if="items.last_page > 1">
                        <Button 
                            v-for="(link, index) in items.links" 
                            :key="index"
                            variant="outline"
                            :disabled="!link.url || link.active"
                            class="h-8 w-8 p-0"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                            @click="link.url && router.get(link.url, filters, { preserveState: true, preserveScroll: true })"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
