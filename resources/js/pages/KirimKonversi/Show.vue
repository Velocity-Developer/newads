<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

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
}

interface KirimKonversi {
    id: number;
    gclid: string;
    jobid: string | null;
    waktu: string | null;
    status: string | null;
    response: string | null;
    source: string | null;
    rekap_form_id: string | null;
    created_at: string;
    updated_at: string;
    tercatat: boolean | null;
    rekap_form: RekapForm | null;
}

interface Props {
    kirimKonversi: KirimKonversi;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kirim Konversi',
        href: '/kirim-konversi',
    },
    {
        title: `Detail #${props.kirimKonversi.id}`,
        href: `/kirim-konversi/${props.kirimKonversi.id}`,
    },
];

const getStatusBadgeVariant = (status: string | null) => {
    switch (status) {
        case 'success':
        case 'sukses':
            return 'default';
        case 'failed':
        case 'gagal':
        case 'error':
            return 'destructive';
        case 'pending':
        case 'menunggu':
            return 'secondary';
        default:
            return 'outline';
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID');
};

const formatJson = (text: string | null) => {
    if (!text) return null;
    try {
        return JSON.parse(text);
    } catch {
        return text;
    }
};
</script>

<template>
    <Head title="Kirim Konversi Detail" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header Actions -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Kirim Konversi Detail</h1>
                    <p class="text-muted-foreground">View detailed information for Kirim Konversi #{{ kirimKonversi.id }}</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link href="/kirim-konversi">Back to List</Link>
                    </Button>
                </div>
            </div>

            <!-- Main Information -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Basic Information -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                        <CardDescription>Core details of the conversion submission</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">ID</label>
                                <p class="font-mono">{{ kirimKonversi.id }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">GCLID</label>
                                <p class="font-mono text-sm break-all">{{ kirimKonversi.gclid }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Job ID</label>
                                <p class="font-mono text-sm">{{ kirimKonversi.jobid || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Rekap Form ID</label>
                                <p class="font-mono text-sm">{{ kirimKonversi.rekap_form_id || '-' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Status Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Status Information</CardTitle>
                        <CardDescription>Current status and timing details</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Status</label>
                                <div class="mt-1">
                                    <Badge v-if="kirimKonversi.status" :variant="getStatusBadgeVariant(kirimKonversi.status)">
                                        {{ kirimKonversi.status }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">-</span>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Source</label>
                                <div class="mt-1">
                                    <Badge v-if="kirimKonversi.source" variant="outline">
                                        {{ kirimKonversi.source }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">-</span>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Waktu</label>
                                <p class="text-sm">{{ kirimKonversi.waktu || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Tercatat</label>
                                <p class="text-sm">{{ kirimKonversi.tercatat ? 'Ya' : 'Tidak' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="kirimKonversi.rekap_form">
                <Card>
                    <CardHeader>
                        <CardTitle>Rekap Form</CardTitle>
                        <CardDescription>Detail terkait data rekap form</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">ID</label>
                                <p class="font-mono text-sm">{{ kirimKonversi.rekap_form?.id }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Nama</label>
                                <p class="text-sm">{{ kirimKonversi.rekap_form?.nama || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">No WhatsApp</label>
                                <p class="text-sm">{{ kirimKonversi.rekap_form?.no_whatsapp || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Jenis Website</label>
                                <p class="text-sm">{{ kirimKonversi.rekap_form?.jenis_website || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Via</label>
                                <p class="text-sm">{{ kirimKonversi.rekap_form?.via || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Status</label>
                                <p class="text-sm">{{ kirimKonversi.rekap_form?.status || '-' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium text-muted-foreground">GCLID</label>
                                <p class="font-mono text-sm break-all">{{ kirimKonversi.rekap_form?.gclid || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Tanggal</label>
                                <p class="text-sm">{{ kirimKonversi.rekap_form?.tanggal ? formatDate(kirimKonversi.rekap_form.tanggal) : '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Created At</label>
                                <p class="text-sm">{{ kirimKonversi.rekap_form?.created_at ? formatDate(kirimKonversi.rekap_form.created_at) : '-' }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Button variant="outline" as-child>
                                <Link :href="`/rekap-form/${kirimKonversi.rekap_form?.id}`">View Rekap Form</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Response Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Response Details</CardTitle>
                    <CardDescription>API response or processing result</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="kirimKonversi.response">
                        <div class="bg-muted/50 rounded-lg p-4">
                            <pre class="whitespace-pre-wrap text-sm overflow-x-auto">{{ formatJson(kirimKonversi.response) || kirimKonversi.response }}</pre>
                        </div>
                    </div>
                    <div v-else class="text-muted-foreground italic">
                        No response data available
                    </div>
                </CardContent>
            </Card>

            <!-- Timestamp Information -->
            <Card>
                <CardHeader>
                    <CardTitle>Timestamp Information</CardTitle>
                    <CardDescription>Creation and update timestamps</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created At</label>
                            <p class="text-sm">{{ formatDate(kirimKonversi.created_at) }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Updated At</label>
                            <p class="text-sm">{{ formatDate(kirimKonversi.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
