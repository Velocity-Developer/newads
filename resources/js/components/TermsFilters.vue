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
    // ai_result?: string;
    // google_status?: string;
    // telegram_notif?: string;
    // sort_by?: string;
    // sort_order?: string;
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

// Reactive filters
const searchQuery = ref(props.filters.search || '');
const aiResultFilter = ref(props.filters.ai_result || '');
// const googleStatusFilter = ref(props.filters.google_status || '');
// const telegramNotifFilter = ref(props.filters.telegram_notif || '');
// const sortBy = ref(props.filters.sort_by || 'created_at');
// const sortOrder = ref(props.filters.sort_order || 'desc');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// Apply filters
const applyFilters = () => {
    const params: Record<string, any> = {};
    
    if (searchQuery.value) params.search = searchQuery.value;
    if (aiResultFilter.value) params.ai_result = aiResultFilter.value;
    // if (googleStatusFilter.value) params.google_status = googleStatusFilter.value;
    // if (telegramNotifFilter.value) params.telegram_notif = telegramNotifFilter.value;
    // if (sortBy.value !== 'created_at') params.sort_by = sortBy.value;
    // if (sortOrder.value !== 'desc') params.sort_order = sortOrder.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    router.get('/terms', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear filters
const clearFilters = () => {
    searchQuery.value = '';
    aiResultFilter.value = '';
    // googleStatusFilter.value = '';
    // telegramNotifFilter.value = '';
    // sortBy.value = 'created_at';
    // sortOrder.value = 'desc';
    dateFrom.value = '';
    dateTo.value = '';
    
    router.get('/terms', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Export to Excel
const exportToExcel = () => {
    const params: Record<string, any> = { export: 'excel' };
    
    if (searchQuery.value) params.search = searchQuery.value;
    if (aiResultFilter.value) params.ai_result = aiResultFilter.value;
    // if (googleStatusFilter.value) params.google_status = googleStatusFilter.value;
    // if (telegramNotifFilter.value) params.telegram_notif = telegramNotifFilter.value;
    // if (sortBy.value !== 'created_at') params.sort_by = sortBy.value;
    // if (sortOrder.value !== 'desc') params.sort_order = sortOrder.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    // Create a temporary link to download the file
    const queryString = new URLSearchParams(params).toString();
    const url = `/terms?${queryString}`;
    
    // Open in new window to trigger download
    window.open(url, '_blank');
};
</script>

<template>
    <!-- Filters Section -->
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Filter class="h-5 w-5" />
                Filters & Search
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- First Row: Search, AI Result, Google Status, Telegram -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 items-end">
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

                <!-- Date From -->
                <div class="space-y-2">
                    <Label for="date-from">Date From</Label>
                    <div class="relative">
                        <Calendar class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="date-from"
                            v-model="dateFrom"
                            type="date"
                            class="pl-8"
                        />
                    </div>
                </div>

                <!-- Date To -->
                <div class="space-y-2">
                    <Label for="date-to">Date To</Label>
                    <div class="relative">
                        <Calendar class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="date-to"
                            v-model="dateTo"
                            type="date"
                            class="pl-8"
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
                        <option value="relevan">Relevan</option>
                        <option value="negatif">Negative</option>
                        <option value="null">Null</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-2">
                    <div class="flex flex-wrap gap-2">
                        <Button @click="applyFilters" class="flex items-center gap-2">
                            <Search class="h-4 w-4" />
                            Apply
                        </Button>
                        <Button variant="outline" @click="clearFilters">
                            Clear
                        </Button>
                    </div>
                </div>

                <!-- Google Status Filter -->
                <!-- <div class="space-y-2">
                    <Label for="google-status">Google Ads Status</Label>
                    <select
                        id="google-status"
                        v-model="googleStatusFilter"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">All Google Status</option>
                        <option value="sukses">Sukses</option>
                        <option value="gagal">Gagal</option>
                        <option value="error">Error</option>
                        <option value="null">Null</option>
                    </select>
                </div> -->

                <!-- Telegram Notification Filter -->
                <!-- <div class="space-y-2">
                    <Label for="telegram-notif">Telegram Notification</Label>
                    <select
                        id="telegram-notif"
                        v-model="telegramNotifFilter"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">All Telegram Status</option>
                        <option value="sukses">Sukses</option>
                        <option value="gagal">Gagal</option>
                        <option value="null">Null</option>
                    </select>
                </div> -->
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