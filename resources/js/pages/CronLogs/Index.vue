<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

type CronLog = {
  id: number;
  name: string;
  type: string | null;
  started_at: string | null;
  finished_at: string | null;
  duration_ms: number | null;
  status: 'running' | 'success' | 'failed';
  error: string | null;
  created_at: string;
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
  logs: Paginator<CronLog>;
}

const props = defineProps<Props>();
const logs = ref<CronLog[]>(props.logs?.data ?? []);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Cron Logs', href: '/cron-logs' },
];

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'success':
      return 'success';
    case 'failed':
      return 'destructive';
    case 'running':
      return 'default'; // or 'info' if available
    default:
      return 'secondary';
  }
};

const formatDate = (dateString: string | null) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleString();
};

const formatDuration = (ms: number | null) => {
  if (ms === null) return '-';
  if (ms < 1000) return `${ms}ms`;
  return `${(ms / 1000).toFixed(2)}s`;
};

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  const perPage = parseInt(target.value);
  
  router.get('/cron-logs', { per_page: perPage }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const confirmReset = () => {
  if (confirm('Are you sure you want to delete all cron logs? This action cannot be undone.')) {
    router.delete('/cron-logs/clear', {
      preserveScroll: true,
      onSuccess: () => {
        // Optional: Show a toast notification here
      },
    });
  }
};

watch(() => props.logs, (val) => {
  logs.value = val?.data ?? [];
});
</script>

<template>
  <Head title="Cron Logs" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 md:p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Cron Logs</h1>
          <p class="text-sm text-muted-foreground">Monitor system tasks and jobs</p>
        </div>
        <Button variant="destructive" @click="confirmReset">
          Reset Logs
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Log History</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="relative w-full overflow-auto">
            <table class="w-full caption-bottom text-sm">
              <thead class="[&_tr]:border-b">
                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">No</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Started At</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Finished At</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Duration</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Error</th>
                </tr>
              </thead>
              <tbody class="[&_tr:last-child]:border-0">
                <tr v-if="logs.length === 0">
                  <td colspan="8" class="p-4 text-center text-muted-foreground">No logs found.</td>
                </tr>
                <tr v-for="log, index in logs" :key="log.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                  <td class="p-4 align-middle">{{ props.logs.from + index }}</td>
                  <td class="p-4 align-middle font-medium">{{ log.name }}</td>
                  <td class="p-4 align-middle text-muted-foreground">{{ log.type }}</td>
                  <td class="p-4 align-middle">{{ formatDate(log.started_at) }}</td>
                  <td class="p-4 align-middle">{{ formatDate(log.finished_at) }}</td>
                  <td class="p-4 align-middle">{{ formatDuration(log.duration_ms) }}</td>
                  <td class="p-4 align-middle">
                    <Badge :variant="getStatusBadgeVariant(log.status)">{{ log.status }}</Badge>
                  </td>
                  <td class="p-4 align-middle max-w-[200px] truncate" :title="log.error ?? ''">
                    <span v-if="log.error" class="text-destructive">{{ log.error }}</span>
                    <span v-else>-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="flex items-center justify-between py-4" v-if="props.logs.total > 0">
            <div class="flex items-center space-x-2 text-sm text-muted-foreground">
               <span>
                Showing {{ props.logs.from }} to {{ props.logs.to }} of {{ props.logs.total }} results
              </span>
              <select class="h-8 w-16 rounded border border-input bg-background px-2 text-sm" 
                :value="props.logs.per_page"
                @change="changePerPage">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
            <div class="flex items-center space-x-2">
              <template v-for="(link, i) in props.logs.links" :key="i">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                  :class="{ 'bg-primary text-primary-foreground shadow hover:bg-primary/90': link.active, 'border-input bg-background shadow-sm': !link.active }"
                  v-html="link.label"
                />
                <span
                  v-else
                  class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md border border-input bg-muted px-3 text-sm font-medium opacity-50"
                  v-html="link.label"
                />
              </template>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
