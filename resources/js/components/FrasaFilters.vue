<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { Search, Filter, Calendar, FileSpreadsheet } from 'lucide-vue-next';
import { ref } from 'vue';

interface Filters {
    search?: string;
    date_from?: string;
    date_to?: string;
}

interface Props {
    filters: Filters;
}

const props = defineProps<Props>();

// Button variants
const getButtonVariant = (status: string) => {
    switch (status) {
        case 'success': return 'success';
        case 'danger': return 'destructive';
        default: return 'secondary';
    }
};

const searchQuery = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const applyFilters = () => {
    const params: Record<string, any> = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    router.get('/frasa', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    dateFrom.value = '';
    dateTo.value = '';

    router.get('/frasa', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Export to Excel
const exportToExcel = () => {
    const params: Record<string, any> = { export: 'excel' };
    
    if (searchQuery.value) params.search = searchQuery.value;
    // if (aiResultFilter.value) params.ai_result = aiResultFilter.value;
    // if (googleStatusFilter.value) params.google_status = googleStatusFilter.value;
    // if (telegramNotifFilter.value) params.telegram_notif = telegramNotifFilter.value;
    // if (sortBy.value !== 'created_at') params.sort_by = sortBy.value;
    // if (sortOrder.value !== 'desc') params.sort_order = sortOrder.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    // Create a temporary link to download the file
    const queryString = new URLSearchParams(params).toString();
    const url = `/frasa?${queryString}`;
    
    // Open in new window to trigger download
    window.open(url, '_blank');
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Filter class="h-5 w-5" />
                Filters & Search
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 items-end">
                <div class="space-y-2">
                    <Label for="search">Search Frasa</Label>
                    <div class="relative">
                        <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="search"
                            v-model="searchQuery"
                            placeholder="Cari frasa..."
                            class="pl-8"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="date-from">Date From</Label>
                    <div class="relative">
                        <Calendar class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input id="date-from" v-model="dateFrom" type="date" class="pl-8" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="date-to">Date To</Label>
                    <div class="relative">
                        <Calendar class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input id="date-to" v-model="dateTo" type="date" class="pl-8" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex flex-wrap gap-2">
                        <Button @click="applyFilters" class="flex items-center gap-2">
                            <Search class="h-4 w-4" />
                            Apply
                        </Button>
                        <Button variant="outline" @click="clearFilters">Clear</Button>
                    </div>
                </div>
            </div>

            <div class="flex justify-start">
                <Button :variant="getButtonVariant('success')" @click="exportToExcel" class="flex items-center gap-2">
                    <FileSpreadsheet class="h-4 w-4" />
                    Export to Excel
                </Button>
            </div>
        </CardContent>
    </Card>
</template>