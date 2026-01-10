<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Send, Loader2, CloudDownload } from 'lucide-vue-next';
import axios from 'axios';
import { toast } from 'vue-sonner'

const isModalOpen = ref(false);
const checkedItems = ref<any[]>([]);
const rekapFormData = ref<any>(null);
const isLoadingRekapForms = ref(false);
const errorRekapForms = ref<string | null>(null);

const openModal = () => {
    isModalOpen.value = true;
    fetchRekapForms();
    //kosongkan checkedItems
    checkedItems.value = [];
};

const fetchRekapForms = async () => {
    isLoadingRekapForms.value = true;
    errorRekapForms.value = null;

    try {
        const response = await fetch('/kirim_konversi/get_list_rekap_forms', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'same-origin', 
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        rekapFormData.value = data;
    } catch (err) {
        errorRekapForms.value = err instanceof Error ? err.message : 'An error occurred';
        console.error('Error fetching rekap forms:', err);
    } finally {
        isLoadingRekapForms.value = false;
    }
};

function formatLocalDate(dateString : string) {
  const date = new Date(dateString)

  const options = {
    timeZone: 'Asia/Jakarta',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  } as any

  const parts = new Intl.DateTimeFormat('en-GB', options).formatToParts(date)
  const get = (type : string) => parts.find(p => p.type === type)?.value

  return `${get('day')}/${get('month')}/${get('year')} ${get('hour')}:${get('minute')}:${get('second')}`
}

// Computed for select all functionality
const isAllSelected = computed(() => {
    if (!rekapFormData.value || !rekapFormData.value.data || rekapFormData.value.data.length === 0) {
        return false;
    }
    return rekapFormData.value.data.every((item: any) => checkedItems.value.some(checked => checked.id === item.id));
});

const isSomeSelected = computed(() => {
    if (!rekapFormData.value || !rekapFormData.value.data || rekapFormData.value.data.length === 0) {
        return false;
    }
    return rekapFormData.value.data.some((item: any) => checkedItems.value.some(checked => checked.id === item.id));
});

const toggleSelectAll = (checked: boolean | string) => {
    if (!rekapFormData.value || !rekapFormData.value.data) return;

    if (checked === true) {
        // Select all - store entire objects
        checkedItems.value = [...rekapFormData.value.data];
    } else {
        // Deselect all
        checkedItems.value = [];
    }
};

const loadingSendKonversi = ref(false);
const countcheckedItems = ref(0);
const countProccess = ref(0);
const sendKonversi = async () => { 
    if (checkedItems.value.length === 0) {
        toast.error('Pilih data yang akan dikirim');
        return;
    }

    loadingSendKonversi.value = true;
    countcheckedItems.value = checkedItems.value.length;
    countProccess.value = 0;

    //loop checkedItems
    for (const item of checkedItems.value) {
        try {
            //kirim konversi dari rekap form ke Velocity Ads
            const response = await axios.post('/kirim_konversi/kirim_konversi_dari_rekap_form', {
                rekapform: item,
            });
            //tambahkan toast success
            toast.success(`Konversi dari rekap form ${item.nama} berhasil dikirim`);
        } catch (error) {
            //tambahkan toast error
            toast.error(`Konversi dari rekap form ${item.nama} gagal dikirim`);
        } finally {
            //tambahkan countProccess
            countProccess.value++;
        }

        //jika semua data selesai diproses
        if (countProccess.value === countcheckedItems.value) {
            //tambahkan toast success
            toast.success(`Semua data selesai diproses`);
            loadingSendKonversi.value = false;
            fetchRekapForms();
        }
    }

}

</script>

<template>
    <Dialog v-model:open="isModalOpen">
        <DialogTrigger as-child>
            <Button @click="openModal">
                <CloudDownload/> Get Update
            </Button>
        </DialogTrigger>
        <DialogContent class="!max-w-4xl lg:!max-w-7xl max-h-[80vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Rekap Forms 'Greeting Ads' </DialogTitle>
                <DialogDescription>
                    List rekap form dari 'Greeting Ads' VDnet
                </DialogDescription>
            </DialogHeader>

            <div class="mt-2 flex justify-between gap-1">  
                <div v-if="rekapFormData" class=":mt-0 flex justify-start gap-1">
                    <div class="border px-4 py-2 rounded">
                        Total : {{ rekapFormData.total }}
                    </div>
                </div>
                <div class="mt-2 md:mt-0 flex justify-end gap-1">                              
                    <Button v-if="checkedItems && checkedItems.length > 0" @click="sendKonversi" class="cursor-pointer !bg-blue-600 hover:!bg-blue-700 text-white dark:!bg-blue-800 dark:hover:!bg-blue-90">
                        <Send /> Kirim Konversi
                    </Button>                     
                    <Button @click="fetchRekapForms"> <Loader2 :class="isLoadingRekapForms?'animate-spin':''"/> Reload</Button>
                </div>
            </div>

            <div class="mt-2">
                
                <!-- Loading Kirim Konversi -->
                <div v-if="loadingSendKonversi" class="flex items-center justify-center py-8">
                    <Loader2 class="animate-spin"/> Kirim konversi : {{ countProccess }}/{{ countcheckedItems }}...
                </div>

                <!-- Loading State -->
                <div v-if="isLoadingRekapForms" class="flex items-center justify-center py-8">
                    <div class="text-muted-foreground">Loading...</div>
                </div>

                <!-- Error State -->
                <div v-else-if="errorRekapForms" class="bg-destructive/10 text-destructive p-4 rounded-md">
                    <p class="font-medium">Error loading data</p>
                    <p class="text-sm">{{ errorRekapForms }}</p>
                </div>

                <!-- Data Display -->
                <div v-else-if="rekapFormData" class="space-y-4">
                    <div class="overflow-auto max-h-[60vh]">
                        <table class="table text-xs w-full">
                            <thead>
                                <tr>
                                    <th class="bg-gray-200 dark:bg-gray-700 px-4 py-2 border border-b text-left font-medium">
                                        <Checkbox
                                            :model-value="isAllSelected"
                                            :indeterminate="isSomeSelected && !isAllSelected"
                                            @update:model-value="toggleSelectAll"
                                            class="bg-white dark:bg-gray-700"
                                        />
                                    </th>
                                    <th class="bg-gray-200 dark:bg-gray-700 px-4 py-2 border border-b text-left font-medium">No</th>
                                    <th class="bg-gray-200 dark:bg-gray-700 px-4 py-2 border border-b text-left font-medium">Form ID</th>
                                    <th class="bg-gray-200 dark:bg-gray-700 px-4 py-2 border border-b text-left font-medium">Status</th>
                                    <th class="bg-gray-200 dark:bg-gray-700 px-4 py-2 border border-b text-left font-medium">gclid</th>
                                    <th class="bg-gray-200 dark:bg-gray-700 px-4 py-2 border border-b text-left font-medium">Created At</th>
                                    <th class="bg-gray-200 dark:bg-gray-700 px-4 py-2 border border-b text-left font-medium">Nama</th>
                                    <th class="bg-gray-200 dark:bg-gray-700 px-4 py-2 border border-b text-left font-medium">No Wa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(data, index) in rekapFormData.data" :key="data.id">
                                    <td class="px-4 py-2 border border-b">
                                        <Checkbox
                                            :model-value="checkedItems.some(item => item.id === data.id)"
                                            @update:model-value="(checked: boolean | string) => {
                                                if (checked === true) {
                                                    checkedItems.push(data);
                                                } else {
                                                    const idx = checkedItems.findIndex(item => item.id === data.id);
                                                    if (idx > -1) {
                                                        checkedItems.splice(idx, 1);
                                                    }
                                                }
                                            }"
                                        />
                                    </td>
                                    <td class="px-4 py-2 border border-b">
                                        {{ Number(index) + 1 }}
                                    </td>
                                    <td class="px-4 py-2 border border-b">
                                        {{ data.id }}
                                    </td>
                                    <td class="px-4 py-2 border border-b">
                                        {{ data.status }}
                                    </td>
                                    <td class="px-4 py-2 border border-b">
                                        <div :title="data.gclid" class="max-w-[200px] overflow-hidden text-ellipsis whitespace-nowrap">
                                            {{ data.gclid }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 border border-b">
                                        {{ formatLocalDate(data.created_at) }}
                                    </td>
                                    <td class="px-4 py-2 border border-b">
                                        {{ data.nama }}
                                    </td>
                                    <td class="px-4 py-2 border border-b">
                                        {{ data.no_whatsapp }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- <pre class="bg-muted p-4 rounded-md overflow-x-auto text-sm">{{ JSON.stringify(rekapFormData, null, 2) }}</pre> -->
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8 text-muted-foreground">
                    No data available
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>