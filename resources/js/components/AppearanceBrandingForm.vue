<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { update } from '@/routes/appearance';

const page = usePage();
const branding = page.props.branding as {
  siteTitle?: string;
  sidebarTitle?: string;
};

const form = useForm({
  site_title: branding?.siteTitle ?? '',
  sidebar_title: branding?.sidebarTitle ?? '',
  sidebar_icon: null as File | null,
  favicon: null as File | null,
  apple_touch_icon: null as File | null,
});

function submit() {
  form.post(update().url, {
    forceFormData: true,
    preserveScroll: true,
  });
}
</script>

<template>
  <div class="space-y-6">
    <div class="space-y-2">
      <label class="text-sm font-medium">General site title</label>
      <Input type="text" v-model="form.site_title" placeholder="My Awesome App" />
    </div>

    <div class="space-y-2">
      <label class="text-sm font-medium">Sidebar title</label>
      <Input type="text" v-model="form.sidebar_title" placeholder="My Sidebar" />
    </div>

    <div class="space-y-2">
      <label class="text-sm font-medium">Sidebar icon (png/jpg/svg)</label>
      <Input type="file" accept=".png,.jpg,.jpeg,.svg" @change="(e: any) => (form.sidebar_icon = e.target.files?.[0] ?? null)" />
    </div>

    <div class="space-y-2">
      <label class="text-sm font-medium">Website favicon (ico/png/svg)</label>
      <Input type="file" accept=".ico,.png,.svg" @change="(e: any) => (form.favicon = e.target.files?.[0] ?? null)" />
    </div>

    <div class="space-y-2">
      <label class="text-sm font-medium">Apple touch icon (png/jpg/svg)</label>
      <Input type="file" accept=".png,.jpg,.jpeg,.svg" @change="(e: any) => (form.apple_touch_icon = e.target.files?.[0] ?? null)" />
    </div>

    <div>
      <Button :disabled="form.processing" @click="submit">Save Branding</Button>
    </div>
  </div>
</template>