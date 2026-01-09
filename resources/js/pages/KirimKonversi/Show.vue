<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

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