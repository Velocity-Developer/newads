<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Plus, X, Pen, Save } from 'lucide-vue-next';

type Item = {
  id: number;
  word: string;
  active: boolean;
  notes?: string | null;
};

interface Props {
  mode: 'create' | 'edit';
  item?: Item;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'saved'): void }>();

const isOpen = ref(false);
const isSubmitting = ref(false);

// Prefill values for edit mode
const initialWord = computed(() => (props.mode === 'edit' ? props.item?.word ?? '' : ''));
const initialActive = computed(() => (props.mode === 'edit' ? !!props.item?.active : true));
const initialNotes = computed(() => (props.mode === 'edit' ? props.item?.notes ?? '' : ''));

const word = ref<string>(initialWord.value);
const active = ref<boolean>(initialActive.value);
const notes = ref<string>(initialNotes.value);

// Keep local state in sync if `item` changes
watch(
  () => props.item,
  () => {
    word.value = initialWord.value;
    active.value = initialActive.value;
    notes.value = initialNotes.value;
  },
  { immediate: true }
);

const resetForm = () => {
  word.value = '';
  active.value = true;
  notes.value = '';
};

const submit = () => {
  if (!word.value.trim()) return;

  isSubmitting.value = true;

  if (props.mode === 'create') {
    router.post(
      '/blacklist-words',
      {
        word: word.value.trim(),
        active: active.value,
        notes: notes.value || null,
      },
      {
        onFinish: () => {
          isSubmitting.value = false;
        },
        onSuccess: () => {
          resetForm();
          isOpen.value = false;
          emit('saved');
        },
      }
    );
  } else {
    if (!props.item?.id) return;
    router.put(
      `/blacklist-words/${props.item.id}`,
      {
        word: word.value.trim(),
        active: active.value,
        notes: notes.value || null,
      },
      {
        onFinish: () => {
          isSubmitting.value = false;
        },
        onSuccess: () => {
          isOpen.value = false;
          emit('saved');
        },
      }
    );
  }
};
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button v-if="mode === 'create'" variant="success">
        <Plus class="mr-2 h-4 w-4" />
        Tambah Kata
      </Button>
      <Button v-else class="px-2 py-1 bg-[#007bff] text-white">
        <Pen class="h-4 w-4" /></Button>
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ mode === 'create' ? 'Tambah Kata' : 'Edit Kata' }}</DialogTitle>
        <DialogDescription>
          Input case-insensitive ("Halo" dan "halo" dianggap sama).
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4">
        <div class="space-y-2">
          <Label for="word">Kata</Label>
          <Input id="word" v-model="word" placeholder="Mis. Promo" />
        </div>

        <div class="space-y-2">
          <Label for="active">Status</Label>
          <select
            id="active"
            v-model="active"
            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
          >
            <option :value="true">Aktif</option>
            <option :value="false">Nonaktif</option>
          </select>
        </div>

        <div class="space-y-2">
          <Label for="notes">Catatan (opsional)</Label>
          <Input id="notes" v-model="notes" placeholder="Catatan tambahan..." />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="isOpen = false">
            <X class="mr-2 h-4 w-4" /> Batal
        </Button>
        <Button :disabled="isSubmitting || !word.trim()" @click="submit">
            <Save class="mr-2 h-4 w-4" /> Simpan
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>