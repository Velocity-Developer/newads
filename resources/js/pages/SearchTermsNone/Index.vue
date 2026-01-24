<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Head, Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import SearchTermGetUpdate from '@/components/SearchTerm/GetUpdate.vue';
import { ref } from 'vue';

interface SearchTermItem {
  id: number;
  term: string;
  check_ai: string | null;
  iklan_dibuat: boolean;
  failure_count: number;
  waktu: string | null;
  created_at: string;
  updated_at: string;
}

interface Props {
  items: {
    data: SearchTermItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  filters: {
    sort_by?: string;
    sort_order?: string;
    per_page?: number;
  };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Search Terms NONE', href: '/search-terms-none' },
];

const urlParams = new URLSearchParams(window.location.search);
const searchQuery = ref(urlParams.get('search') || '');

const changePerPage = (e: Event) => {
  const select = e.target as HTMLSelectElement;
  const perPage = Number(select.value);
  const params: Record<string, any> = { per_page: perPage };
  if (searchQuery.value) params.search = searchQuery.value;
  router.get('/search-terms-none', params, { preserveState: true, preserveScroll: true });
};

//reload page when update search terms
const reloadPage = () => {
  router.get('/search-terms-none', { page: 1 }, { preserveScroll: true });
}

const applySearch = () => {
  const params: Record<string, any> = {};
  if (searchQuery.value) params.search = searchQuery.value;
  const perPage = urlParams.get('per_page');
  if (perPage) params.per_page = Number(perPage);
  params.page = 1;
  router.get('/search-terms-none', params, { preserveState: true, preserveScroll: true });
};

const clearSearch = () => {
  searchQuery.value = '';
  const params: Record<string, any> = {};
  const perPage = urlParams.get('per_page');
  if (perPage) params.per_page = Number(perPage);
  router.get('/search-terms-none', params, { preserveState: true, preserveScroll: true });
};
</script>

<template>
  <Head title="Search Terms NONE" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">

      <div class="flex justify-end">
        <SearchTermGetUpdate @update="reloadPage" />
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
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
              <input
                v-model="searchQuery"
                placeholder="Cari term..."
                class="flex h-8 w-56 rounded-md border border-input bg-background px-2 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                @keyup.enter="applySearch"
              />
              <button
                class="h-8 rounded-md border px-3 text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
                @click="applySearch"
              >
                Cari
              </button>
              <button
                class="h-8 rounded-md border px-3 text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
                @click="clearSearch"
              >
                Reset
              </button>
            </div>
            <div class="text-sm text-muted-foreground">
              Total: {{ items.total }}
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="px-3 py-2 text-left">ID</th>
                  <th class="px-3 py-2 text-left">Term</th>
                  <th class="px-3 py-2 text-left">Failure Count</th>
                  <th class="px-3 py-2 text-left">Waktu</th>
                  <th class="px-3 py-2 text-left">Check AI</th>
                  <th class="px-3 py-2 text-left">Iklan Dibuat</th>
                  <th class="px-3 py-2 text-left">Created At</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item, index in items.data" :key="item.id" class="border-b">
                  <td class="px-3 py-2">{{ Number(items.from + index) }}</td>
                  <td class="px-3 py-2">{{ item.term }}</td>
                  <td class="px-3 py-2">{{ item.failure_count }}</td>
                  <td class="px-3 py-2">{{ item.waktu ?? '-' }}</td>
                  <td class="px-3 py-2">{{ item.check_ai ?? 'NONE' }}</td>
                  <td class="px-3 py-2">
                    <span
                      :class="item.iklan_dibuat ? 'text-green-600' : 'text-gray-500'"
                    >
                      {{ item.iklan_dibuat ? 'Ya' : 'Tidak' }}
                    </span>
                  </td>
                  <td class="px-3 py-2">{{ item.created_at }}</td>
                </tr>
                <tr v-if="items.data.length === 0">
                  <td class="px-3 py-6 text-center text-muted-foreground" colspan="7">
                    Tidak ada data.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex items-center gap-2">
            <template v-for="(link, idx) in items.links" :key="idx">
              <Link
                v-if="link.url"
                :href="link.url"
                class="rounded-md px-3 py-1 text-sm"
                :class="link.active ? 'bg-neutral-200 dark:bg-neutral-800' : 'hover:bg-neutral-100 dark:hover:bg-neutral-800'"
              >
                <span v-html="link.label" />
              </Link>
              <span v-else class="rounded-md px-3 py-1 text-sm" v-html="link.label" />
            </template>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
