<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';

interface RekapForm {
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
  kirim_konversi?: Array<{
    id: number;
    gclid: string | null;
    jobid: string | null;
    waktu: string | null;
    status: string | null;
    source: string | null;
    rekap_form_id: string | null;
    rekap_form_source: string | null;
    tercatat: boolean | null;
    conversion_action_id: string | null;
    created_at: string | null;
    updated_at?: string | null;
  }>;
}

const props = defineProps<{ rekapForm: RekapForm }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Rekap Form', href: '/rekap-form' },
  { title: `Detail #${props.rekapForm.id}`, href: `/rekap-form/${props.rekapForm.id}` },
];

const formatDate = (dateStr?: string | null) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  return date.toLocaleString('id-ID');
};
</script>

<template>
  <Head :title="`Rekap Form #${rekapForm.id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Rekap Form Detail</h1>
          <p class="text-muted-foreground">Informasi lengkap Rekap Form #{{ rekapForm.id }}</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" as-child>
            <Link href="/rekap-form">Kembali ke Daftar</Link>
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informasi Utama -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Informasi Utama</CardTitle>
            <CardDescription>Detail data utama</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="text-sm text-muted-foreground">Nama</div>
                <div class="font-medium">{{ rekapForm.nama || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">No WhatsApp</div>
                <div class="font-medium">{{ rekapForm.no_whatsapp || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">GCLID</div>
                <div class="font-medium break-all">{{ rekapForm.gclid || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Jenis Website</div>
                <div class="font-medium">{{ rekapForm.jenis_website || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Via</div>
                <div class="font-medium">{{ rekapForm.via || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Status</div>
                <div class="font-medium">{{ rekapForm.status || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Tanggal</div>
                <div class="font-medium">{{ formatDate(rekapForm.tanggal) }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Dibuat</div>
                <div class="font-medium">{{ formatDate(rekapForm.created_at) }}</div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Metadata -->
        <Card>
          <CardHeader>
            <CardTitle>Metadata</CardTitle>
            <CardDescription>Informasi sumber dan UTM</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div>
                <div class="text-sm text-muted-foreground">Source</div>
                <div class="font-medium">{{ rekapForm.source || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Source ID</div>
                <div class="font-medium break-all">{{ rekapForm.source_id || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">UTM Content</div>
                <div class="font-medium">{{ rekapForm.utm_content || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">UTM Medium</div>
                <div class="font-medium">{{ rekapForm.utm_medium || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Greeting</div>
                <div class="font-medium">{{ rekapForm.greeting || '-' }}</div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Analisis & Konversi -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card class="lg:col-span-1">
          <CardHeader>
            <CardTitle>AI Result</CardTitle>
            <CardDescription>Hasil analisis AI</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="text-sm">{{ rekapForm.ai_result || '-' }}</div>
          </CardContent>
        </Card>

        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Status Konversi</CardTitle>
            <CardDescription>Cek konversi ads & nominal</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="text-sm text-muted-foreground">Cek Konversi Ads</div>
                <div class="font-medium">
                  {{ rekapForm.cek_konversi_ads === true ? 'Ya' : rekapForm.cek_konversi_ads === false ? 'Tidak' : '-' }}
                </div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Cek Konversi Nominal</div>
                <div class="font-medium">
                  {{ rekapForm.cek_konversi_nominal === true ? 'Ya' : rekapForm.cek_konversi_nominal === false ? 'Tidak' : '-' }}
                </div>
              </div>
              <div class="md:col-span-2">
                <div class="text-sm text-muted-foreground">Kategori Konversi Nominal</div>
                <div class="font-medium">{{ rekapForm.kategori_konversi_nominal || '-' }}</div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Kirim Konversi Terkait -->
      <Card v-if="rekapForm.kirim_konversi && rekapForm.kirim_konversi.length">
        <CardHeader>
          <CardTitle>Kirim Konversi Terkait</CardTitle>
          <CardDescription>Daftar kirim konversi yang terkait dengan Rekap Form ini</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse">
              <thead class="bg-muted/50">
                <tr class="border-b">
                  <th class="text-left p-2 font-medium">No</th>
                  <th class="text-left p-2 font-medium">ID</th>
                  <th class="text-left p-2 font-medium">GCLID</th>
                  <th class="text-left p-2 font-medium">Status</th>
                  <th class="text-left p-2 font-medium">Waktu</th>
                  <th class="text-left p-2 font-medium">Source</th>
                  <th class="text-left p-2 font-medium">Tercatat</th>
                  <th class="text-left p-2 font-medium">Conversion Action</th>
                  <th class="text-left p-2 font-medium">Created At</th>
                  <th class="text-left p-2 font-medium">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item, index in rekapForm.kirim_konversi" :key="item.id" class="border-b hover:bg-muted/50">
                  <td class="p-2">
                    <div class="text-sm">{{ index + 1 }}</div>
                  </td>
                  <td class="p-2">
                    <div class="font-medium">{{ item.id }}</div>
                  </td>
                  <td class="p-2">
                    <div class="text-sm break-all max-w-[60px] overflow-hidden text-ellipsis whitespace-nowrap">{{ item.gclid || '-' }}</div>
                  </td>
                  <td class="p-2 text-sm">
                    <Badge v-if="item.status === 'success'" variant="success">{{ item.status || '-' }}</Badge>
                    <Badge v-else-if="item.status === 'failed'" variant="danger">{{ item.status || '-' }}</Badge>
                  </td>
                  <td class="p-2">
                    <div class="text-sm">{{ item.waktu || '-' }}</div>
                  </td>
                  <td class="p-2">
                    <div class="text-sm">{{ item.source || '-' }}</div>
                  </td>
                  <td class="p-2">
                    <div class="text-sm">{{ item.tercatat === true ? 'Ya' : item.tercatat === false ? 'Tidak' : '-' }}</div>
                  </td>
                  <td class="p-2">
                    <div class="text-sm">{{ item.conversion_action_id || '-' }}</div>
                  </td>
                  <td class="p-2">
                    <span class="text-sm text-muted-foreground">{{ item.created_at ? formatDate(item.created_at) : '-' }}</span>
                  </td>
                  <td class="p-2">
                    <Button variant="outline" size="sm" as-child>
                      <Link :href="`/kirim-konversi/${item.id}`">Detail</Link>
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
