<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import BlacklistWordsFilters from '@/components/BlacklistWordsFilters.vue';

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

function addWord() {
  router.post('/blacklist-words', {
    word: newWord.value.trim(),
    active: newActive.value,
    notes: newNotes.value || null,
  }, {
    onSuccess: () => {
      newWord.value = '';
      newActive.value = true;
      newNotes.value = '';
      refresh();
    },
  });
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

const newWord = ref<string>('');
const newActive = ref<boolean>(true);
const newNotes = ref<string>('');
</script>

<template>
  <Head title="Blacklist Words" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <!-- Filter -->
      <!-- Ganti blok filter lama dengan komponen bergaya seperti Terms/Frasa -->
      <BlacklistWordsFilters :filters="filters" />

      <!-- Tambah Kata -->
      <div class="border rounded p-3">
        <h2 class="font-medium mb-2">Tambah Kata</h2>
        <div class="flex gap-2">
          <input v-model="newWord" type="text" class="border px-2 py-1 rounded flex-1" placeholder="Kata (exact-case)">
          <select v-model="newActive" class="border px-2 py-1 rounded">
            <option :value="true">Aktif</option>
            <option :value="false">Nonaktif</option>
          </select>
          <input v-model="newNotes" type="text" class="border px-2 py-1 rounded flex-1" placeholder="Catatan (opsional)">
          <button class="px-3 py-2 bg-green-600 text-white rounded" @click="addWord">Tambah</button>
        </div>
      </div>

      <!-- Tabel -->
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100">
            <th class="border p-2 text-left">Word</th>
            <th class="border p-2 text-left">Active</th>
            <th class="border p-2 text-left">Notes</th>
            <th class="border p-2">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td class="border p-2">
              <input v-model="item.word" class="border px-2 py-1 rounded w-full">
            </td>
            <td class="border p-2">
              <span :class="item.active ? 'text-green-700' : 'text-gray-500'">{{ item.active ? 'Aktif' : 'Nonaktif' }}</span>
            </td>
            <td class="border p-2">
              <input v-model="item.notes" class="border px-2 py-1 rounded w-full">
            </td>
            <td class="border p-2 space-x-2">
              <button class="px-2 py-1 bg-blue-500 text-white rounded" @click="updateWord(item)">Simpan</button>
              <button class="px-2 py-1 bg-yellow-500 text-white rounded" @click="toggleActive(item)">{{ item.active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
              <button class="px-2 py-1 bg-red-600 text-white rounded" @click="removeWord(item)">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>

      <p class="text-sm text-gray-500">Catatan: matching case-sensitive; huruf besar/kecil harus persis.</p>
    </div>
  </AppLayout>
</template>

<style scoped>
table th, table td { font-size: 14px; }
</style>