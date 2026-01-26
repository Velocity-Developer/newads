<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { BreadcrumbItem, User } from '@/types';
import { Pencil, Trash2, Plus } from 'lucide-vue-next';

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
  users: Paginator<User>;
  filters: { search?: string; sort_by?: string; sort_order?: string; per_page?: number };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Users', href: '/users' },
];

const searchQuery = ref(props.filters.search || '');
const perPage = ref(props.users.per_page || 15);
const isDialogOpen = ref(false);
const isEditing = ref(false);
const editingUserId = ref<number | null>(null);

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

watch(searchQuery, (val) => {
  const params: Record<string, any> = { search: val, per_page: perPage.value };
  router.get('/users', params, { preserveState: true, preserveScroll: true });
});

const changePerPage = (e: Event) => {
  const select = e.target as HTMLSelectElement;
  perPage.value = Number(select.value);
  const params: Record<string, any> = { per_page: perPage.value };
  if (searchQuery.value) params.search = searchQuery.value;
  router.get('/users', params, { preserveState: true, preserveScroll: true });
};

const stripTags = (s: string) => s.replace(/<[^>]*>/g, '');

const openAddDialog = () => {
  isEditing.value = false;
  editingUserId.value = null;
  form.reset();
  form.clearErrors();
  isDialogOpen.value = true;
};

const openEditDialog = (user: User) => {
  isEditing.value = true;
  editingUserId.value = user.id;
  form.name = user.name;
  form.email = user.email;
  form.password = '';
  form.password_confirmation = '';
  form.clearErrors();
  isDialogOpen.value = true;
};

const submit = () => {
  if (isEditing.value && editingUserId.value) {
    form.put(`/users/${editingUserId.value}`, {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      },
    });
  } else {
    form.post('/users', {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      },
    });
  }
};

const deleteUser = (user: User) => {
  if (confirm(`Apakah Anda yakin ingin menghapus user ${user.name}?`)) {
    router.delete(`/users/${user.id}`);
  }
};
</script>

<template>
  <Head title="Users" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Daftar Users</CardTitle>
            <Button @click="openAddDialog">
              <Plus/> Tambah User
            </Button>
          </div>
          <div class="flex flex-col md:flex-row md:items-center justify-end md:justify-between mt-3 gap-4">
            <div class="flex items-center gap-2">
              <Input
                v-model="searchQuery"
                placeholder="Cari nama atau email"
                class="w-64"
              />
              <Button variant="secondary" @click="router.get('/users', { search: searchQuery, per_page: perPage }, { preserveState: true, preserveScroll: true })">
                Cari
              </Button>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm text-muted-foreground">Items per page:</span>
              <select
                :value="users.per_page"
                @change="changePerPage"
                class="flex h-8 w-20 rounded-md border border-input bg-background px-2 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
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
                  <th class="text-left p-2 font-medium">Email</th>
                  <th class="text-left p-2 font-medium">Verifikasi Email</th>
                  <th class="text-left p-2 font-medium">Dibuat</th>
                  <th class="text-right p-2 font-medium">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="u in users.data" :key="u.id" class="border-b hover:bg-muted/50">
                  <td class="p-2">{{ u.id }}</td>
                  <td class="p-2">{{ u.name }}</td>
                  <td class="p-2">{{ u.email }}</td>
                  <td class="p-2">
                    <span class="text-sm" :class="u.email_verified_at ? 'text-green-600' : 'text-red-600'">
                      {{ u.email_verified_at ? 'Terverifikasi' : 'Belum' }}
                    </span>
                  </td>
                  <td class="p-2">
                    <span class="text-sm text-muted-foreground">
                      {{ new Date(u.created_at).toLocaleString() }}
                    </span>
                  </td>
                  <td class="p-2 text-right">
                    <div class="flex justify-end gap-2">
                      <Button variant="outline" size="icon" @click="openEditDialog(u)">
                        <Pencil class="h-4 w-4" />
                      </Button>
                      <Button variant="destructive" size="icon" @click="deleteUser(u)">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="users.last_page > 1" class="flex items-center justify-center gap-4 mt-4">
            <div class="text-sm text-muted-foreground">
              Halaman {{ users.current_page }} dari {{ users.last_page }}
            </div>
            <div class="flex gap-2">
              <Link
                v-for="link in users.links"
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
              >
                {{ stripTags(link.label) }}
              </Link>
            </div>
          </div>
          <div class="mt-5 text-sm text-muted-foreground">
            Menampilkan {{ users.from }} sampai {{ users.to }} dari {{ users.total }} hasil
          </div>
        </CardContent>
      </Card>

      <Dialog v-model:open="isDialogOpen">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>{{ isEditing ? 'Edit User' : 'Tambah User' }}</DialogTitle>
            <DialogDescription>
              {{ isEditing ? 'Ubah detail user di sini.' : 'Tambahkan user baru ke sistem.' }}
            </DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submit" class="grid gap-4 py-4">
            <div class="grid grid-cols-4 items-center gap-4">
              <Label for="name" class="text-right">
                Nama
              </Label>
              <div class="col-span-3">
                <Input
                  id="name"
                  v-model="form.name"
                  class="col-span-3"
                  :class="{ 'border-red-500': form.errors.name }"
                />
                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
              </div>
            </div>
            <div class="grid grid-cols-4 items-center gap-4">
              <Label for="email" class="text-right">
                Email
              </Label>
              <div class="col-span-3">
                <Input
                  id="email"
                  type="email"
                  v-model="form.email"
                  class="col-span-3"
                  :class="{ 'border-red-500': form.errors.email }"
                />
                <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
              </div>
            </div>
            <div class="grid grid-cols-4 items-center gap-4">
              <Label for="password" class="text-right">
                Password
              </Label>
              <div class="col-span-3">
                <Input
                  id="password"
                  type="password"
                  v-model="form.password"
                  class="col-span-3"
                  :class="{ 'border-red-500': form.errors.password }"
                  :placeholder="isEditing ? 'Biarkan kosong jika tidak diubah' : ''"
                />
                <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
              </div>
            </div>
            <div class="grid grid-cols-4 items-center gap-4">
              <Label for="password_confirmation" class="text-right">
                Konfirmasi
              </Label>
              <div class="col-span-3">
                <Input
                  id="password_confirmation"
                  type="password"
                  v-model="form.password_confirmation"
                  class="col-span-3"
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="submit" :disabled="form.processing">
                {{ isEditing ? 'Simpan Perubahan' : 'Tambah User' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
