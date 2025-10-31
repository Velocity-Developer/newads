<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Trash2, Power, PowerOff, Upload } from 'lucide-vue-next';
import BlacklistWordsFilters from '@/components/BlacklistWordsFilters.vue';
import BlacklistWordModal from '@/components/BlacklistWordModal.vue';
import BlacklistWordsImportModal from '@/components/BlacklistWordsImportModal.vue';

type Item = {
  id: number;
  word: string;
  active: boolean;
  notes?: string | null;
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
  items: Paginator<Item>;
  filters: { q?: string; active?: boolean | string | null };
}

const props = defineProps<Props>();

const normalizeActive = (val: any): boolean | null => {
  if (val === null || val === undefined) return null;
  if (typeof val === 'boolean') return val;
  // Jika '1'/'0' atau 'true'/'false' string
  if (typeof val === 'string') {
    if (val === '1') return true;
    if (val === '0') return false;
    if (val.toLowerCase() === 'true') return true;
    if (val.toLowerCase() === 'false') return false;
  }
  return !!val;
};

const items = ref<Item[]>(props.items?.data ?? []);
const filters = ref<{ q?: string; active?: boolean | null }>({
  q: props.filters?.q ?? '',
  active: normalizeActive(props.filters?.active),
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Blacklist Words', href: '/blacklist-words' },
];

// Badge variant untuk status
const getStatusBadgeVariant = (active: boolean) => {
  return active ? 'default' : 'secondary';
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleString();
};

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  const perPage = parseInt(target.value);
  const params: Record<string, any> = { ...props.filters };
  params.per_page = perPage;
  
  router.get('/blacklist-words', params, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Sinkronkan saat props berubah (mis. setelah filter/tambah/update dengan preserveState)
watch(() => props.items, (val) => {
  items.value = val?.data ?? [];
});

watch(() => props.filters, (val) => {
  filters.value.q = val?.q ?? '';
  filters.value.active = normalizeActive(val?.active);
});

function refresh() {
  const query: any = {};
  if (filters.value.q) query.q = filters.value.q;
  if (filters.value.active !== null && filters.value.active !== undefined)
    query.active = filters.value.active ? 1 : 0;

  router.get('/blacklist-words', query, { preserveScroll: true, preserveState: true });
}

function updateWord(item: Item) {
  router.put(`/blacklist-words/${item.id}`, {
    word: item.word.trim(),
    active: item.active,
    notes: item.notes || null,
  }, { onSuccess: refresh });
}

function toggleActive(item: Item) {
  router.post(`/blacklist-words/${item.id}/toggle`, {}, { onSuccess: refresh });
}

function removeWord(item: Item) {
  if (!confirm(`Hapus "${item.word}" dari blacklist?`)) return;
  router.delete(`/blacklist-words/${item.id}`, { onSuccess: refresh });
}

function importLocal() {
  if (!confirm('Import dari file .txt di repo? Semua kata akan di-set aktif. Lanjutkan?')) return;
  router.post('/blacklist-words/import-local', {}, {
    onSuccess: () => {
      refresh();
    },
  });
}
</script>

<template>
  <Head title="Blacklist Words" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
      <!-- Filter -->
      <BlacklistWordsFilters :filters="filters" />

      <!-- Aksi: Tambah & Import -->
      <div class="flex items-center gap-2">
        <BlacklistWordModal mode="create" @saved="refresh" />
        <BlacklistWordsImportModal @imported="refresh" />
        <!-- Opsi import dari file repo (tetap tersedia) -->
        <button
          class="inline-flex items-center gap-2 px-3 py-2 bg-neutral-700 text-white rounded hover:bg-neutral-800"
          @click="importLocal"
        >
          <Upload class="w-4 h-4" />
          <span class="text-sm">Import .txt (repo)</span>
        </button>
      </div>

      <!-- Blacklist Words Table -->
      <Card>
        <CardHeader>
          <CardTitle>Blacklist Words Data</CardTitle>
          
          <!-- Per Page Selector -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-sm text-muted-foreground">Items per page:</span>
              <select
                :value="props.items.per_page"
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
              Showing {{ props.items.from }} to {{ props.items.to }} of {{ props.items.total }} results
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse">
              <thead class="bg-muted/50">
                <tr class="border-b">
                  <th class="text-left p-2 font-medium">ID</th>
                  <th class="text-left p-2 font-medium">Word</th>
                  <th class="text-left p-2 font-medium">Status</th>
                  <th class="text-left p-2 font-medium">Notes</th>
                  <th class="text-left p-2 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in items" :key="item.id" class="border-b hover:bg-muted/50">
                  <td class="p-2">
                    <div class="font-medium">{{ item.id }}</div>
                  </td>
                  <td class="p-2">
                    <div class="font-medium">{{ item.word }}</div>
                  </td>
                  <td class="p-2">
                    <Badge :variant="getStatusBadgeVariant(item.active)">
                      {{ item.active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                  </td>
                  <td class="p-2">
                    <div class="text-sm text-muted-foreground">{{ item.notes || '-' }}</div>
                  </td>
                  <td class="p-2">
                    <div class="flex items-center gap-2">
                      <!-- Edit via Modal -->
                      <BlacklistWordModal mode="edit" :item="item" @saved="refresh" />

                      <!-- Toggle status dengan ikon dan warna dinamis -->
                      <button
                        class="inline-flex items-center gap-1 p-2 rounded text-white text-xs"
                        :class="item.active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                        @click="toggleActive(item)"
                        :aria-label="item.active ? 'Off' : 'On'"
                      >
                        <component :is="item.active ? PowerOff : Power" class="h-3 w-3" />
                        <span>{{ item.active ? 'Off' : 'On' }}</span>
                      </button>

                      <!-- Hapus -->
                      <button 
                        class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600" 
                        @click="removeWord(item)"
                        aria-label="Delete"
                      >
                        <Trash2 class="h-3 w-3" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="props.items.last_page > 1" class="flex items-center justify-center gap-4 mt-4">
            <div class="text-sm text-muted-foreground">
              Page {{ props.items.current_page }} of {{ props.items.last_page }}
            </div>
            <div class="flex gap-2">
              <Link
                v-for="link in props.items.links"
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

          <!-- Info -->
          <div class="mt-4 text-sm text-muted-foreground">
            <ul class="list-disc list-inside space-y-1">
              <li>Matching case-sensitive; huruf besar/kecil harus persis.</li>
              <li>Status: Aktif = terdeteksi; Nonaktif = tidak terdeteksi.</li>
            </ul>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>