<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { Search, Filter } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Filters {
  q?: string;
  active?: boolean | string | null;
}

interface Props {
  filters: Filters;
}

const props = defineProps<Props>();

const toActiveString = (val: any): string => {
  if (val === null || val === undefined || val === '') return '';
  if (typeof val === 'string') {
    const lower = val.toLowerCase();
    if (val === '1' || lower === 'true') return '1';
    if (val === '0' || lower === 'false') return '0';
    return '';
  }
  return val ? '1' : '0';
};

const searchQuery = ref(props.filters?.q || '');
const active = ref<string>(toActiveString(props.filters?.active));

watch(
  () => props.filters,
  (val) => {
    searchQuery.value = val?.q || '';
    active.value = toActiveString(val?.active);
  }
);

const applyFilters = () => {
  const params: Record<string, any> = {};
  if (searchQuery.value) params.q = searchQuery.value;
  if (active.value !== '') params.active = active.value;

  router.get('/blacklist-words', params, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  active.value = '';

  router.get('/blacklist-words', {}, {
    preserveState: true,
    preserveScroll: true,
  });
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
          <Label for="search">Search Blacklist</Label>
          <div class="relative">
            <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              id="search"
              v-model="searchQuery"
              placeholder="Cari kata..."
              class="pl-8"
              @keyup.enter="applyFilters"
            />
          </div>
        </div>

        <div class="space-y-2">
          <Label for="active">Status</Label>
          <select
            id="active"
            v-model="active"
            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
          >
            <option value="">Semua</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>

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
      </div>
    </CardContent>
  </Card>
</template>