<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { Search, Filter } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Filters {
    search?: string;
    ai_result?: string;
    google_status?: string;
    telegram_notif?: string;
    sort_by?: string;
    sort_order?: string;
}

interface Props {
    filters: Filters;
}

const props = defineProps<Props>();

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
                        <option value="relevan">Relevan</option>
                        <option value="negative">Negative</option>
                        <option value="null">Null</option>
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
                        <option value="sukses">Sukses</option>
                        <option value="gagal">Gagal</option>
                        <option value="error">Error</option>
                        <option value="null">Null</option>
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
                        <option value="sukses">Sukses</option>
                        <option value="gagal">Gagal</option>
                        <option value="null">Null</option>
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
</template>