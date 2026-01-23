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
const dataSearchTerms = ref([]);

const openModal = () => {
    isModalOpen.value = true;
    fetchSearchTerms();
}

const loading = ref(false);
const fetchSearchTerms = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/update-search-terms-none');
        if (response.data.success) {
            dataSearchTerms.value = response.data.data;
            toast.success('Search Terms berhasil diupdate');
        } else {
            toast.error('Gagal mengupdate Search Terms');
        }
    } catch (error) {
        toast.error('Terjadi kesalahan saat mengupdate Search Terms');
    } finally {
        loading.value = false;
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
                <DialogTitle>Get Update Search Terms </DialogTitle>
                <DialogDescription>
                    List Search Terms dari 'Search Terms' dari Google Ads
                </DialogDescription>
            </DialogHeader>

            <div v-if="loading">
                <Loader2 class="animate-spin inline-block h-8 w-8 text-primary" />
            </div>
            <div v-else>
                <table class="w-full">
                    <tr>
                        <th class="p-2 border-b text-left">No</th>
                        <th class="p-2 border-b text-left">Term</th>
                    </tr>
                    <tr v-for="item,index in dataSearchTerms" :key="item">
                        <td class="p-2 border-b text-left">
                            {{ Number(index) + 1 }}
                        </td> 
                        <td class="p-2 border-b text-left">
                            {{ item }}
                        </td>  
                    </tr>
                </table>
            </div>

        </DialogContent>
    </Dialog>
</template>