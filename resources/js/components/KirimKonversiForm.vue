<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { CloudUpload, Send, CalendarClock,Binoculars, BinocularsIcon  } from 'lucide-vue-next';
import { Form } from '@inertiajs/vue3'

const isModalOpen = ref(false);
const form = ref({
    gclid: '',
    conversion_time: '',
});

const openModal = () => {
    isModalOpen.value = true;
};

</script>

<template>

    <Dialog v-model:open="isModalOpen">
        <DialogTrigger as-child>
            <Button @click="openModal" class="!bg-blue-700 text-white dark:!bg-blue-800"> 
                <CloudUpload/>Kirim Konversi Manual
            </Button>
        </DialogTrigger>
         <DialogContent class="!max-w-2xl max-h-[80vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Kirim Konversi Manual</DialogTitle>
            </DialogHeader>
            
            <Form 
                action="/kirim_konversi/kirim_konversi_google_ads" 
                method="post"
                :show-progress="true"
                :onSuccess="() => {
                    isModalOpen = false;
                }"
            >
                <div class="flex flex-col gap-4">
                    <div>
                        <Label class="mb-2" for="gclid">Masukkan GCLID:</Label>
                        <Input id="gclid" type="text" name="gclid" v-model="form.gclid" />
                    </div>
                    <div>
                        <Label class="mb-2" for="conversion_time">Waktu Konversi (24 Jam):</Label>
                        <Input id="conversion_time" type="text" name="conversion_time" v-model="form.conversion_time" />
                    </div>
                    <div class="flex justify-end gap-1">
                        <Button type="submit" class="!bg-blue-600 text-white dark:!bg-blue-800">
                            <Binoculars /> Cek GCLID
                        </Button>
                        <Button type="submit" class="!bg-green-600 text-white dark:!bg-green-800">
                            <CalendarClock /> Cek TimeZone Akun
                        </Button>                        
                        <Button type="submit">
                            <Send /> Kirim ke Google Ads
                        </Button>
                    </div>
                </div>
            </Form>

            <div class="mt-5 text-xs text-muted-foreground p-2 rounded bg-muted dark:bg-muted-foreground">
                Catatan: Picker menampilkan waktu format 24 jam (HH:MM).<br>Sistem PHP tetap mengirim ke Google Ads dalam format: <code class="bg-gray-300 text-amber-700 px-2">Y-m-d H:i:sP</code> (dengan offset +07:00).
            </div>

         </DialogContent>


    </Dialog>
    
</template>
