<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Upload } from 'lucide-vue-next';

const open = ref(false);
const file = ref<File | null>(null);
const activeDefault = ref(true);

const emit = defineEmits<{
  (e: 'imported'): void;
}>();

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  file.value = target.files && target.files[0] ? target.files[0] : null;
}

function reset() {
  file.value = null;
  activeDefault.value = true;
}

function submit() {
  if (!file.value) return;
  const fd = new FormData();
  fd.append('file', file.value);
  fd.append('active', activeDefault.value ? '1' : '0');

  router.post('/blacklist-words/import-upload', fd, {
    onSuccess: () => {
      emit('imported');
      open.value = false;
      reset();
    },
  });
}
</script>

<template>
  <div>
    <button
      class="inline-flex items-center gap-2 px-3 py-2 outline outline-1 outline-neutral-700 rounded hover:bg-neutral-800 hover:text-white text-sm"
      @click="open = true"
    >
      <Upload class="w-4 h-4" />
      <span class="text-sm">Import .txt (manual)</span>
    </button>

    <Dialog v-model:open="open">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Import Blacklist dari File .txt</DialogTitle>
        </DialogHeader>

        <div class="space-y-4">
          <div class="space-y-1">
            <label class="text-sm font-medium">Pilih file .txt</label>
            <input
              type="file"
              accept=".txt"
              class="block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-neutral-700 file:text-white hover:file:bg-neutral-800"
              @change="onFileChange"
            />
            <p class="text-xs text-neutral-500">
              Format: 1 kata per baris. Baris kosong diabaikan. Duplikat di-skip.
            </p>
          </div>

          <div class="space-y-1">
            <label class="text-sm font-medium">Status default</label>
            <select
              v-model="activeDefault"
              class="w-full rounded border border-neutral-300 bg-white px-2 py-1 text-sm"
            >
              <option :value="true">Aktif</option>
              <option :value="false">Nonaktif</option>
            </select>
          </div>
        </div>

        <DialogFooter>
          <button
            class="px-3 py-2 rounded border border-neutral-300 text-sm hover:bg-neutral-50"
            @click="open = false"
          >
            Batal
          </button>
          <button
            class="px-3 py-2 rounded bg-neutral-700 text-white text-sm hover:bg-neutral-800 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!file"
            @click="submit"
          >
            Import
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>