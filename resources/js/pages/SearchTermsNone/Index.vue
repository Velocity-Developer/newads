<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import SearchTermGetUpdate from '@/components/SearchTerm/GetUpdate.vue';
import { ref } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface SearchTermItem {
  id: number;
  term: string;
  source?: string | null;
  check_ai: string | null;
  iklan_dibuat: boolean;
  failure_count: number;
  waktu: string | null;
  created_at: string;
  updated_at: string;
  waktu_local: string;
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
  analytics: {
    total_by_check_ai: Array<{ check_ai: string | null; total: number }>;
    total_by_iklan_dibuat: Array<{ iklan_dibuat: boolean; total: number }>;
    total_new_data_by_date: Array<{ date: string; total: number }>;
  };
}

const props = defineProps<Props>();

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

// Form handling
const isDialogOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<number | null>(null);

const form = useForm({
  term: '',
});

const openAddDialog = () => {
  isEditing.value = false;
  currentId.value = null;
  form.reset();
  form.clearErrors();
  isDialogOpen.value = true;
};

const openEditDialog = (item: SearchTermItem) => {
  isEditing.value = true;
  currentId.value = item.id;
  form.term = item.term;
  form.clearErrors();
  isDialogOpen.value = true;
};

const submitForm = () => {
  if (isEditing.value && currentId.value) {
    form.put(`/search-terms-none/${currentId.value}`, {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      },
    });
  } else {
    form.post('/search-terms-none', {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      },
    });
  }
};

const deleteTerm = (item: SearchTermItem) => {
  if (confirm('Apakah Anda yakin ingin menghapus search term ini?')) {
    router.delete(`/search-terms-none/${item.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        // Optional: Show success message toast
      },
    });
  }
};

// Selection handling
const selectedIds = ref<number[]>([]);
const selectedTerms = ref([]);
const isAllSelected = computed(() => {
  const data = props.items?.data || [];
  if (data.length === 0) return false;
  return data.every(item => selectedIds.value.includes(item.id));
});

const toggleAll = (checked: boolean) => {
  const data = props.items?.data || [];
  if (data.length === 0) return;

  if (checked) {
    const newIds = data.map(item => item.id);
    selectedIds.value = [...new Set([...selectedIds.value, ...newIds])];
  } else {
    const pageIds = data.map(item => item.id);
    selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id));
  }
  console.log(checked)
};

const toggleSelection = (term: SearchTermItem, checked: boolean) => {
  // if (checked) {
  //   if (!selectedTerms.value.some(t => t.id === term.id)) {
  //     selectedTerms.value.push(term)
  //   }
  // } else {
  //   selectedTerms.value = selectedTerms.value.filter(
  //     t => t.id !== term.id
  //   )
  // }
  console.log(term)
};
</script>

<template>
  <Head title="Search Terms NONE" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">

      <!-- Analytics Cards -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <h3 class="font-semibold leading-none tracking-tight">Status Check AI</h3>
          </CardHeader>
          <CardContent>
            <div v-for="item in analytics.total_by_check_ai" :key="item.check_ai || 'null'" class="flex justify-between py-1 text-sm">
              <span class="text-muted-foreground">{{ item.check_ai || 'Belum Dicek' }}</span>
              <span class="font-medium">{{ item.total }}</span>
            </div>
            <div v-if="analytics.total_by_check_ai.length === 0" class="text-sm text-muted-foreground py-1">
              Tidak ada data
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <h3 class="font-semibold leading-none tracking-tight">Status Iklan Dibuat</h3>
          </CardHeader>
          <CardContent>
            <div v-for="item in analytics.total_by_iklan_dibuat" :key="String(item.iklan_dibuat)" class="flex justify-between py-1 text-sm">
              <span class="text-muted-foreground">{{ item.iklan_dibuat ? 'Sudah' : 'Belum' }}</span>
              <span class="font-medium">{{ item.total }}</span>
            </div>
            <div v-if="analytics.total_by_iklan_dibuat.length === 0" class="text-sm text-muted-foreground py-1">
              Tidak ada data
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <h3 class="font-semibold leading-none tracking-tight">Data Baru (30 Hari)</h3>
          </CardHeader>
          <CardContent class="max-h-40 overflow-y-auto">
            <div v-for="item in analytics.total_new_data_by_date" :key="item.date" class="flex justify-between py-1 text-sm">
              <span class="text-muted-foreground">{{ item.date }}</span>
              <span class="font-medium">{{ item.total }}</span>
            </div>
            <div v-if="analytics.total_new_data_by_date.length === 0" class="text-sm text-muted-foreground py-1">
              Tidak ada data baru dalam 30 hari terakhir
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="flex justify-end gap-2">
        <Button @click="openAddDialog" class="gap-2">
          <Plus class="h-4 w-4" />
          Tambah Manual
        </Button>
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
                  <th class="px-3 py-2 text-left w-[40px]">
                    <Checkbox
                      :checked="isAllSelected"
                      @update:checked="(checked: boolean) => toggleAll(checked)"
                    />
                  </th>
                  <th class="px-3 py-2 text-left">No</th>
                  <th class="px-3 py-2 text-left">Term</th>
                  <th class="px-3 py-2 text-left">Check AI</th>
                  <th class="px-3 py-2 text-left">Iklan Dibuat</th>
                  <th class="px-3 py-2 text-left">Failure Count</th>
                  <th class="px-3 py-2 text-left">Tanggal</th>
                  <th class="px-3 py-2 text-left">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item, index in items.data" :key="item.id" class="border-b">
                  <td class="px-3 py-2">
                    <Checkbox
                      :checked="selectedIds.includes(item.id)"
                      @update:checked="(checked: boolean) => toggleSelection(item, checked)"
                    />
                  </td>
                  <td class="px-3 py-2">{{ Number(items.from + index) }}</td>
                  <td class="px-3 py-2">{{ item.term }}</td>
                  <td class="px-3 py-2">{{ item.check_ai ?? 'NONE' }}</td>
                  <td class="px-3 py-2">
                    <span
                      :class="item.iklan_dibuat ? 'text-green-600' : 'text-gray-500'"
                    >
                      {{ item.iklan_dibuat ? 'Sudah' : 'Belum' }}
                    </span>
                  </td>
                  <td class="px-3 py-2">{{ item.failure_count }}</td>
                  <td class="px-3 py-2">{{ item.waktu_local }}</td>
                  <td class="px-3 py-2">
                    <div class="flex items-center gap-1" v-if="item.source === 'manual'">
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        @click="openEditDialog(item)"
                      >
                        <Pencil class="h-4 w-4" />
                        <span class="sr-only">Edit</span>
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50"
                        @click="deleteTerm(item)"
                      >
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Delete</span>
                      </Button>
                    </div>
                  </td>
                </tr>
                <tr v-if="items.data.length === 0">
                  <td class="px-3 py-6 text-center text-muted-foreground" colspan="8">
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

  <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ isEditing ? 'Edit Search Term' : 'Tambah Search Term' }}</DialogTitle>
        <DialogDescription>
          {{ isEditing ? 'Edit data search term di sini.' : 'Tambahkan search term baru secara manual.' }}
        </DialogDescription>
      </DialogHeader>
      <div class="grid gap-4 py-4">
        <div class="grid grid-cols-4 items-center gap-4">
          <Label htmlFor="term" class="text-right">
            Term
          </Label>
          <Textarea
            id="term"
            v-model="form.term"
            class="col-span-3"
            placeholder="Masukkan search term..."
            :class="{ 'border-red-500': form.errors.term }"
          />
        </div>
        <div v-if="form.errors.term" class="grid grid-cols-4 gap-4">
          <div class="col-span-1"></div>
          <div class="col-span-3 text-sm text-red-500">
            {{ form.errors.term }}
          </div>
        </div>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="isDialogOpen = false">
          Batal
        </Button>
        <Button type="submit" @click="submitForm" :disabled="form.processing">
          {{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
