<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { CloudUpload, Send, CalendarClock,Binoculars, BinocularsIcon, Loader  } from 'lucide-vue-next';
import axios from 'axios';
import { toast } from 'vue-sonner'

const emit = defineEmits(['update']);

const isModalOpen = ref(false);
const form = ref({
    gclid: '',
    conversion_time: '',
    action: '',
});

const openModal = () => {
    isModalOpen.value = true;
    //reset form
    form.value = {
        gclid: '',
        conversion_time: '',
        action: '',
    };    
    dataResponse.value = null;    
};

const loading = ref(false);
const dataResponse = ref(null) as any;
const submitForm = async () => {

    //jika action click_conversion, conversion_time dan gclid harus diisi
    if (form.value.action == 'click_conversion' && form.value.conversion_time == '' || form.value.action == 'click_conversion' && form.value.gclid == '') {
        toast.error('Wajib isi Waktu Konversi dan GCLID');
        return;
    }
    
    //jika action check_gclid, conversion_time dan gclid harus diisi
    if (form.value.action == 'check_gclid' && form.value.conversion_time == '' || form.value.action == 'check_gclid' && form.value.gclid == '') {
        toast.error('Wajib isi Waktu Konversi dan GCLID');
        return;
    }

    loading.value = true;
    dataResponse.value = null;

    try {
        const response = await axios.post('/kirim_konversi/kirim_konversi_velocity', {
            ...form.value
        });
        dataResponse.value = response.data;
        if (form.value.action == 'click_conversion') {
            emit('update', dataResponse.value);
        }
    } catch (error : any) {
        console.error('Error fetching time zone:', error);
        toast.error(error.response?.data?.message || 'Error fetching time zone')
    } finally {
        loading.value = false;
    }
}

</script>

<template>

    <Dialog v-model:open="isModalOpen">
        <DialogTrigger as-child>
            <Button @click="openModal" class="!bg-blue-700 text-white dark:!bg-blue-800"> 
                <CloudUpload/>Kirim Konversi Manual
            </Button>
        </DialogTrigger>
         <DialogContent class="!max-w-2xl md:!max-w-4xl max-h-[80vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Kirim Konversi Manual</DialogTitle>
                <DialogDescription>
                    Kirim Konversi Manual ke Google Ads.
                </DialogDescription>
            </DialogHeader>

            
            <form method="post" @submit.prevent="submitForm">
                <div class="flex flex-col gap-4">
                    <div>
                        <Label class="mb-2" for="gclid">Masukkan GCLID:</Label>
                        <Textarea id="gclid" type="text" name="gclid" v-model="form.gclid" />
                    </div>
                    <div>
                        <Label class="mb-2" for="conversion_time">Waktu Konversi (24 Jam):</Label>
                        <Input id="conversion_time" type="datetime-local" name="conversion_time" v-model="form.conversion_time" />
                    </div>
                    <div class="flex flex-col xl:flex-row  justify-end gap-1">                       
                        <Button type="button" @click="form.action = 'click_conversion'; submitForm()" class="mb-4 xl:mb-0 cursor-pointer !bg-blue-600 hover:!bg-blue-700 text-white dark:!bg-blue-800 dark:hover:!bg-blue-900">
                            <Send /> Kirim ke Google Ads
                        </Button>

                        <Button type="button" @click="form.action = 'check_gclid'; submitForm()" class="cursor-pointer">
                            <Binoculars /> Cek GCLID
                        </Button>
                        <Button type="button" @click="form.action = 'timezone'; submitForm()" class="!bg-green-600 cursor-pointer text-white dark:!bg-green-800">
                            <CalendarClock /> Cek TimeZone Akun
                        </Button> 
                    </div>
                </div>
            </form>


            <!-- loading -->
             <div v-if="loading" class="flex items-center justify-center gap-2 bg-blue-100 dark:bg-muted-foreground p-4 rounded">
                <Loader class="animate-spin" />
                loading...
             </div>
            
             <!-- response -->
            <template v-if="dataResponse">
                
                <div v-if="dataResponse.message">
                    <p class="p-4 rounded border mb-2" v-html="dataResponse.message" :class="dataResponse.success ? 'bg-green-100 dark:bg-green-800 border-green-600 dark:border-green-400' : 'bg-red-100 dark:bg-red-800 border-red-600 dark:border-red-400'">
                    </p>
                </div>

                <div v-if="dataResponse.result && dataResponse.success && form.action == 'timezone'">
                    <p class="p-4 mb-1 rounded border bg-green-100 dark:bg-green-800 border-green-600 dark:border-green-400">
                       Timezone akun: <strong> {{ dataResponse.result }} </strong>
                    </p>
                </div>

                <div v-if="dataResponse.result && dataResponse.success">
                    <div class="p-4 mb-4 rounded text-slate-700 border bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-400 text-xs">
                        <pre>{{ JSON.stringify(dataResponse.result, null, 2) }}</pre>
                    </div>
                </div>

            </template>

            <div class="mt-5 text-xs text-muted-foreground p-2 rounded bg-muted dark:bg-muted-foreground">
                Catatan: Picker menampilkan waktu format 24 jam (HH:MM).<br>Sistem PHP tetap mengirim ke Google Ads dalam format: <code class="bg-gray-300 text-amber-700 px-2">Y-m-d H:i:sP</code> (dengan offset +07:00).
            </div>

         </DialogContent>


    </Dialog>
    
</template>
