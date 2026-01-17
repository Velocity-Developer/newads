<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import { Search, Calendar } from 'lucide-vue-next';

interface RekapFormItem {
  id: number;
  source: string | null;
  source_id: string | null;
  nama: string | null;
  no_whatsapp: string | null;
  jenis_website: string | null;
  ai_result: string | null;
  via: string | null;
  utm_content: string | null;
  utm_medium: string | null;
  greeting: string | null;
  status: string | null;
  gclid: string | null;
  cek_konversi_ads: boolean | null;
  cek_konversi_nominal: boolean | null;
  kategori_konversi_nominal: string | null;
  tanggal: string | null;
  created_at: string | null;
}

interface Props {
  rekapForms: {
    data: RekapFormItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  filters: {
    search?: string;
    date_from?: string;
    date_to?: string;
    sort_by?: string;
    sort_order?: string;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Rekap Form', href: '/rekap-form' },
];

const searchQuery = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const applyFilters = () => {
  const params: Record<string, any> = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (dateFrom.value) params.date_from = dateFrom.value;
  if (dateTo.value) params.date_to = dateTo.value;

  router.get('/rekap-form', params, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  dateFrom.value = '';
  dateTo.value = '';
  router.get('/rekap-form', {}, { preserveState: true, preserveScroll: true });
};

const changePerPage = (e: Event) => {
  const value = Number((e.target as HTMLSelectElement).value);
  router.get('/rekap-form', { ...props.filters, per_page: value }, {
    preserveScroll: true,
  });
};

const formatDate = (dateStr?: string | null) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  return date.toLocaleString();
};
</script>

<template>
  <Head title="Rekap Form" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Search</label>
              <div class="flex gap-2">
                <Input v-model="searchQuery" placeholder="Nama / WA / GCLID / Source ID" />
                <Button variant="secondary" @click="applyFilters">
                  <Search class="h-4 w-4 mr-1" /> Apply
                </Button>
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Dari tanggal</label>
              <div class="flex gap-2">
                <Input v-model="dateFrom" type="date" />
                <Calendar class="h-4 w-4 mt-2 text-muted-foreground" />
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Sampai tanggal</label>
              <div class="flex gap-2">
                <Input v-model="dateTo" type="date" />
                <Calendar class="h-4 w-4 mt-2 text-muted-foreground" />
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium">Actions</label>
              <div class="flex gap-2">
                <Button variant="default" @click="applyFilters">Apply</Button>
                <Button variant="outline" @click="clearFilters">Clear</Button>
              </div>
            </div>
          </div>
        </CardHeader>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Daftar Rekap Form</CardTitle>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-sm text-muted-foreground">Items per page:</span>
              <select
                :value="rekapForms.per_page"
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
              Showing {{ rekapForms.from }} to {{ rekapForms.to }} of {{ rekapForms.total }} results
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse">
              <thead class="bg-muted/50">
                <tr class="border-b">
                  <th class="text-left p-2 font-medium">ID</th>
                  <th class="text-left p-2 font-medium">Nama</th>
                  <!-- <th class="text-left p-2 font-medium">No WhatsApp</th> -->
                  <th class="text-left p-2 font-medium">GCLID</th>
                  <!-- <th class="text-left p-2 font-medium">Jenis Website</th> -->
                  <!-- <th class="text-left p-2 font-medium">Via</th> -->
                  <th class="text-left p-2 font-medium">Status</th>
                  <th class="text-left p-2 font-medium">Tanggal</th>
                  <!-- <th class="text-left p-2 font-medium">Created At</th> -->
                  <th class="text-left p-2 font-medium">Source</th>
                  <th class="text-left p-2 font-medium">Source ID</th>
                  <!-- <th class="text-left p-2 font-medium">AI Result</th> -->
                  <th class="text-left p-2 font-medium">Cek Konversi Ads</th>
                  <th class="text-left p-2 font-medium">Cek Konversi Nominal</th>
                  <th class="text-left p-2 font-medium">Kategori Nominal</th>
                  <!-- <th class="text-left p-2 font-medium">UTM Content</th> -->
                  <!-- <th class="text-left p-2 font-medium">UTM Medium</th> -->
                  <!-- <th class="text-left p-2 font-medium">Greeting</th> -->
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in rekapForms.data" :key="item.id" class="border-b hover:bg-muted/50">
                  <td class="p-2">
                    <div class="font-medium">{{ item.id }}</div>
                  </td>
                  <td class="p-2">
                    <div class="font-medium">{{ item.nama || '-' }}</div>
                  </td>
                  <!-- <td class="p-2">
                    <div class="text-sm">{{ item.no_whatsapp || '-' }}</div>
                  </td> -->
                  <td class="p-2">
                    <div class="text-sm">{{ item.gclid || '-' }}</div>
                  </td>
                  <!-- <td class="p-2">
                    <div class="text-sm">{{ item.jenis_website || '-' }}</div>
                  </td> -->
                  <!-- <td class="p-2">
                    <div class="text-sm">{{ item.via || '-' }}</div>
                  </td> -->
                  <td class="p-2">
                    <div class="text-sm">{{ item.status || '-' }}</div>
                  </td>
                  <td class="p-2">
                    <span class="text-sm text-muted-foreground">{{ formatDate(item.tanggal) }}</span>
                  </td>
                  <!-- <td class="p-2">
                    <span class="text-sm text-muted-foreground">{{ formatDate(item.created_at) }}</span>
                  </td> -->
                  <td class="p-2">
                    <div class="text-sm">{{ item.source || '-' }}</div>
                  </td>
                  <td class="p-2">
                    <div class="text-sm">{{ item.source_id || '-' }}</div>
                  </td>
                  <!-- <td class="p-2">
                    <div class="text-sm">{{ item.ai_result || '-' }}</div>
                  </td> -->
                  <td class="p-2">
                    <div class="text-sm">{{ item.cek_konversi_ads === true ? 'Ya' : item.cek_konversi_ads === false ? 'Tidak' : '-' }}</div>
                  </td>
                  <td class="p-2">
                    <div class="text-sm">{{ item.cek_konversi_nominal === true ? 'Ya' : item.cek_konversi_nominal === false ? 'Tidak' : '-' }}</div>
                  </td>
                  <td class="p-2">
                    <div class="text-sm">{{ item.kategori_konversi_nominal || '-' }}</div>
                  </td>
                  <!-- <td class="p-2">
                    <div class="text-sm">{{ item.utm_content || '-' }}</div>
                  </td> -->
                  <!-- <td class="p-2">
                    <div class="text-sm">{{ item.utm_medium || '-' }}</div>
                  </td> -->
                  <!-- <td class="p-2">
                    <div class="text-sm">{{ item.greeting || '-' }}</div>
                  </td> -->
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="rekapForms.last_page > 1" class="flex items-center justify-center gap-4 mt-4">
            <div class="text-sm text-muted-foreground">Page {{ rekapForms.current_page }} of {{ rekapForms.last_page }}</div>
            <div class="flex gap-2">
              <Link
                v-for="link in rekapForms.links"
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

